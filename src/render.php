<?php
/**
 * Render.php
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 *
 * @package vacarme
 */

global $post;

$rootPost = $post;
$ancestors = get_post_ancestors($post);
if( empty($post->post_parent) ) {
    $parent = $post->ID;
} else {
    $parent = end($ancestors);
}
?>

<p <?php echo wp_kses_data( get_block_wrapper_attributes() ); ?>>
<?php esc_html_e( 'Block with Dynamic Rendering 5 – hello!!!', 'block-development-examples' ); ?>
<br/>
<!-- <nav class="wp-block-navigation"> -->
<ul class="wp_block_page_list">
<?php wp_list_pages(array(
    'title_li' => '',
    'child_of' => $parent,
    'depth' => 2,
    'sort_column' => 'menu_order,post_title',
));?>
</ul>
<!-- </nav> -->
</p>