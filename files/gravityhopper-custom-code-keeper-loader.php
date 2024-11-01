<?php
add_filter( 'gform_system_report', function( $system_report ) {

    $wp_upload_dir = wp_upload_dir();
    $code_dir = $wp_upload_dir['basedir'] . '/gravity_hopper/';

    if ( file_exists( $code_dir . 'code' ) ) {
        $code_location = $code_dir . 'code';
    } else {
        $code_location = 'Custom code directory does not exists.';
    }

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
                'label'        => esc_html__( 'Custom Code Location', 'gravityhopper-cck' ),
                'label_export' => 'Custom Code Location',
                'value'        => esc_html__( $code_location, 'gravityhopper-cck' ),
            ),
            array(
                'label'        => esc_html__( 'Code Loading', 'gravityhopper-cck' ),
                'label_export' => 'Code Loading',
                'value'        => get_option( 'gravityhopper_cck_loading', true ) ?  esc_html__( 'Code is Active', 'gravityhopper-cck' ) : esc_html__( 'Disabled', 'gravityhopper-cck' ),
            ),
            array(
                'label'         => esc_html__( 'Allowed Prefixes', 'gravityhopper-cck' ),
                'label_export'  => 'Allowed File Prefixes',
                'value'         => implode( ', ', apply_filters( 'gravityhopper-cck/allowed_file_prefixes', array( 'gf-' ) ) )
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

add_action( 'init', function() {

    $code_loading = get_option( 'gravityhopper_cck_loading', true ) || ! file_exists( WPMU_PLUGIN_DIR . '/gravityhopper-custom-code-keeper-loader.php' );

    if ( class_exists( 'GFForms' ) && version_compare( GFForms::$version, '2.5', '>' ) && $code_loading ) {

        $wp_upload_dir = wp_upload_dir();
        $code_dir = $wp_upload_dir['basedir'] . '/gravity_hopper/';
        $doing_file_test = urldecode( rgget( 'ghcck-testing-edits' ) );
        $doing_deletion_test = urldecode( rgget( 'ghcck-testing-deletion' ) );

        GFCommon::log_debug( current_filter() . ": testing: {$doing_file_test}" );
        
        if ( file_exists( $code_dir . 'code' ) ) {
            
            // include the global code file we've added
            GFCommon::log_debug( current_filter() . ": Global Temp GET: {$doing_file_test}" );
            
            if ( $doing_file_test == realpath( $code_dir ) . '/code/gf-global-code.php' && file_exists( realpath( $code_dir ) . '/code/gf-global-code-tmp.php' ) ) {
                include_once realpath( $code_dir ) . '/code/gf-global-code-tmp.php';
            } else if ( file_exists( $code_dir . 'code/gf-global-code.php' ) ) {
                include_once realpath( $code_dir ) . '/code/gf-global-code.php';
            }
            
            // find and include files with filename matching pattern of explicitly allowed prefixes
            $allowed_prefixes = apply_filters( 'gravityhopper-cck/allowed_file_prefixes', array( 'gf-' ) );
    
            foreach ( $allowed_prefixes as $allowed_prefix ) {
                
                foreach ( glob( realpath( $code_dir ) . "/code/{$allowed_prefix}*.php" ) as $filename ) {

                    if ( $doing_file_test == realpath( $filename ) && file_exists( realpath( str_replace( '.php', '-tmp.php', $filename ) ) ) ) {
                        include_once realpath( str_replace( '.php', '-tmp.php', $filename ) );
                    } else if ( strpos( $filename, '-tmp.php' ) === false && $doing_deletion_test != realpath( $filename ) ) {
                        include_once realpath( $filename );
                    }
                    
                    
                }

            }
            
            // find and include files with filename matching explicit pattern of gform-*.php if form with corresponding ID exists
            foreach ( glob( realpath( $code_dir ) . "/code/gform-*.php" ) as $filename ) {
                
                if ( $doing_file_test == realpath( $filename ) && file_exists( realpath( str_replace( '.php', '-tmp.php', $filename ) ) ) ) {
                    include_once realpath( str_replace( '.php', '-tmp.php', $filename ) );
                } else if ( strpos( $filename, '-tmp.php' ) === false && $doing_deletion_test != realpath( $filename ) ) {

                    // strip directory path and filename to retrieve form ID
                    $form_id = ltrim( str_replace( [ realpath( $code_dir ), '/code/gform-', '.php' ], '', $filename ), '0' );
                    
                    // only include file if matching form exists
                    if ( GFAPI::form_id_exists( (int) $form_id ) ) {

                        include_once realpath( $filename );
                        
                    }

                }

            }
            
        }

    }

}, 1, 0 );

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

        update_option( 'gravityhopper_cck_loading', sanitize_text( $_POST['gravityhopper_cck_load'] ) );

    }

} );