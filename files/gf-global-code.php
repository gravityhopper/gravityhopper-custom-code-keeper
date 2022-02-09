<?php
/**
 * This file houses globally run Gravity Forms related code and will load prior to all other files in the gravity_hopper/code/ directory.
 * To include form-specific code, please create a file with the naming convention of gf-00xx.php.
 * Files in this directory are loaded by the must-use plugin Gravity Custom Code Keeper Loader
 * Please note that Gravity Custom Code Keeper is intended for code organization only and doesn't restrict when code is run.
 * All code residing in files prefixed with `gf-` or `gform-` in the `gravity_hopper/code/` directory will run for all forms.
 * Always use appropriate hooks and/or conditional checks when targeting specific forms and fields.
 * 
 * Uncomment the filters below in order to change behavior of file auto-creation/duplication/deletion when managing forms.
 * For more information on Gravity Custom Code Keeper visit gravityhopper.com
 */

// add_filter( 'gravityhopper-ca/create_file_after_new_form', '__return_true' );
// add_filter( 'gravityhopper-ca/create_file_after_duplicate_form', '__return_true' );
// add_filter( 'gravityhopper-ca/remove_file_after_delete_form', '__return_true' );
