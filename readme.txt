=== Forminator Field Widths ===
Contributors: francotaaber
Tags: forminator, form, field, width, responsive, layout
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add visual field width controls to Forminator forms. Easily customize field widths without writing CSS code.

== Description ==

**Forminator Field Widths** solves a common frustration: changing field widths in Forminator forms without writing custom CSS.

= Features =

* **Simple Admin Interface** - Select a form and set widths for each field
* **Preset Buttons** - Quick select common widths: 100%, 75%, 66%, 50%, 33%, 25%
* **Custom Values** - Enter any percentage value you need
* **Responsive** - Fields automatically become full-width on mobile devices
* **Lightweight** - Only 36KB, no external dependencies
* **Auto Updates** - Receive updates directly from GitHub releases

= How It Works =

1. Go to Forminator → Field Widths
2. Select a form from the dropdown
3. Set width for each field using preset buttons or custom values
4. Click Save - that's it!

The plugin generates optimized CSS that targets your specific form fields.

= Use Cases =

* Create multi-column form layouts
* Make name fields (first/last) appear side by side at 50% each
* Create compact forms with smaller fields
* Design professional-looking forms without CSS knowledge

= Requirements =

* WordPress 5.8 or higher
* PHP 7.4 or higher
* Forminator 1.20.0 or higher (free or Pro version)

== Installation ==

= From GitHub =

1. Download the latest release from [GitHub](https://github.com/FrancoTaaber/forminator-field-widths/releases)
2. Go to Plugins → Add New → Upload Plugin
3. Upload the ZIP file and activate

= Manual Installation =

1. Upload the `forminator-field-widths` folder to `/wp-content/plugins/`
2. Activate through the Plugins menu

== Frequently Asked Questions ==

= Does this work with Forminator Free? =

Yes! This plugin works with both Forminator Free and Forminator Pro.

= Will my width settings affect other forms? =

No. Each form's width settings are scoped to that specific form using unique CSS selectors.

= Do fields become full-width on mobile? =

Yes, by default all fields become 100% width on screens smaller than 768px for better mobile usability.

= How do I update the plugin? =

The plugin supports auto-updates from GitHub releases. You'll see update notifications in your WordPress admin.

== Screenshots ==

1. Simple admin interface with field width controls
2. Preset buttons for quick width selection
3. Form with custom field widths applied

== Changelog ==

= 1.0.0 =
* Initial release
* Simple admin interface for setting field widths
* Preset buttons for common widths (100%, 75%, 66%, 50%, 33%, 25%)
* Custom percentage input
* Responsive mobile support (full-width on mobile)
* Auto-updates from GitHub releases

== Upgrade Notice ==

= 1.0.0 =
Initial release.
