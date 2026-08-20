=== Blockparty Anchors ===
Contributors:      beapi
Tags:              block, gutenberg, anchor, navigation, table of contents
Requires at least: 6.8
Tested up to:      6.8
Requires PHP:      8.1
Stable tag:        1.0.2
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Adds two Gutenberg blocks to create in-page anchors and display a quick-access navigation list.

== Description ==

Blockparty Anchors lets editors build an anchor system directly in the block editor. Place **Anchor** blocks throughout a page, then add an **Anchors List** block to generate a linked table of contents.

= Features =

* Two native Gutenberg blocks: `blockparty/anchor` and `blockparty/anchors-list`
* Place invisible anchor points on a page with an HTML-friendly `id`
* Customize anchor titles inline with RichText
* Automatic slug generation from titles (for example, `My anchor` becomes `my-anchor`)
* Manual slug override from the block inspector sidebar
* Dynamic anchors list that discovers all Anchor blocks on the current page
* Bidirectional editor sync between the Anchors List block and Anchor blocks
* Server-side PHP rendering with theme template override support
* Recursive anchor discovery, including inside nested blocks
* Object cache for anchor metadata, invalidated on content updates
* French translation included

= Blocks =

**Anchor**

Place an anchor on your page. Each anchor renders as a `<span>` with a unique `id` that can be targeted by in-page links.

**Anchors List**

Displays a quick-access list of all anchors found on the current page. In the editor, anchor titles can be edited directly from the list.

= Theme overrides =

Both blocks support theme-level template overrides via `get_template_part()`.

* Anchor default theme slug: `components/gutenberg/anchor`
* Anchors List default theme slug: `components/gutenberg/anchors-list`

If no theme template is found, the plugin falls back to its default PHP views.

== Installation ==

= Automatic installation =

1. Go to **Plugins > Add New** in your WordPress admin
2. Search for **Blockparty Anchors**
3. Click **Install Now**, then **Activate**

= Manual installation =

1. Upload the plugin files to `/wp-content/plugins/blockparty-anchors/`, or install the plugin through the WordPress plugins screen
2. Activate the plugin through the **Plugins** screen in WordPress

= Composer installation =

`composer require beapi/blockparty-anchors`

= After activation =

1. Open the block editor on a page or post
2. Add **Anchor** blocks where you want navigation targets
3. Add an **Anchors List** block to display the navigation list

== Frequently Asked Questions ==

= Do I need to add both blocks? =

Yes, for the full experience. **Anchor** blocks define the targets on the page, and the **Anchors List** block renders the linked navigation.

= How are slugs generated? =

When you edit an anchor title, a slug is generated automatically using an HTML-friendly format. You can override it manually from the block sidebar under **Anchor ID**.

= Can I customize the frontend markup? =

Yes. You can override the default templates in your theme using `components/gutenberg/anchor.php` and `components/gutenberg/anchors-list.php`, or use the available PHP filters documented in the plugin README.

= Does the anchors list work with nested blocks? =

Yes. Anchor blocks are collected recursively, including when placed inside columns, groups, or other container blocks.

== Screenshots ==

1. Anchor block in the editor with an editable label
2. Anchors List block showing all anchors on the page
3. Frontend quick-access navigation with in-page links

== Changelog ==

= 1.0.2 =
* Add frontend scroll-spy on the Anchors List block, with an is-active state, smooth in-page scrolling, URL hash sync, and sticky position support.

= 1.0.1 =
* Collect anchors from block theme templates and template parts in document order, including content rendered via core/post-content.

= 1.0.0 =
* Initial release
* Add Anchor block with RichText label and editable slug
* Add Anchors List block with dynamic editor synchronization
* Add server-side PHP rendering and default view templates
* Add theme template override support and PHP filters
* Add French translation
* Add CI workflows and WordPress Playground blueprint

== Upgrade Notice ==

= 1.0.0 =
Initial release of Blockparty Anchors.
