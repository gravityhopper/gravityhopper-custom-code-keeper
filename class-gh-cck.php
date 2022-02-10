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
    protected $_short_title = 'Code Keeper';
    protected $_title = 'Gravity Custom Code Keeper';
	protected $_path = 'gravityhopper-custom-code-keeper/gravityhopper-custom-code-keeper.php';
	protected $_full_path = GRAVITYHOPPER_CCK_DIR_PATH;
    protected $_capabilities_form_settings = 'manage_options';

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

        add_filter( 'gform_form_settings_menu',                     [ $this, 'add_form_settings_menu_item' ],       10, 2   );
        add_action( 'gform_form_settings_page_gravityhopper_cck',   [ $this, 'add_form_settings_subview_page' ]       );

        add_action( 'gform_after_save_form',                        [ $this, 'maybe_create_form_file' ],            10, 2   );
        add_action( 'gform_post_form_duplicated',                   [ $this, 'duplicate_form_file' ],               10, 2   );
        add_action( 'gform_forms_post_import',                      [ $this, 'do_after_form_import' ],              10, 2   );
        add_action( 'gform_after_delete_form',                      [ $this, 'remove_form_file' ]                           );
        add_action( 'wp_ajax_create_form_file',                     [ $this, 'ajax_create_form_file' ]                      );

        add_filter( 'gform_export_menu',                            [ $this, 'add_export_menu_item' ]                       );
        add_action( 'gform_export_page_export_gravityhopper_cck',   [ $this, 'add_export_page' ]                      );
        
        $this->maybe_export();

	} // end constructor

	/*---------------------------------------------------------------------------------*
	* Administrative Functions
	*---------------------------------------------------------------------------------*/

    public function maybe_upgrade() {

        $old_version = get_option( 'gravityhopper_cck_version', 0 );

        if ( $old_version != GRAVITYHOPPER_CCK_VERSION ) {

            $this->do_upgrade( $old_version, GRAVITYHOPPER_CCK_VERSION );

        }

        update_option( 'gravityhopper_cck_version', GRAVITYHOPPER_CCK_VERSION, false);

    }

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
                    array( 'query' => 'page=gf_edit_forms&view=settings&subview=gravityhopper_cck' )
                )
            )
        );

        return array_merge( parent::scripts(), $scripts );

    }

    /**
     * Create folder after new form is created
     *
     * @return void
     */
    public static function initialize_root_folder() {

		if ( ! file_exists( GH_CCK::$code_dir ) ) {

            $result = wp_mkdir_p( GH_CCK::$code_dir );

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
            
            $result = copy ( GRAVITYHOPPER_CCK_DIR_PATH . '/files/gf-global-code.php', $global_filename );

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
     * Create or update mu-plugin code loader if needed
     *
     * @return void
     */
    public static function maybe_update_mu_loader() {

        $mustuse_filename = WPMU_PLUGIN_DIR . '/gravityhopper-custom-code-keeper-loader.php';
        
        if ( file_exists( $mustuse_filename ) && sha1_file( $mustuse_filename ) != sha1_file( GRAVITYHOPPER_CCK_DIR_PATH . '/files/gravityhopper-custom-code-keeper-loader.php' ) ) {
            
            $result = copy( GRAVITYHOPPER_CCK_DIR_PATH . '/files/gravityhopper-custom-code-keeper-loader.php', $mustuse_filename );

            $log_type = $result ? 'debug' : 'error';

            GH_CCK::log( $log_type, "Updated file {$mustuse_filename}: {$result}", __METHOD__ );

        }
        
    }

    public function ajax_create_form_file() {
        
        // check the nonce
        if ( wp_verify_nonce( $_POST['data']['nonce'], 'create_form_file' ) == false || ! isset( $_POST['data']['formID'] ) ) {
            wp_send_json_error();
        }

        $created = GH_CCK::create_form_file( $_POST['data']['formID'] );

        // generate the response
        if ( $created ) {
            wp_send_json_success( array(
                'replace' => GH_CCK::get_form_file_preview_markup( $_POST['data']['formID'] )
            ) );
        } else {
            wp_send_json_error( array(
                'replace' => $created
            ) );
        }

    }

	/*---------------------------------------------------------------------------------*
	* Public Functions
	*---------------------------------------------------------------------------------*/
    
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
     * @param object $form
     * @param boolean $is_new
     * @return void
     */
    public static function create_form_file( $form_id ) {

        $form_filename = GH_CCK::$code_dir . 'gform-' . str_pad( $form_id, 4, '0', STR_PAD_LEFT ) . '.php';
        $form = GFAPI::get_form( $form_id );

        if ( ! file_exists( $form_filename ) ) {
        
            GH_CCK::initialize_root_folder();
            
            $result = @touch( $form_filename );
            if ( $result ) file_put_contents( $form_filename, '<?php
/**
 * Form ID '.$form_id.' ~~'.rgar( $form, 'title' ).'~~
 * 
 * This file is intended for housing code specific to the above-indicated form.
 * Please note that Gravity Custom Code Keeper is intended for code organization only and does not restrict when code is run.
 * All code residing in files prefixed with `gf-` or `gform-` in the `gravity_hopper/code/` directory will run for all forms.
 * Always use appropriate hooks and/or conditional checks when targeting specific forms and fields.
 */

' );

            $log_type = $result ? 'debug' : 'error';

            GH_CCK::log( $log_type, "Make file {$form_filename}: {$result}", __METHOD__ );

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

        if ( apply_filters( 'gravityhopper-cck/create_file_after_duplicate_form', true ) ) {
        
            GH_CCK::initialize_root_folder();

            $existing_form_filename = GH_CCK::$code_dir . 'gform-' . str_pad( rgar( $existing_form_id, 'id' ), 4, '0', STR_PAD_LEFT ) . '.php';
            
            if ( file_exists( $existing_form_filename ) ) {
                
                $new_form_filename = GH_CCK::$code_dir . 'gform-' . str_pad( rgar( $new_form_id, 'id' ), 4, '0', STR_PAD_LEFT ) . '.php';

                $result = copy( $existing_form_filename, $new_form_filename );

                $log_type = $result ? 'debug' : 'error';
                GH_CCK::log( $log_type, "Duplicate file {$existing_form_filename} to {$new_form_filename}: {$result}", __METHOD__ );

            } else {

                GH_CCK::maybe_create_form_file( GFAPI::get_form( $new_form_id ), true );

            }

        }
        
    }

    public static function do_after_form_import( $forms ) {

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

        $form_filename = GH_CCK::$code_dir . 'gform-' . str_pad( $form_id, 4, '0', STR_PAD_LEFT ) . '.php';

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
            'label'        => __( 'Custom Code', 'gravityhopper-cck' ),
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

        $form_id = rgget('id');

        $global_file_name = GH_CCK::$code_dir . 'gf-global-code.php';
        $form_file_name = GH_CCK::$code_dir . 'gform-' . str_pad( $form_id, 4, '0', STR_PAD_LEFT ) . '.php';

        $global_file_contents = file_exists( $global_file_name ) ? esc_html( trim( ltrim( file_get_contents( $global_file_name ), '<?php' ) ) ) : '';
        $form_file_contents = file_exists( $form_file_name ) ? esc_html( trim( ltrim( file_get_contents( $form_file_name ), '<?php' ) ) ) : '';

        if ( file_exists( $global_file_name ) && $global_file_contents != '' ) :
            ?>

            <fieldset id="gravityhopper_cck_global" class="gform-settings-panel gform-settings-panel--with-title gform-settings-panel--collapsible gform-settings-panel--collapsed">
                <legend class="gform-settings-panel__title gform-settings-panel__title--header">
                    <?php esc_html_e( 'Global Code', 'gravityforms' ); ?><code style="font-size: 70%; margin-left: 2em;"><?= $global_file_name; ?></code>
                </legend>
                <span class="gform-settings-panel__collapsible-control">
                        <input
                                type="checkbox"
                                id="gform_settings_section_collapsed_gravityhopper_cck_global"
                                name="gform_settings_section_collapsed_gravityhopper_cck_global"
                                value="1"
                                onclick="this.checked ? this.closest( '.gform-settings-panel' ).classList.add( 'gform-settings-panel--collapsed' ) : this.closest( '.gform-settings-panel' ).classList.remove( 'gform-settings-panel--collapsed' )"
                                checked="yes"
                        />
                        <label class="gform-settings-panel__collapsible-toggle" for="gform_settings_section_collapsed_uninstall"><span class="screen-reader-text"><?php printf( esc_html__( 'Toggle %s Section', 'gravityforms' ), 'Global Code' ); ?></span></label>
                    </span>
                <div class="gform-settings-panel__content" style="max-width: 858px;">
                    <pre style="background-color: #ecedf8; padding: 1.5em; margin: 0; overflow-x: auto;"><code style="background: transparent; padding: 0; margin: 0; font-size: .72rem;"><?= $global_file_contents; ?></code></pre>
                </div>
            </fieldset>
            <br />
        <?php endif; ?>
        <?php if ( file_exists( $form_file_name ) ) : ?>
            <?php echo GH_CCK::get_form_file_preview_markup( $form_id ); ?>
        <?php else :
            $nonce = wp_create_nonce( 'create_form_file' ); ?>    
            <div id="gravityhopper_cck-create_file_trigger_container" class="gform-settings-panel__content" style="font-style:italic; padding: 1.4em; font-size: 80%;">
                <button id="gravityhopper_cck-create_file_trigger" data-form-id="<?= $form_id; ?>" data-nonce="<?= $nonce; ?>" class="primary button large" style="vertical-align:middle;margin-right:1em;">Create File</button><span><em>File for housing code will be created at <code style="font-size: 90%; margin-left: .25em;"><?= $form_file_name; ?></code>.
            </div>
        <?php endif;
            if ( ( ! file_exists( $global_file_name ) && ! file_exists( $form_file_name ) ) || ( $global_file_contents == '' && $form_file_contents == '' ) ) :
        ?>
            <div class="gform-settings-panel__content">
                No custom code for this form resides in the directory at <code style="font-size: 90%; margin-left: .25em;"><?= GH_CCK::$code_dir; ?></code>
            </div>
            <?php endif; ?>
            <div class="gform-settings-panel__content" style="font-style:italic; padding: 1.4em; font-size: 80%;">
                <strong>Always use appropriate hooks and/or conditional checks when targeting specific forms and fields.</strong><br>
                Gravity Custom Code Keeper is intended for code organization only and doesn't restrict when code is run.<br>
                All code residing in <code style="font-size: 90%; margin-left: .25em;"><?= GH_CCK::$code_dir; ?></code> will run for all forms.
            </div>
            <?php
    }

    public static function get_form_file_preview_markup( $form_id ) {

        $form_file_name = GH_CCK::$code_dir . 'gform-' . str_pad( $form_id, 4, '0', STR_PAD_LEFT ) . '.php';
        $form_file_contents = file_exists( $form_file_name ) ? esc_html( trim( ltrim( file_get_contents( $form_file_name ), '<?php' ) ) ) : '';

        ob_start(); ?>

        <fieldset class="gform-settings-panel gform-settings-panel--with-title">
            <legend class="gform-settings-panel__title gform-settings-panel__title--header">
                <?php esc_html_e( 'Form Code', 'gravityforms' ); ?><code style="font-size: 70%; margin-left: 2em;"><?= $form_file_name; ?></code>
            </legend>
            <div class="gform-settings-panel__content" style="max-width: 858px;">
                <pre style="background-color: #ecedf8; padding: 1.5em; margin: 0; overflow-x: auto;"><code style="background: transparent; padding: 0; margin: 0; font-size: .72rem;"><?= $form_file_contents; ?></code></pre>
            </div>
        </fieldset>

        <?php return ob_get_clean();

    }

    public function add_export_menu_item( $setting_tabs ) {

        if ( GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
            $setting_tabs['37.3'] = array(
                'name'  => 'export_gravityhopper_cck',
                'label' => __( 'Export Code', 'gravityhopper-cck' ),
                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" width="24" height="24" preserveAspectRatio="xMidYMid meet" viewBox="0 0 20 20"><path d="M9 6l-4 4l4 4l-1 2l-6-6l6-6zm2 8l4-4l-4-4l1-2l6 6l-6 6z" fill="currentColor"/></svg>',
            );
        }

        return $setting_tabs;

    }

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
                                        
                                        $files = array_map( function( $file ) use ( $forms ) {

                                            $filename = str_replace( GH_CCK::$code_dir, '', $file );
                                            $fileabbr = ltrim( str_replace( [ 'gform-', '.php' ], '', $filename ), '0' );
                                            
                                            if ( is_numeric( $fileabbr ) ) {
                                                $form = array_values( array_filter( $forms, function( $form ) use ( $fileabbr ) {
                                                    return $fileabbr == $form->id;
                                                } ) );
                                            } else {
                                                $form = false;
                                            }

                                            return [
                                                'name' => $filename,
                                                'abbr' => $fileabbr,
                                                'formID' => is_numeric( $fileabbr ) ? (int) $fileabbr : false,
                                                'formTitle' => ( $form !== false ) ? $form[0]->title : ''
                                            ];

                                        }, $files );

					                    foreach ( $files as $file ) {
						                    ?>
                                            <li>
                                                <input type="checkbox" name="gh_code_file_name[]" id="gh_code_file_name_<?php echo $file['abbr'] ?>" value="<?php echo $file['name'] ?>" />
                                                <label for="gh_code_file_name_<?php echo $file['abbr'] ?>"><code><?php echo esc_html( $file['name'] ) ?></code>
                                                <?php
                                                    echo ( $file['formTitle'] != '' ) ? " - {$file['formTitle']}" : '';
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
				GFCommon::add_error_message( __( 'Please select the files to be exported', 'gravityhopper-cck' ) );

				return;
			}

			self::export_files( $selected_files );
		}

	}

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

    public static function get_upload_root() {

        $dir = wp_upload_dir();

        if ( $dir['error'] ) {
            return null;
        }

        return $dir['basedir'] . '/gravity_hopper/code/';

    }

    /**
     * Process log messages
     *
     * @param string $type
     * @param string $announce
     * @param string $method
     * @param string $code
     * @param string $message
     * @param string $body
     * @return void
     */
    public static function log( $type, $announce, $method = '', $code = '', $message = '', $body = '' ) {

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
