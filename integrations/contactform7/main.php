<?php defined( 'ABSPATH' ) || exit;

class ETCHFOIN_CF7 {

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

		/* Admin-side panel & AJAX */

		add_action( 'wpcf7_admin_init', [ $this, 'cf7_register_editor_panel' ], 15 );

		//ajax calls/posts
		add_action( 'wp_ajax_etchfoin_get_cf7_enabled', [ $this, 'ajax_get_enabled' ] );
		add_action( 'wp_ajax_etchfoin_save_cf7_enabled', [ $this, 'ajax_save_enabled' ] );

		add_action( 'wp_ajax_etchfoin_get_cf7_lists', [ $this, 'ajax_get_lists' ] );
        add_action( 'wp_ajax_etchfoin_save_cf7_list_uid', [$this, 'ajax_save_list_uid'] );
		add_action('wp_ajax_etchfoin_get_cf7_list_fields', [$this, 'ajax_get_list_fields']);

		add_action('wp_ajax_etchfoin_get_cf7_settings',      [$this,'ajax_get_settings']);
		add_action('wp_ajax_etchfoin_get_cf7_form_tags',     [$this,'ajax_get_form_tags']);
		add_action('wp_ajax_etchfoin_save_cf7_mapped_fields',[$this,'ajax_save_mapped_fields']);

		add_action( 'admin_enqueue_scripts', [
			$this,
			'enqueue_assets'
		] ); // using this as wp_enqueue_script does not fire

