<?php
/*
	Plugin Name: Gravity Custom Code Keeper Loader
    Plugin URI: https://wordpress.org/plugins/gravityhopper-custom-code-keeper
	Description: Loads Gravity Forms related code from <code>gf-*.php</code> and <code>gform-*.php</code> files residing in <code>uploads/gravity_hopper/code/</code>
    Author: Gravity Hopper
    Author URI: https://gravityhopper.com
	Version: 2.0
 */

add_filter( 'gform_system_report', function( $system_report ) {

    $table = array(
        'title'        => esc_html__( 'Custom Code Keeper', 'gravityhopper-cck' ),
        'title_export' => 'Custom Code Keeper',
        'items'        => array(
            array(
                'label'        => esc_html__( 'Loader Location', 'gravityhopper-cck' ),
                'label_export' => 'Loader Location',
                'value'        => __DIR__,
            ),
            array(
                'label'        => esc_html__( 'Code Loading', 'gravityhopper-cck' ),
                'label_export' => 'Code Loading',
                'value'        => get_option( 'gravityhopper_cck_loading', true ) ?  __( 'Code is Active', 'gravityforms' ) : __( 'Disabled', 'gravityforms' ),
            )
        )
    );

    if ( is_plugin_active( 'gravityhopper/gravityhopper.php' ) ) {

        foreach ( $system_report as &$section ) {
            
            if ( $section['title'] == 'Gravity Hopper Environment' ) {
                $section['tables'][] = $table;
            }
    
        }

    } else {

        $system_report[] = array(
            'title'        => esc_html__( 'Gravity Hopper Environment', 'gravityforms' ),
            'title_export' => 'Gravity Hopper Environment',
            'tables'       => array(
                $table
            )
        );

    }

    return $system_report;

}, 11 );

add_action( 'gform_loaded', function() {

    $code_loading = get_option( 'gravityhopper_cck_loading', true ) || ! file_exists( WPMU_PLUGIN_DIR . '/gravityhopper-custom-code-keeper-loader.php' );

    if ( class_exists( 'GFForms' ) && version_compare( GFForms::$version, '2.5', '>' ) && $code_loading ) {
      
        $dir = wp_upload_dir();
        $code_dir = $dir['basedir'] . '/gravity_hopper/';

        if ( file_exists( $code_dir . 'code' ) ) {
            
            if ( file_exists( $code_dir . 'code/gf-global-code.php' ) ) {
                include_once $code_dir . 'code/gf-global-code.php';
            }
    
            foreach ( glob( $code_dir . "code/gform-*.php" ) as $filename ) {
                include_once $filename;
            }
    
            foreach ( glob( $code_dir . "code/gf-*.php" ) as $filename ) {
                include_once $filename;
            }
            
        }

    }

}, 1 );

add_filter( 'plugin_action_links', function( $actions, $plugin_file, $plugin_data, $context ) {

    $new_actions = array();

    if ( $plugin_file == 'gravityhopper-custom-code-keeper-loader.php' ) {

        $code_loading = get_option( 'gravityhopper_cck_loading', true ) || ( isset( $_POST['gravityhopper_cck_load'] ) && $_POST['gravityhopper_cck_load'] ) ;

        if ( $code_loading ) {

            $action_form = current_user_can( 'activate_plugins' ) ? sprintf( __( '<form action="%1$s">%2$s<input type="checkbox" name="checked[]" checked style="position: absolute; visibility: hidden;" /><input type="hidden" name="gravityhopper_cck_load" value="0" /><input type="submit" class="button" value="Stop Loading" style="vertical-align:middle;" /></form>', 'gravityhopper-cck' ), esc_url( admin_url( 'plugins.php?plugin_status=mustuse' ) ), wp_nonce_field( 'gh_toggle_code_load', 'gh_toggle_code_load_nonce' ) ) : '';
            
            $new_actions['status'] = sprintf( __( '<span style="color:#698939;vertical-align:middle;margin-right:1em;">Code is Active</span>%s', 'gravityhopper-cck' ), $action_form );
            
        } else {

            $action_form = current_user_can( 'activate_plugins' ) ? sprintf( __( '<form action="%1$s">%2$s<input type="checkbox" name="checked[]" checked style="position: absolute; visibility: hidden;" /><input type="hidden" name="gravityhopper_cck_load" value="1" /><input type="submit" class="button" value="Start Loading" style="vertical-align:middle;" /></form>', 'gravityhopper-cck' ), esc_url( admin_url( 'plugins.php?plugin_status=mustuse' ) ), wp_nonce_field( 'gh_toggle_code_load', 'gh_toggle_code_load_nonce' ) ) : '';

            $new_actions['status'] = sprintf( __( '<span style="color:#f35919;vertical-align:middle;margin-right:1em;">Not Active</span>%s', 'gravityhopper-cck' ), $action_form );

        }
    }

    return array_merge( $new_actions, $actions );

}, 10, 4 );

add_action( 'admin_init', function() {

    if ( isset( $_POST['gravityhopper_cck_load'] ) ) {

        check_admin_referer( 'gh_toggle_code_load', 'gh_toggle_code_load_nonce' );

        update_option( 'gravityhopper_cck_loading', $_POST['gravityhopper_cck_load'] );

    }

} );