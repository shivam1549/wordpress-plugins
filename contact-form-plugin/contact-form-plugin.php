<?php
/*
Plugin Name: Contact Form Plugin
Plugin URI: http://example.com
Description: A simple contact form plugin.
Version: 1.0
Author: Your Name
Author URI: http://example.com
License: GPL2
*/

function cfp_display_form(){
    $content = '<form method="post" action="'.esc_url($_SERVER['REQUEST_URI']).'">';
    $content .= '<p>Your Name (required) <br/>';
    $content .= '<input type="text" name="cfp-name" pattern="[a-zA-Z0-9 ]+" required /></p>';
    $content .= '<p>Your Email (required) <br/>';
    $content .= '<input type="email" name="cfp-email" required /></p>';
    $content .= '<p>Your Message (required) <br/>';
    $content .= '<textarea name="cfp-message" required></textarea></p>';
    $content .= '<p><input type="submit" name="cfp-submitted" value="Send"/></p>';
    $content .= '</form>';
    return $content;
}

add_shortcode("cfp_form", "cfp_display_form");

function cfp_handle_form(){
    if(isset($_POST['cfp-submitted'])){
        global $wpdb;
        $name = sanitize_text_field($_POST['cfp-name']);
        $email = sanitize_email($_POST['cfp-email']);
        $message = sanitize_textarea_field($_POST['cfp-message']);
        $table_name = $wpdb->prefix . 'cfp_entries';
        $wpdb->insert(
            $table_name,
            array(
                'name' =>$name,
                'email' => $email,
                'message' => $message
            )
            );
    }
}

add_action("init", "cfp_handle_form");

function cfp_create_table(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'cfp_entries';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        email varchar(100) NOT NULL,
        message text NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}


register_activation_hook(__FILE__, 'cfp_create_table');

function cfp_register_menu(){
    add_menu_page(
        'Contact Form Entries', // Page title
        'Contact Form',         // Menu title
        'manage_options',       // Capability required
        'cfp-entries',          // Menu slug
        'cfp_display_entries',  // Function to display page content
        'dashicons-email-alt',  // Icon URL
        6                       // Menu position
    );
    
}

// To display entries in dashboard
add_action("admin_menu", "cfp_register_menu");

function cfp_display_entries() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cfp_entries';
    $results = $wpdb->get_results("SELECT * FROM $table_name");
    echo '<div class="wrap"><h2>Contact Form Entries</h2>';
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead><tr><th class="manage-column">ID</th><th class="manage-column">Name</th><th class="manage-column">Email</th><th class="manage-column">Message</th></tr></thead><tbody>';
    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . $row->id . '</td>';
        echo '<td>' . $row->name . '</td>';
        echo '<td>' . $row->email . '</td>';
        echo '<td>' . $row->message . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

?>
