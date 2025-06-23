<?php

/**
 * todo
 * Etchmail × Fluent Forms integration
 *
 */

defined( 'ABSPATH' ) || exit;

final class EMFI_Fluent {

	public function __construct() {

		// 1.  Admin – add a “Etchmail” panel inside the builder
		add_filter( 'fluentform/form_settings_menu', [ $this, 'add_settings_tab' ], 20, 2 );
		add_action( 'fluentform_render_settings_tab_etchmail', [ $this, 'render_settings_tab' ], 10, 2 );

		// 2.  AJAX endpoints (use the exact same JS you already wrote)
		add_action( 'wp_ajax_emfi_get_lists', [ $this, 'ajax_get_lists' ] );
		add_action( 'wp_ajax_emfi_ff_save', [ $this, 'ajax_save_settings' ] );
		add_action( 'wp_ajax_emfi_ff_get_list_fields', [ $this, 'ajax_get_list_fields' ] );

		// 3.  Front-end – fire after a submission is stored
		add_action( 'fluentform_submission_inserted', [ $this, 'handle_submission' ], 10, 3 );
	}

	/*--------------------------------------------------------------*/
	/*  1. Settings tab                                             */
	/*--------------------------------------------------------------*/

	public function add_settings_tab( $tabs, $form_id ) {

		// push your own tab
		$tabs['etchmail'] = [
			'title' => __( 'Etchmail Integration', 'emfi' ), // label shown in the sidebar
			'slug'  => 'form_settings',          // keep this – Fluent expects it
			'hash'  => 'etchmail',               // used for “#etchmail” hash-route
			'route' => '/etchmail'               // Vue route (anything unique)
		];

		return $tabs;
	}

	public function render_settings_tab( $form_id, $form ) {
		// load current opts
		$enabled       = get_option( "emfi_ff_{$form_id}_enabled", '0' );
		$list_uid      = get_option( "emfi_ff_{$form_id}_list_uid", '' );
		$mapped_fields = get_option( "emfi_ff_{$form_id}_mapped_fields", [] );

		// fetch Fluent field meta *once* for the UI
		$fields = $this->get_form_fields_flat( $form );

		$list_fields = []; // populated via AJAX later
		require __DIR__ . '/assets/view.php';             // 100 % identical to Formidable view – reuse it
	}

	/*--------------------------------------------------------------*/
	/*  2. AJAX – lists, list fields, save mapping                  */
	/*--------------------------------------------------------------*/

	public function ajax_get_lists() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'no perms' );
		}

		$lists = EmfiConfig::getLists();
		$out   = [];
		foreach ( $lists as $uid => $name ) {
			$out[] = [ 'list_uid' => $uid, 'name' => $name ];
		}
		wp_send_json_success( $out );
	}

	public function ajax_get_list_fields() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'no perms' );
		}

		$list_uid = sanitize_text_field( $_POST['list_uid'] ?? '' );
		$form_id  = absint( $_POST['form_id'] ?? 0 );
		$fields   = EmfiConfig::getFields( $list_uid );          // uses your API wrapper

		wp_send_json_success( [
			'list_fields' => $fields,
			'saved_map'   => get_option( "emfi_ff_{$form_id}_mapped_fields", [] ),
		] );
	}

	public function ajax_save_settings() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'no perms' );
		}

		$form_id = absint( $_POST['form_id'] );
		update_option( "emfi_ff_{$form_id}_enabled", sanitize_text_field( $_POST['enabled'] ?? '0' ) );
		update_option( "emfi_ff_{$form_id}_list_uid", sanitize_text_field( $_POST['list_uid'] ?? '' ) );

		// cached mapping (contains etch_tag, field_id, basetype) –
		// store exactly like you did in Formidable integration
		$map = array_map( 'wp_unslash', $_POST['mapped_fields'] ?? [] );
		update_option( "emfi_ff_{$form_id}_mapped_fields", $map );

		wp_send_json_success();
	}

	/*--------------------------------------------------------------*/
	/*  3. Submission hook                                          */
	/*--------------------------------------------------------------*/

	/**
	 * @param int $insertId – Fluent internal submission id
	 * @param array $data – raw submission data
	 * @param array $formData – full form object inc. fields
	 */
	public function handle_submission( $insertId, $data, $formData ) {

		$form_id = $formData['id'];
		if ( get_option( "emfi_ff_{$form_id}_enabled", '0' ) == '0' ) {
			return;
		}

		$map = get_option( "emfi_ff_{$form_id}_mapped_fields", [] );
		if ( empty( $map ) ) {
			return;
		}

		/* build Etchmail payload ----------------------------------- */
		$payload = [];
		foreach ( $map as $ff_name => $cfg ) {

			$value = $data[ $ff_name ] ?? '';
			if ( is_array( $value ) ) {
				$value = implode( ',', $value );
			}

			$payload[] = [
				'tag'   => $cfg['etch_tag'],
				'type'  => $cfg['basetype'],
				'value' => $value,
			];
		}

		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		EmfiConfig::submitToList(
			get_option( "emfi_ff_{$form_id}_list_uid", '' ),
			$payload,
			$ip
		);
	}

	/*--------------------------------------------------------------*/
	/*  Helpers                                                     */
	/*--------------------------------------------------------------*/

	/**
	 * Return an *array* of field meta keyed by field name.
	 * Fluent stores the fields JSON-encoded in `form_fields`.
	 */
	private function get_form_fields_flat( $form ): array {
		$flat = [];
		foreach ( $form['fields'] as $f ) {
			// skip break, page, html etc
			if ( empty( $f['name'] ) || empty( $f['type'] ) || $f['type'] === 'submit' ) {
				continue;
			}
			$flat[ $f['name'] ] = [
				'id'       => $f['id'],
				'basetype' => $f['type'],        // text | email | select …
			];
		}

		return $flat;
	}
}

new EMFI_Fluent();
