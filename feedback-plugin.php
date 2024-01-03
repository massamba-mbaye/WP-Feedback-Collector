<?php
/**
 * Plugin Name: Feedback Collector
 * Description: This plugin allows users to share their opinions on content, helping you gather insights and enhance user experience.
 * Version: 1.0
 * Author: Massamba MBAYE
 * Author URI: https://im-mass.com/
 * Requires at least: 5.6
 * Tested up to: 5.9
 * Requires PHP: 7.2
 * 
 * @package Feedback_Collector
 */

// Add your code below this line.

// Enqueue jQuery script
function enqueue_feedback_script() {
    wp_enqueue_script('feedback-script', plugin_dir_url(__FILE__) . 'feedback-script.js', array('jquery'), '1.0', true);

    // Use wp_create_nonce() to create a nonce for added security
    $nonce = wp_create_nonce('feedback_nonce');

    wp_localize_script('feedback-script', 'feedback_script_params', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => $nonce,
    ));
}

add_action('wp_enqueue_scripts', 'enqueue_feedback_script');


// Enqueue external CSS file for feedback form
function enqueue_feedback_styles() {
    wp_enqueue_style('feedback-styles', plugin_dir_url(__FILE__) . 'feedback-styles.css');
}

add_action('wp_enqueue_scripts', 'enqueue_feedback_styles');



function display_feedback_form($content) {
    if (is_single() || is_page()) {
        // Get the current post or page ID
        $post_id = get_the_ID();

        $feedback_form = '<div id="feedback-form">
                            <p>Ce contenu vous a-t-il été utile ?</p>
                            <button class="feedback-btn" data-feedback="yes" data-post-id="' . esc_attr($post_id) . '">Oui</button>
                            <button class="feedback-btn" data-feedback="no" data-post-id="' . esc_attr($post_id) . '">Non</button>
                         </div>';

        $content .= $feedback_form;
    }

    return $content;
}


add_filter('the_content', 'display_feedback_form');


function save_feedback_to_db() {
    check_ajax_referer('feedback_nonce', 'nonce');

    if (isset($_POST['feedback'])) {
        global $wpdb;

        $feedback = sanitize_text_field($_POST['feedback']);
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        $wpdb->insert(
            $wpdb->prefix . 'feedback_table',
            array(
                'date' => current_time('mysql'),
                'feedback' => $feedback,
                'post_id' => $post_id,
            ),
            array('%s', '%s', '%d')
        );

        echo 'Feedback submitted successfully';

        // It's a good practice to exit after echoing the response in an AJAX function
        wp_die();
    }
}

add_action('wp_ajax_save_feedback', 'save_feedback_to_db');
add_action('wp_ajax_nopriv_save_feedback', 'save_feedback_to_db');

function create_feedback_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'feedback_table';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        feedback varchar(20) NOT NULL,
        post_id bigint(20) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'create_feedback_table');


function display_feedback_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'feedback_table';
    $feedback_data = $wpdb->get_results("SELECT * FROM $table_name");

    echo '<div class="wrap">
            <h2>Feedback Data</h2>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Feedback</th>
                        <th>Post</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($feedback_data as $data) {
        $post_title = get_post_field('post_title', $data->post_id);
        $post_url = get_permalink($data->post_id);

        echo '<tr>
                <td>' . $data->id . '</td>
                <td>' . $data->date . '</td>
                <td>' . $data->feedback . '</td>
                <td><a href="' . esc_url($post_url) . '">' . ($post_title) . '</a></td>
              </tr>';
    }

    echo '</tbody></table></div>';
}


add_action('rest_api_init', function () {
    register_rest_route('feedback/v1', '/save/', array(
        'methods' => 'POST',
        'callback' => 'save_feedback_to_db',
        'permission_callback' => '__return_true', // No specific permissions required
    ));
});



function feedback_menu() {
    add_menu_page('Feedback', 'Feedback', 'manage_options', 'feedback-plugin', 'display_feedback_table');
}

add_action('admin_menu', 'feedback_menu');
