<?php
/**  Etchmail × CF7 – main integration class  */
defined( 'ABSPATH' ) || exit;

class EMFI_CF7 {

	/* -------------------------------------------------------------------- */
	public $form           = null;
	public $enabled        = '0';
	public $list_uid       = '';
	public $form_fields    = [];
	public $mapped_fields  = [];

	public $list_fields    = [];
	public $debug          = false;   // set true to dump vars in admin panel
	/* -------------------------------------------------------------------- */

	private static $fields = [
		'enabled'       => ['label' => 'Enabled',       'type' => 'checkbox',     'default' => 'false'],
		'list_uid'      => ['label' => 'List UID',      'type' => 'text',         'default' => ''],
		'mapped_fields' => ['label' => 'Mapped Fields', 'type' => 'mapped-field', 'default' => ''],
	];

	/* ==============================  Init  ============================== */

	public function __construct() {

		/* Admin-side panel & AJAX */
		add_action( 'wpcf7_admin_init',                    [$this, 'cf7_register_editor_panel'], 15 );

		add_action( 'wp_ajax_emfi_get_lists',              [$this, 'ajax_get_lists'] );
		add_action( 'wp_ajax_emfi_get_list_fields',        [$this, 'ajax_get_list_fields'] );
		add_action( 'wp_ajax_emfi_save_cf7_enabled',       [$this, 'ajax_save_enabled'] );
		add_action( 'wp_ajax_emfi_save_cf7_list',          [$this, 'ajax_save_list'] );
		add_action( 'wp_ajax_emfi_save_cf7_settings',      [$this, 'ajax_save_settings'] );

		/* Front-end hook – form has been sent */
		add_action( 'wpcf7_mail_sent',                     [$this, 'handle_form_submission'] );
	}

	/* ======================  Editor-panel renderer  ===================== */

	public function cf7_register_editor_panel() {
		add_filter( 'wpcf7_editor_panels', function ( $panels ) {
			$panels['etchmail-panel'] = [
				'title'    => 'Etchmail Integration',
				'callback' => [$this, 'cf7_render_editor_panel'],
			];
			return $panels;
		} );
	}

	public function cf7_render_editor_panel( $form ) {

		$this->register_vars( $form );

		if ( $this->form !== null ) {
			$this->form_fields = $this->form->scan_form_tags();
			$this->list_fields = $this->list_uid ? EmfiConfig::getFields( $this->list_uid ) : [];

			include EMFI_PLUGIN_DIR . 'integrations/cf7/assets/view.php';
		}
	}

	private function register_vars( $form ) {

		$this->form = $form;

		if ( $this->form === null ) {
			echo '<div class="notice notice-error"><p>Unable to render Etchmail panel: Invalid form context.</p></div>';
			return;
		}

		foreach ( self::$fields as $key => $field ) {
			register_setting( 'EMFI_CF7', "emfi_cf7_{$this->form->id}_{$key}" );
		}

		$this->enabled       = get_option( "emfi_cf7_{$this->form->id}_enabled", '0' );
		$this->list_uid      = get_option( "emfi_cf7_{$this->form->id}_list_uid", '' );
		$this->mapped_fields = get_option( "emfi_cf7_{$this->form->id}_mapped_fields", [] );
		if ( ! is_array( $this->mapped_fields ) ) {
			$this->mapped_fields = [];
		}

		if ( $this->debug ) {
			echo '<pre>';
			var_dump( [
				'enabled'       => $this->enabled,
				'list_uid'      => $this->list_uid,
				'mapped_fields' => $this->mapped_fields,
			] );
			echo '</pre>';
		}
	}

	/* ============================  AJAX  =============================== */

