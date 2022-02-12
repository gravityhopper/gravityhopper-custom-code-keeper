<?php
/**
* Plugin Name: Gravity Custom Code Keeper
* Plugin URI: https://wordpress.org/plugins/gravity-custom-code-keeper
* Description: Provides a reliable and consistent way to store and load custom form-related code on your site.
* Version: 2.1.2
* Author: Gravity Hopper
* Author URI: https://gravityhopper.com
* Text Domain: gravityhopper-cck
*
* @package gravityhopper
* @version 2.1.2
* @author uamv
* @copyright Copyright (c) 2021, uamv
* @link https://gravityhopper.com
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.1.html
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! file_exists( WPMU_PLUGIN_DIR . '/gravityhopper-custom-code-keeper-loader.php' ) ) {
    require_once 'files/gravityhopper-custom-code-keeper-loader.php';
}

if ( ! function_exists( 'run_gravityhopper_custom_code_keeper' ) ) :

    define( 'GRAVITYHOPPER_CCK_VERSION', '2.1.2' );
    define( 'GRAVITYHOPPER_CCK_DIR_PATH', plugin_dir_path( __FILE__ ) );
    define( 'GRAVITYHOPPER_CCK_DIR_URL', plugin_dir_url( __FILE__ ) );

    function run_gravityhopper_custom_code_keeper() {

        add_action( 'gform_loaded', function() {

            if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
                return;
            }

            require_once 'class-gh-cck.php';

            GFAddOn::register( 'GH_CCK' );
            
        }, 5 );
        
    }
    run_gravityhopper_custom_code_keeper();

endif;