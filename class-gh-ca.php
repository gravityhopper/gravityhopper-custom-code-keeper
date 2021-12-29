<?php

GFForms::include_addon_framework();

/**
* GH_CA Class
*
* @package gravityhopper-code-abode
* @author  uamv
*/
class GH_CA extends GFAddOn {

	/*---------------------------------------------------------------------------------*
	* Attributes
	*---------------------------------------------------------------------------------*/

	protected $_version = GRAVITYHOPPER_CA_VERSION;
	protected $_min_gravityforms_version = '2.5';
	protected $_slug = 'gravityhopper_ca';
    protected $_short_title = 'Code Abode';
    protected $_title = 'Gravity Hopper: Code Abode';
	protected $_path = 'gravityhopper-code-abode/gravityhopper-code-abode.php';
	protected $_full_path = GRAVITYHOPPER_CA_DIR_PATH;
    protected $_capabilities_form_settings = 'manage_options';

	private static $_instance = null;
    private static $code_dir;

	/*---------------------------------------------------------------------------------*
	* Constructor
	*---------------------------------------------------------------------------------*/

	/**
	* Get an instance of this class.
	*
	* @return GH_CA
	*/
	public static function get_instance() {

		if ( self::$_instance == null ) {
			self::$_instance = new GH_CA();
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

		load_plugin_textdomain( 'gravityhopper-ca', false, basename( dirname( __file__ ) ) . '/languages/' );

        self::$code_dir = GH_CA::get_upload_root();

        add_filter( 'gform_form_settings_menu',             [ $this, 'add_form_settings_menu_item' ],       10, 2   );
        add_action( 'gform_form_settings_page_code_abode',  [ $this, 'add_form_settings_subview_page' ]             );

        add_action( 'gform_after_save_form',                [ $this, 'create_form_file' ],                  10, 2   );

        if ( apply_filters( 'gravityhopper-ca/remove_file', false ) ) {
            add_action( 'gform_after_delete_form',          [ $this, 'remove_form_file' ]                           );
        }

        add_filter( 'gform_export_menu',                    [ $this, 'add_export_menu_item' ]                       );
        add_action( 'gform_export_page_export_gravityhopper_code_abode', [ $this, 'add_export_page' ]               );
        
        $this->maybe_export();

	} // end constructor

	/*---------------------------------------------------------------------------------*
	* Public Functions
	*---------------------------------------------------------------------------------*/

    /**
     * Add form settings menu item
     *
     * @param array $setting_tabs
     * @param integer $form_id
     * @return void
     */
    public function add_form_settings_menu_item( $setting_tabs, $form_id ) {

        $setting_tabs['32.7'] = array(
            'name'         => 'code_abode',
            'label'        => __( 'Code Abode', 'gravityhopper-ca' ),
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

        $global_file_name = GH_CA::$code_dir . 'gf-global-code.php';
        $form_file_name = GH_CA::$code_dir . 'gform-' . str_pad( $form_id, 4, '0', STR_PAD_LEFT ) . '.php';

        $global_file_contents = file_exists( $global_file_name ) ? esc_html( trim( ltrim( file_get_contents( $global_file_name ), '<?php' ) ) ) : '';
        $form_file_contents = file_exists( $form_file_name ) ? esc_html( trim( ltrim( file_get_contents( $form_file_name ), '<?php' ) ) ) : '';

        if ( file_exists( $global_file_name ) && $global_file_contents != '' ) :
            ?>

            <fieldset id="gravityhopper_ca_global" class="gform-settings-panel gform-settings-panel--with-title gform-settings-panel--collapsible gform-settings-panel--collapsed">
                <legend class="gform-settings-panel__title gform-settings-panel__title--header">
                    <?php esc_html_e( 'Global Code', 'gravityforms' ); ?><code style="font-size: 70%; margin-left: 2em;"><?= $global_file_name; ?></code>
                </legend>
                <span class="gform-settings-panel__collapsible-control">
                        <input
                                type="checkbox"
                                id="gform_settings_section_collapsed_gravityhopper_ca_global"
                                name="gform_settings_section_collapsed_gravityhopper_ca_global"
                                value="1"
                                onclick="this.checked ? this.closest( '.gform-settings-panel' ).classList.add( 'gform-settings-panel--collapsed' ) : this.closest( '.gform-settings-panel' ).classList.remove( 'gform-settings-panel--collapsed' )"
                                checked="yes"
                        />
                        <label class="gform-settings-panel__collapsible-toggle" for="gform_settings_section_collapsed_uninstall"><span class="screen-reader-text"><?php printf( esc_html__( 'Toggle %s Section', 'gravityforms' ), 'Global Code' ); ?></span></label>
                    </span>
                <div class="gform-settings-panel__content">
                    <pre style="background-color: #ecedf8; padding: 1.5em; margin: 0;"><?= $global_file_contents; ?></pre>
                </div>
            </fieldset>
            <br />
        <?php endif; ?>
        <?php if ( file_exists( $form_file_name ) && $form_file_contents != '' ) : ?>
            <fieldset class="gform-settings-panel gform-settings-panel--with-title">
                <legend class="gform-settings-panel__title gform-settings-panel__title--header">
                    <?php esc_html_e( 'Form Code', 'gravityforms' ); ?><code style="font-size: 70%; margin-left: 2em;"><?= $form_file_name; ?></code>
                </legend>
                <div class="gform-settings-panel__content">
                    <pre style="background-color: #ecedf8; padding: 1.5em; margin: 0;"><?= $form_file_contents; ?></pre>
                </div>
            </fieldset>
        <?php
            endif;
            if ( ( ! file_exists( $global_file_name ) && ! file_exists( $form_file_name ) ) || ( $global_file_contents == '' && $form_file_contents == '' ) ) :
        ?>
            <div class="gform-settings-panel__content" style="padding: 1em;">
                No custom code for this form resides in the directory at <code style="font-size: 90%; margin-left: .25em;"><?= GH_CA::$code_dir; ?></code>
            </div>
            <?php endif; ?>
            <div class="gform-settings-panel__content" style="font-style:italic; padding: 1.4em; font-size: 80%;">
                <strong>Always use appropriate hooks and/or conditional checks when targeting specific forms and fields.</strong><br>
                Code Abode is intended for code organization only and doesn't restrict when code is run.<br>
                All code residing in <code style="font-size: 90%; margin-left: .25em;"><?= GH_CA::$code_dir; ?></code> will run for all forms.
            </div>
            <?php
    }

    /**
     * Create folder after new form is created
     *
     * @return void
     */
    public static function maybe_create_root_folder() {

		if ( ! file_exists( GH_CA::$code_dir ) ) {

            $result = wp_mkdir_p( GH_CA::$code_dir );

            if ( $result ) {
                @touch( substr( GH_CA::$code_dir, 0, -5 ) . 'index.html' );
                @touch( GH_CA::$code_dir . 'index.html' );
            }

            $log_type = $result ? 'debug' : 'error';
            
            GH_CA::log( $log_type, "Make directory {GH_CA::$code_dir}: {$result}", __METHOD__ );
            
		}

        GH_CA::maybe_create_global_file();
        GH_CA::maybe_create_mu_loader();
        
    }
    
    /**
     * Create file after new form is created
     *
     * @param object $form
     * @param boolean $is_new
     * @return void
     */
    public static function create_form_file( $form, $is_new ) {
        
        GH_CA::maybe_create_root_folder();

        $form_filename = GH_CA::$code_dir . 'gform-' . str_pad( rgar( $form, 'id' ), 4, '0', STR_PAD_LEFT ) . '.php';
        
        $result = @touch( $form_filename );
        $log_type = $result ? 'debug' : 'error';

        GH_CA::log( $log_type, "Make file {$form_filename}: {$result}", __METHOD__ );
        
    }
  
    /**
     * Remove code file associated with form
     *
     * @param integer $form_id
     * @return void
     */
    public static function remove_form_file( $form_id ) {
        
        $form_filename = GH_CA::$code_dir . 'gform-' . str_pad( $form_id, 4, '0', STR_PAD_LEFT ) . '.php';
        
        $result = unlink( $form_filename );
        $log_type = $result ? 'debug' : 'error';
        
        GH_CA::log( $log_type, "Delete file {$form_filename}: {$result}", __METHOD__ );
        
    }
    
    /**
     * Create global code file if it doesn't exist
     *
     * @return void
     */
    public static function maybe_create_global_file() {

        $global_filename = GH_CA::$code_dir . 'gf-global-code.php';
        
        if ( ! file_exists( $global_filename ) ) {
            
            $result = @touch( $global_filename );
            $log_type = $result ? 'debug' : 'error';

            GH_CA::log( $log_type, "Make file {$global_filename}: {$result}", __METHOD__ );

        }
        
    }

    /**
     * Create or update mu-plugin code loader if needed
     *
     * @return void
     */
    public static function maybe_create_mu_loader() {

        $mustuse_filename = WPMU_PLUGIN_DIR . '/gravityhopper-code-abode-loader.php';
        
        if ( ! file_exists( $mustuse_filename ) || ( file_exists( $mustuse_filename ) && sha1_file( $mustuse_filename ) != sha1_file( GRAVITYHOPPER_CA_DIR_PATH . '/gravityhopper-code-abode-loader.php' ) ) ) {
            
            $result = copy( GRAVITYHOPPER_CA_DIR_PATH . '/gravityhopper-code-abode-loader.php', $mustuse_filename );

            $log_type = $result ? 'debug' : 'error';

            GH_CA::log( $log_type, "Make file {$mustuse_filename}: {$result}", __METHOD__ );

        }
        
    }

    public function add_export_menu_item( $setting_tabs ) {

        if ( GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
            $setting_tabs['37.3'] = array(
                'name'  => 'export_gravityhopper_code_abode',
                'label' => __( 'Export Code', 'gravityhopper-ca' ),
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
                    <header class="gform-settings-panel__header"><legend class="gform-settings-panel__title"><?php esc_html_e( 'Export Code Files', 'gravityhopper-ca' )?></legend></header>
                    <div class="gform-settings-panel__content">
                        <div class="gform-settings-description">
	                        <?php esc_html_e( 'Select the files you would like to export. When you click the download button below, Gravity Forms will save a .zip file to your computer.', 'gravityhopper-ca' ); ?>
                        </div>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <label for="export_code_files_list"><?php esc_html_e( 'Select Files', 'gravityhopper-ca' ); ?></label> <?php gform_tooltip( 'export_select_code_files' ) ?>
                                </th>
                                <td>
                                    <ul id="export_code_files_list">
                                        <li>
                                            <input type="checkbox" id="gh_export_code_files_all" />
                                            <label for="gh_export_code_files_all" data-deselect="<?php esc_attr_e( 'Deselect All', 'gravityhopper-ca' ); ?>" data-select="<?php esc_attr_e( 'Select All', 'gravityhopper-ca' ); ?>"><?php esc_html_e( 'Select All', 'gravityhopper-ca' ); ?></label>
                                        </li>
					                    <?php

					                    $files = GH_CA::get_files();
                                        $forms = RGFormsModel::get_forms( null, 'title' );
                                        
                                        $files = array_map( function( $file ) use ( $forms ) {

                                            $filename = str_replace( GH_CA::$code_dir, '', $file );
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
                        <input type="submit" value="<?php esc_attr_e( 'Download Export File', 'gravityhopper-ca' ) ?>" name="export_gravityhopper_code_abode_files" class="button large primary" />
                    </div>
                </div>
            </form>
        </div>
		<?php

        GFForms::admin_footer();

    }

    public static function get_files() {

        $files = [];

        if ( file_exists( GH_CA::$code_dir . 'gf-global-code.php' ) ) {
            $files[] = GH_CA::$code_dir . 'gf-global-code.php';
        }

        foreach ( glob( GH_CA::$code_dir . "gform-*.php" ) as $filename ) {
            $files[] = $filename;
        }

        foreach ( glob( GH_CA::$code_dir . "gf-*.php" ) as $filename ) {
            if ( $filename != GH_CA::$code_dir . 'gf-global-code.php' ) {
                $files[] = $filename;
            }
        }

        return $files;

    }

    /**
	 * Process the code file export request.
	 */
	public static function maybe_export() {

		if ( isset( $_POST['export_gravityhopper_code_abode_files'] ) ) {
			check_admin_referer( 'gh_export_code_files', 'gh_export_code_files_nonce' );
			$selected_files = rgpost( 'gh_code_file_name' );
			if ( empty( $selected_files ) ) {
				GFCommon::add_error_message( __( 'Please select the files to be exported', 'gravityhopper-ca' ) );

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
		$zipname = apply_filters( 'gravityhopper-ca/export_filename', 'gh-code-abode-export-' . date( 'Y-m-d' ), $form_ids ) . '.zip';
		$zipname = sanitize_file_name( $zipname );
        
        $zip = new ZipArchive;
        $zip->open( $zipname, ZipArchive::CREATE|ZipArchive::OVERWRITE );
        foreach( $filenames as $filename ) {
            $zip->addfile( GH_CA::$code_dir . $filename, $filename );
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
            (new GH_CA())->log_error( "{$method} :: {$log}" );
            break;
            case 'debug':
            (new GH_CA())->log_debug( "{$method} :: {$log}" );
            break;
        }

    }


}
