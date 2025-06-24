<?php
/**  Etchmail × Formidable Forms integration – main integration class  */
defined( 'ABSPATH' ) || exit;

class EMFI_Formidable {

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

	public function __construct() {
		add_filter( 'frm_add_form_settings_section', [ $this, 'add_settings_tab' ] );

		add_action( 'wp_ajax_emfi_get_lists', [ $this, 'ajax_get_lists' ] );
		add_action( 'wp_ajax_emfi_get_list_fields', [ $this, 'ajax_get_list_fields' ] );
		add_action( 'wp_ajax_emfi_save_frm_enabled', [ $this, 'ajax_save_enabled' ] );
		add_action( 'wp_ajax_emfi_save_frm_list', [ $this, 'ajax_save_list' ] );
		add_action( 'wp_ajax_emfi_save_frm_settings', [ $this, 'ajax_save_settings' ] );

		add_action( 'frm_after_create_entry', [ $this, 'handle_submission' ], 20, 2 );
	}

	public function add_settings_tab( $sections ): array {

		$sections['etchmail'] = [
			/* key → label inside the tab-bar                  */
			'name'     => 'Etchmail Integration',

			/* bigger title that shows above the tab content   */
			'title'    => 'Etchmail Integration',

			/* what function should be called for the content  */
			'function' => [ $this, 'render_settings_ui' ],

			/* any dashicon or Formidable icon class           */
			'icon'     => 'dashicons-email',      // or frm_icon_font …
		];

		return $sections;
	}


	public function render_settings_ui( $values ) {

		$this->register_vars( $values );

		if ( $this->form !== null ) {
			$this->form_fields = $this->scan_for_tags( $this->form['id'] );
			$this->list_fields = $this->list_uid ? EmfiConfig::getFields( $this->list_uid ) : [];

			if ( $this->form['id'] == 0 ) {
				echo 'Please save the form, for the integration to enable.';
			} else {
				include EMFI_PLUGIN_DIR . 'integrations/formidable/assets/view.php';
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
                                'EMFI_FRM',
                                "emfi_frm_{$this->form['id']}_{$key}",
                                [ 'sanitize_callback' => 'sanitize_text_field' ]
                        );
                }

		$this->enabled       = get_option( "emfi_frm_{$this->form['id']}_enabled", '0' );
		$this->list_uid      = get_option( "emfi_frm_{$this->form['id']}_list_uid", '' );
		$this->mapped_fields = get_option( "emfi_frm_{$this->form['id']}_mapped_fields", [] );
		if ( ! is_array( $this->mapped_fields ) ) {
			$this->mapped_fields = [];
		}
	}



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

	public function ajax_save_enabled() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$post     = $_POST;
		$form_id  = isset( $post['form_id'] ) ? intval( $post['form_id'] ) : 0;
		$enabled  = isset( $post['enabled'] ) ? sanitize_text_field( wp_unslash( $post['enabled'] ) ) : '0';

		if ( ! $form_id ) {
			wp_send_json_error( 'Missing form ID' );
		}

