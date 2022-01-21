<?php
/**
* Plugin Name: Gravity Hopper: Code Abode
* Plugin URI: https://github.com/gravityhopper/gravityhopper-code-abode
* Description: Provides a reliable and consistent way to store and load custom form-related code on your site.
* Version: 1.3
* Author: Gravity Hopper
* Author URI: https://gravityhopper.com
* Text Domain: gravityhopper-ca
*
* @package gravityhopper
* @version 1.3
* @author uamv
* @copyright Copyright (c) 2021, uamv
* @link https://gravityhopper.com
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! function_exists( 'run_gravityhopper_code_abode' ) ) :

    define( 'GRAVITYHOPPER_CA_VERSION', '1.3' );
    define( 'GRAVITYHOPPER_CA_DIR_PATH', plugin_dir_path( __FILE__ ) );
    define( 'GRAVITYHOPPER_CA_DIR_URL', plugin_dir_url( __FILE__ ) );

    function run_gravityhopper_code_abode() {

        add_action( 'gform_loaded', function() {

            if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
                return;
            }

            require_once( 'class-gh-ca.php' );

            GFAddOn::register( 'GH_CA' );
            
        }, 5 );
        
    }
    run_gravityhopper_code_abode();

endif;