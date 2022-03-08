## Custom Code Keeper for Gravity Forms
Provides a reliable and consistent way to store and load custom form-related code on your site.

### How does it work?
Custom Code Keeper for Gravity Forms…
1. creates a directory at `wp-content/uploads/gravity_hopper/code/`.
1. adds a file `gf-global-code.php` *(for housing globally run code)*.
1. optionally creates/duplicates/deletes a `gf-00xx.php` file for every form created/duplicated/deleted. *(for housing form-specific code)*
1. will load all files in the code directory associated with an existing form or having prefix that has been explicitly allowed.
1. allows exporting any number of code files to compressed .zip.
1. introduces a form settings page whereby you can preview relevant custom code and create a file if one doesn't yet exist.

**Please note that Custom Code Keeper is intended for code organization only and doesn't restrict when code is run. All code from allowed files residing in the `gravity_hopper/code/` directory will run for all forms. Always use appropriate hooks and/or conditional checks when targeting specific forms and fields.**

### Why use it?
Custom Code Keeper provides orderliness to your code customizations, gives you a better sense of the specific code running across your site, and helps ensure your customizations are not lost when other site modifications are made.

Learn more in the walk-through article _[What is Custom Code Keeper for Gravity Forms?](https://gravityhopper.com/custom-code-keeper-for-gravity-forms/)_

### Need more for your Gravity Forms development?
Saving you time and effort with every form you build, **[Gravity Hopper](https://gravityhopper.com)** offers an elite array of builder tools that integrates seamlessly with Gravity Forms.