<?php
/*
Plugin Name: SEO Meta Plugin
Description: A basic plugin to add custom title and meta description fields.
Version: 1.0
Author: Your Name
*/

add_action('add_meta_boxes', 'custom_meta_box');

function custom_meta_box(){
    add_meta_box(
        'cmp_meta',
        'SEO Meta',
        'cmp_meta_box_callback',
        ['post', 'page'],
        'normal',
        'high',
    );
}
function cmp_meta_box_callback($post){
    wp_nonce_field('cmp_meta_box', 'cmp_meta_box_nonce');
    $title = get_post_meta($post->ID, '_custom_meta_title', true);
    $description = get_post_meta($post->ID, '_custom_meta_description', true);
    echo '<label for="custom_meta_title">Custom Title</label>';
    echo '<input type="text" id="custom_meta_title" name="custom_meta_title" value="' . esc_attr($title) . '" size="25" />';
    
    echo '<label for="custom_meta_description">Custom Description</label>';
    echo '<textarea id="custom_meta_description" name="custom_meta_description" rows="4" cols="50">' . esc_textarea($description) . '</textarea>';

}


add_action('save_post', 'cmp_save_meta_box_data');

function cmp_save_meta_box_data($postid){
    if (!isset($_POST['cmp_meta_box_nonce']) || !wp_verify_nonce($_POST['cmp_meta_box_nonce'], 'cmp_meta_box')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if(isset($_POST['custom_meta_title'])) {
        update_post_meta($postid, '_custom_meta_title', sanitize_text_field($_POST['custom_meta_title']));
    }
    
    if(isset($_POST['custom_meta_description'])) {
        update_post_meta($postid, '_custom_meta_description', sanitize_text_field($_POST['custom_meta_description']));
    }
}
add_filter('pre_get_document_title', 'cmp_custom_document_title');

function cmp_custom_document_title($title) {
    if (is_single() || is_page()) {
        global $post;
        $custom_title = get_post_meta($post->ID, '_custom_meta_title', true);
        if (!empty($custom_title)) {
            return esc_html($custom_title);
        }
    }
    return $title;
}

add_action('wp_head', 'cmp_output_custom_meta');

function cmp_output_custom_meta(){
    if(is_single() || is_page()){
        global $post;
        $description = get_post_meta($post->ID, '_custom_meta_description', true);
        if(!empty($description)){
            echo '<meta name="description" content="' . esc_attr($description) . '">';
        }
    }
}

?>