	/** Small wrapper: nonce + capability */
	private function check_admin_ajax() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}
	}

	/* ---------- Save enable / disable only ---------- */
	public function ajax_save_enabled() {
		$this->check_admin_ajax();

		$form_id = intval( $_POST['form_id'] ?? 0 );
		$enabled = sanitize_text_field( $_POST['enabled'] ?? '0' );

		if ( ! $form_id ) {
			wp_send_json_error( 'Missing form ID' );
		}
		update_option( "emfi_cf7_{$form_id}_enabled", $enabled );
		wp_send_json_success();
	}

	/* ---------- Save enable + list UID ---------- */
	public function ajax_save_list() {
		$this->check_admin_ajax();

		$form_id  = intval( $_POST['form_id'] ?? 0 );
		$enabled  = sanitize_text_field( $_POST['enabled'] ?? '0' );
		$list_uid = sanitize_text_field( $_POST['list_uid'] ?? '' );

		if ( ! $form_id ) {
			wp_send_json_error( 'Missing form ID' );
		}
		update_option( "emfi_cf7_{$form_id}_enabled", $enabled );
		update_option( "emfi_cf7_{$form_id}_list_uid", $list_uid );
		wp_send_json_success();
	}

	/* ---------- Full save (enable, list + mappings) ---------- */
	public function ajax_save_settings() {
		$this->check_admin_ajax();

		$form_id  = intval( $_POST['form_id'] ?? 0 );
		$enabled  = sanitize_text_field( $_POST['enabled'] ?? '0' );
		$list_uid = sanitize_text_field( $_POST['list_uid'] ?? '' );

		/** ── NEW: guarantee we always get an array ───────────────────────── */
		$mapped_raw = isset( $_POST['mapped_fields'] ) && is_array( $_POST['mapped_fields'] )
			? $_POST['mapped_fields']
			: [];

		$mapped = array_map( 'sanitize_text_field', $mapped_raw );
		/* ------------------------------------------------------------------ */

		if ( ! $form_id ) {
			wp_send_json_error( 'Missing form ID' );
		}

		/* --- Validate required list fields are mapped --------------------- */
		$required = array_filter(
			EmfiConfig::getFields( $list_uid ),
			fn ( $f ) => ( $f['required'] ?? '' ) === 'yes'
		);
		foreach ( $required as $field ) {
			if ( ! in_array( $field['tag'], $mapped, true ) ) {
				wp_send_json_error( 'Missing mapping for required field: ' . $field['label'] );
			}
		}

		update_option( "emfi_cf7_{$form_id}_enabled",        $enabled   );
		update_option( "emfi_cf7_{$form_id}_list_uid",       $list_uid  );
		update_option( "emfi_cf7_{$form_id}_mapped_fields",  $mapped    );

		wp_send_json_success();
	}

	/* ---------- Get lists (name + UID) ---------- */
	public function ajax_get_lists() {
		$this->check_admin_ajax();

		if ( ! method_exists( 'EmfiConfig', 'getLists' ) ) {
			wp_send_json_error( 'Etchmail config not available.' );
		}
		$lists = EmfiConfig::getLists();
		if ( ! is_array( $lists ) ) {
			wp_send_json_error( 'Unable to fetch lists' );
		}

		$out = [];
		foreach ( $lists as $uid => $name ) {
			$out[] = ['list_uid' => $uid, 'name' => $name];
		}
		wp_send_json_success( $out );
	}

	/* ---------- Get field schema + saved map for one list ---------- */
	public function ajax_get_list_fields() {
		$this->check_admin_ajax();

		$form_id  = intval( $_POST['form_id'] ?? 0 );
		$list_uid = sanitize_text_field( $_POST['list_uid'] ?? '' );

		if ( ! $form_id || ! $list_uid ) {
			wp_send_json_error( 'Missing parameters' );
		}

		$cf = wpcf7_contact_form( $form_id );
		if ( ! $cf ) {
			wp_send_json_error( 'Invalid form ID' );
		}
		$form_fields = $cf->scan_form_tags();

		if ( ! method_exists( 'EmfiConfig', 'getFields' ) ) {
			wp_send_json_error( 'Etchmail config not available.' );
		}
		$list_fields = EmfiConfig::getFields( $list_uid );
		$saved_map   = get_option( "emfi_cf7_{$form_id}_mapped_fields", [] );

		wp_send_json_success( [
			'form_fields' => $form_fields,
			'list_fields' => $list_fields,
			'saved_map'   => $saved_map,
		] );
	}

	/* ====================  Form-submission hook  ======================= */

	public function handle_form_submission( $contact_form ) {

		$form_id = (int) $contact_form->id();
		if ( ! get_option( "emfi_cf7_{$form_id}_enabled", false ) ) {
			return;                       // integration off
		}

		$list_uid      = get_option( "emfi_cf7_{$form_id}_list_uid", '' );
		$mapped_fields = get_option( "emfi_cf7_{$form_id}_mapped_fields", [] );
		if ( empty( $list_uid ) || empty( $mapped_fields ) ) {
			return;                       // mis-configured
		}

		$submission = WPCF7_Submission::get_instance();
		if ( ! $submission ) {
			return;                       // should never happen
		}

		/* -------- Build lookup: cf7Name -> baseType (e.g. "email") -------- */
		$cf7_types = [];
		foreach ( $contact_form->scan_form_tags() as $tag ) {
			$cf7_types[ $tag->name ] = strtolower( rtrim( $tag->type, '*' ) );
		}

		/* -------- Assemble the payload for submitToList() -------- */
		$payload = [];
		foreach ( $mapped_fields as $cf7_name => $etch_tag ) {

			$payload[] = [
				'tag'   => $etch_tag,
				'type'  => rtrim(strtolower($cf7_types[ $cf7_name ]) ?? 'text', '*'),   // default to text
				'value' => $submission->get_posted_data( $cf7_name ) ?? '',
			];
		}

		/* -------- Call the helper -------- */
		EmfiConfig::submitToList( $list_uid, $payload );
	}
}

new EMFI_CF7();
