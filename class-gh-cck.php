<?php

GFForms::include_addon_framework();

/**
 * GH_CCK Class
 *
 * @package gravityhopper-custom-code-keeper
 * @author  uamv
 */
class GH_CCK extends GFAddOn {

	/*---------------------------------------------------------------------------------*
	* Attributes
	*---------------------------------------------------------------------------------*/

	protected $_version = GRAVITYHOPPER_CCK_VERSION;
	protected $_min_gravityforms_version = '2.5';
	protected $_slug = 'gravityhopper_cck';
    protected $_short_title = 'Code';
    protected $_title = 'Code Keep';
	protected $_path = 'gravityhopper-custom-code-keeper/gravityhopper-custom-code-keeper.php';
	protected $_full_path = GRAVITYHOPPER_CCK_DIR_PATH;
    protected $_capabilities_form_settings = 'manage_options';
    protected $_capabilities_plugin_page = 'manage_options';

	private static $_instance = null;
    private static $code_dir;

	/*---------------------------------------------------------------------------------*
	* Constructor
	*---------------------------------------------------------------------------------*/

	/**
	 * Get an instance of this class.
	 *
	 * @return GH_CCK
	 */
	public static function get_instance() {

		if ( self::$_instance == null ) {
			self::$_instance = new GH_CCK();
		}

		return self::$_instance;

	}

    /**
     * Initialize the plugin by setting localization, filters, and administration functions.
     *
     * @return void
     */
	public function init() {

		parent::init();

		load_plugin_textdomain( 'gravityhopper-cck', false, basename( dirname( __file__ ) ) . '/languages/' );

        add_action( 'admin_init',                                   [ $this, 'maybe_upgrade' ]                              );

        self::$code_dir = GH_CCK::get_upload_root();

        // Load the fancy Gravity Forms header on our custom pages
        add_action( 'wp_after_admin_bar_render',                    [ $this, 'assemble_code_page' ]                         );

        add_filter( 'gform_form_settings_menu',                     [ $this, 'add_form_settings_menu_item' ],       10, 2   );
        // add_action( 'gform_settings_gravityhopper_cck',             [ $this, 'add_settings_page' ]                          );
        add_action( 'gform_form_settings_page_gravityhopper_cck',   [ $this, 'add_form_settings_subview_page' ]             );
        add_action( 'forms_page_gravityhopper_cck',                 [ $this, 'print_file_load_disclaimer' ]                );
        add_action( 'admin_enqueue_scripts',                        [ $this, 'enqueue_editor_script' ]                      );

        add_action( 'gform_after_save_form',                        [ $this, 'maybe_create_form_file' ],            10, 2   );
        add_action( 'gform_post_form_duplicated',                   [ $this, 'duplicate_form_file' ],               10, 2   );
        add_action( 'gform_forms_post_import',                      [ $this, 'maybe_create_form_files' ],           10, 2   );
        add_action( 'gform_after_delete_form',                      [ $this, 'remove_form_file' ]                           );
        add_action( 'wp_ajax_create_form_file',                     [ $this, 'ajax_create_form_file' ]                      );
        add_action( 'wp_ajax_create_prefixed_file',                 [ $this, 'ajax_create_prefixed_file' ]                      );
        add_action( 'wp_ajax_save_file',                            [ $this, 'ajax_save_file' ]                             );
        add_action( 'wp_ajax_delete_file',                          [ $this, 'ajax_delete_file' ]                           );

        add_filter( 'gform_export_menu',                            [ $this, 'add_export_menu_item' ]                       );
        add_action( 'gform_export_page_export_gravityhopper_cck',   [ $this, 'add_export_page' ]                            );

        add_filter( 'gravityhopper-ks/keyboard_shortcuts',          [ $this, 'keyboard_shortcuts' ]                         );
        
        $this->maybe_export();

	}

	/*---------------------------------------------------------------------------------*
	* Administrative Functions
	*---------------------------------------------------------------------------------*/

    /**
     * Compare any installed version with current and maybe run upgrade processes
     *
     * @return void
     */
    public function maybe_upgrade() {

        $old_version = get_option( 'gravityhopper_cck_version', 0 );

        if ( $old_version != GRAVITYHOPPER_CCK_VERSION ) {

            $this->do_upgrade( $old_version, GRAVITYHOPPER_CCK_VERSION );

        }

        update_option( 'gravityhopper_cck_version', GRAVITYHOPPER_CCK_VERSION, false );

    }

    /**
     * Process any upgrade tasks
     *
     * @param string $old_version
     * @param string $current_version
     * @return void
     */
    public function do_upgrade( $old_version, $current_version ) {

        GH_CCK::initialize_root_folder();

    }

    /**
    * Return the scripts which should be enqueued.
    *
    * @return array
    */
    public function scripts() {

        $scripts = array(
            array(
                'handle'  => 'gravityhopper-cck',
                'src'     => GRAVITYHOPPER_CCK_DIR_URL . "gh-cck.js",
                'version' => $this->_version,
                'deps'    => array( 'jquery', 'wp-util' ),
                'enqueue' => array(
                    array( 'query' => 'page=gf_edit_forms&view=settings&subview=gravityhopper_cck' ),
                    array( 'query' => 'page=gravityhopper_cck' )
                )
            )
        );

        return array_merge( parent::scripts(), $scripts );

    }

