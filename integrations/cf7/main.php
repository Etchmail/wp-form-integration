<?php
/**  Etchmail × CF7 – main integration class  */
defined( 'ABSPATH' ) || exit;

class EMFI_CF7 {

	/* -------------------------------------------------------------------- */
	public $form = null;
	public $enabled = '0';
	public $list_uid = '';
	public $form_fields = [];
	public $mapped_fields = [];

	public $list_fields = [];
	/* -------------------------------------------------------------------- */

	private static $fields = [
		'enabled'       => [ 'label' => 'Enabled', 'type' => 'checkbox', 'default' => 'false' ],
		'list_uid'      => [ 'label' => 'List UID', 'type' => 'text', 'default' => '' ],
		'mapped_fields' => [ 'label' => 'Mapped Fields', 'type' => 'mapped-field', 'default' => '' ],
	];

	/* ==============================  Init  ============================== */

	public function __construct() {

		/* Admin-side panel & AJAX */
		add_action( 'wpcf7_admin_init', [ $this, 'cf7_register_editor_panel' ], 15 );

		add_action( 'wp_ajax_emfi_get_lists', [ $this, 'ajax_get_lists' ] );
		add_action( 'wp_ajax_emfi_get_list_fields', [ $this, 'ajax_get_list_fields' ] );
		add_action( 'wp_ajax_emfi_save_cf7_enabled', [ $this, 'ajax_save_enabled' ] );
		add_action( 'wp_ajax_emfi_save_cf7_list', [ $this, 'ajax_save_list' ] );
		add_action( 'wp_ajax_emfi_save_cf7_settings', [ $this, 'ajax_save_settings' ] );

		/* Front-end hook – form has been sent */
		add_action( 'wpcf7_mail_sent', [ $this, 'handle_form_submission' ] );
	}

	/* ======================  Editor-panel renderer  ===================== */

	public function cf7_register_editor_panel() {
		add_filter( 'wpcf7_editor_panels', function ( $panels ) {
			$panels['etchmail-panel'] = [
				'title'    => 'Etchmail Integration',
				'callback' => [ $this, 'cf7_render_editor_panel' ],
			];

			return $panels;
		} );
	}

	public function cf7_render_editor_panel( $form ) {

		$this->register_vars( $form );

		if ( $this->form !== null ) {
			$this->form_fields = $this->form->scan_form_tags();
			$this->list_fields = $this->list_uid ? EmfiConfig::getFields( $this->list_uid ) : [];

			if ( $this->form->id == 0 ) {
				echo 'Please save the form, for the integration to enable.';
			} else {
				include EMFI_PLUGIN_DIR . 'integrations/cf7/assets/view.php';
			}

		}
	}

	private function register_vars( $form ) {

		$this->form = $form;

		if ( $this->form === null ) {
			echo '<div class="notice notice-error"><p>Unable to render Etchmail panel: Invalid form context.</p></div>';

			return;
		}

		foreach ( self::$fields as $key => $field ) {
			register_setting(
				'EMFI_CF7',
				"emfi_cf7_{$this->form->id}_{$key}",
				[ 'sanitize_callback' => 'sanitize_text_field' ]
			);
		}

		$this->enabled       = get_option( "emfi_cf7_{$this->form->id}_enabled", '0' );
		$this->list_uid      = get_option( "emfi_cf7_{$this->form->id}_list_uid", '' );
		$this->mapped_fields = get_option( "emfi_cf7_{$this->form->id}_mapped_fields", [] );
		if ( ! is_array( $this->mapped_fields ) ) {
			$this->mapped_fields = [];
		}
	}

	/* ============================  AJAX  =============================== */

	/* ---------- Save enable / disable only ---------- */
	public function ajax_save_enabled() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$form_id = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;
		$enabled = isset( $_POST['enabled'] ) ? sanitize_text_field( wp_unslash( $_POST['enabled'] ) ) : '0';

