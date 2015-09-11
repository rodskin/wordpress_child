<?php
add_action('wp_enqueue_scripts', 'theme_enqueue_scripts_and_styles');
function theme_enqueue_scripts_and_styles () {
    // Enqueue scripts
    wp_enqueue_script('js-site', get_stylesheet_directory_uri() . '/js/site.js', array(), '', false);
    
    // Enqueue styles A LAISSERR EN DERNIER
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css'); // Get parent css stylesheet
    wp_enqueue_style('site-style', get_stylesheet_directory_uri() . '/css/style.css');
    wp_enqueue_style('responsive-style', get_stylesheet_directory_uri() . '/css/responsive.css');
}

function theme_enqueue_print_style () {
    wp_enqueue_style('print-style', get_stylesheet_directory_uri() . '/css/print.css', '4', 'print');
    if (isset($_GET['is_print']) && 'yes' == $_GET['is_print']) {
        // used if you need to export a page
        wp_enqueue_style('print-no-media-style', get_stylesheet_directory_uri() . '/css/print_no_media.css');
    }
}
add_action('wp_enqueue_scripts', 'theme_enqueue_print_style', 11);