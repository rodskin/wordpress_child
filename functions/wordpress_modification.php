<?php
// Functions to modify wordpress

// desactivate the WP menu
add_filter('comments_open', 'wpc_comments_closed', 10, 2);
function wpc_comments_closed( $open, $post_id ) {
    return false;
}

// pour virer les liens WP inutiles
function edit_admin_bar() {
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('wp-logo'); // Logo
    $wp_admin_bar->remove_menu('about'); // A propos de WordPress
    $wp_admin_bar->remove_menu('wporg'); // WordPress.org
    $wp_admin_bar->remove_menu('documentation'); // Documentation
    $wp_admin_bar->remove_menu('support-forums');  // Forum de support
    $wp_admin_bar->remove_menu('feedback'); // Remarque
    $wp_admin_bar->remove_menu('view-site'); // Aller voir le site
}
add_action('wp_before_admin_bar_render', 'edit_admin_bar');