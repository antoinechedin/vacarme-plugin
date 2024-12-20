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

<nav <?php echo wp_kses_data( get_block_wrapper_attributes() ); ?>>
<ul class="wp_block_page_list">
<?php wp_list_pages(array(
    'title_li' => '',
    'child_of' => $parent,
    'depth' => 0,
    'sort_column' => 'menu_order,post_title',
));?>
</ul>
</nav>