<?php
/**
 * Plugin Name:       Vacarme
 * Description:       Vacarme plugin and custom blocks
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            Antoine Chedin
 * License:           MIT
 * License URI:       https://mit-license.org/
 * Text Domain:       vacarme-plugin
 * Domain Path:       /languages
 * 
 * @package vacarme
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

const VACARME_QUEST = 'vacarme_quest';

function VacarmeLoadTextdomain($mofile, $domain)
{
	if ('my-domain' === $domain && false !== strpos($mofile, WP_LANG_DIR . '/plugins/')) {
		$locale = apply_filters('plugin_locale', determine_locale(), $domain);
		$mofile = WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)) . '/languages/' . $domain . '-' . $locale . '.mo';
	}
	return $mofile;
}
add_filter('load_textdomain_mofile', 'VacarmeLoadTextdomain', 10, 2);

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function VacarmeCustomBlocksInit()
{
	register_block_type(__DIR__ . '/build/auto-page-list');
	register_block_type(__DIR__ . '/build/geojson');
}
add_action('init', 'VacarmeCustomBlocksInit');

function VacarmeQuestPostType()
{
	register_post_type(
		VACARME_QUEST,
		array(
			'labels' => array(
				'name' => __('Quests', 'vacarme-plugin'),
				'singular_name' => __('Quest', 'vacarme-plugin'),
			),
			'public' => true,
			'has_archive' => true,
			'show_in_rest' => true,
			'rewrite' => array('slug' => 'quest'),
			'supports' => array('title', 'editor', 'excerpt', 'author', 'custom-fields'),
			'delete_with_user' => false,
		)
	);

	register_post_meta(
		VACARME_QUEST,
		'geojson',
		array(
			'type' => 'string',
			'single' => true,
			'show_in_rest' => true
		),
	);
}
add_action('init', 'VacarmeQuestPostType');

function VacarmeAddCustomBox()
{
	add_meta_box(
		'vacarme_geojson',
		__('GeoJSON', 'vacarme-plugin'),
		'wporg_custom_box_html',
		array(VACARME_QUEST),
	);
}
//add_action( 'add_meta_boxes', 'VacarmeAddCustomBox' );

function worldmap()
{
	?>
	<div class="wrap">
		<h1>Test function</h1>
	</div>
	<?php
}
function VacarmeWorldMapMenu(): void
{
	$world_map_menu_slug = 'vacarme_world_map_menu';
	add_menu_page(
		__('World map', 'vacarme-plugin'),
		__('World map', 'vacarme-plugin'),
		'edit_posts',
		$world_map_menu_slug,
		'worldmap',
		'dashicons-admin-site',
		40
	);
	add_submenu_page(
		$world_map_menu_slug,
		__('Locations', 'vacarme-plugin'),
		__('Locations', 'vacarme-plugin'),
		'edit_posts',
		'vacarme_world_map_menu_location',
		'worldmap'
	);
}
add_action('admin_menu', 'VacarmeWorldMapMenu');

function VacarmeMapLocationPostType(): void
{
	register_post_type(
		'vacarme_map_location',
		array(
			'labels' => array(
				'name' => __('Locations', 'vacarme-plugin'),
				'singular_name' => __('Location', 'vacarme-plugin')
			),
			'public' => false
		)
	);
}
add_action('init', 'VacarmeMapLocationPostType');