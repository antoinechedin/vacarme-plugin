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
 * Text Domain:       vacarme
 * 
 * @package vacarme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

const VACARME_QUEST = 'vacarme_quest';

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function VacarmeCustomBlocksInit() {
	register_block_type( __DIR__ . '/build/auto-page-list' );
	register_block_type( __DIR__ . '/build/geojson' );
}
add_action( 'init', 'VacarmeCustomBlocksInit' );

function VacarmeQuestPostType() {
	
	register_post_type(VACARME_QUEST,
		array(
			'labels'      => array(
				'name'          => __('Quests'),
				'singular_name' => __('Quest'),
			),
			'public'      => true,
			'has_archive' => true,
			'show_in_rest' => true,
			'rewrite'     => array( 'slug' => 'quest' ),
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

function VacarmeAddCustomBox() {
	add_meta_box(
		'vacarme_geojson',                
		__('GeoJSON'),
		'wporg_custom_box_html',
		array(VACARME_QUEST),
	);
}
//add_action( 'add_meta_boxes', 'VacarmeAddCustomBox' );