    /**
    * Return the styles which should be enqueued.
    *
    * @return array
    */
    public function styles() {

        $styles = array(
            array(
                'handle'  => 'gravityhopper-cck',
                'src'     => GRAVITYHOPPER_CCK_DIR_URL . "gh-cck.css",
                'version' => $this->_version,
                'enqueue' => array(
                    array( 'query' => 'page=gf_edit_forms&view=settings&subview=gravityhopper_cck' ),
                    array( 'query' => 'page=gravityhopper_cck' )
                )
            )
        );

        return array_merge( parent::styles(), $styles );

    }

    /**
     * Enqueue the PHP code editor on settings pages
     *
     * @return void
     */
    public function enqueue_editor_script() {

        if ( GFForms::get_page() !== 'form_settings_gravityhopper_cck' && ( GFForms::get_page() !== 'settings' && rgget( 'subview' ) == 'gravityhopper_cck' ) ) {
            return;
        }
    
        $editor_settings['php_code_editor'] = wp_enqueue_code_editor( array(
            'type' => 'php',
            'codemirror' => [
                'inputStyle'    => 'textarea',
                'matchBrackets' => true,
                'lineWrapping'  => false,
                'extraKeys'     => [
                    'Alt-F'      => 'findPersistent',
                    'Ctrl-Space' => 'autocomplete',
                    'Ctrl-/'     => 'toggleComment',
                    'Cmd-/'      => 'toggleComment',
                    'Alt-Up'     => 'swapLineUp',
                    'Alt-Down'   => 'swapLineDown',
                ],
                'gutters'       => [ 'CodeMirror-lint-markers', 'CodeMirror-foldgutter' ],
                'lint'          => true,
                'direction'     => 'ltr',
                'colorpicker'   => [ 'mode' => 'edit' ],
                'foldOptions'   => [ 'widget' => '...' ],
                'mode' => [
                    'name' => 'php',
                    'startOpen' => true
                ]
            ]
        ));
    
        wp_localize_script( 'jquery', 'editor_settings', $editor_settings );
    
        wp_enqueue_script( 'wp-theme-plugin-editor' );
        wp_enqueue_style( 'wp-codemirror' );

    }    

    /**
     * Initialize directory in uploads folder for housing code and create initial files
     *
     * @return void
     */
    public static function initialize_root_folder() {

		if ( ! file_exists( GH_CCK::$code_dir ) ) {

            // make the directory at uploads/gravity_hopper/code/
            $result = wp_mkdir_p( GH_CCK::$code_dir );

            // if directory created successfully, then add index.html files to both directory levels
            if ( $result ) {
                @touch( substr( GH_CCK::$code_dir, 0, -5 ) . 'index.html' );
                @touch( GH_CCK::$code_dir . 'index.html' );
            }

            $log_type = $result ? 'debug' : 'error';            
            GH_CCK::log( $log_type, "Make directory {GH_CCK::$code_dir}: {$result}", __METHOD__ );
            
		}

        GH_CCK::maybe_create_global_file();
        GH_CCK::maybe_create_form_file_template();
        GH_CCK::maybe_update_mu_loader();
        
    }

    /**
     * Create global code file if it doesn't exist
     *
     * @return void
     */
    public static function maybe_create_global_file() {

        $global_filename = GH_CCK::$code_dir . 'gf-global-code.php';
        
        if ( ! file_exists( $global_filename ) ) {
            
            $result = copy( GRAVITYHOPPER_CCK_DIR_PATH . '/files/gf-global-code.php', $global_filename );

            $log_type = $result ? 'debug' : 'error';
            GH_CCK::log( $log_type, "Make file {$global_filename}: {$result}", __METHOD__ );

        }
        
    }

    /**
     * Create first form file as example if it doesn't exist
     *
     * @return void
     */
    public static function maybe_create_form_file_template() {

        $template_filename = GH_CCK::$code_dir . 'gform-00xx.php';
        
        if ( ! file_exists( $template_filename ) ) {
            
            $result = copy( GRAVITYHOPPER_CCK_DIR_PATH . '/files/gform-00xx.php', $template_filename );

            $log_type = $result ? 'debug' : 'error';
            GH_CCK::log( $log_type, "Make file {$template_filename}: {$result}", __METHOD__ );

        }
        
    }

    /**
     * Create or update mu-plugin code loader if it exists and differs from packaged version
     *
     * @return void
     */
    public static function maybe_update_mu_loader() {

        $mustuse_filename = WPMU_PLUGIN_DIR . '/gravityhopper-custom-code-keeper-loader.php';
        
        // check if user has added loader file as mu-plugin and whether packaged version of file differs from what they are running
        if ( file_exists( $mustuse_filename ) && sha1_file( $mustuse_filename ) != sha1_file( GRAVITYHOPPER_CCK_DIR_PATH . '/files/gravityhopper-custom-code-keeper-loader.php' ) ) {
            
            $result = copy( GRAVITYHOPPER_CCK_DIR_PATH . '/files/gravityhopper-custom-code-keeper-loader.php', $mustuse_filename );

            $log_type = $result ? 'debug' : 'error';
            GH_CCK::log( $log_type, "Updated file {$mustuse_filename}: {$result}", __METHOD__ );

        }
        
    }

    /**
     * Load file for rendering the plugin page
     *
     * @return void
     */
    public function plugin_page() {

        require_once( GRAVITYHOPPER_CCK_DIR_PATH . 'page-gh-cck.php' );

    }

