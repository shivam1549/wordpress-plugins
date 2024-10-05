<?php

/**
 *Plugin Name: Custom Product Attributes
 *Plugin URI: https://example.com
 *Description: Product attributes and price change
 *Version:1.0
 *Author: Anyone
 *Author URI: https://example.com
 *License: GPL v2 or later
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

register_activation_hook(__FILE__, 'fabric_pricing_plugin_activation');

function fabric_pricing_plugin_activation()
{
    if (!is_plugin_active('woocommerce/woocommerce.php') && current_user_can('activate_plugins')) {
        wp_die('Sorry, but this plugin requires WooCommerce to be installed and active.');
    }
}

add_action('add_meta_boxes', 'add_fabric_meta_boxes');

function add_fabric_meta_boxes()
{
    add_meta_box(
        'fabric_pricing_meta_box',
        'Fabric Pricing', // Box title
        'fabric_pricing_meta_box_callback', // Content callback, function name
        'product', // Post type
        'normal', // Context
        'high' // Priority
    );
}




function fabric_pricing_meta_box_callback($post)
{
    $fabric_pricing_json = get_post_meta($post->ID, '_fabric_pricing', true);
    // echo $fabric_pricing_json;
    $fabric_pricing_data = json_decode($fabric_pricing_json, true);
    wp_nonce_field('fabric_pricing_save_meta_box_data', 'fabric_pricing_meta_box_nonce');
?>

    <div id="fabric_pricing_wrapper">
        <table id="fabric_pricing_table" class="widefat">
            <thead>
                <tr>
                    <th>Fabric Weight</th>
                    <th>Fabric Width (inches)</th>
                    <th>Fabric Rate (Price per yard)</th>
                    <th>Minimum Length (yards)</th>
                    <th>Minimum Price </th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // var_dump($fabric_pricing_data);
                if (!empty($fabric_pricing_data) && is_array($fabric_pricing_data)) {
                    foreach ($fabric_pricing_data as $index => $fabric) {
                ?>
                        <tr>
                            <td><input type="text" name="fabric_weight[]" value="<?php echo esc_attr($fabric['weight']); ?>" /></td>
                            <td><input type="text" name="fabric_width[]" value="<?php echo esc_attr($fabric['width']); ?>" /></td>
                            <td><input type="text" name="fabric_rate[]" value="<?php echo esc_attr($fabric['rate']); ?>" /></td>
                            <td><input type="text" name="fabric_min_length[]" value="<?php echo esc_attr($fabric['min_length']); ?>" /></td>
                            <td><input type="text" name="fabric_min_price[]" value="<?php echo esc_attr($fabric['min_price']); ?>" /></td>
                            <td><button type="button" class="remove_row button">Remove</button></td>
                        </tr>
                    <?php
                    }
                } else {
                    // Display one empty row as a default
                    ?>
                    <tr>
                        <td><input type="text" name="fabric_weight[]" value="" /></td>
                        <td><input type="text" name="fabric_width[]" value="" /></td>
                        <td><input type="text" name="fabric_rate[]" value="" /></td>
                        <td><input type="text" name="fabric_min_length[]" value="" /></td>
                        <td><input type="text" name="fabric_min_price[]" value="" /></td>
                        <td><button type="button" class="remove_row button">Remove</button></td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
        <p><button type="button" id="add_more_rows" class="button">Add More</button></p>
    </div>
    <style>
        #fabric_pricing_wrapper table th,
        #fabric_pricing_wrapper table td {
            padding: 8px;
        }

        #fabric_pricing_wrapper table td input {
            width: 100%;
        }
    </style>
    <script>
        jQuery(document).ready(function($) {
            // Add a new row when "Add More" is clicked
            $('#add_more_rows').click(function() {
                var newRow = `<tr>
                    <td><input type="text" name="fabric_weight[]" value="" /></td>
                    <td><input type="text" name="fabric_width[]" value="" /></td>
                    <td><input type="text" name="fabric_rate[]" value="" /></td>
                    <td><input type="text" name="fabric_min_length[]" value="" /></td>
                    <td><input type="text" name="fabric_min_price[]" value="" /></td>
                    <td><button type="button" class="remove_row button">Remove</button></td>
                </tr>`;
                $('#fabric_pricing_table tbody').append(newRow);
            });

            // Remove a row when "Remove" is clicked
            $(document).on('click', '.remove_row', function() {
                $(this).closest('tr').remove();
            });
        });
    </script>

<?php

}

// Step 3: Save the meta box data
add_action('save_post', 'save_fabric_pricing_meta_box_data');

function save_fabric_pricing_meta_box_data($post_id)
{
    // Verify nonce
    if (!isset($_POST['fabric_pricing_meta_box_nonce']) || !wp_verify_nonce($_POST['fabric_pricing_meta_box_nonce'], 'fabric_pricing_save_meta_box_data')) {
        return;
    }

    // Save fabric pricing data
    $fabric_weight = isset($_POST['fabric_weight']) ? $_POST['fabric_weight'] : [];
    $fabric_widths = isset($_POST['fabric_width']) ? $_POST['fabric_width'] : [];
    $fabric_rates = isset($_POST['fabric_rate']) ? $_POST['fabric_rate'] : [];
    $fabric_min_lengths = isset($_POST['fabric_min_length']) ? $_POST['fabric_min_length'] : [];
    $fabric_min_prices = isset($_POST['fabric_min_price']) ? $_POST['fabric_min_price'] : [];

    $fabric_pricing_data = [];

    for ($i = 0; $i < count($fabric_widths); $i++) {
        if (!empty($fabric_widths[$i])) {
            $fabric_pricing_data[] = [
                'weight' => sanitize_text_field($fabric_weight[$i]),
                'width' => sanitize_text_field($fabric_widths[$i]),
                'rate' => sanitize_text_field($fabric_rates[$i]),
                'min_length' => sanitize_text_field($fabric_min_lengths[$i]),
                'min_price' => sanitize_text_field($fabric_min_prices[$i])
            ];
        }
    }

    $fabric_pricing_json = json_encode($fabric_pricing_data);

    update_post_meta($post_id, '_fabric_pricing', $fabric_pricing_json);
}


// Show price in and fabric values in product page

add_action('woocommerce_before_add_to_cart_button', 'show_fabric_pricing_options');
function show_fabric_pricing_options() {
    global $post;
    
    // Get the fabric pricing data from the post meta
    $fabric_pricing_json = get_post_meta($post->ID, '_fabric_pricing', true);
    if (!$fabric_pricing_json) {
        return; // If no fabric pricing data, do nothing
    }

    $fabric_pricing_data = json_decode($fabric_pricing_json, true); // Decode the JSON into an array
    
    if (!empty($fabric_pricing_data)) {
        wp_enqueue_script('jquery');

        wp_enqueue_script(
            'fabric-pricing-js',
            plugin_dir_url(__FILE__) . 'assets/js/fabric-pricing.js',
            array('jquery'),
            '1.0.0',
            true
        );

            // Pass the currency symbol to the JS file
            wp_localize_script('fabric-pricing-js', 'fabricPricingDataarray', array(
                'fabricdata' => $fabric_pricing_data,
            )); 
        ?>
        
       
        <div class="fabric-pricing">

        <div class="fabric-weight" id="fabricweight">
            
        </div>

            <label for="fabric-width">Select Fabric Width:</label>
            <select id="fabric-width" name="fabric_width">
                <?php foreach ($fabric_pricing_data as $fabric) { ?>
                    <option value="<?php echo esc_attr($fabric['width']); ?>"
                            data-rate="<?php echo esc_attr($fabric['rate']); ?>"
                            data-min-length="<?php echo esc_attr($fabric['min_length']); ?>"
                            data-min-price="<?php echo esc_attr($fabric['min_price']); ?>">
                        <?php echo esc_html($fabric['width']); ?> inches
                    </option>
                <?php } ?>
            </select>
            
            <div id="fabric-details">
                <p>Rate per yard: <span id="fabric-rate"></span></p>
                <p>Minimum length: <span id="fabric-min-length"></span></p>
                <p>Minimum price: <span id="fabric-min-price"></span></p>
            </div>
            
            <label for="fabric-length">Enter Length (in yards):</label>
            <input type="number" id="fabric-length" name="fabric_length" min="0" step="0.1" />

            <p>Total Price: <span style="color:red; font-size:20px" id="total-price"></span></p>
        </div>
        <?php
    }


    // Retrieve the extra options from product meta
    $fabric_extra_options_json = get_post_meta($post->ID, '_fabric_extra_options', true);

    if (!$fabric_extra_options_json) {
        return; // If no fabric pricing data, do nothing
    }
    $fabric_extra_options = json_decode($fabric_extra_options_json, true);
    if (!empty($fabric_extra_options)) {
        echo '<div class="extra-fabric-options">';
        foreach ($fabric_extra_options as $option) {
            echo '<p><label><input type="checkbox" class="extra-option" name="extra_fabric_options[]" value="' . esc_attr($option['name']) . '" data-price-increase="' . esc_attr($option['percentage']) . '"> ' . esc_html($option['name']) . ' (+' . esc_attr($option['percentage']) . '%)</label></p>';
        }
        echo '</div>';
    }
}


function my_plugin_enqueue_scripts() {
    if (is_product()) { // Only load on single product pages
        wp_enqueue_script('jquery');

        wp_enqueue_script(
            'fabric-pricing-js',
            plugin_dir_url(__FILE__) . 'assets/js/fabric-pricing.js',
            array('jquery'),
            '1.0.0',
            true
        );
            // Get WooCommerce currency symbol
            $currency_symbol = get_woocommerce_currency_symbol();

            // Pass the currency symbol to the JS file
            wp_localize_script('fabric-pricing-js', 'fabricPricingData', array(
                'currency' => $currency_symbol,
            )); 
    }
}
add_action('wp_enqueue_scripts', 'my_plugin_enqueue_scripts');


// Capture fabric width and length when the product is added to the cart
function add_fabric_custom_fields_to_cart($cart_item_data, $product_id, $variation_id) {
    if (isset($_POST['extra_fabric_options'])) {
        // Sanitize and store the extra fabric options
        $cart_item_data['extra_fabric_options'] = array_map('sanitize_text_field', $_POST['extra_fabric_options']);
    }
    if (isset($_POST['fabric_width']) && isset($_POST['fabric_length']) && isset($_POST['fabric_weight'])) {
        $cart_item_data['fabric_weight'] = sanitize_text_field($_POST['fabric_weight']);
        $cart_item_data['fabric_width'] = sanitize_text_field($_POST['fabric_width']);
        $cart_item_data['fabric_length'] = sanitize_text_field($_POST['fabric_length']);
        
        // Optional: Generate a unique key to avoid cart item merging
        $cart_item_data['unique_key'] = md5(microtime() . rand());
    }
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'add_fabric_custom_fields_to_cart', 10, 3);
// Display fabric width and length in the cart
function display_fabric_custom_fields_in_cart($item_data, $cart_item) {
    if (isset($cart_item['fabric_weight'])) {
        $item_data[] = array(
            'name' => 'Fabric Weight',
            'value' => sanitize_text_field($cart_item['fabric_weight']),
        );
    }
    if (isset($cart_item['fabric_width'])) {
        $item_data[] = array(
            'name' => 'Fabric Width',
            'value' => sanitize_text_field($cart_item['fabric_width']),
        );
    }
    
    if (isset($cart_item['fabric_length'])) {
        $item_data[] = array(
            'name' => 'Fabric Length',
            'value' => sanitize_text_field($cart_item['fabric_length']),
        );
    }
    if (isset($cart_item['extra_fabric_options'])) {
        $extra_options = implode(', ', array_map('sanitize_text_field', $cart_item['extra_fabric_options']));
        $item_data[] = array(
            'name' => 'Extra Fabric Options',
            'value' => $extra_options,
        );
    }
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'display_fabric_custom_fields_in_cart', 10, 2);


// Update the price based on fabric width rate and length
function update_fabric_price_in_cart($cart) {
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['fabric_width']) && isset($cart_item['fabric_length']) && isset($cart_item['fabric_weight'])) {
            // Retrieve the pricing information for the selected fabric width
            $fabric_pricing_json = get_post_meta($cart_item['product_id'], '_fabric_pricing', true);
            
            // Decode the JSON data into an array
            $fabric_pricing_data = json_decode($fabric_pricing_json, true);

            if (!empty($fabric_pricing_data)) {
                $selected_width = sanitize_text_field($cart_item['fabric_width']);
                $length = floatval($cart_item['fabric_length']);
                $selected_weight = sanitize_text_field($cart_item['fabric_weight']);
                $rate_per_yard = 0;

                // Find the rate for the selected width
                foreach ($fabric_pricing_data as $fabric) {
                    if ($fabric['width'] == $selected_width && $fabric['weight'] == $selected_weight) {
                        $rate_per_yard = floatval($fabric['rate']);
                        $min_price = floatval($fabric['min_price']);
                        break;
                    }
                }

                // Calculate the new price based on the selected width's rate and length entered by user
                if ($rate_per_yard > 0 && $length > 0) {
                    $new_price = $rate_per_yard * $length;
                    if($new_price < $min_price){
                        $new_price = $min_price; 
                    }
                    $price_increase = 0;
                    if(isset($cart_item['extra_fabric_options'])){
                        $extra_options_meta = get_post_meta($cart_item['product_id'], '_fabric_extra_options', true);
                        $extra_options = json_decode($extra_options_meta, true);
            
                        // Initialize price increment
                        
            
                        // Loop through selected extra options
                        foreach ($cart_item['extra_fabric_options'] as $selected_option) {
                            foreach ($extra_options as $option) {
                                if ($selected_option === $option['name']) {
                                    // Calculate the price increment based on the percentage
                                    $price_increase += ($option['percentage'] / 100) * $new_price;
                                }
                            }
                        }
                    }
                    
                    // Update the price in the cart item
                    $cart_item['data']->set_price($new_price + $price_increase);
                }
            }
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'update_fabric_price_in_cart', 10, 1);




// Save fabric width and length to order meta
function save_fabric_fields_to_order_items($item, $cart_item_key, $values, $order) {
    if (isset($values['fabric_width'])) {
        $item->add_meta_data('Fabric Width', $values['fabric_width'], true);
    }
    if (isset($values['fabric_length'])) {
        $item->add_meta_data('Fabric Length', $values['fabric_length'], true);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'save_fabric_fields_to_order_items', 10, 4);


// Display fabric details on the admin order page
// function display_fabric_fields_on_admin_order($item_id, $item, $product) {
//     if ($meta_data = wc_get_order_item_meta($item_id, 'Fabric Width')) {
//         echo '<p><strong>Fabric Width:</strong> ' . $meta_data . '</p>';
//     }
//     if ($meta_data = wc_get_order_item_meta($item_id, 'Fabric Length')) {
//         echo '<p><strong>Fabric Length:</strong> ' . $meta_data . '</p>';
//     }
// }
// add_action('woocommerce_admin_order_item_headers', 'display_fabric_fields_on_admin_order', 10, 3);



// Extra options


function add_fabric_extra_options_meta_box() {
    add_meta_box(
        'fabric_extra_options_meta_box',
        'Extra Fabric Options',
        'fabric_extra_options_meta_box_callback',
        'product',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_fabric_extra_options_meta_box');

function fabric_extra_options_meta_box_callback($post) {
    $fabric_extra_options_json = get_post_meta($post->ID, '_fabric_extra_options', true);
    $fabric_extra_options = json_decode($fabric_extra_options_json, true);
    ?>
    <div id="fabric-extra-options-wrapper">
        <table id="fabric-extra-options-table">
            <thead>
                <tr>
                    <th>Option Name</th>
                    <th>Percentage Price Increase</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($fabric_extra_options)) {
                    foreach ($fabric_extra_options as $option) {
                        ?>
                        <tr>
                            <td><input type="text" name="extra_option_name[]" value="<?php echo esc_attr($option['name']); ?>"></td>
                            <td><input type="number" name="extra_option_percentage[]" value="<?php echo esc_attr($option['percentage']); ?>" step="0.01"></td>
                            <td><button type="button" class="remove-extra-option button">Remove</button></td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td><input type="text" name="extra_option_name[]" value=""></td>
                        <td><input type="number" name="extra_option_percentage[]" value="" step="0.01"></td>
                        <td><button type="button" class="remove-extra-option button">Remove</button></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
        <button type="button" id="add-extra-option" class="button">Add More</button>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('#add-extra-option').on('click', function() {
                $('#fabric-extra-options-table tbody').append('<tr><td><input type="text" name="extra_option_name[]" value=""></td><td><input type="number" name="extra_option_percentage[]" value="" step="0.01"></td><td><button type="button" class="remove-extra-option button">Remove</button></td></tr>');
            });

            $(document).on('click', '.remove-extra-option', function() {
                $(this).closest('tr').remove();
            });
        });
    </script>
    <?php
}

function save_fabric_extra_options_meta_box_data($post_id) {
    if (isset($_POST['extra_option_name']) && isset($_POST['extra_option_percentage'])) {
        $extra_options = [];
        $option_names = $_POST['extra_option_name'];
        $option_percentages = $_POST['extra_option_percentage'];

        for ($i = 0; $i < count($option_names); $i++) {
            if (!empty($option_names[$i])) {
                $extra_options[] = [
                    'name' => sanitize_text_field($option_names[$i]),
                    'percentage' => sanitize_text_field($option_percentages[$i])
                ];
            }
        }

        $extra_options_json = json_encode($extra_options);

        update_post_meta($post_id, '_fabric_extra_options', $extra_options_json);
    }
}
add_action('save_post', 'save_fabric_extra_options_meta_box_data');






?>