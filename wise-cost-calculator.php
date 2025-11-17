<?php
/**
 * Plugin Name: Wise Cost Calculator
 * Plugin URI: https://github.com/farooqbinmunir?tab=repositories
 * Description: A custom cost calculator for WooCommerce products
 * Version: 1.0.0
 * Author: Wiselogix Technologies
 * Author URI: https://www.wiselogix.com/
 * Text Domain: wise-cost-calculator
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WISE_CALCULATOR_PATH', plugin_dir_path(__FILE__));
define('WISE_CALCULATOR_URL', plugin_dir_url(__FILE__));
define('WISE_CALCULATOR_VERSION', '1.0.0');

// Initialize the plugin
function wise_cost_calculator_init() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'wise_cost_calculator_woocommerce_notice');
        return;
    }
    
    // Include required files
    $include_files = [
        'raza.php',
        'shipping.php',
    ];

    foreach ($include_files as $file) {
        $file_path = WISE_CALCULATOR_PATH . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
    
    // Enqueue assets
    add_action('wp_enqueue_scripts', 'wise_cost_calculator_enqueue_assets', 15);
}
add_action('plugins_loaded', 'wise_cost_calculator_init');

// WooCommerce missing notice
function wise_cost_calculator_woocommerce_notice() {
    ?>
    <div class="error">
        <p><?php _e('Wise Cost Calculator requires WooCommerce to be installed and active.', 'wise-cost-calculator'); ?></p>
    </div>
    <?php
}

// Enqueue assets function
function wise_cost_calculator_enqueue_assets() {
    wp_enqueue_style('wise-cost-calculator-css', WISE_CALCULATOR_URL . 'css/wise-cost-calculator.css', [], WISE_CALCULATOR_VERSION);
    
    // ============ Enqueueing Calculator assets
    global $product;
    
    if (!is_product() || !$product) {
        return;
    }
    
    $product_id = get_the_ID();
    
    // Check if we're on a single product page and calculator should be shown
    if (wise_should_show_calculator($product)) {
        
        // Get calculator type
        $calculator_type = get_post_meta($product_id, '_calculator_type', true);
        
        // Get pricing data
        $pricing_data = wise_get_calculator_pricing_data();
        
        // Enqueue JS based on calculator type
        $js_file = '';
        if ($calculator_type === 'icf') {
            $js_file = WISE_CALCULATOR_URL . 'js/icf.js';
        } elseif ($calculator_type === 'reolok') {
            $js_file = WISE_CALCULATOR_URL . 'js/reolok.js';
        }
        
        if (!empty($js_file)) {
            wp_enqueue_script('wise-cost-calculator-js', $js_file, ['jquery'], WISE_CALCULATOR_VERSION, true);
            
            // Pass pricing data to JavaScript
            wp_localize_script('wise-cost-calculator-js', 'calculatorPricing', $pricing_data);
            
            // Add common js
            wp_add_inline_script('wise-cost-calculator-js', '
                jQuery(document).ready($ => {
                    document.addEventListener("input", function (e) {
                      const target = e.target;

                      // Only apply on specific IDs
                      if (["eeLength", "eeHeight", "eeCorners", "rlLength", "rlHeight", "rlEndcaps"].includes(target.id)) {
                        let value = target.value;

                        // Prevent negative values
                        if (value < 0) {
                          target.value = 0;
                          return;
                        }

                        // CORNERS must be whole number (no decimals)
                        if (target.id === "eeCorners") {
                          target.value = value.replace(/\\D/g, ""); // remove non-digits
                        } 
                        else {
                          // Limit to 2 decimal places for all others
                          if (value.includes(".")) {
                            const [intPart, decPart] = value.split(".");
                            target.value = intPart + "." + decPart.slice(0, 2);
                          }
                        }

                        // Height limit = 3.6m
                        if (target.id === "rlHeight" && parseFloat(target.value) > 3.6) {
                          target.value = 3.6;
                        }
                      }
                    });
                });
            ');
        }
    }
}

// Check if calculator should be shown
function wise_should_show_calculator($product) {    
    if (!$product) {
        return false;
    }
    // Check if product has calculator enabled via custom field
    $has_calculator = get_post_meta($product->get_id(), '_enable_cost_calculator', true);
    return $has_calculator === 'yes';
}

// Get calculator type for product
function wise_get_calculator_type($product_id) {
    return get_post_meta($product_id, '_calculator_type', true);
}

// Get pricing data
function wise_get_calculator_pricing_data() {
    return array(
        // ICF
        'cChannelPrice' => floatval(get_option('c_channel_price', 3.7567)),
        'cChannelVolume' => floatval(get_option('c_channel_volume_m3', 0.00315)),
        'bracesPrice' => floatval(get_option('braces_price', 19.5)),
        'blockWaste' => floatval(get_option('block_waste_percentage', 0.05)),
        
        // Reolok
        'reolok_material_waste' => floatval(get_option('reolok_material_waste', 0.05)),
        'reolok_full_cassette_price_threshold' => floatval(get_option('reolok_full_cassette_price_threshold', 0.88888)),
        'reolok_bottom_track_price' => floatval(get_option('reolok_bottom_track_price', 30.96)),
        'reolok_bottom_track_volume' => floatval(get_option('reolok_bottom_track_volume', 0.0008352)),
    );
}

// ____________________ Calculator _________________________ \\
// Calculator - Add calculator to product page
add_action('woocommerce_before_add_to_cart_button', 'wise_add_cost_calculator_to_product');
function wise_add_cost_calculator_to_product() {
    global $product;
    
    if (!$product) {
        return;
    }
    
    $product_id = get_the_ID();
    
    // Only show for specific products or categories
    if (wise_should_show_calculator($product)) {
        $calculator_type = wise_get_calculator_type($product_id);
        
        echo '<div id="woocommerce-cost-calculator">';
        if ($calculator_type === 'icf') {
            include WISE_CALCULATOR_PATH . 'icf.php';
        } elseif ($calculator_type === 'reolok') {
            include WISE_CALCULATOR_PATH . 'reolok.php';
        }
        echo '</div>';
        
        // Add hidden field for calculated price
        echo '<input type="hidden" name="calculated_price" id="calculated_price" value="0" />';
        echo '<input type="hidden" name="calculated_data" id="calculated_data" value="" />';
    }
}

// Validate before adding to cart
add_filter('woocommerce_add_to_cart_validation', 'wise_validate_calculator_product', 10, 3);
function wise_validate_calculator_product($passed, $product_id, $quantity) {
    $product = wc_get_product($product_id);
    if ($product && wise_should_show_calculator($product)) {
        if (isset($_POST['calculated_price']) && empty($_POST['calculated_price'])) {
            wc_add_notice(__('Please calculate the price first', 'wise-cost-calculator'), 'error');
            return false;
        }
    }
    return $passed;
}

// Add custom data to cart item
add_filter('woocommerce_add_cart_item_data', 'wise_add_calculator_data_to_cart', 10, 3);
function wise_add_calculator_data_to_cart($cart_item_data, $product_id, $variation_id) {
    if (isset($_POST['calculated_price']) && $_POST['calculated_price'] > 0) {
        // SET Calculated PRICE
        $cart_item_data['calculated_price'] = floatval($_POST['calculated_price']);
        
        // SET Calculated DATA - decode it first
        if (isset($_POST['calculated_data'])) {
            $calculated_data = json_decode(stripslashes($_POST['calculated_data']), true);
            $cart_item_data['calculated_data'] = $calculated_data;
        }
    }
    return $cart_item_data;
}

// Display custom data in cart, checkout, orders
add_filter('woocommerce_get_item_data', 'wise_display_calculator_data_in_cart', 10, 2);
function wise_display_calculator_data_in_cart($item_data, $cart_item) {
    
    // Get the main product ID
    $product_id = $cart_item['product_id'];
    
    // Check if we have calculator data
    if (!isset($cart_item['calculated_data']) || empty($cart_item['calculated_data'])) {
        return $item_data;
    }
    
    // No need to decode - it's already an array
    $calculation_data = $cart_item['calculated_data'];
    if (!$calculation_data || !is_array($calculation_data)) {
        return $item_data;
    }

    $wallType = isset($calculation_data['wallType']) ? $calculation_data['wallType'] : '';
    $wallLabel = isset($calculation_data['wallLabel']) ? $calculation_data['wallLabel'] : '';
    $m2Rate = isset($calculation_data['m2Rate']) ? "$" . $calculation_data['m2Rate'] : '$0.00';
    $totalAreaM2 = isset($calculation_data['totalAreaM2']) ? $calculation_data['totalAreaM2'] : '0.00';
    
    // Common data in both products(at top)
    if (isset($calculation_data['wallType'])) {
        $item_data[] = array(
            'key' => __('<strong>Wall Type</strong>', 'wise-cost-calculator'),
            'value' => $wallType
        );
    }

    if (isset($calculation_data['wallLabel'])) {
        $item_data[] = array(
            'key' => __('<strong>Wall Label/Description</strong>', 'wise-cost-calculator'),
            'value' => $wallLabel
        );
    }
    
    // Get calculator type for product
    $calculator_type = wise_get_calculator_type($product_id);
    
    // ICF Product
    if ($calculator_type === 'icf') {
        $length = isset($calculation_data['length']) ? $calculation_data['length'] : '0';
        $height = isset($calculation_data['height']) ? $calculation_data['height'] : '0';
        $dimensions = "{$length}m × {$height}m";
        
        if (isset($calculation_data['fullBlocks'])) {
            $item_data[] = array(
                'key' => __('<strong>Full Blocks</strong>', 'wise-cost-calculator'),
                'value' => sanitize_text_field($calculation_data['fullBlocks'])
            );
        }
        
        if (isset($calculation_data['cnrBlocks'])) {
            $item_data[] = array(
                'key' => __('<strong>Corner Blocks</strong>', 'wise-cost-calculator'),
                'value' => sanitize_text_field($calculation_data['cnrBlocks'])
            );
        }
        
        if (isset($calculation_data['cChannel'])) {
            $item_data[] = array(
                'key' => __('<strong>C Channel</strong>', 'wise-cost-calculator'),
                'value' => sanitize_text_field($calculation_data['cChannel']) . "m"
            );
        }
        
        if (isset($calculation_data['length']) && isset($calculation_data['height'])) {
            $item_data[] = array(
                'key' => __('<strong>Dimensions</strong>', 'wise-cost-calculator'),
                'value' => $dimensions
            );
        }
                
    }
    
    // Reolok Product
    if ($calculator_type === 'reolok') {
        // Display Reolok-specific data        
        if(isset($calculation_data['height'])){
            $item_data[] = array(
                'key' => '<strong>Height</strong>',
                'value' => $calculation_data['height'],
                'display' => $calculation_data['height'] . "m"
            );    
        }
        if(isset($calculation_data['cassetteQty'])){
            $item_data[] = array(
                'key' => '<strong>Cassette Qty</strong>',
                'value' => $calculation_data['cassetteQty'],
            );
        }
        if(isset($calculation_data['endcapLength'])){
            $item_data[] = array(
                'key' => '<strong>Endcap Length</strong>',
                'value' => "{$calculation_data['endcapLength']}m",
            );
        }
        
        if(isset($calculation_data['bottomTrack'])){
            $item_data[] = array(
                'key' => '<strong>Bottom Track</strong>',
                'value' => $calculation_data['bottomTrack'],
                'display' => $calculation_data['bottomTrack']
            );
        }
    }
    
    // Common data in both products(at bottom)        
    if (isset($calculation_data['m2Rate'])) {
        $item_data[] = array(
            'key' => __('<strong>m<sup>2</sup> Rate</strong>', 'wise-cost-calculator'),
            'value' =>  $m2Rate
        );
    }

    if (isset($calculation_data['totalAreaM2'])) {
        $item_data[] = array(
            'key' => __('<strong>Total Area</strong>', 'wise-cost-calculator'),
            'value' => $totalAreaM2
        );
    }
    return $item_data;
}

// Set custom price
add_action('woocommerce_before_calculate_totals', 'wise_set_calculated_price', 10, 1);
function wise_set_calculated_price($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    
    foreach ($cart->get_cart() as $cart_item) {
        if (isset($cart_item['calculated_price']) && $cart_item['calculated_price'] > 0) {
            $cart_item['data']->set_price($cart_item['calculated_price']);
        }
    }
}

// Add custom field to variations
add_action('woocommerce_product_after_variable_attributes', 'wise_add_variation_custom_fields', 10, 3);
function wise_add_variation_custom_fields($loop, $variation_data, $variation) {
    $product_id = $variation->post_parent;
    $calculator_type = wise_get_calculator_type($product_id);
    
    if($calculator_type === 'icf'){
        // Field for CORNER PRICE
        woocommerce_wp_text_input(array(
            'id' => '_cnr_blocks_price[' . $loop . ']',
            'name' => '_cnr_blocks_price[' . $loop . ']',
            'label' => __('Corner Blocks Price', 'wise-cost-calculator'),
            'value' => get_post_meta($variation->ID, '_cnr_blocks_price', true),
            'type' => 'text'
        ));
        // Field for FULL BLOCK VOLUME (m3)
        woocommerce_wp_text_input(array(
            'id' => '_full_block_volume_m3[' . $loop . ']',
            'name' => '_full_block_volume_m3[' . $loop . ']',
            'label' => __('Full Block Volume (m<sup>3</sup>)', 'wise-cost-calculator'),
            'value' => get_post_meta($variation->ID, '_full_block_volume_m3', true),
            'type' => 'text',
        ));
        // Field for CORNER BLOCK VOLUME (m3)
        woocommerce_wp_text_input(array(
            'id' => '_corner_block_volume_m3[' . $loop . ']',
            'name' => '_corner_block_volume_m3[' . $loop . ']',
            'label' => __('Corner Block Volume (m<sup>3</sup>)', 'wise-cost-calculator'),
            'value' => get_post_meta($variation->ID, '_corner_block_volume_m3', true),
            'type' => 'text',
        ));
    }elseif($calculator_type === 'reolok'){
        // Field for Cassette Price
        woocommerce_wp_text_input(array(
            'id' => '_reolok_cassette_price[' . $loop . ']',
            'name' => '_reolok_cassette_price[' . $loop . ']',
            'label' => __('Cassette Price', 'wise-cost-calculator'),
            'value' => get_post_meta($variation->ID, '_reolok_cassette_price', true),
            'type' => 'text',
        ));
        woocommerce_wp_text_input(array(
            'id' => '_reolok_cassette_volume[' . $loop . ']',
            'name' => '_reolok_cassette_volume[' . $loop . ']',
            'label' => __('Cassette Volume', 'wise-cost-calculator'),
            'value' => get_post_meta($variation->ID, '_reolok_cassette_volume', true),
            'type' => 'text',
        ));
        woocommerce_wp_text_input(array(
            'id' => '_reolok_joining_truss_price[' . $loop . ']',
            'name' => '_reolok_joining_truss_price[' . $loop . ']',
            'label' => __('Joining Truss Price', 'wise-cost-calculator'),
            'value' => get_post_meta($variation->ID, '_reolok_joining_truss_price', true),
            'type' => 'text',
        ));
        woocommerce_wp_text_input(array(
            'id' => '_reolok_joining_truss_volume[' . $loop . ']',
            'name' => '_reolok_joining_truss_volume[' . $loop . ']',
            'label' => __('Joining Truss Volume', 'wise-cost-calculator'),
            'value' => get_post_meta($variation->ID, '_reolok_joining_truss_volume', true),
            'type' => 'text',
        ));
        woocommerce_wp_text_input(array(
            'id' => '_reolok_endcap_price[' . $loop . ']',
            'name' => '_reolok_endcap_price[' . $loop . ']',
            'label' => __('Endcap (3.6m) Price', 'wise-cost-calculator'),
            'value' => get_post_meta($variation->ID, '_reolok_endcap_price', true),
            'type' => 'text',
        ));
        woocommerce_wp_text_input(array(
            'id' => '_reolok_endcap_volume[' . $loop . ']',
            'name' => '_reolok_endcap_volume[' . $loop . ']',
            'label' => __('Endcap (3.6m) Volume', 'wise-cost-calculator'),
            'value' => get_post_meta($variation->ID, '_reolok_endcap_volume', true),
            'type' => 'text',
        ));
    }
}

// Save custom fields
add_action('woocommerce_save_product_variation', 'wise_save_variation_custom_fields', 10, 2);
function wise_save_variation_custom_fields($variation_id, $loop) {
    // ICF
    if (isset($_POST['_full_block_volume_m3'][$loop])) {
        update_post_meta($variation_id, '_full_block_volume_m3', sanitize_text_field($_POST['_full_block_volume_m3'][$loop]));
    }
    if (isset($_POST['_cnr_blocks_price'][$loop])) {
        update_post_meta($variation_id, '_cnr_blocks_price', sanitize_text_field($_POST['_cnr_blocks_price'][$loop]));
    }
    if (isset($_POST['_corner_block_volume_m3'][$loop])) {
        update_post_meta($variation_id, '_corner_block_volume_m3', sanitize_text_field($_POST['_corner_block_volume_m3'][$loop]));
    }
    // Reolok
    if (isset($_POST['_reolok_cassette_price'][$loop])) {
        update_post_meta($variation_id, '_reolok_cassette_price', sanitize_text_field($_POST['_reolok_cassette_price'][$loop]));
    }
    if (isset($_POST['_reolok_cassette_volume'][$loop])) {
        update_post_meta($variation_id, '_reolok_cassette_volume', sanitize_text_field($_POST['_reolok_cassette_volume'][$loop]));
    }
    if (isset($_POST['_reolok_joining_truss_price'][$loop])) {
        update_post_meta($variation_id, '_reolok_joining_truss_price', sanitize_text_field($_POST['_reolok_joining_truss_price'][$loop]));
    }
    if (isset($_POST['_reolok_joining_truss_volume'][$loop])) {
        update_post_meta($variation_id, '_reolok_joining_truss_volume', sanitize_text_field($_POST['_reolok_joining_truss_volume'][$loop]));
    }
    if (isset($_POST['_reolok_endcap_price'][$loop])) {
        update_post_meta($variation_id, '_reolok_endcap_price', sanitize_text_field($_POST['_reolok_endcap_price'][$loop]));
    }
    if (isset($_POST['_reolok_endcap_volume'][$loop])) {
        update_post_meta($variation_id, '_reolok_endcap_volume', sanitize_text_field($_POST['_reolok_endcap_volume'][$loop]));
    }
}

// Add corner blocks price to variation data
add_filter('woocommerce_available_variation', 'wise_add_cnr_blocks_price_to_variation_data', 10, 3);
function wise_add_cnr_blocks_price_to_variation_data($variation_data, $product, $variation) {
    $parent_product = wc_get_product($variation->get_parent_id());
    $product_id = $parent_product ? $parent_product->get_id() : 0;
    $calculator_type = wise_get_calculator_type($product_id);
    
    // ICF Product
    if($calculator_type === 'icf'){
        $cnr_blocks_price = get_post_meta($variation->get_id(), '_cnr_blocks_price', true);
        $variation_data['cnr_blocks_price'] = $cnr_blocks_price ? floatval($cnr_blocks_price) : '';

        $full_block_volume_m3 = get_post_meta($variation->get_id(), '_full_block_volume_m3', true);
        $variation_data['full_block_volume_m3'] = $full_block_volume_m3 ? floatval($full_block_volume_m3) : '';

        $corner_block_volume_m3 = get_post_meta($variation->get_id(), '_corner_block_volume_m3', true);
        $variation_data['corner_block_volume_m3'] = $corner_block_volume_m3 ? floatval($corner_block_volume_m3) : '';
    }
    // Reolok Product
    elseif($calculator_type === 'reolok'){
        $reolok_cassette_price = get_post_meta($variation->get_id(), '_reolok_cassette_price', true);
        $variation_data['reolok_cassette_price'] = $reolok_cassette_price ? floatval($reolok_cassette_price) : '';

        $reolok_cassette_volume = get_post_meta($variation->get_id(), '_reolok_cassette_volume', true);
        $variation_data['reolok_cassette_volume'] = $reolok_cassette_volume ? floatval($reolok_cassette_volume) : '';

        $reolok_joining_truss_price = get_post_meta($variation->get_id(), '_reolok_joining_truss_price', true);
        $variation_data['reolok_joining_truss_price'] = $reolok_joining_truss_price ? floatval($reolok_joining_truss_price) : '';

        $reolok_joining_truss_volume = get_post_meta($variation->get_id(), '_reolok_joining_truss_volume', true);
        $variation_data['reolok_joining_truss_volume'] = $reolok_joining_truss_volume ? floatval($reolok_joining_truss_volume) : '';

        $reolok_endcap_price = get_post_meta($variation->get_id(), '_reolok_endcap_price', true);
        $variation_data['reolok_endcap_price'] = $reolok_endcap_price ? floatval($reolok_endcap_price) : '';

        $reolok_endcap_volume = get_post_meta($variation->get_id(), '_reolok_endcap_volume', true);
        $variation_data['reolok_endcap_volume'] = $reolok_endcap_volume ? floatval($reolok_endcap_volume) : '';
    }
    
    return $variation_data;
}

// Add calculator fields in product settings
add_action('woocommerce_product_options_general_product_data', 'wise_add_calculator_fields');
function wise_add_calculator_fields() {
    echo '<div class="options_group">';
    
    // Enable Calculator Checkbox
    woocommerce_wp_checkbox(array(
        'id' => '_enable_cost_calculator',
        'label' => __('Enable Cost Calculator', 'wise-cost-calculator'),
        'description' => __('Show cost calculator on product page', 'wise-cost-calculator')
    ));
    
    // Calculator Type Dropdown
    woocommerce_wp_select(array(
        'id' => '_calculator_type',
        'label' => __('Calculator Type', 'wise-cost-calculator'),
        'description' => __('Select the type of calculator to display', 'wise-cost-calculator'),
        'desc_tip' => true,
        'options' => array(
            '' => __('Select Calculator Type', 'wise-cost-calculator'),
            'icf' => __('FormPro® ICF Calculator', 'wise-cost-calculator'),
            'reolok' => __('Reolok Calculator', 'wise-cost-calculator')
        )
    ));
    
    echo '</div>';
}

// Save calculator fields
add_action('woocommerce_process_product_meta', 'wise_save_calculator_fields');
function wise_save_calculator_fields($post_id) {
    // Save enable calculator field
    $enable_calculator = isset($_POST['_enable_cost_calculator']) ? 'yes' : 'no';
    update_post_meta($post_id, '_enable_cost_calculator', $enable_calculator);
    
    // Save calculator type field
    if (isset($_POST['_calculator_type'])) {
        update_post_meta($post_id, '_calculator_type', sanitize_text_field($_POST['_calculator_type']));
    }
}

// Options page
// Add admin menu for calculator settings
add_action('admin_menu', 'wise_add_cost_calculator_admin_menu');
function wise_add_cost_calculator_admin_menu() {
    add_options_page(
        'Cost Calculator Settings',
        'Cost Calculator',
        'manage_options',
        'cost-calculator-settings',
        'wise_cost_calculator_settings_page'
    );
}

// Register settings
add_action('admin_init', 'wise_register_cost_calculator_settings');
function wise_register_cost_calculator_settings() {
    
    // Additional Prices
    // ICF
    register_setting('cost_calculator_settings', 'c_channel_price');
    register_setting('cost_calculator_settings', 'c_channel_volume_m3');
    register_setting('cost_calculator_settings', 'braces_price');
    register_setting('cost_calculator_settings', 'block_waste_percentage');
    // Reolok
    register_setting('cost_calculator_settings', 'reolok_material_waste');
    register_setting('cost_calculator_settings', 'reolok_full_cassette_price_threshold');
    register_setting('cost_calculator_settings', 'reolok_bottom_track_price');
    register_setting('cost_calculator_settings', 'reolok_bottom_track_volume');
    
    // New Shipping settings
    // -- ICF
    register_setting('shipping_settings', 'icf_pallets_full_container_threshold');
    register_setting('shipping_settings', 'icf_pallets_per_container');
    register_setting('shipping_settings', 'icf_pallet_height_m');
    register_setting('shipping_settings', 'icf_pallet_width_m');
    register_setting('shipping_settings', 'icf_per_pallet_load_markup');
    
    // -- Reolok
    register_setting('shipping_settings', 'reolok_pallets_full_container_threshold');
    register_setting('shipping_settings', 'reolok_pallets_per_container');
    register_setting('shipping_settings', 'reolok_pallet_height_m');
    register_setting('shipping_settings', 'reolok_pallet_width_m');
    register_setting('shipping_settings', 'reolok_per_pallet_load_markup');
}

// Add a submenu for Shipping
add_action('admin_menu', 'wise_add_shipping_menu_page');
function wise_add_shipping_menu_page() {
    add_submenu_page(
        'options-general.php',           // parent menu (Settings)
        'SHIPPING PRICES',             // page title
        'Shipping',                      // menu title
        'manage_options',                // capability
        'shipping-settings',             // slug
        'wise_shipping_settings_page_html'    // callback function
    );
}

// Admin settings page
function wise_cost_calculator_settings_page() {
    ?>
    <div class="wrap">
        <h1>Cost Calculator Settings</h1>
        
        <form method="post" action="options.php">
            <?php settings_fields('cost_calculator_settings'); ?>
            <?php do_settings_sections('cost_calculator_settings'); ?>
            <!-- ICF -->
            <h2>ICF</h2>    
            <table class="form-table">
                <tr>
                    <th scope="row">Materials Waste(%)</th>
                    <td>
                        <input type="number" step="0.01" min="0" max="1" name="block_waste_percentage" 
                               value="<?php echo esc_attr(get_option('block_waste_percentage', '0.05')); ?>" />
                        <p class="description">Enter as decimal (0.05 = 5%)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">C Channel Price (m)</th>
                    <td>
                        <input type="number" step="0.01" min="0" name="c_channel_price"  value="<?php echo esc_attr(get_option('c_channel_price', '3.7567')); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">C Channel Volume (m<sup>3</sup>)</th>
                    <td>
                         <input type="text" name="c_channel_volume_m3" 
                               value="<?php echo esc_attr(get_option('c_channel_volume_m3', '0.00315')); ?>" />
                    </td>
                </tr>               
                <tr>
                    <th scope="row">Braces Price (each)</th>
                    <td>
                        <input type="number" step="0.01" min="0" name="braces_price" 
                               value="<?php echo esc_attr(get_option('braces_price', '19.5')); ?>" />
                    </td>
                </tr>
            </table>
            <hr />
            <!-- Reolok -->
            <h2>Reolok</h2>    
            <table class="form-table">
                <tr>
                    <th scope="row">Material Waste (%)</th>
                    <td>
                        <input type="text" step="0.01" min="0" max="1" name="reolok_material_waste" 
                               value="<?php echo esc_attr(get_option('reolok_material_waste', '0.05')); ?>" />
                        <p class="description">Enter as decimal (0.05 = 5%)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Full Cassette Price Threshold</th>
                    <td>
                        <input type="text" step="0.01" min="0" max="1" name="reolok_full_cassette_price_threshold" 
                               value="<?php echo esc_attr(get_option('reolok_full_cassette_price_threshold', '0.88888')); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Bottom Track (3.6m) Price</th>
                    <td>
                        <input type="text" name="reolok_bottom_track_price"  
                               value="<?php echo esc_attr(get_option('reolok_bottom_track_price', '30.96')); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Bottom Track (3.6m) Volume</th>
                    <td>
                        <input type="text" name="reolok_bottom_track_volume"  
                               value="<?php echo esc_attr(get_option('reolok_bottom_track_volume', '0.0008352')); ?>" />
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Shipping settings page
function wise_shipping_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>SHIPPING PRICES</h1>
        <b>Shipping misc. variables:</b>
        
        <form method="post" action="options.php">
            <?php
            settings_fields('shipping_settings');
            do_settings_sections('shipping_settings');
            ?>
            <!-- ICF -->
            <section class="shipping_prices_for_icf">
                <h3>ICF</h3>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="icf_pallets_full_container_threshold">Full Container Threshold</label></th>
                        <td><input type="number" step="0.01" name="icf_pallets_full_container_threshold" value="<?php echo esc_attr(get_option('icf_pallets_full_container_threshold')); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="icf_pallets_per_container">Pallets Per Container</label></th>
                        <td><input type="number" step="1" name="icf_pallets_per_container" value="<?php echo esc_attr(get_option('icf_pallets_per_container')); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="icf_pallet_height_m">Pallet Height (m)</label></th>
                        <td><input type="number" step="0.01" name="icf_pallet_height_m" value="<?php echo esc_attr(get_option('icf_pallet_height_m')); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="icf_pallet_width_m">Pallet Width (m)</label></th>
                        <td><input type="number" step="0.01" name="icf_pallet_width_m" value="<?php echo esc_attr(get_option('icf_pallet_width_m')); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="icf_per_pallet_load_markup">Per Pallet Load Markup</label></th>
                        <td><input type="number" step="0.01" name="icf_per_pallet_load_markup" value="<?php echo esc_attr(get_option('icf_per_pallet_load_markup')); ?>" /></td>
                    </tr>
                </table>
            </section>
            <hr />
            <!-- REOLOK -->
            <section class="shipping_prices_for_reolok">
                <h3>REOLOK</h3>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="reolok_pallets_full_container_threshold">Full Container Threshold</label></th>
                        <td><input type="number" step="0.01" name="reolok_pallets_full_container_threshold" value="<?php echo esc_attr(get_option('reolok_pallets_full_container_threshold')); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="reolok_pallets_per_container">Pallets Per Container</label></th>
                        <td><input type="number" step="1" name="reolok_pallets_per_container" value="<?php echo esc_attr(get_option('reolok_pallets_per_container')); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="reolok_pallet_height_m">Pallet Height (m)</label></th>
                        <td><input type="number" step="0.01" name="reolok_pallet_height_m" value="<?php echo esc_attr(get_option('reolok_pallet_height_m')); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="reolok_pallet_width_m">Pallet Width (m)</label></th>
                        <td><input type="number" step="0.01" name="reolok_pallet_width_m" value="<?php echo esc_attr(get_option('reolok_pallet_width_m')); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="reolok_per_pallet_load_markup">Per Pallet Load Markup</label></th>
                        <td><input type="number" step="0.01" name="reolok_per_pallet_load_markup" value="<?php echo esc_attr(get_option('reolok_per_pallet_load_markup')); ?>" /></td>
                    </tr>
                </table>
            </section>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Save data to order
add_action('woocommerce_checkout_create_order_line_item', 'wise_save_reolok_summary_to_order', 10, 4);
function wise_save_reolok_summary_to_order($item, $cart_item_key, $values, $order) {
    if (isset($values['calculated_data'])) {
        $item->add_meta_data('_calculated_data', $values['calculated_data']);
    }
}

// Display in order details and emails
add_action('woocommerce_order_item_meta_end', 'wise_display_reolok_summary_in_order', 10, 4);
function wise_display_reolok_summary_in_order($item_id, $item, $order, $plain_text) {
    
    $product_id = $item->get_product_id();
    $calculator_type = wise_get_calculator_type($product_id);
    
    $calculated_data = $item->get_meta('_calculated_data');
    if ($calculated_data && is_array($calculated_data)) {
        $wallType = isset($calculated_data['wallType']) ? $calculated_data['wallType'] : '';
        $wallLabel = isset($calculated_data['wallLabel']) ? $calculated_data['wallLabel'] : null;
        $m2Rate = isset($calculated_data['m2Rate']) ? "$" . $calculated_data['m2Rate'] : '$0.00';
        $totalAreaM2 = isset($calculated_data['totalAreaM2']) ? $calculated_data['totalAreaM2'] : '0.00';
        
        if($calculator_type === 'icf'){
            $length = isset($calculated_data['length']) ? $calculated_data['length'] : '0';
            $height = isset($calculated_data['height']) ? $calculated_data['height'] : '0';
            $dimensions = "{$length}m × {$height}m";
            
            if ($plain_text) {
                // For plain text emails
                echo "\nWall Type: " . $wallType;
                echo isset($calculated_data['wallLabel']) ? "\nWall Label/Description: " . $calculated_data['wallLabel'] : '';
                echo "\nFull Blocks: " . $calculated_data['fullBlocks'];
                echo "\nCorner Blocks: " . $calculated_data['cnrBlocks'];
                echo "\nC Channel (m): " . $calculated_data['cChannel'];
                echo "\nDimensions: " . $dimensions;
                echo "\nm² Rate: " . $m2Rate;
                echo "\nTotal Area: " . $totalAreaM2;
            } else {
                // For HTML display
                echo '<div class="reolok-summary" style="margin-top: 10px; padding: 10px; background: #f8f8f8; border-radius: 4px;">';
                echo '<h4 style="margin: 0 0 8px 0;">FormPro® ICF Details:</h4>';
                echo '<table style="width: 100%; border-collapse: collapse;">';
                
                echo '<tr><td style="padding: 4px; border-bottom: 1px solid #ddd;"><strong>Wall Type:</strong></td><td style="padding: 4px; border-bottom: 1px solid #ddd;">' . $wallType . '</td></tr>';
                
                echo $wallLabel ? '<tr><td style="padding: 4px; border-bottom: 1px solid #ddd;"><strong>Wall Label/Description:</strong></td><td style="padding: 4px; border-bottom: 1px solid #ddd;">' . $wallLabel . '</td></tr>' : '';
                
                echo '<tr><td style="padding: 4px; border-bottom: 1px solid #ddd;"><strong>Full Blocks:</strong></td><td style="padding: 4px; border-bottom: 1px solid #ddd;">' . $calculated_data['fullBlocks'] . '</td></tr>';
                
                echo '<tr><td style="padding: 4px; border-bottom: 1px solid #ddd;"><strong>Corner Blocks:</strong></td><td style="padding: 4px; border-bottom: 1px solid #ddd;">' . $calculated_data['cnrBlocks'] . '</td></tr>';
                
                echo '<tr><td style="padding: 4px; border-bottom: 1px solid #ddd;"><strong>C Channel (m):</strong></td><td style="padding: 4px; border-bottom: 1px solid #ddd;">' . $calculated_data['cChannel'] . '</td></tr>';
                
                echo '<tr><td style="padding: 4px;"><strong>Dimensions:</strong></td><td style="padding: 4px;">' . $dimensions . '</td></tr>';
                
                echo '<tr><td style="padding: 4px;"><strong>m<sup>2</sup> Rate:</strong></td><td style="padding: 4px;">' . $m2Rate . '</td></tr>';
                
                echo '<tr><td style="padding: 4px;"><strong>Total Area:</strong></td><td style="padding: 4px;">' . $totalAreaM2 . '</td></tr>';
                
                echo '</table>';
                echo '</div>';
            }           
        }elseif($calculator_type === 'reolok'){
            if ($plain_text) {
                // For plain text emails
                echo "\nWall Type: " . $wallType;
                echo $wallLabel ? "\nWall Label/Description: " . $wallLabel : '';
                echo "\nHeight: " . $calculated_data['height'];
                echo "\nCassette Qty: " . $calculated_data['cassetteQty'];
                echo "\nEndcap Length: " . $calculated_data['endcapLength'];
                echo "\nBottom Track: " . $calculated_data['bottomTrack'];
                echo "\nm² Rate: " . $m2Rate;
                echo "\nTotal Area: " . $totalAreaM2;
            } else {
                // For HTML display
                echo '<div class="reolok-summary" style="margin-top: 10px; padding: 10px; background: #f8f8f8; border-radius: 4px;">';
                echo '<h4 style="margin: 0 0 8px 0;">ReoLok Details:</h4>';
                echo '<table style="width: 100%; border-collapse: collapse;">';
                
                echo '<tr><td style="padding: 4px; border-bottom: 1px solid #ddd;"><strong>Wall Type:</strong></td><td style="padding: 4px; border-bottom: 1px solid #ddd;">' . $wallType . '</td></tr>';
                
                echo $wallLabel ? '<tr><td style="padding: 4px; border-bottom: 1px solid #ddd;"><strong>Wall Label/Description:</strong></td><td style="padding: 4px; border-bottom: 1px solid #ddd;">' . $wallLabel . '</td></tr>' : '';
                
                echo '<tr><td style="padding: 4px; border-bottom: 1px solid #ddd;"><strong>Height:</strong></td><td style="padding: 4px; border-bottom: 1px solid #ddd;">' . $calculated_data['height'] . '</td></tr>';
                
                echo '<tr><td style="padding: 4px; border-bottom: 1px solid #ddd;"><strong>Cassette Qty:</strong></td><td style="padding: 4px; border-bottom: 1px solid #ddd;">' . $calculated_data['cassetteQty'] . '</td></tr>';
                
                echo '<tr><td style="padding: 4px; border-bottom: 1px solid #ddd;"><strong>Endcap Length:</strong></td><td style="padding: 4px; border-bottom: 1px solid #ddd;">' . $calculated_data['endcapLength'] . '</td></tr>';
                
                echo '<tr><td style="padding: 4px;"><strong>Bottom Track:</strong></td><td style="padding: 4px;">' . $calculated_data['bottomTrack'] . '</td></tr>';
                
                echo '<tr><td style="padding: 4px;"><strong>m<sup>2</sup> Rate:</strong></td><td style="padding: 4px;">' . $m2Rate  . '</td></tr>';
                
                echo '<tr><td style="padding: 4px;"><strong>Total Area:</strong></td><td style="padding: 4px;">' . $totalAreaM2 . '</td></tr>';
                
                echo '</table>';
                echo '</div>';
            }           
        }
    }
}

// Display in admin order details
add_action('woocommerce_after_order_itemmeta', 'wise_display_reolok_summary_in_admin', 10, 3);
function wise_display_reolok_summary_in_admin($item_id, $item, $product) {
    $summary = $item->get_meta('_reolok_summary');
    
    if ($summary && is_array($summary)) {
        echo '<div class="reolok-summary" style="margin-top: 10px; padding: 10px; background: #f0f0f1; border: 1px solid #ccd0d4; border-radius: 4px;">';
        echo '<h4 style="margin: 0 0 8px 0;">ReoLok Details:</h4>';
        echo '<table style="width: 100%; border-collapse: collapse;">';
        echo '<tr><td style="padding: 4px; border-bottom: 1px solid #ddd;"><strong>Height:</strong></td><td style="padding: 4px; border-bottom: 1px solid #ddd;">' . $summary['height'] . '</td></tr>';
        echo '<tr><td style="padding: 4px; border-bottom: 1px solid #ddd;"><strong>Cassette Qty:</strong></td><td style="padding: 4px; border-bottom: 1px solid #ddd;">' . $summary['cassetteQty'] . '</td></tr>';
        echo '<tr><td style="padding: 4px; border-bottom: 1px solid #ddd;"><strong>Endcap Length:</strong></td><td style="padding: 4px; border-bottom: 1px solid #ddd;">' . $summary['endcapLength'] . '</td></tr>';
        echo '<tr><td style="padding: 4px;"><strong>Bottom Track:</strong></td><td style="padding: 4px;">' . $summary['bottomTrack'] . '</td></tr>';
        echo '<tr><td style="padding: 4px;"><strong>Volume:</strong></td><td style="padding: 4px;">' . $summary['volume'] . '</td></tr>';
        echo '<tr><td style="padding: 4px;"><strong>m² Rate:</strong></td><td style="padding: 4px;">' . $summary['m2Rate'] . '</td></tr>';
        echo '<tr><td style="padding: 4px;"><strong>Total Area (m²):</strong></td><td style="padding: 4px;">' . $summary['totalAreaM2'] . '</td></tr>';
        echo '</table>';
        echo '</div>';
    }
}

// Providing variations data on checkout page to use for calculations
add_action('woocommerce_checkout_before_order_review', function() {
    if (WC()->cart && !WC()->cart->is_empty()) {
        $variation_data = [];

        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['calculated_data'])) {
                $calculated_data = $cart_item['calculated_data'];

                $raw_summary_data_reolok = isset($calculated_data['rawSummaryDataReolok']) ? $calculated_data['rawSummaryDataReolok'] : [];
                $raw_summary_data_icf = isset($calculated_data['rawSummaryDataIcf']) ? $calculated_data['rawSummaryDataIcf'] : [];
                
                if(!empty($raw_summary_data_reolok)){
                    $variation_data['summary_reolok'][] = $raw_summary_data_reolok;
                }
                
                if(!empty($raw_summary_data_icf)){
                    $variation_data['summary_icf'][] = $raw_summary_data_icf;
                }
            }
        }

        // Output variation data as hidden JSON
        echo '<textarea id="checkout_variations_data" style="display:none;">' 
            . esc_textarea(json_encode($variation_data)) 
            . '</textarea>';
    }
});