    /**
     * Initialize and prepare for rendering of plugin page
     *
     * @return void
     */
    public function assemble_code_page() {

        if ( is_admin() ) {

            global $current_screen;

            if ( $current_screen->id == 'forms_page_gravityhopper_cck' ) {

                $allowed_prefixes = apply_filters( 'gravityhopper-cck/allowed_file_prefixes', array( 'gf-' ) );
                $allowed_prefixes[] = 'gform-';
                
                $tabs = [];
                foreach ( $allowed_prefixes as $prefix ) {
                    $tabs[] = array(
                        'name'  => $prefix,
                        'label' => "{$prefix}*.php",
                        'icon'  => 'dashicons-editor-code'
                    );
                }

                GFForms::admin_header( $tabs, false );
                
            }
            
        }

    }

    /**
     * Add content to top of plugin page
     *
     * @return void
     */
    public static function print_file_load_disclaimer() { ?>

        <div style="font-style:italic; padding: 0 1.4em 1.4em 1.4em;">
            This code will load and run for all forms across the site. Files are included in the order shown.<br>
            Always use appropriate hooks and/or conditional checks when targeting specific forms and fields.
            <?php if ( rgget('page') == 'gravityhopper_cck' && rgget('subview') == 'gform-' ) : ?>
                <br><strong>Form-specific files having no associated form on the site will not be loaded.</strong>
            <?php endif; ?>
        </div>

    <?php }

    /**
     * Create file in uploads/gravity_hopper/code/ at behest of user
     *
     * @return void
     */
    public function ajax_create_form_file() {
        
        // check the nonce and that form ID has been provided
        if ( wp_verify_nonce( sanitize_text_field( $_POST['data']['nonce'] ), 'create_form_file' ) == false || ! isset( $_POST['data']['formID'] ) ) {
            wp_send_json_error();
        }

        $form_id = sanitize_text_field( $_POST['data']['formID'] );

        $created = GH_CCK::create_form_file( $form_id );

        // generate the response
        if ( $created ) {
            wp_send_json_success( array(
                'replace' => GH_CCK::get_file_editor_markup( $form_id )
            ) );
        } else {
            wp_send_json_error( array(
                'replace' => $created
            ) );
        }

    }

