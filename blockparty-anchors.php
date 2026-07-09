<?php
/**
 * Plugin Name:       Blockparty Anchors
 * Description:       Adds two new blocks to the WordPress editor: an anchor block to create an anchor system on your pages, and an anchor list block to list those anchors.
 * Version:           1.0.0
 * Requires at least: 6.8
 * Requires PHP:      7.4
 * Author:            Be API Technical Team
 * Author URI:        https://beapi.fr
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       blockparty-anchors
 *
 * @package Blockparty\Anchors
 */

namespace Blockparty\Anchors;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	include_once __DIR__ . '/vendor/autoload.php';
}

define( 'BLOCKPARTY_ANCHORS_VERSION', '1.0.0' );
define( 'BLOCKPARTY_ANCHORS_URL', plugin_dir_url( __FILE__ ) );
define( 'BLOCKPARTY_ANCHORS_DIR', plugin_dir_path( __FILE__ ) );
define( 'BLOCKPARTY_ANCHORS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

Anchors::register_hooks();

/**
 * Bootstrap the plugin.
 */
function init() {
	load_plugin_textdomain(
		'blockparty-anchors',
		false,
		dirname( BLOCKPARTY_ANCHORS_PLUGIN_BASENAME ) . '/languages'
	);

	register_block_type(
		BLOCKPARTY_ANCHORS_DIR . 'build/anchor',
		[
			'render_callback' => [ BlockRenderer::class, 'render' ],
		]
	);

	register_block_type(
		BLOCKPARTY_ANCHORS_DIR . 'build/anchors-list',
		[
			'render_callback' => [ BlockRenderer::class, 'render_list' ],
		]
	);

	wp_set_script_translations(
		'blockparty-anchor-editor-script',
		'blockparty-anchors',
		BLOCKPARTY_ANCHORS_DIR . 'languages'
	);

	wp_set_script_translations(
		'blockparty-anchors-list-editor-script',
		'blockparty-anchors',
		BLOCKPARTY_ANCHORS_DIR . 'languages'
	);
}

add_action( 'init', __NAMESPACE__ . '\\init', 0 );
