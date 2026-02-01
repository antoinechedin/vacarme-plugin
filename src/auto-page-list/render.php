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
if (empty($post->post_parent)) {
    $ancestors = array($post->ID);
} else {
    $ancestors = get_post_ancestors($post);
    array_unshift($ancestors, $post->ID);
}

function render_list($ID, &$ancestors, $depth = 0)
{
    ?>
    <ul class="<?php echo ($depth == 0 ? 'page_list' : 'children') ?>">
    <?php
    $parent = array_pop($ancestors);
    $pages = get_children(array(
        'post_parent' => $parent,
        'orderby' => array('menu_order' => 'asc', 'title' => 'asc'),
    ));
    foreach ($pages as $page) {
        $is_ancestor = $ancestors && $page->ID == end($ancestors)
        ?>
        <li class="page_item<?php if ($page->ID == $ID) { echo (' current_page_item'); } elseif ($is_ancestor) { echo (' current_page_ancestor'); } ?>">
            <a href="<?php echo esc_url(get_permalink($page)) ?>">
                <?php echo esc_html($page->post_title) ?>
            </a>
            <?php
            if ($is_ancestor) {
                render_list($ID,$ancestors, $depth + 1);
            }
            ?>
        </li>
        <?php
    }
    ?>
    </ul>
    <?php
}
?>

<nav <?php echo wp_kses_data(get_block_wrapper_attributes()); ?>>
<?php render_list($post->ID, $ancestors) ?>
</nav>

<!-- <nav <?php echo wp_kses_data(get_block_wrapper_attributes()); ?>>
<ul class="wp_block_page_list">
<?php wp_list_pages(array(
    'title_li' => '',
    'child_of' => end($ancestors),
    'depth' => 0,
    'sort_column' => 'menu_order,post_title',
)); ?>
</ul>
</nav> -->