		if ( ! $form_id ) {
			wp_send_json_error( 'Missing form ID' );
		}

		update_option( "emfi_cf7_{$form_id}_enabled", $enabled );
		wp_send_json_success();
	}

	/* ---------- Save enable + list UID ---------- */
	public function ajax_save_list() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$form_id  = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;
		$enabled  = isset( $_POST['enabled'] ) ? sanitize_text_field( wp_unslash( $_POST['enabled'] ) ) : '0';
		$list_uid = isset( $_POST['list_uid'] ) ? sanitize_text_field( wp_unslash( $_POST['list_uid'] ) ) : '';

		if ( ! $form_id ) {
			wp_send_json_error( 'Missing form ID' );
		}

		update_option( "emfi_cf7_{$form_id}_enabled", $enabled );
		update_option( "emfi_cf7_{$form_id}_list_uid", $list_uid );
		wp_send_json_success();
	}

	/* ---------- Full save (enable, list + mappings) ---------- */
	public function ajax_save_settings() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$form_id  = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;
		$enabled  = isset( $_POST['enabled'] ) ? sanitize_text_field( wp_unslash( $_POST['enabled'] ) ) : '0';
		$list_uid = isset( $_POST['list_uid'] ) ? sanitize_text_field( wp_unslash( $_POST['list_uid'] ) ) : '';

		$post_data   = $_POST; // access once
		$raw_input   = array_key_exists( 'mapped_fields', $post_data ) ? wp_unslash( $post_data['mapped_fields'] ) : [];
		$mapped_raw = is_array( $raw_input ) ? $raw_input : [];
		$mapped     = array_map( 'sanitize_text_field', $mapped_raw );

		if ( ! $form_id ) {
			wp_send_json_error( 'Missing form ID' );
		}

		// Ensure required fields are mapped
		$required = array_filter(
			EmfiConfig::getFields( $list_uid ),
			fn( $f ) => ( $f['required'] ?? '' ) === 'yes'
		);
		foreach ( $required as $field ) {
			if ( ! in_array( $field['tag'], $mapped, true ) ) {
				wp_send_json_error( 'Missing mapping for required field: ' . esc_html( $field['label'] ) );
			}
		}

		update_option( "emfi_cf7_{$form_id}_enabled", $enabled );
		update_option( "emfi_cf7_{$form_id}_list_uid", $list_uid );
		update_option( "emfi_cf7_{$form_id}_mapped_fields", $mapped );

		wp_send_json_success();
	}

	/* ---------- Get lists (name + UID) ---------- */
	public function ajax_get_lists() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		if ( ! method_exists( 'EmfiConfig', 'getLists' ) ) {
			wp_send_json_error( 'Etchmail config not available.' );
		}
		$lists = EmfiConfig::getLists();
		if ( ! is_array( $lists ) ) {
			wp_send_json_error( 'Unable to fetch lists' );
		}

		$out = [];
		foreach ( $lists as $uid => $name ) {
			$out[] = [ 'list_uid' => $uid, 'name' => $name ];
		}
		wp_send_json_success( $out );
	}

	/* ---------- Get field schema + saved map for one list ---------- */
	public function ajax_get_list_fields() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$form_id  = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;
		$list_uid = isset( $_POST['list_uid'] ) ? sanitize_text_field( wp_unslash( $_POST['list_uid'] ) ) : '';

		if ( ! $form_id || empty( $list_uid ) ) {
			wp_send_json_error( 'Missing parameters - list_uid: ' . esc_html( $list_uid ) . ' | form_id: ' . esc_html( $form_id ) );
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
				'type'  => rtrim( strtolower( $cf7_types[ $cf7_name ] ) ?? 'text', '*' ),   // default to text
				'value' => $submission->get_posted_data( $cf7_name ) ?? '',
			];
		}

		/* -------- Call the helper -------- */
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		EmfiConfig::submitToList( $list_uid, $payload, $ip );
	}
}

new EMFI_CF7();