		add_action( 'wpcf7_mail_sent', [ $this, 'handle_form_submission' ] );

	}

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

		if ( null !== $this->form ) {
			$this->form_fields = $this->form->scan_form_tags();
			$this->list_fields = $this->list_uid ? ETCHFOINConfig::getFields( $this->list_uid ) : [];

			if ( 0 === $this->form->id ) {
				echo esc_html__( 'Please save the form, for the integration to enable.', 'etchfoin' );
			} else {
				include ETCHFOIN_PLUGIN_DIR . 'integrations/contactform7/assets/etchfoin-view-cf7.php';
			}
		}
	}

	private function register_vars( $form ) {

		$this->form = $form;

		if ( null === $this->form ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Unable to render Etchmail panel: Invalid form context.', 'etchfoin' ) . '</p></div>';

			return;
		}

		foreach ( self::$fields as $key => $field ) {
			register_setting(
				'ETCHFOIN_CF7',
				"etchfoin_cf7_{$this->form->id}_{$key}",
				[ 'sanitize_callback' => 'sanitize_text_field' ]
			);
		}

		$this->enabled       = get_option( "etchfoin_cf7_{$this->form->id}_enabled", '0' );
		$this->list_uid      = get_option( "etchfoin_cf7_{$this->form->id}_list_uid", '' );
		$this->mapped_fields = get_option( "etchfoin_cf7_{$this->form->id}_mapped_fields", [] );
		if ( ! is_array( $this->mapped_fields ) ) {
			$this->mapped_fields = [];
		}
	}

	public function ajax_get_settings() {
		$this->verify_ajax_request();
		$form_id = filter_input(INPUT_POST, 'form_id', FILTER_VALIDATE_INT);
		if (!$form_id) wp_send_json_error('Invalid form ID');

		$enabled       = (bool) get_option("etchfoin_cf7_{$form_id}_enabled", '0');
		$list_uid      = (string) get_option("etchfoin_cf7_{$form_id}_list_uid", '');
		$mapped_fields = get_option("etchfoin_cf7_{$form_id}_mapped_fields", []);
		if (!is_array($mapped_fields)) $mapped_fields = [];

		wp_send_json_success([
			'enabled'       => $enabled,
			'list_uid'      => $list_uid,
			'mapped_fields' => $mapped_fields,
		]);
	}

	public function ajax_get_enabled() {

		$this->verify_ajax_request();

		// Sanitise
		$form_id = filter_input( INPUT_POST, 'form_id', FILTER_SANITIZE_NUMBER_INT );
		// Validate it as an integer
		$form_id = filter_var( $form_id, FILTER_VALIDATE_INT );

		if ( false === $form_id ) {
			wp_send_json_error( "Invalid form ID" );
		}

		wp_send_json_success( [
				'formID'  => (int) $form_id,
				'enabled' => (bool) $this->get_form_option( $form_id, 'enabled' ),
			]
		);
	}

	public function ajax_save_enabled() {

		$this->verify_ajax_request();

		// Sanitise
		$form_id = filter_input( INPUT_POST, 'form_id', FILTER_SANITIZE_NUMBER_INT );
		// Validate it as an integer
		$form_id = filter_var( $form_id, FILTER_VALIDATE_INT );

		if ( false === $form_id ) {
			wp_send_json_error( "Invalid form ID" );
		}

		$enabled = filter_input( INPUT_POST, 'enabled', FILTER_VALIDATE_BOOL );

		if ( $this->set_form_option( $form_id, 'enabled', $enabled ) ) {
			wp_send_json_success(
				[
					'formID'  => (int) $form_id,
					'enabled' => (bool) $enabled,
				] );
		} else {
			wp_send_json_error( 'Could not save your changes' );
		}
	}

    public function ajax_get_lists() {
        $this->verify_ajax_request();

        $form_id = filter_input(INPUT_POST, 'form_id', FILTER_VALIDATE_INT);
        if ( ! $form_id ) {
            wp_send_json_error( 'Invalid form ID' );
        }

        // Guard before calling static method
        if ( ! method_exists( 'ETCHFOINConfig', 'getLists' ) ) {
            wp_send_json_error( 'Etchmail config not available.' );
        }

        $lists = ETCHFOINConfig::getLists();
        if ( ! is_array( $lists ) ) {
            wp_send_json_error( 'Unable to fetch lists' );
        }

        $out = [];
        foreach ( $lists as $uid => $name ) {
            $out[] = [ 'list_uid' => $uid, 'name' => $name ];
        }

        $selected = get_option( "etchfoin_cf7_{$form_id}_list_uid", '' );

        wp_send_json_success( [
            'lists'         => $out,
            'selected_list' => (string) $selected,
        ] );
    }

	public function ajax_get_form_tags() {
		$this->verify_ajax_request();
		$form_id = filter_input(INPUT_POST, 'form_id', FILTER_VALIDATE_INT);
		if (!$form_id) wp_send_json_error('Invalid form ID');

		$form = wpcf7_contact_form($form_id);
		if (!$form) wp_send_json_error('Form not found');

		$tags = [];
		foreach ((array) $form->scan_form_tags() as $t) {
			if (empty($t->name)) continue;
			$tags[] = [
				'name'     => (string) $t->name,
				'basetype' => (string) ($t->basetype ?? 'text'),
			];
		}
		wp_send_json_success(['tags' => $tags]);
	}

	public function ajax_save_list_uid() {
		$this->verify_ajax_request();
        $form_id  = filter_input( INPUT_POST, 'form_id', FILTER_VALIDATE_INT );
        // Unslash then sanitize.

        $list_uid = isset( $_POST['list_uid'] )
            ? sanitize_text_field( wp_unslash( $_POST['list_uid'] ) )
            : '';

        if ( ! $form_id ) {
            wp_send_json_error( 'Invalid form ID' );
        }

        $ok = $this->set_form_option( $form_id, 'list_uid', $list_uid );

        if ( $ok ) {
            wp_send_json_success( [ 'list_uid' => $list_uid ] );
        } else {
            wp_send_json_error( 'Save failed' );
        }
	}

	public function ajax_get_list_fields() {
		$this->verify_ajax_request();

        $list_uid = isset( $_POST['list_uid'] )
            ? sanitize_text_field( wp_unslash( $_POST['list_uid'] ) )
            : '';

        if ( '' === $list_uid ) {
            wp_send_json_error( 'Missing list uid' );
        }

        if ( ! method_exists( 'ETCHFOINConfig', 'getFields' ) ) {
            wp_send_json_error( 'Etchmail config not available.' );
        }

        $fields = ETCHFOINConfig::getFields( $list_uid );
        if ( ! is_array( $fields ) ) {
            wp_send_json_error( 'Unable to fetch fields' );
        }

        wp_send_json_success( [ 'fields' => $fields ] );
	}

	public function ajax_save_mapped_fields() {
		$this->verify_ajax_request();

        $form_id = filter_input( INPUT_POST, 'form_id', FILTER_VALIDATE_INT );
        if ( ! $form_id ) {
            wp_send_json_error( 'Invalid data' );
        }

        // Unslash raw input first.
        $mapped_raw = isset( $_POST['mapped_fields'] ) ? wp_unslash( $_POST['mapped_fields'] ) : [];

        // Accept either JSON string or array; decode/sanitize safely.
        if ( is_string( $mapped_raw ) ) {
            $decoded = json_decode( $mapped_raw, true );
            $mapped  = is_array( $decoded ) ? $decoded : [];
        } else {
            $mapped = is_array( $mapped_raw ) ? $mapped_raw : [];
        }

        if ( ! is_array( $mapped ) ) {
            wp_send_json_error( 'Invalid data' );
        }

        $clean = [];
        foreach ( $mapped as $cf7 => $etch ) {
            $cf7_key  = sanitize_key( (string) $cf7 );
            $etch_key = is_string( $etch ) ? sanitize_key( $etch ) : '';
            $clean[ $cf7_key ] = $etch_key; // '' means "Not mapped"
        }

        $ok = $this->set_form_option( $form_id, 'mapped_fields', $clean );

        if ( $ok ) {
            wp_send_json_success( [ 'mapped_fields' => $clean ] );
        } else {
            wp_send_json_error( 'Save failed' );
        }
	}


	public function verify_ajax_request() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}
	}

	public function get_form_option( $form_id, $option ) {
		if ( isset( self::$fields[ $option ] ) ) {
			return get_option( "etchfoin_cf7_{$form_id}_{$option}", '0' );
		}

		return null;
	}

	public function set_form_option( $form_id, $option, $value = '0' ) {
		if ( isset( self::$fields[ $option ] ) ) {
			update_option( "etchfoin_cf7_{$form_id}_{$option}", $value );

			return true;
		}

		return false;
	}


	public function enqueue_assets( $hook ) {
		// WordPress passes the current screen's hook suffix
		if ( 'toplevel_page_wpcf7' !== $hook ) {
			return; // Load nothing
		}

		wp_register_script(
			'etchfoin-contact-form-7',
			plugins_url( 'integrations/contactform7/assets/etchfoin-js-cf7.js', ETCHFOIN_PLUGIN ),
			[ 'jquery' ],
			ETCHFOIN_PLUGIN_VERSION,
			true
		);


        wp_localize_script('etchfoin-contact-form-7','etchfoinDataCF7',[
            'nonce' => wp_create_nonce('etchmail_nonce'),
        ]);

		wp_enqueue_script( 'etchfoin-contact-form-7' );


		// Register and enqueue CSS
		wp_register_style(
			'etchfoin-contact-form-7-style',
			plugins_url( 'integrations/contactform7/assets/etchfoin-styles-cf7.css', ETCHFOIN_PLUGIN ),
			[],
			ETCHFOIN_PLUGIN_VERSION
		);

		wp_enqueue_style( 'etchfoin-contact-form-7-style' );
	}

	public function handle_form_submission( $contact_form ) {
		$form_id = (int) $contact_form->id();
		if ( ! $this->get_form_option( $form_id,"enabled") ) {
			return; // integration off
		}

		$list_uid      = (string) ( $this->get_form_option( $form_id, 'list_uid' ) ?? '' );
		$mapped_fields = $this->get_form_option( $form_id, 'mapped_fields' );

		if ( empty( $list_uid ) || empty( $mapped_fields ) || ! is_array( $mapped_fields ) ) {
			return; // mis-configured
		}

		$submission = WPCF7_Submission::get_instance();
		if ( ! $submission ) {
			return; // should never happen
		}

		// Build lookup: cf7Name -> baseType (e.g., "email")
		$cf7_types = [];
		foreach ( $contact_form->scan_form_tags() as $tag ) {
			// $tag->type can include trailing '*' for required; normalise safely
			$type_raw = is_string( $tag->type ) ? $tag->type : '';
			$cf7_types[ $tag->name ] = strtolower( rtrim( $type_raw, '*' ) );
		}

		// Assemble the payload
		$payload = [];
		foreach ( $mapped_fields as $cf7_name => $etch_tag ) {
			if (!is_string($cf7_name) || !is_string($etch_tag) || '' === $etch_tag) {
				continue;
			}

			$type = isset($cf7_types[$cf7_name]) ? $cf7_types[$cf7_name] : 'text';
			$posted = $submission->get_posted_data( $cf7_name );

			$payload[] = [
				'tag'   => strtoupper( $etch_tag ),                 // Etchmail tags are uppercase
				'type'  => $type,                                   // already normalised
				'value' => $posted ?? '',
			];
		}

		if (empty($payload)) {
			return;
		}

		// Submit
		$ip = null;
		if (isset($_SERVER['REMOTE_ADDR'])) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		ETCHFOINConfig::submitToList( $list_uid, $payload, $ip );
	}

}

new ETCHFOIN_CF7();