    /**
     * Create named file in uploads/gravity_hopper/code/ at behest of user
     *
     * @return void
     */
    public function ajax_create_prefixed_file() {
        
        // check the nonce and that form ID has been provided
        if ( ! isset( $_POST['data']['prefix'] ) || wp_verify_nonce( sanitize_text_field( $_POST['data']['nonce'] ), "create_file_{$_POST['data']['prefix']}" ) == false ) {
            wp_send_json_error();
        }

        $prefix = sanitize_text_field( $_POST['data']['prefix'] );
        $filename = GH_CCK::$code_dir . $prefix . sanitize_text_field( $_POST['data']['filename'] ) . '.php';
        $username = wp_get_current_user()->user_login;
        $file_slug = GH_CCK::get_file_slug( $filename );

        $created = GH_CCK::create_file( $filename, "<?php
/**
 * {$username} ~~{$file_slug}~~
 * 
 * Code Keeper does not restrict when specific files are included on the site.
 * All code from allowed files residing in the `gravity_hopper/code/` directory will run for all forms.
 * Always use appropriate hooks and/or conditional checks when targeting specific forms and fields.
 */

" );

        // generate the response
        if ( $created ) {
            wp_send_json_success( array(
                'created' => true
            ) );
        } else {
            wp_send_json_error( array(
                'created' => false
            ) );
        }

    }

    /**
     * Save file in uploads/gravity_hopper/code/ at behest of user
     *
     * @return void
     */
    public function ajax_save_file() {
        
        // check the nonce and that form ID has been provided
        if ( ! isset( $_POST['data']['fileName'] ) || wp_verify_nonce( sanitize_text_field( $_POST['data']['nonce'] ), 'save_file_' . GH_CCK::get_file_slug( $_POST['data']['fileName'] ) ) == false ) {
            wp_send_json_error();
        }

        $file_name = sanitize_text_field( $_POST['data']['fileName'] );
        $file_contents = stripslashes(urldecode( $_POST['data']['fileContent'] ));

        $test = $this->test_file_edits( $file_name, $file_contents );

        // generate the response
        if ( $test === true ) {

            $truncated_file_name = GFCommon::truncate_middle( $file_name, '88' );
            wp_send_json_success( array(
                'saved' => true,
                'replace' => GH_CCK::get_notice( 'success', "<strong>Saved:</strong> {$truncated_file_name}")
            ) );

        } else {

            wp_send_json_error( array(
                'saved' => false,
                'replace' => GH_CCK::get_notice( 'error', 'This file can\'t be saved.<br>' . $test )
            ) );

        }

    }

    /**
     * Delete file in uploads/gravity_hopper/code/ at behest of user
     *
     * @return void
     */
    public function ajax_delete_file() {
        
        // check the nonce and that form ID has been provided
        if ( ! isset( $_POST['data']['fileName'] ) || wp_verify_nonce( sanitize_text_field( $_POST['data']['nonce'] ), 'delete_file_' . GH_CCK::get_file_slug( $_POST['data']['fileName'] ) ) == false ) {
            wp_send_json_error();
        }

        $file_name = sanitize_text_field( $_POST['data']['fileName'] );

        $test = $this->test_file_deletion( $file_name );

        // generate the response
        if ( $test === true ) {

            $truncated_file_name = GFCommon::truncate_middle( $file_name, '88' );
            wp_send_json_success( array(
                'saved' => true,
                'replace' => GH_CCK::get_notice( 'success', "<strong>Deleted:</strong> {$truncated_file_name}")
            ) );

        } else {

            wp_send_json_error( array(
                'saved' => false,
                'replace' => GH_CCK::get_notice( 'error', 'This file can\'t be deleted.<br>' . $test )
            ) );

        }

    }

    /**
     * Run test to ensure file edits do not result in fatal error on the site and maybe update file
     *
     * @param string $file
     * @param string $content
     * @return mixed string returned on error | (bool) true on success
     */
    public function test_file_edits( $file, $content ) {

        $original_file = $file;
        $temp_file = str_replace( '.php', '-tmp.php', $original_file );
        $content = '<?php
' . $content;
        
        // Copy the original file to a temporary location.
        $has_copied_file = copy($original_file, $temp_file);
        GH_CCK::log( $has_copied_file, "Create temp file {$temp_file}: {$has_copied_file}", __METHOD__ );
            
        // Here you write code to make changes to $temp_file
        $is_temp_file_updated = file_put_contents($temp_file, $content);
        GH_CCK::log( $is_temp_file_updated, "Update temp file: {$is_temp_file_updated}", __METHOD__ );

        $test_url = add_query_arg( 'ghcck-testing-edits', urlencode( $file ), site_url() );

        GH_CCK::log( 'debug', "Test request URL: {$test_url}", __METHOD__ );
        $test = wp_remote_get( $test_url );
        
        GH_CCK::log( 'debug', "Test response: " . wp_remote_retrieve_response_code( $test ), __METHOD__ );
        
        // Check for HTTP errors
        if ( is_wp_error( $test ) ) {
            
            GH_CCK::log( 'error', "HTTP error during test: " . $test->get_error_message(), __METHOD__ );

            $is_temp_file_removed = unlink( $temp_file );
            GH_CCK::log( $is_temp_file_removed, "Remove temp file: {$is_temp_file_removed}", __METHOD__ );

            return $test->get_error_message();
            
        }

        // Get the response body and check for execution errors
        $body = wp_remote_retrieve_body( $test );
        if ( wp_remote_retrieve_response_code( $test ) !== 200 ) {

            GH_CCK::log( 'error', "Failed request: " . GH_CCK::extract_fatal_error( $body ), __METHOD__ );

            $is_temp_file_removed = unlink( $temp_file );
            GH_CCK::log( $is_temp_file_removed, "Remove temp file: {$is_temp_file_removed}", __METHOD__ );

            return GH_CCK::extract_fatal_error( $body );
            
        }

        // If validation passes, update the original file
        $has_temp_replaced_original = rename( $temp_file, $original_file );
        GH_CCK::log( $has_temp_replaced_original, "Update original file: {$has_temp_replaced_original}", __METHOD__ );

        return $has_temp_replaced_original;
        
    }

    /**
     * Run test to ensure file edits do not result in fatal error on the site and maybe update file
     *
     * @param string $file
     * @param string $content
     * @return mixed string returned on error | (bool) true on success
     */
    public function test_file_deletion( $file ) {

        $test_url = add_query_arg( 'ghcck-testing-deletion', urlencode( $file ), site_url() );

        GH_CCK::log( 'debug', "Test request URL: {$test_url}", __METHOD__ );
        $test = wp_remote_get( $test_url );
        
        GH_CCK::log( 'debug', "Test Response: " . wp_remote_retrieve_response_code( $test ), __METHOD__ );
        
        // Check for HTTP errors
        if ( is_wp_error( $test ) ) {
            
            GH_CCK::log( 'error', "HTTP error during test: " . $test->get_error_message(), __METHOD__ );

            return $test->get_error_message();
            
        }

        // Get the response body and check for execution errors
        $body = wp_remote_retrieve_body( $test );
        if ( wp_remote_retrieve_response_code( $test ) !== 200 ) {

            GH_CCK::log( 'error', "Failed request: " . GH_CCK::extract_fatal_error( $body ), __METHOD__ );

            // return wp_remote_retrieve_response_message( $test );
            return GH_CCK::extract_fatal_error( $body );
            
        }

        // If validation passes, update the original file
        $is_file_deleted = unlink( $file );
        GH_CCK::log( $is_file_deleted, 'Delete file ' . ( $is_file_deleted ? $file : '' ), __METHOD__ );

        return $is_file_deleted;
        
    }
    
    /**
     * Maybe create file after new form is created
     *
     * @param object $form
     * @param boolean $is_new
     * @return void
     */
    public static function maybe_create_form_file( $form, $is_new = true ) {

        if ( apply_filters( 'gravityhopper-cck/create_file_after_new_form', false ) ) {
        
            GH_CCK::create_form_file( rgar( $form, 'id' ) );

        }
        
    }

    /**
     * Create file for specific form
     *
     * @param integer $form_id
     * @return mixed
     */
    public static function create_form_file( $form_id ) {

        $form_filename = GH_CCK::get_file_name( $form_id );
        $form = GFAPI::get_form( $form_id );

        $content =
'<?php
/**
 * Form ID '.$form_id.' ~~'.rgar( $form, 'title' ).'~~
 * 
 * This file is intended for housing code specific to the above-indicated form.
 * Code Keeper does not restrict when specific files are included on the site.
 * All code from allowed files residing in the `gravity_hopper/code/` directory will run for all forms.
 * Always use appropriate hooks and/or conditional checks when targeting specific forms and fields.
 */

';
 
        return GH_CCK::create_file( $form_filename, $content );
        
    }

    /**
     * Create file given file name and starter content
     *
     * @param string $file_name
     * @param string $content
     * @return mixed
     */
    public static function create_file( $file_name, $content ) {

        if ( ! file_exists( $file_name ) ) {
        
            GH_CCK::initialize_root_folder();
            
            $result = @touch( $file_name );

            if ( $result ) {

                file_put_contents( $file_name, $content );

            }

            $log_type = $result ? 'debug' : 'error';
            GH_CCK::log( $log_type, "Make file {$file_name}: {$result}", __METHOD__ );

            return $result;

        } else {

            return false;

        }
        
    }

    /**
     * Duplicate file after existing form is duplicated
     *
     * @param int $existing_form_id
     * @param int $new_form_id
     * @return void
     */
    public static function duplicate_form_file( $existing_form_id, $new_form_id ) {

        if ( apply_filters( 'gravityhopper-cck/create_file_after_duplicate_form', false ) ) {
        
            GH_CCK::initialize_root_folder();

            $existing_form_filename = GH_CCK::$code_dir . 'gform-' . str_pad( rgar( $existing_form_id, 'id' ), 4, '0', STR_PAD_LEFT ) . '.php';
            
            if ( file_exists( $existing_form_filename ) ) {
                
                $new_form_filename = GH_CCK::$code_dir . 'gform-' . str_pad( rgar( $new_form_id, 'id' ), 4, '0', STR_PAD_LEFT ) . '.php';

                $result = copy( $existing_form_filename, $new_form_filename );

                $log_type = $result ? 'debug' : 'error';
                GH_CCK::log( $log_type, "Duplicate file {$existing_form_filename} to {$new_form_filename}: {$result}", __METHOD__ );

            } else {

                GH_CCK::create_form_file( $new_form_id );

            }

        }
        
    }

    /**
     * Maybe create files for an array of forms
     *
     * @param array $forms
     * @return void
     */
    public static function maybe_create_form_files( $forms ) {

        foreach ( $forms as $form ) {

            GH_CCK::maybe_create_form_file( $form );
            
        }

    }
  
    /**
     * Remove code file associated with form
     *
     * @param integer $form_id
     * @return void
     */
    public static function remove_form_file( $form_id ) {

        $form_filename = GH_CCK::get_file_name( $form_id );

        if ( apply_filters( 'gravityhopper-cck/remove_file_after_delete_form', false ) && file_exists( $form_filename ) ) {
            
            $result = unlink( $form_filename );

            $log_type = $result ? 'debug' : 'error';
            GH_CCK::log( $log_type, "Delete file {$form_filename}: {$result}", __METHOD__ );

        }
        
    }

    /**
     * Add form settings menu item
     *
     * @param array $setting_tabs
     * @param integer $form_id
     * @return void
     */
    public function add_form_settings_menu_item( $setting_tabs, $form_id ) {

        $setting_tabs['32.7'] = array(
            'name'         => 'gravityhopper_cck',
            'label'        => esc_html__( 'Code Keep', 'gravityhopper-cck' ),
            'icon'         => 'dashicons dashicons-editor-code',
            'query'        => array( 'cid' => null, 'nid' => null, 'fid' => null ),
            'capabilities' => array( 'gravityforms_edit_forms' ),
            'class'        => '',
        );

        return $setting_tabs;

    }    

    /**
     * Render content for form settings subview page
     *
     * @return void
     */
    public function add_form_settings_subview_page() {

        GFFormSettings::page_header();

        $form_id = sanitize_text_field( rgget( 'id' ) );
        $form_file_name = GH_CCK::get_file_name( $form_id );

        if ( file_exists( $form_file_name ) ) : ?>
            <?php echo GH_CCK::get_file_editor_markup( $form_file_name, array( 'title' => 'Form Code' ) ); ?>
        <?php else :
            $nonce = wp_create_nonce( 'create_form_file' ); ?>    
            <div id="gravityhopper_cck-create_file_trigger_container" class="gform-settings-panel__content" style="font-style:italic; padding: 1.4em; font-size: 80%;">
                <button id="gravityhopper_cck-create_file_trigger" data-form-id="<?php esc_attr_e( $form_id ); ?>" data-nonce="<?php esc_attr_e( $nonce ); ?>" class="primary button large" style="vertical-align:middle;margin-right:1em;">Create File</button><span><em>File for housing code will be created at <code style="font-size: 90%; margin-left: .25em;"><?php esc_html_e( GFCommon::truncate_middle( $form_file_name, 88 ) ); ?></code>.
            </div>
        <?php endif; ?>
            
        <?php

    }

    /**
	 * @param $type string The type of code editor to get. One of 'js' or 'css
	 * @parap $code string The code to render in the editor.
	 */
	public static function get_file_editor_markup( $file_name, $args = [] ) {
        do_action( 'qm/debug', $file_name );
        $defaults = array(
            'collapsible' => false,
            'collapsed' => true,
            'preview' => false,
            'title' => '',
            'show_path' => true,
            'allow_delete' => true
        );

        $file_slug = GH_CCK::get_file_slug( $file_name );
        $args = wp_parse_args( $args, $defaults );
		
        $file_contents = file_exists( $file_name ) ? trim( ltrim( file_get_contents( $file_name ), '<?php' ) ) : '';
        
        $collapse_classes = $args['collapsible'] ? ' gform-settings-panel--collapsible' : '';
        $collapse_classes .= $args['collapsible'] && $args['collapsed'] ? ' gform-settings-panel--collapsed' : '';

        if ( $args['collapsible'] ) {
            
            ob_start(); ?>
            
                <span class="gform-settings-panel__collapsible-control">
                    <input
                            type="checkbox"
                            id="gform_settings_section_collapsed_<?= $file_slug ?>"
                            name="gform_settings_section_collapsed_<?= $file_slug ?>"
                            value="1"
                            onclick="this.checked ? this.closest( '.gform-settings-panel' ).classList.add( 'gform-settings-panel--collapsed' ) : this.closest( '.gform-settings-panel' ).classList.remove( 'gform-settings-panel--collapsed' )"
                            checked="yes"
                    />
                    <label class="gform-settings-panel__collapsible-toggle" for="gform_settings_section_collapsed_uninstall"><span class="screen-reader-text"><?php printf( esc_html__( 'Toggle %s Section', 'gravityforms' ), 'Global Code' ); ?></span></label>
                </span>
            
            <?php $collapse_control = ob_get_clean();
        
        } else { $collapse_control = ''; }

        ob_start(); ?>

            <fieldset id="<?= $file_slug; ?>" class="gravityhopper_cck_editor gform-settings-panel gform-settings-panel--with-title <?= $collapse_classes; ?>">
                <legend class="gform-settings-panel__title gform-settings-panel__title--header">
                    <?= $args['title']; ?><?php if ( $args['show_path'] ) : ?><code style="font-size: 70%; margin-left: 2em;"><?php esc_html_e( $file_name ); ?></code><?php endif; ?>
                </legend>
                <?= $collapse_control; ?>
                <div class="gform-settings-panel__content" style="max-width: 858px;">

                    <?php
                        global $current_screen;
                        if ( $current_screen->id != 'forms_page_gravityhopper_cck' ) {
                            GH_CCK::print_file_load_disclaimer();
                        }
                    ?>
                
                    <textarea id="gravityhopper_cck_<?php esc_attr_e( $file_slug ); ?>" name="gravityhopper_cck_<?php esc_attr_e( $file_slug ); ?>" spellcheck="false" style="width:100%%;height:14rem;"><?= $file_contents; ?></textarea>

                    <div class="gravityhopper_cck_file_action_container">
                        <div data-js="button-container">
                            <?php $nonce = wp_create_nonce( "save_file_{$file_slug}" ); ?>
                            <button
                                id="gravityhopper_cck_save--<?php esc_attr_e( $file_slug ); ?>"
                                data-file-slug="<?= esc_attr_e( $file_slug ); ?>"
                                data-file-name="<?= esc_attr_e( $file_name ); ?>"
                                data-nonce="<?php esc_attr_e( $nonce ); ?>"
                                name="save--<?php esc_attr_e( $file_slug ); ?>"
                                class="gform-button gform-button--size-xs gform-button--primary gform-button--active-type-loader">
                                <span
                                    class="gform-button__text gform-button__text--inactive"
                                    data-js="button-inactive-text">
                                    Save File
                                </span>
                            </button>
                        </div>
                        <div class="gravityhopper_cck_alert_container" data-file-slug="<?php esc_attr_e( $file_slug ); ?>"></div>
                        <?php if ( $args['allow_delete'] ) : ?>
                            <div data-js="button-container">
                                <?php $nonce = wp_create_nonce( "delete_file_{$file_slug}" ); ?>
                                <button
                                    id="gravityhopper_cck_delete--<?php esc_attr_e( $file_slug ); ?>"
                                    data-file-slug="<?= esc_attr_e( $file_slug ); ?>"
                                    data-file-name="<?= esc_attr_e( $file_name ); ?>"
                                    data-nonce="<?php esc_attr_e( $nonce ); ?>"
                                    name="delete--<?php esc_attr_e( $file_slug ); ?>"
                                    class="gform-button gform-button--size-xs gform-button--white gform-button--active-type-loader"
                                >
                                    <span
                                    class="gform-button__text gform-button__text--inactive"
                                    data-js="button-inactive-text"
                                    >
                                    Delete File
                                    </span>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </fieldset>
            
            <?php if ( ! $args['preview'] ) : ?>
                <script>
                    jQuery( document ).ready( function( $ ) {
                        function updateTextarea(fileSlug) {
                            document.getElementById(`gravityhopper_cck_${fileSlug}`).value = editors[`${fileSlug}`].codemirror.getValue();
                        }
                        var editors = {};
                        editors['<?php echo esc_js($file_slug); ?>'] = wp.codeEditor.initialize($("#gravityhopper_cck_<?php echo esc_js($file_slug); ?>"), editor_settings.php_code_editor);
                        $('#gform_settings_section_collapsed_<?= esc_js( $file_slug ); ?>').click(function() {
                            editors['<?php echo esc_js( $file_slug ); ?>'].codemirror.refresh(); // Refresh the CodeMirror instance
                        });
                        // Attach the function to CodeMirror's change event
                        editors['<?php echo esc_js($file_slug); ?>'].codemirror.on("change", function() {
                            updateTextarea('<?php echo esc_js($file_slug); ?>');
                        });
                    } );
                </script>
            <?php endif; ?>
        
        <?php return ob_get_clean();

	}

    /**
     * Sluggify file name given full path
     *
     * @param string $file_name
     * @return string
     */
    public static function get_file_slug( $file_name ) {
        return sanitize_title( str_replace( GH_CCK::$code_dir, '', $file_name ) );
    }

    /**
     * Get full path file name given form ID
     *
     * @param string $form_id
     * @return string
     */
    public static function get_file_name( $form_id ) {
        return GH_CCK::$code_dir . 'gform-' . str_pad( $form_id, 4, '0', STR_PAD_LEFT ) . '.php';
    }

    /**
     * Get notice markup for error or success
     *
     * @param string $type
     * @param string $message
     * @return void
     */
    public static function get_notice( $type, $message ) {
        
        if ( $type == 'error' ) {
            $icon = 'error';
        } elseif ( $type = 'success' ) {
            $icon = 'check';
        }

        ob_start(); ?>

            <div id="alert-gravityhopper_cck" class="gform-alert gform-alert--<?= $type ?> gform-alert--theme-primary gform-alert--inline">
                <span aria-hidden="true" class="gform-alert__icon gform-icon gform-icon--circle-<?= $icon; ?>-fine"></span>
                <div class="gform-alert__message-wrap">
                    <p class="gform-alert__message"><?= $message; ?></p>
                </div>
            </div>

        <?php return ob_get_clean();

    }

    /**
     * Extract the fatal error message from body content of HTTP response
     *
     * @param string $body
     * @return mixed
     */
    public static function extract_fatal_error( $body ) {

        $pattern = '/<b>(Fatal error|Parse error|Syntax error|Uncaught exception|Internal Server Error|Memory Exhausted|Database Connection Error|Service Unavailable|Timeout Error)<\/b>[\s\S]*?\.php(:\d+|<\/b> on line <b>\d+<\/b>)/';

        if ( preg_match( $pattern, $body, $matches ) ) {
            return $matches[0];
        } else {
            return null;
        }

    }

    /**
     * Set GF add-on menu icon
     *
     * @return string
     */
    public function get_menu_icon() {

        return 'dashicons-buddicons-pm';

    }

    /**
     * Adds menu item to Gravity Forms Import/Export screen
     *
     * @param array $setting_tabs
     * @return void
     */
    public function add_export_menu_item( $setting_tabs ) {

        if ( GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {

            $setting_tabs['37.3'] = array(
                'name'  => 'export_gravityhopper_cck',
                'label' => esc_html__( 'Export Code', 'gravityhopper-cck' ),
                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" width="24" height="24" preserveAspectRatio="xMidYMid meet" viewBox="0 0 20 20"><path d="M9 6l-4 4l4 4l-1 2l-6-6l6-6zm2 8l4-4l-4-4l1-2l6 6l-6 6z" fill="currentColor"/></svg>',
            );

        }

        return $setting_tabs;

    }

    /**
     * Render contents of export code files page
     *
     * @return void
     */
    public function add_export_page() {

        if ( ! GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
			wp_die( 'You do not have permission to access this page' );
		}

		GFForms::admin_header( GFExport::get_tabs(), false );

		?>
		<script type="text/javascript">

			( function( $, window, undefined ) {

				$( document ).on( 'click keypress', '#gh_export_code_files_all', function( e ) {

					var checked  = e.target.checked,
					    label    = $( 'label[for="gh_export_code_files_all"]' ),
					    fileList = $( '#export_code_files_list' );

					// Set label.
					label.find( 'strong' ).html( checked ? label.data( 'deselect' ) : label.data( 'select' ) );

					// Change checkbox status.
					$( 'input[name]', fileList ).prop( 'checked', checked );

				} );

			}( jQuery, window ));

		</script>

        <div class="gform-settings__content">
            <form method="post" id="gform_export" class="gform_settings_form">
	            <?php wp_nonce_field( 'gh_export_code_files', 'gh_export_code_files_nonce' ); ?>
                <div class="gform-settings-panel gform-settings-panel--full">
                    <header class="gform-settings-panel__header"><legend class="gform-settings-panel__title"><?php esc_html_e( 'Export Code Files', 'gravityhopper-cck' )?></legend></header>
                    <div class="gform-settings-panel__content">
                        <div class="gform-settings-description">
	                        <?php esc_html_e( 'Select the files you would like to export. When you click the download button below, Gravity Forms will save a .zip file to your computer.', 'gravityhopper-cck' ); ?>
                        </div>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <label for="export_code_files_list"><?php esc_html_e( 'Select Files', 'gravityhopper-cck' ); ?></label> <?php gform_tooltip( 'export_select_code_files' ) ?>
                                </th>
                                <td>
                                    <ul id="export_code_files_list">
                                        <li>
                                            <input type="checkbox" id="gh_export_code_files_all" />
                                            <label for="gh_export_code_files_all" data-deselect="<?php esc_attr_e( 'Deselect All', 'gravityhopper-cck' ); ?>" data-select="<?php esc_attr_e( 'Select All', 'gravityhopper-cck' ); ?>"><?php esc_html_e( 'Select All', 'gravityhopper-cck' ); ?></label>
                                        </li>
					                    <?php

					                    $files = GH_CCK::get_files();
                                        $forms = RGFormsModel::get_forms( null, 'title' );
                                        
                                        // set an array of file form data for displaying checkox options
                                        $files = array_map( function( $file ) use ( $forms ) {

                                            // strip filename to retrieve file identifier
                                            $filename = str_replace( GH_CCK::$code_dir, '', $file );
                                            $fileabbr = ltrim( str_replace( [ 'gform-', '.php' ], '', $filename ), '0' );
                                            
                                            // loop through available forms and if file identifier is numeric, set form
                                            if ( is_numeric( $fileabbr ) ) {
                                                $form = array_values( array_filter( $forms, function( $form ) use ( $fileabbr ) {
                                                    return $fileabbr == $form->id;
                                                } ) );
                                            } else {
                                                $form = false;
                                            }

                                            // return structured data that we can loop through
                                            return [
                                                'name' => $filename,
                                                'abbr' => $fileabbr,
                                                'formID' => is_numeric( $fileabbr ) ? (int) $fileabbr : false,
                                                'formTitle' => ( $form !== false ) ? $form[0]->title : ''
                                            ];

                                        }, $files );

                                        // render file export inputs
					                    foreach ( $files as $file ) {
						                    ?>
                                            <li>
                                                <input type="checkbox" name="gh_code_file_name[]" id="gh_code_file_name_<?php esc_attr_e( $file['abbr'] ) ?>" value="<?php esc_attr_e( $file['name'] ) ?>" />
                                                <label for="gh_code_file_name_<?php esc_attr_e( $file['abbr'] ) ?>"><code><?php esc_html_e( $file['name'] ) ?></code>
                                                <?php
                                                    if ( $file['formTitle'] != '' ) {
                                                        esc_html_e( " - {$file['formTitle']}" );
                                                    }
                                                ?>
                                                </label>
                                            </li>
						                    <?php
					                    }
					                    ?>
                                    </ul>
                                </td>
                            </tr>
                        </table>
                        <br /><br />
                        <input type="submit" value="<?php esc_attr_e( 'Download Export File', 'gravityhopper-cck' ) ?>" name="export_gravityhopper_cck_files" class="button large primary" />
                    </div>
                </div>
            </form>
        </div>
		<?php

        GFForms::admin_footer();

    }

    /**
     * Retrieve array of all filenames contained in uploads/gravity_hopper/code/
     *
     * @return void
     */
    public static function get_files() {

        $files = [];

        if ( file_exists( GH_CCK::$code_dir . 'gf-global-code.php' ) ) {
            $files[] = GH_CCK::$code_dir . 'gf-global-code.php';
        }

        foreach ( glob( GH_CCK::$code_dir . "gform-*.php" ) as $filename ) {
            $files[] = $filename;
        }

        foreach ( glob( GH_CCK::$code_dir . "gf-*.php" ) as $filename ) {
            if ( $filename != GH_CCK::$code_dir . 'gf-global-code.php' ) {
                $files[] = $filename;
            }
        }

        return $files;

    }

    /**
	 * Process the code file export request.
	 */
	public static function maybe_export() {

		if ( isset( $_POST['export_gravityhopper_cck_files'] ) ) {

			check_admin_referer( 'gh_export_code_files', 'gh_export_code_files_nonce' );

			$selected_files = rgpost( 'gh_code_file_name' );

			if ( empty( $selected_files ) ) {

				GFCommon::add_error_message( __( 'Please select the files to be exported.', 'gravityhopper-cck' ) );
				return;

			}

			self::export_files( $selected_files );

		}

	}

    /**
     * Package and trigger download of the exported files
     *
     * @param array $filenames
     * @return void
     */
	public static function export_files( $filenames ) {

		/**
		 * Allows the code export filename to be changed.
		 *
		 * @since 2.3.4
		 *
		 * @param string   $filename	The new filename to use for the export file.
		 * @param array    $form_ids    Array containing the IDs of forms selected for export.
		 */
		$zipname = apply_filters( 'gravityhopper-cck/export_filename', 'gform-custom-code-export-' . date( 'Y-m-d' ), $form_ids ) . '.zip';
		$zipname = sanitize_file_name( $zipname );
        
        $zip = new ZipArchive;
        $zip->open( $zipname, ZipArchive::CREATE|ZipArchive::OVERWRITE );
        foreach( $filenames as $filename ) {
            $zip->addfile( GH_CCK::$code_dir . $filename, $filename );
        }        
        $zip->close();

		header( 'Content-Description: File Transfer' );
		header( "Content-Disposition: attachment; filename=$zipname" );
		header( 'Content-Type: application/zip' );
		header( 'Content-Length: ' . filesize( $zipname ) );
		readfile($zipname);

		die();

	}

    /**
     * Get our directory located within WordPress uploads/
     *
     * @return void
     */
    public static function get_upload_root() {

        $wp_upload_dir = wp_upload_dir();

        if ( $wp_upload_dir['error'] ) {
            return null;
        }

        return $wp_upload_dir['basedir'] . '/gravity_hopper/code/';

    }

    public function keyboard_shortcuts( $shortcuts ) {

        $screen = get_current_screen();

        if ( rgget( 'id' ) && $screen->parent_base == 'gf_edit_forms' ) {

            $shortcuts['Gravity Hopper']['Custom Code Keeper']['custom_code_keeper'] = [
                'keys' => 'g <',
                'function' => 'window.location.href = `${window.location.pathname}?page=gf_edit_forms&view=settings&subview=gravityhopper_cck&id={{id}}`;',
                'description' => 'Go to Custom Code',
                'priority' => 10
            ];

        }

        return $shortcuts;

    }

    /**
     * Process log messages
     *
     * @param mixed  $type
     * @param string $announce
     * @param string $method
     * @param string $code
     * @param string $message
     * @param string $body
     * @return void
     */
    public static function log( $type, $announce, $method = '', $code = '', $message = '', $body = '' ) {

        $type = ( is_bool( $type ) && $type === false) || $type == 'error' ? 'error' : 'debug';
        $method = str_pad( $method.'()', 42 );
        $response = $code || $message ? "{$code} {$message} << " : '';
        $log = $response . $announce;

        switch ( $type ) {
            case 'error':
                (new GH_CCK())->log_error( "{$method} :: {$log}" );
                break;
            case 'debug':
                (new GH_CCK())->log_debug( "{$method} :: {$log}" );
                break;
        }

    }

}
