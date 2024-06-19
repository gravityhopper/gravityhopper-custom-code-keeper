<?php

$current_prefix = rgget( 'subview' ) ? rgget( 'subview' ) : 'gf-';
$wp_upload_dir = wp_upload_dir();
$code_dir = $wp_upload_dir['basedir'] . '/gravity_hopper/';

$files = glob( realpath( $code_dir ) . "/code/{$current_prefix}*.php" );

// ensure gf-global-code.php is listed first
$global_code_index = array_search( realpath( $code_dir ) . '/code/gf-global-code.php', $files ); // Find the index of 'gf-global-code.php'

if ( $global_code_index !== false ) {

    $global_code_file = $files[$global_code_index];
    unset( $files[ $global_code_index ] );
    $files = array_values( $files );
    array_unshift( $files, $global_code_file );

}

if ( $current_prefix == 'gform-' ) {
    $files = array_reverse( $files );
}

$nonce = wp_create_nonce( "create_file_{$current_prefix}" );

foreach ( $files as $file_name ) {
    
    $file_contents = file_exists( $file_name ) ? trim( ltrim( file_get_contents( $file_name ), '<?php' ) ) : '';
    $short_file_name = str_replace( realpath( $code_dir ) . "/code/", '', $file_name );
    $allow_delete = $short_file_name != 'gf-global-code.php';
    
    if ( file_exists( $file_name ) && $short_file_name != 'gform-00xx.php' ) : ?>
        <?php echo GH_CCK::get_file_editor_markup( $file_name, array( 'title' => $short_file_name, 'collapsible' => true, 'collapsed' => true, 'show_path' => false, 'allow_delete' => $allow_delete ) ); ?>
    <?php endif;

} ?>

<?php if ( $current_prefix != 'gform-' ) : ?>
    <div id="gravityhopper_cck-create_file_container" class="gform-settings-panel__content">
        <div id="gravityhopper_cck-create_file_name_container">
            <span><?php esc_html_e( $current_prefix ); ?></span>
            <input type="text" id="filename" placeholder="Enter file name">
            <span id="filename-sizer" style="position:absolute; top:-9999px; left:-9999px;"></span>
            <span>.php</span>
        </div>
        <div id="gravityhopper_cck-create_file_trigger_container" class="gform-settings-panel__content" style="font-style:italic; padding: 1.4em; font-size: 80%;">
            <button type="submit" id="gravityhopper_cck_create--<?php esc_attr_e( $current_prefix ); ?>" data-nonce="<?php esc_attr_e( $nonce ); ?>" data-prefix="<?php esc_attr_e( $current_prefix ); ?>" name="create--<?php esc_attr_e( $current_prefix ); ?>" value="create" class="primary button large">Create New File</button>
        </div>
    </div>
<?php endif; ?>