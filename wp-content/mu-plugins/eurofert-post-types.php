<?php
function eurofert_custom_post_types()
{
    //Register Product Post Type 
    register_post_type('eurofert_product', array(
        'public' => true,
        'label' => 'Products',
        'has_archive' => true,
        'rewrite' => array('slug' => 'products'),
        'show_in_rest' => true,  //enables Gutenberg editor
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'labels' => array(
            'add_new_item' => 'Add New Product',
            'edit_item' => 'Edit Product',
            'all_items' => 'All Products',
            'singular_name' => 'Product'
        ),
        'menu_icon' => 'dashicons-carrot'
    ));
}

add_action('init', 'eurofert_custom_post_types'); //adding a custom post type, using the init hook



function eurofert_register_taxonomies()
{

    $labels = array(
        'name'              => 'Product Categories',
        'singular_name'     => 'Product Category',
        'search_items'      => 'Search Product Categories',
        'all_items'         => 'All Product Categories',
        'parent_item'       => 'Parent Product Category',
        'parent_item_colon' => 'Parent Product Category:',
        'edit_item'         => 'Edit Product Category',
        'update_item'       => 'Update Product Category',
        'add_new_item'      => 'Add New Product Category',
        'new_item_name'     => 'New Product Category Name',
        'menu_name'         => 'Product Categories',
    );



    register_taxonomy(
        'fertilizer_category',          // taxonomy slug
        'eurofert_product',           // attached post type
        array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array(
                'slug'         => 'product-category',
                'with_front'   => false,
                'hierarchical' => true,
            )
        )
    );
}

add_action('init', 'eurofert_register_taxonomies');
