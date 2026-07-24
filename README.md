# Blockparty — Anchors

[![Test with WordPress Playground](https://img.shields.io/badge/Test%20with-WordPress%20Playground-0073aa?style=for-the-badge&logo=wordpress&logoColor=white)](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/BeAPI/blockparty-anchors/refs/heads/develop/.wordpress-org/blueprints/blueprint.json)

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress: 6.8+](https://img.shields.io/badge/WordPress-6.8+-green.svg)](https://wordpress.org/)
[![PHP: 8.1+](https://img.shields.io/badge/PHP-8.1+-purple.svg)](https://php.net/)

A WordPress plugin that adds Gutenberg blocks to create in-page anchors and display a quick-access navigation list.

## 📋 Description

Blockparty Anchors is a WordPress plugin that lets editors build an anchor system directly in the block editor. Place **Anchor** blocks throughout a page, then add an **Anchors List** block to generate a linked table of contents.

### ✨ Features

- **Two native Gutenberg blocks**: `blockparty/anchor` and `blockparty/anchors-list`
- **Anchor block**: Place invisible anchor points on a page with an HTML-friendly `id`
- **Editable labels**: Customize anchor titles inline with `RichText`
- **Automatic slug generation**: Slugs are derived from the title (e.g. `My anchor` → `my-anchor`)
- **Manual slug override**: Edit the anchor ID from the block inspector sidebar
- **Dynamic anchors list**: The list block automatically discovers all Anchor blocks on the current page
- **Bidirectional editor sync**: Editing a title from the Anchors List block updates the corresponding Anchor block
- **Dynamic PHP rendering**: Server-side output with theme override support
- **Nested block support**: Anchors are collected recursively, including inside columns, groups, and other containers
- **Object cache**: Anchor metadata is cached per post and invalidated on content updates
- **Internationalized**: Multilingual support with translation files (English and French included)

## 🔧 Requirements

- **WordPress**: Version 6.8 or higher
- **PHP**: Version 8.1 or higher
- **PHP Extension**: ext-json
- **Node.js**: Version 24.x (recommended via Volta) for asset development

## 📦 Installation

### Installation via Composer

```bash
composer require beapi/blockparty-anchors
```

### Manual Installation

1. Download the latest version of the plugin
2. Extract the archive to the `/wp-content/plugins/` folder
3. Activate the plugin from the WordPress "Plugins" menu

### Development Installation

```bash
# Clone the repository
git clone https://github.com/BeAPI/blockparty-anchors.git
cd blockparty-anchors

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Build the assets
npm run build
```

## 🚀 Usage

1. Open the Gutenberg block editor on a page or post
2. Add **Anchor** blocks where you want in-page navigation targets
3. Edit each anchor label directly in the editor
4. Optionally customize the anchor ID from the block sidebar (**Settings** → **Anchor ID**)
5. Add an **Anchors List** block (search for "Anchors List" in the Widgets category) to display quick-access links
6. On the frontend:
   - Each anchor renders as `<span id="your-slug" aria-hidden="true">`
   - The anchors list renders a linked navigation to `#your-slug`

### Editor workflow

| Block | Editor behavior |
| --- | --- |
| **Anchor** | `RichText` for the label; slug auto-generated from the title |
| **Anchor** (sidebar) | `TextControl` to override the HTML `id` manually |
| **Anchors List** | Lists all Anchor blocks on the page; each item is editable via `RichText` |

### Theme template overrides

Both blocks support theme-level template overrides via `get_template_part()`. If no theme template is found, the plugin falls back to its default views.

| Block | Default theme slug | Plugin fallback |
| --- | --- | --- |
| Anchor | `components/gutenberg/anchor` | `views/anchor.php` |
| Anchors List | `components/gutenberg/anchors-list` | `views/anchors-list.php` |

Example theme file: `components/gutenberg/anchors-list.php`

```php
<?php
/**
 * @var array $args {
 *     @type array  $block_attributes
 *     @type string $block_wrapper_attributes
 *     @type array  $anchors
 *     @type bool   $is_preview
 * }
 */
$anchors = $args['anchors'] ?? [];
?>
<div <?php echo $args['block_wrapper_attributes']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<!-- Custom theme markup -->
</div>
```

### Available filters

#### Anchor block

| Filter | Default | Description |
| --- | --- | --- |
| `blockparty/anchor/classnames` | `[]` | Additional CSS class names |
| `blockparty/anchor/template_slug` | `components/gutenberg/anchor` | Theme template slug |
| `blockparty/anchor/template_name` | `''` | Theme template name |
| `blockparty/anchor/template_args` | — | Template arguments passed to the view |

#### Anchors List block

| Filter | Default | Description |
| --- | --- | --- |
| `blockparty/anchors_list/classnames` | `['anchor-list']` | Additional CSS class names |
| `blockparty/anchors_list/template_slug` | `components/gutenberg/anchors-list` | Theme template slug |
| `blockparty/anchors_list/template_name` | `''` | Theme template name |
| `blockparty/anchors_list/template_args` | — | Template arguments passed to the view |

Example:

```php
add_filter( 'blockparty/anchors_list/classnames', function ( $classnames, $attributes, $block ) {
	$classnames[] = 'my-custom-anchor-list';

	return $classnames;
}, 10, 3 );
```

## 🛠️ Development

### Project Structure

```
blockparty-anchors/
├── src/                              # Block sources
│   ├── anchor/
│   │   ├── block.json                # Anchor block configuration
│   │   ├── edit.js                   # Edit component (RichText + inspector)
│   │   ├── index.js                  # Entry point
│   │   ├── editor.scss               # Editor styles
│   │   └── style.scss                # Frontend and editor styles
│   └── anchors-list/
│       ├── block.json                # Anchors List block configuration
│       ├── edit.js                   # Dynamic list editor component
│       ├── index.js                  # Entry point
│       ├── editor.scss               # Editor styles
│       └── style.scss                # Frontend and editor styles
├── includes/                         # PHP classes
│   ├── Anchors.php                   # Anchor discovery and caching
│   └── BlockRenderer.php             # Dynamic block rendering
├── views/                            # Default PHP templates
│   ├── anchor.php
│   └── anchors-list.php
├── build/                            # Compiled assets (blocks-manifest.php, etc.)
├── languages/                        # Translation files
├── .wordpress-org/blueprints/        # WordPress Playground blueprint
├── blockparty-anchors.php            # Main plugin file
├── composer.json                     # PHP dependencies
└── package.json                      # JavaScript dependencies
```

### Available Scripts

#### JavaScript

```bash
# Development with hot reload
npm start

# Production build
npm run build

# JavaScript linter
npm run lint:js

# CSS linter
npm run lint:css

# Code formatting
npm run format

# Generate POT file
npm run make-pot

# Generate JSON translation files
npm run make-json

# Create plugin ZIP archive
npm run plugin-zip

# Start local development environment
npm run env:start

# Stop local development environment
npm run env:stop
```

#### PHP

```bash
# Check code with PHP_CodeSniffer
composer cs

# Automatically fix code
composer cb

# Run unit tests
composer phpunit
```

### Coding Standards

The project follows WordPress coding standards:

- **WPCS** (WordPress Coding Standards) for PHP
- **ESLint** with WordPress rules for JavaScript
- **GrumPHP** to automate pre-commit checks

### Development Environment Setup

The plugin uses `@wordpress/env` to create a local WordPress development environment:

```bash
# Start the environment
npm run env:start

# Access WordPress
# URL: http://localhost:8888
# Default credentials: admin / password

# Stop the environment
npm run env:stop
```

## 🔍 Code Quality

The project integrates several quality tools:

- **PHP_CodeSniffer**: PHP coding standards verification
- **PHPCompatibility**: PHP compatibility verification
- **PHP Parallel Lint**: PHP syntax error detection
- **GrumPHP**: Pre-commit checks automation

## 🌍 Internationalization

The plugin is fully internationalized (text domain: `blockparty-anchors`). Translation files are available in the `languages/` folder.

### Available Languages

- English (default)
- French (`blockparty-anchors-fr_FR.po`)

### Adding a Translation

1. Use the `languages/blockparty-anchors.pot` file as a base
2. Create your `.po` and `.mo` files
3. Place them in the `languages/` folder
4. Run `npm run make-json` to generate Jed JSON files for the block editor

## 🤝 Contributing

Contributions are welcome! To contribute:

1. Fork the project
2. Create a branch for your feature (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

Make sure your code:

- Follows WordPress coding standards
- Passes all quality tests (PHPCS, ESLint)
- Is properly documented
- Includes translations if necessary

## 📄 License

This plugin is distributed under the GPL-2.0-or-later license.

## 👥 Authors

**Be API Technical Team**

- Email: <technical@beapi.fr>
- Website: [https://beapi.fr](https://beapi.fr)

## 🔗 Useful Links

- [WordPress Block Editor Documentation](https://developer.wordpress.org/block-editor/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Block API Reference](https://developer.wordpress.org/block-editor/reference-guides/block-api/)
- [Dynamic Blocks (Server-Side Rendering)](https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/creating-dynamic-blocks/)

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for the full version history.

---

Developed with ❤️ by [Be API](https://beapi.fr)
