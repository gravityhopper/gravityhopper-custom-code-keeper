=== Custom Code Keeper for Gravity Forms ===
Contributors: uamv
Donate link: https://gravityhopper.com
Tags: gravity, forms, code, developer, php, snippets
Requires PHP: 7.2
Requires at least: 5.6
Tested up to: 5.9.1
Stable tag: 2.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Provides a reliable and consistent way to store and load custom form-related code on your site.

== Description ==

= How does it work? =

Custom Code Keeper for Gravity Forms…

- creates a directory at `wp-content/uploads/gravity_hopper/code/`.
- adds a file `gf-global-code.php` *(for housing globally run code)*.
- optionally creates/duplicates/deletes a `gf-00xx.php` file for every form created/duplicated/deleted. *(for housing form-specific code)*
- will load all files in the code directory associated with an existing form or having prefix that has been explicitly allowed.
- allows exporting any number of code files to compressed .zip.
- introduces a form settings page whereby you can preview relevant custom code and create a file if one doesn't yet exist.

**Please note that Custom Code Keeper is intended for code organization only and doesn't restrict when code is run. All code from allowed files residing in the `gravity_hopper/code/` directory will run for all forms. Always use appropriate hooks and/or conditional checks when targeting specific forms and fields.**

= Why use it? =

Custom Code Keeper provides orderliness to your code customizations, gives you a better sense of the specific code running across your site, and helps ensure your customizations are not lost when other site modifications are made.

Learn more in the walk-through article _[What is Custom Code Keeper for Gravity Forms?](https://gravityhopper.com/custom-code-keeper-for-gravity-forms/)_

= Need more for your Gravity Forms development? =

Saving you time and effort with every form you build, **[Gravity Hopper](https://gravityhopper.com)** offers an elite array of builder tools that integrates seamlessly with Gravity Forms.

== Frequently Asked Questions ==

= Does Custom Code Keeper load custom code per form? =

No. This plugin is intended for code wrangling only and does not restrict when code is run. All code residing in files prefixed with `gf-` or `gform-` in the `gravity_hopper/code/` directory will run for all forms. Always use appropriate hooks and/or conditional checks when targeting specific forms and fields.

= Can I edit the custom code from within my WordPress dashboard? =

No. While each form provides a Custom Code previewer, the actual codes needs to be edited directly from within the files housed on your server.

= How are files loaded? =

Allowed files within the directory `gravity_hopper/code/` of your uploads directory will be loaded.

The file `gf-global-code.php` will always be loaded and will be loaded before other files. If you need to declare classes for use in other code, this is the file in which to do so.

Any files matching prefix patterns allowed via the filter `gravityhopper-cck/allowed_file_prefixes` will be loaded next.

Finally, any form-specific files named using the convention `gform-00xx.php` will be loaded next, provided a form matching the ID exists on the site.

== Installation ==

After installation and activation on your WordPress site, you will find a new `gravity_hopper/code` directory for housing your Gravity Forms related code within your WordPress site's `upload` folder.

== Changelog ==

= 2.3 // 2022.03-Mar.08 =
🐛 FIX: Load on `init` rather than `gform_loaded` to prevent form breakage
✨ IMPROVE: Consolidates multiple calls to GFAPI::get_forms()
✨ IMPROVE: Adds more details to system report
📖 DOC: Simplifies readme file

= 2.2.2 // 2022.02-Feb.18 =
🚀 RELEASE: renaming the plugin

= 2.2.1 // 2022.02-Feb.15 =
📖 DOC: formats readme file

= 2.2 // 2022.02-Feb.15 =
🚀 RELEASE: Initial public launch
