=== PHP Code Keeper for Gravity Forms ===
Contributors: uamv
Donate link: https://typewheel.xyz/give/?via=GH-CCK
Tags: gravity, code, developer, php, snippets
Requires PHP: 7.4
Requires at least: 5.6
Tested up to: 6.5.4
Stable tag: 3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Provides a reliable and consistent way to create, store, edit, and load custom form-related PHP code on your site.

== Description ==

= How does it work? =

PHP Code Keeper for Gravity Forms…

- creates a directory at `wp-content/uploads/gravity_hopper/code/`.
- adds a file `gf-global-code.php` *(for housing globally run code)*.
- optionally creates/duplicates/deletes a `gf-00xx.php` file for every form created/duplicated/deleted. *(for housing form-specific code)*
- will load all files in the code directory associated with an existing form or having prefix that has been explicitly allowed.
- allows exporting any number of code files to compressed .zip.
- introduces pages in the Gravity Forms interface whereby you can manage custom PHP code.

**Code Keeper does not restrict when specific files are included on the site. All code from allowed files residing in the `gravity_hopper/code/` directory will run for all forms. Always use appropriate hooks and/or conditional checks when targeting specific forms and fields.**

= Why use it? =

PHP Code Keeper provides orderliness to your code customizations, gives you a better sense of the specific code running across your site, and helps ensure your customizations are not lost when other site modifications are made.

Learn more in the walk-through article _[What is PHP Code Keeper for Gravity Forms?](https://gravityhopper.com/custom-code-keeper-for-gravity-forms/)_

= Need more for your Gravity Forms development? =

Saving you time and effort with every form you build, **[Gravity Hopper](https://gravityhopper.com)** offers an elite array of builder tools that integrates seamlessly with Gravity Forms.

== Frequently Asked Questions ==

= Does PHP Code Keeper load custom code per form? =

No. This plugin is intended for code wrangling only and does not restrict when code is run. AAll code from allowed files residing in the `gravity_hopper/code/` directory will run for all forms. Always use appropriate hooks and/or conditional checks when targeting specific forms and fields.

= Can I edit the custom code from within my WordPress dashboard? =

Yes! Create, edit, and delete files via **Forms → Code** and **Form → Settings → Code Keep**

= How are files loaded? =

Allowed files within the directory `gravity_hopper/code/` of your uploads directory will be loaded.

The file `gf-global-code.php` will always be loaded and will be loaded before other files.

Any files matching prefix patterns allowed via the filter `gravityhopper-cck/allowed_file_prefixes` will be loaded in the order they appear in that filtered array.

Finally, any form-specific files named using the convention `gform-00xx.php` will be loaded next, provided a form matching the ID exists on the site.

== Changelog ==

= 3.0 // 2024.06-Jun.19 =
📦 NEW: Allows file editing and management via UI
📦 NEW: Adds integration with Gravity Hopper: Keyboard Shortcut module

= 2.3.1 // 2022.11-Nov.10 =
✨ IMPROVE: Performance in checking files to load
📖 DOC: Tested up to 6.1

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
