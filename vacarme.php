<?php
/**
 * Plugin Name:       Vacarme
 * Description:       Vacarme plugin and custom
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

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function test_block_test_block_block_init() {
	register_block_type( __DIR__ . '/build' );
}
add_action( 'init', 'test_block_test_block_block_init' );
