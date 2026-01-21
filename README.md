# Forminator Field Widths

[![CI](https://github.com/FrancoTaaber/forminator-field-widths/actions/workflows/ci.yml/badge.svg)](https://github.com/FrancoTaaber/forminator-field-widths/actions/workflows/ci.yml)
[![Release](https://img.shields.io/github/v/release/FrancoTaaber/forminator-field-widths)](https://github.com/FrancoTaaber/forminator-field-widths/releases)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![PHP](https://img.shields.io/badge/php-%3E%3D7.4-8892BF.svg)](https://php.net/)
[![WordPress](https://img.shields.io/badge/wordpress-%3E%3D5.8-21759B.svg)](https://wordpress.org/)

Add visual field width controls to Forminator forms. Easily customize field widths without writing CSS code.

## The Problem

Forminator doesn't have a built-in option to change field widths. Users are forced to:
- Learn CSS
- Add custom CSS classes manually  
- Write media queries for responsive layouts
- Debug CSS specificity issues

This plugin solves all of that with a simple admin interface.

## Features

- **Simple Admin Interface** - Select a form and set widths for each field
- **Preset Buttons** - Quick select: 100%, 75%, 66%, 50%, 33%, 25%
- **Custom Values** - Enter any percentage value
- **Responsive** - Fields automatically become full-width on mobile
- **No CSS Knowledge Required** - Everything is managed through the UI
- **Lightweight** - Only 36KB, no external dependencies

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Forminator 1.20.0+ (Free or Pro)

## Installation

### From GitHub Releases

1. Download the latest `forminator-field-widths.zip` from [Releases](https://github.com/FrancoTaaber/forminator-field-widths/releases)
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP file and activate

## Usage

1. Go to **Forminator → Field Widths**
2. Select a form from the dropdown
3. Set width for each field using:
   - Preset buttons (100%, 75%, 66%, 50%, 33%, 25%)
   - Or enter a custom percentage
4. Click **Save Field Widths**
5. Refresh your form page to see changes

## Screenshots

### Admin Interface
Simple table showing all fields with width controls and preset buttons.

## How It Works

The plugin generates optimized CSS that targets your specific form fields:

```css
.forminator-custom-form-123 #text-1 {
  width: 50% !important;
  flex: 0 0 50% !important;
  max-width: 50% !important;
}
```

CSS is output in `<head>` to ensure it loads before the form renders.

## Development

### Prerequisites

- PHP 7.4+
- Composer (optional, for dev tools)

### Setup

```bash
git clone https://github.com/FrancoTaaber/forminator-field-widths.git
cd forminator-field-widths
```

### Build

```bash
./build.sh
```

Creates `build/forminator-field-widths.zip` ready for distribution.

### Code Quality

```bash
# PHP syntax check
find . -name "*.php" -not -path "./vendor/*" | xargs -n1 php -l

# PHPCS (if installed)
composer phpcs
```

## CI/CD

This project uses GitHub Actions:

- **CI**: PHP linting across multiple versions (7.4, 8.0, 8.1, 8.2), security scanning, build verification
- **Release**: Automated releases with ZIP generation when tags are pushed

### Creating a Release

1. Update version in `forminator-field-widths.php` (both header and `FFW_VERSION` constant)
2. Update `readme.txt` changelog
3. Commit and push
4. Create and push a tag:

```bash
git tag v1.0.0
git push origin v1.0.0
```

The release workflow automatically creates a GitHub release with the ZIP file.

## Hooks

### Filter: Modify Generated CSS

```php
add_filter( 'ffw_generated_css', function( $css, $form_id ) {
    // Modify CSS before output
    return $css;
}, 10, 2 );
```

### Action: After Widths Saved

```php
add_action( 'ffw_after_save_form_widths', function( $form_id, $widths ) {
    // Clear page cache, etc.
}, 10, 2 );
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

GPL v2 or later. See [LICENSE](LICENSE) file.

## Credits

Developed by [Franco Taaber](https://francotaaber.com)

---

This plugin solves the problem described in the [WPMU DEV support forum](https://wpmudev.com/forums/topic/change-fileds-width-in-forminator/) where users asked for an easier way to change Forminator field widths.
