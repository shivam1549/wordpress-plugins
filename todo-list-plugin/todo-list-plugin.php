<?php
/*
Plugin Name: Todo list plugin
Plugin URI: http://example.com
Description: A simple Todo list plugin.
Version: 1.0
Author: Your Name
Author URI: http://example.com
License: GPL2
*/

function tlp_create_table(){
    global $wpdb;
    $tablename = $wpdb->prefix . 'todo_list';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $tablename (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title text NOT NULL,
        description text NULL,
        status tinyint(2) DEFAULT 0,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'tlp_create_table');


function display_list_form(){
    $content = '<form method="POST" action="'.esc_url($_SERVER['REQUEST_URI']).'">';
    $content .= '<input type="text" name="todo_title" placeholder="Title" required>';
    $content .= '<textarea name="todo_description" placeholder="Description" required></textarea>';
    $content .= '<input type="hidden" name="simple_todo_nonce_field" value="' . wp_create_nonce('simple_todo_form_action') . '">';
    $content .= '<button type="submit" name="simple_todo_submit">Add To-Do</button>';
    $content .= '</form>';
    return $content;
}


add_shortcode("tlp_list", "display_list_form");

function tlp_handle_form(){
    if(isset($_POST['simple_todo_submit'])){
        global $wpdb;
        $name = sanitize_text_field($_POST['todo_title']);
        $description = sanitize_text_field($_POST['todo_description']);
        $tablename = $wpdb->prefix . 'todo_list';
        $wpdb->insert(
            $tablename,
            array(
                'title' => $name,
                'description' => $description
            )
            );
       
    }
}

add_action("init", "tlp_handle_form");


function render_to_do_list(){
    global $wpdb;
    $tablename = $wpdb->prefix. 'todo_list';
    $results = $wpdb->get_results("SELECT * FROM $tablename");
    if ($results) {
        // Start the table and add headers
        $content = '<table>';
        $content .= '<thead>';
        $content .= '<tr><th>Sr No</th><th>Task</th><th>Description</th><th>Edit/Delete</th></tr>';
        $content .= '</thead>';
        $content .= '<tbody>';

        // Add table rows for each to-do item
        foreach ($results as $index => $item) {
            $content .= '<tr>';
            $content .= '<td>' . ($index + 1) . '</td>'; // Serial number
            $content .= '<td>';
            $content .= '<form method="POST" action="' . esc_url($_SERVER['REQUEST_URI']) . '">';
            $content .= '<input type="text" name="todo_title" value="' . esc_attr($item->title) . '" required>';
            $content .= '<input type="hidden" name="todo_id" value="' . esc_attr($item->id) . '">';
            $content .= '<input type="hidden" name="simple_todo_nonce_field" value="' . wp_create_nonce('simple_todo_form_action') . '">';
            $content .= '<textarea name="todo_description" required>' . esc_textarea($item->description) . '</textarea>';
       
            $content .= '<button type="submit" name="update_todo">Update</button>';
            $content .= '<button type="submit" name="delete_todo" onclick="return confirm(\'Are you sure you want to delete this item?\')">Delete</button>';
            $content .= '</form>';
            $content .= '</td>';
            $content .= '</tr>';
        }

        $content .= '</tbody></table>';
    } else {
        // Display a message if no items found
        $content = 'No to-do items found.';
    }

    return $content;

}

add_shortcode("todo_list_show", "render_to_do_list");

function handle_form_submission() {
    global $wpdb;
    $tablename = $wpdb->prefix . 'todo_list';

    // Handle Update
    if (isset($_POST['update_todo'])) {
        // Verify nonce
        if (!isset($_POST['simple_todo_nonce_field']) || !wp_verify_nonce($_POST['simple_todo_nonce_field'], 'simple_todo_form_action')) {
            return;
        }

        // Update the to-do item
        $todo_id = intval($_POST['todo_id']);
        $title = sanitize_text_field($_POST['todo_title']);
        $description = sanitize_textarea_field($_POST['todo_description']);
        
        $wpdb->update(
            $tablename,
            [
                'title' => $title,
                'description' => $description
            ],
            ['id' => $todo_id]
        );
    }

    // Handle Delete
    if (isset($_POST['delete_todo'])) {
        // Verify nonce
        if (!isset($_POST['simple_todo_nonce_field']) || !wp_verify_nonce($_POST['simple_todo_nonce_field'], 'simple_todo_form_action')) {
            return;
        }

        $todo_id = intval($_POST['todo_id']);
        $wpdb->delete($tablename, ['id' => $todo_id]);
    }
}
add_action('init', 'handle_form_submission');

?>