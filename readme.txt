=== Gravity Custom Code Keeper ===
Contributors: uamv
Donate link: https://gravityhopper.com
Tags: gravity, forms, code, developer, php, snippets
Requires PHP: 7.2
Requires at least: 5.6
Tested up to: 5.9
Stable tag: 2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Provides a reliable and consistent way to store and load custom form-related code on your site.

== Description ==

= How does it work?
Gravity Custom Code Keeper…
1. creates a directory at `wp-content/uploads/gravity_hopper/code/`.
1. adds a file `gf-global-code.php` *(for housing globally run code)*.
1. optionally creates/duplicates/deletes a `gf-00xx.php` file for every form created/duplicated/deleted. *(for housing form-specific code)*
1. will load all files in the code directory which are prefixed with `gf-` and `gform-`.
1. allows exporting any number of code files to compressed .zip.
1. introduces a form settings page whereby you can preview relevant custom code and create a file if one doesn't yet exist.

**Please note that Gravity Custom Code Keeper is intended for code organization only and doesn't restrict when code is run.**
**All code residing in files prefixed with `gf-` or `gform-` in the `gravity_hopper/code/` directory will run for all forms.**
**Always use appropriate hooks and/or conditional checks when targeting specific forms and fields.**

Use the following filters to override default behavior file auto-generation/duplication/deletion.

```
add_filter( 'gravityhopper-cck/create_file_after_new_form', '__return_true' );
add_filter( 'gravityhopper-cck/create_file_after_duplicate_form', '__return_true' );
add_filter( 'gravityhopper-cck/remove_file_after_delete_form', '__return_true' );
```

= Why use it?
Gravity Custom Code Keeper provides orderliness to your code customizations, gives you a better sense of the specific code running across your site, and helps ensure your customizations are not lost when other site modifications are made.

**Organization:** Our file-per-form framework for organizing your code helps you keep your code in a way that is easier to access, investigate, and update.
**Reliability:** Since code is initialized via an mu-plugin loader, your code is not dependant upon a specific theme or plugin being active on the site. The mu-plugin loader is retained when Gravity Custom Code Keeper is deactivated or removed from the site. Thus, the only dependency for your code is Gravity Forms itself running on the site.
**Preservation:** Having your code reside outside of a specific plugin or theme ensures it is not lost to theme updates or plugin deactivation.
**Troubleshoot:** The mu-plugin includes an option to quickly toggle the active state of your custom code. Be sure to deactivate your code when running conflict tests.
**Portability:** A form is often only as good as the code that stands behind it. Taking your form to another site? Code file exports make it easy to pull the code you need.

= Need more for your Gravity Forms development?
Saving you time and effort with every form you build, **[Gravity Hopper](https://gravityhopper.com)** offers an elite array of developer tools that integrates seamlessly with Gravity Forms.

**Field Templates:** Add often-used fields and groups of fields to your hopper. Quickly search and add your field templates to a form.
**Organized Forms:** Create folders and file forms and entries for easy access right when you need it.
**Network Hub:** Set up a central dashboard for form development and management.
**Form Integrity:** Form Integrity runs in the background to track form dependencies and alerts you when something seems to be missing.
**Field Hinting:** Easily peek at the underlying settings of each field. Need to quickly review all field conditional logic? Get it done with a single click.
**Field Notes:** Field Notes lets you track your form development and mark up fields with markdown commenting.
**Keyboard Shortcuts:** An array of keyboard shortcuts for use in navigating your Gravity Forms dashboard.
**Enhancements:** Introduces improvements to the Gravity Forms interface and featureset.
**Configurations:** Set basic Gravity Forms options that otherwise need to be configured by code.

== Frequently Asked Questions ==

= Does Gravity Custom Code Keeper load custom code per form? =

No. This plugin is intended for code wrangling only and does not restrict when code is run. All code residing in files prefixed with `gf-` or `gform-` in the `gravity_hopper/code/` directory will run for all forms. Always use appropriate hooks and/or conditional checks when targeting specific forms and fields.

= Can I edit the custom code from within my WordPress dashboard? =

No. While each form provides a Custom Code previewer, the actual codes needs to be edited directly from within the files housed on your server.

== Installation ==

After installation and activation on your WordPress site, you will find a new `gravity_hopper/code` directory for housing your Gravity Forms related code within your WordPress site's `upload` folder.

== Changelog ==

= 2.1 // 2022.02-Feb. =
📦 NEW: Adds loader location and status indication to system report
📦 NEW: Moves loader to plugin unless user has manually added loader to mu-plugins folder
!! BREAKING: Renames filters for clarity as to how they behave

= 2.0.1 // 2022.01-Jan.31 =
🐛 FIX: Loads jquery & wp-util dependencies

= 2.0 // 2022.01-Jan.21 =
🚀 RELEASE: v2.0 renamed in preparation for WP plugin directory

= 1.3 // 2022.01-Jan.21 =
📦 NEW: Adds ability to request file creation from form setting page
📦 NEW: Adds form file template during initialization
📦 NEW: Allows file creation during import

= 1.2 // 2022.01-Jan.20 =
📦 NEW: Allows auto-duplication of files when forms are duplicated
📦 NEW: Adds filter `gravityhopper-ca/duplicate_file` to control auto-duplication of files
📦 NEW: Adds informational header and commented filters to generated `gf-global-code.php` file
👌 IMPROVE: Initializes directory structure on installation
👌 IMPROVE: Initializes created files with `<?php` opening tag

= 1.1 // 2022.01-Jan.03 =
📦 NEW: Adds filter `gravityhopper-ca/create_file` to control auto-creation of files
🐛 FIX: Prevents mu-plugin template from showing in plugins list
👌 IMPROVE: Constrain width of code previewer

= 1.0 // 2021.12-Dec.29 =
🚀 RELEASE: Initial launch
