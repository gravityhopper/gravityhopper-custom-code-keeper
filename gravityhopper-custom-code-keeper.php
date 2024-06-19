<?php
/*
 * Plugin Name:       Gravity PHP Code Keeper
 * Plugin URI:        https://wordpress.org/plugins/custom-code-keeper
 * Description:       Provides a reliable and consistent way to create, store, edit, and load custom form-related PHP code on your site.
 * Version:           3.0
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            Gravity Hopper
 * Author URI:        https://gravityhopper.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gravityhopper-cck
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! file_exists( WPMU_PLUGIN_DIR . '/gravityhopper-custom-code-keeper-loader.php' ) ) {
    require_once 'files/gravityhopper-custom-code-keeper-loader.php';
}

if ( ! function_exists( 'run_gravityhopper_custom_code_keeper' ) ) :

    define( 'GRAVITYHOPPER_CCK_VERSION', '3.0' );
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