<?php

function wsrcp_install() {
    // Create custom tables
    // global $wpdb;
    // $table_name = $wpdb->prefix . 'wsrcp_renewal_subscriptions';
    // $charset_collate = $wpdb->get_charset_collate();

    // $sql = "CREATE TABLE IF NOT EXISTS $table_name (
    //     id mediumint(9) NOT NULL AUTO_INCREMENT,
    //     name tinytext NOT NULL,
    //     value text NOT NULL,
    //     PRIMARY KEY  (id)
    // ) $charset_collate;";

    // require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    // dbDelta( $sql );

    // Create custom post type
    // $post_type_args = array(
    //     'public' => true,
    //     'label'  => 'WSRCP Post Type'
    // );
    // register_post_type( 'wsrcp_post_type', $post_type_args );

    // Create custom taxonomy
    // $taxonomy_args = array(
    //     'label' => 'WSRCP Taxonomy',
    //     'rewrite' => array( 'slug' => 'wsrcp_taxonomy' ),
    //     'hierarchical' => true,
    // );
    // register_taxonomy( 'wsrcp_taxonomy', 'custom_post_type', $taxonomy_args );

    // Create custom meta fields
    // add_action( 'add_meta_boxes', 'wsrcp_add_custom_meta_box' );
    // function wsrcp_add_custom_meta_box() {
    //     add_meta_box(
    //         'custom_meta_box', // $id
    //         'Custom Meta Box', // $title
    //         'wsrcp_show_custom_meta_box', // $callback
    //         'custom_post_type', // $screen
    //         'normal', // $context
    //         'high' // $priority
    //     );
    // }

    // function wsrcp_show_custom_meta_box() {
    //    global $post;
    //    $meta = get_post_meta( $post->ID, 'custom_meta', true ); 
    ?>
        <!-- <input type="text" name="custom_meta" value=""> -->
    <?php 
    // }

    // Create default settings
    // add_option( 'wsrcp_default_setting', 'default_value' );

    // Create custom pages
    $page_definitions = array(
        'renew-subscription' => array(
            'title' => __( 'Renew Subscription', 'textdomain' ),
            'content' => '[renew_subscription_page]'
        ),
    );

    foreach ( $page_definitions as $slug => $page ) {
        // Check that the page doesn't exist already
        $query = new WP_Query( 'pagename=' . $slug );
        if ( ! $query->have_posts() ) {
            // Add the page using the data from the array above
            wp_insert_post(
                array(
                    'post_content'   => $page['content'],
                    'post_name'      => $slug,
                    'post_title'     => $page['title'],
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'ping_status'    => 'closed',
                    'comment_status' => 'closed',
                )
            );
        }
    }
}

register_activation_hook( __FILE__, 'wsrcp_install' );