		update_option( "emfi_frm_{$form_id}_enabled", $enabled );
		wp_send_json_success();
	}

	public function ajax_get_list_fields() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$post     = $_POST;
		$form_id  = isset( $post['form_id'] ) ? intval( $post['form_id'] ) : 0;
		$list_uid = isset( $post['list_uid'] ) ? sanitize_text_field( wp_unslash( $post['list_uid'] ) ) : '';

		if ( ! $form_id || empty( $list_uid ) ) {
			wp_send_json_error( 'Missing parameters - list_uid: ' . esc_html( $list_uid ) . ' | form_id: ' . esc_html( $form_id ) );
		}

		$form = FrmForm::getOne( $form_id );
		if ( ! $form ) {
			wp_send_json_error( 'Invalid form ID' );
		}

		$form_fields = $this->scan_for_tags( $form_id );

		if ( ! method_exists( 'EmfiConfig', 'getFields' ) ) {
			wp_send_json_error( 'Etchmail config not available.' );
		}

		$list_fields = EmfiConfig::getFields( $list_uid );
		$saved_map   = get_option( "emfi_frm_{$form_id}_mapped_fields", [] );

		wp_send_json_success( [
			'form_fields' => $form_fields,
			'list_fields' => $list_fields,
			'saved_map'   => $saved_map,
		] );
	}

	public function ajax_save_list() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$post     = $_POST;
		$form_id  = isset( $post['form_id'] ) ? intval( $post['form_id'] ) : 0;
		$enabled  = isset( $post['enabled'] ) ? sanitize_text_field( wp_unslash( $post['enabled'] ) ) : '0';
		$list_uid = isset( $post['list_uid'] ) ? sanitize_text_field( wp_unslash( $post['list_uid'] ) ) : '';

		if ( ! $form_id ) {
			wp_send_json_error( 'Missing form ID' );
		}

		update_option( "emfi_frm_{$form_id}_enabled", $enabled );
		update_option( "emfi_frm_{$form_id}_list_uid", $list_uid );
		wp_send_json_success();
	}

	/* ---------- Full save (enable, list + mappings) ---------- */
	public function ajax_save_settings() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$post     = $_POST;
		$form_id  = isset( $post['form_id'] ) ? intval( $post['form_id'] ) : 0;
		$enabled  = isset( $post['enabled'] ) ? sanitize_text_field( wp_unslash( $post['enabled'] ) ) : '0';
		$list_uid = isset( $post['list_uid'] ) ? sanitize_text_field( wp_unslash( $post['list_uid'] ) ) : '';

		$raw_input   = array_key_exists( 'mapped_fields', $post ) ? wp_unslash( $post['mapped_fields'] ) : [];
		$mapped_raw  = is_array( $raw_input ) ? $raw_input : [];
		$mapped      = array_map( 'sanitize_text_field', $mapped_raw );

		if ( ! $form_id ) {
			wp_send_json_error( 'Missing form ID' );
		}

		$required = array_filter(
			EmfiConfig::getFields( $list_uid ),
			fn( $f ) => ( $f['required'] ?? '' ) === 'yes'
		);
		foreach ( $required as $field ) {
			if ( ! in_array( $field['tag'], $mapped, true ) ) {
				wp_send_json_error( 'Missing mapping for required field: ' . esc_html( $field['label'] ) );
			}
		}

		update_option( "emfi_frm_{$form_id}_enabled", $enabled );
		update_option( "emfi_frm_{$form_id}_list_uid", $list_uid );
		update_option( "emfi_frm_{$form_id}_mapped_fields", $mapped );

		wp_send_json_success();
	}

	/**
	 * Get all (non-divider/html) fields for a Formidable form and return
	 *   [ 'Field Name' => 'type', … ].
	 *
	 * @param int $form_id
	 *
	 * @return array
	 */
	function scan_for_tags( int $form_id ): array {
		$out = [];

		if ( ! class_exists( 'FrmField' ) ) {
			return $out;                       // plugin missing
		}

		$fields = FrmField::get_all_for_form( $form_id );

		foreach ( $fields as $f ) {
			// ignore layout / submit / HTML blocks
			if ( in_array( $f->type, [ 'divider', 'html', 'break', 'end_divider', 'submit','credit_card', 'captcha' ], true ) ) {
				continue;
			}

			$out[] = [
				'id' => $f->id,
				'name' => $f->name,
				'basetype' => $f->type,
			];
		}

		return $out;
	}

	/*--------------------------------------------------------------
		# 3 – ON SUBMISSION
		--------------------------------------------------------------*/
	public function handle_submission( $entry_id, $form_id ) {

		if ( get_option( "emfi_frm_{$form_id}_enabled", '0' ) == 0 ) {
			return; // check if the form has emfi enabled
		}

		$entry    = FrmEntry::getOne( $entry_id, true ); // true = include metas


		if ($entry->is_draft == 1){
			return; // safety net to not allow drafts
		}

		// get stored fields
		$form_fields = [];
		$list_uid      = get_option( "emfi_frm_{$form_id}_list_uid", '' );
		$mapped_fields = get_option( "emfi_frm_{$form_id}_mapped_fields", [] );

		// rebuild form fields, to allow for easier data retrieval
		foreach ($this->scan_for_tags($form_id) as $tag) {
			$form_fields[$tag['name']] = [
				'basetype' => $tag['basetype'],
				'id' => $tag['id'],
			];
		}

		/* ---------- Build array ---------- */
		$payload = [];

		foreach ( $mapped_fields as $frm_name => $etch_tag ) {
			$payload[] = [
				'tag'   => $etch_tag,
				'type'  => $form_fields[$frm_name]['basetype']?? 'text',
				'value' => $entry->metas[$form_fields[$frm_name]['id']] ?? '',
			];
		}

                $ip = sanitize_text_field( $entry->ip );
                EmfiConfig::submitToList( $list_uid, $payload, $ip );
        }
}

new EMFI_Formidable();