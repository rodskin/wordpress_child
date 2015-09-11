<?php
add_action('init', 'create_post_type');
function create_post_type() {
    register_post_type(
        'actualites',
        array(
            'labels' => array(
                'name' => __('Actualités'),
                'singular_name' => __('Actualités')
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'actualites'),
            'supports' => array('title', 'editor', 'thumbnail'),
            'taxonomies' => array('category','status'), // categories is the default categories in WP
        )
    );
}

add_action('init', 'build_taxonomies');  
function build_taxonomies() { 
    register_taxonomy(
        'status',  
        'actualites',  // this is the custom post type(s) I want to use this taxonomy for
        array(  
            'hierarchical' => true,  
            'label' => 'Status',
        )  
    );
}