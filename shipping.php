<?php
// ================
// SHIPPING.PHP
// ================

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// 1. Frontend script to auto-populate suburbs + shipping calculation
add_action('wp_footer', function() {
    if (!is_checkout()) return;
    
    $shipping_destinations = get_option('shipping_destinations');
    $suburb_map = [];
    if ($shipping_destinations && is_array($shipping_destinations)) {
        foreach($shipping_destinations as $destination){
            $suburb_map[$destination['state']] = $destination['city'];
        }
    }
    
    $icf_pallet_width = get_option('icf_pallet_width_m');
    $icf_pallet_height = get_option('icf_pallet_height_m');
    $icf_pallets_per_container = get_option('icf_pallets_per_container');
    $icf_per_pallet_load_markup = get_option('icf_per_pallet_load_markup');

    $reolok_pallet_width = get_option('reolok_pallet_width_m');
    $reolok_pallet_height = get_option('reolok_pallet_height_m');
    $reolok_pallets_per_container = get_option('reolok_pallets_per_container');
    $reolok_per_pallet_load_markup = get_option('reolok_per_pallet_load_markup');

    $shipping_data = [
        'icf' => [
            'pw' => $icf_pallet_width,
            'ph' => $icf_pallet_height,
            'ppc' => $icf_pallets_per_container,
            'pplm' => $icf_per_pallet_load_markup,
        ],
        'reolok' => [
            'pw' => $reolok_pallet_width,
            'ph' => $reolok_pallet_height,
            'ppc' => $reolok_pallets_per_container,
            'pplm' => $reolok_per_pallet_load_markup,
        ]
    ];

    $ajaxurl = admin_url('admin-ajax.php');
    ?>
    <script>
    jQuery(function($) {
        var suburbMap = <?php echo wp_json_encode($suburb_map) ?>;
        var ajaxURL = "<?php echo $ajaxurl ?>";
        var shippingData = <?php echo wp_json_encode($shipping_data); ?>;
        var shippingNonce = "<?php echo wp_create_nonce('update_shipping_nonce'); ?>";

        // ICF
        var palletWidthIcf = parseFloat(shippingData.icf.pw) || 0;
        var palletHeightIcf = parseFloat(shippingData.icf.ph) || 0;
        var palletsPerContainerIcf = parseFloat(shippingData.icf.ppc) || 0;
        var perPalletLoadMarkupIcf = parseFloat(shippingData.icf.pplm) || 0;

        // Reolok
        var palletWidthReolok = parseFloat(shippingData.reolok.pw) || 0;
        var palletHeightReolok = parseFloat(shippingData.reolok.ph) || 0;
        var palletsPerContainerRL = parseFloat(shippingData.reolok.ppc) || 0;
        var perPalletLoadMarkupRL = parseFloat(shippingData.reolok.pplm) || 0;
        
        function updateSuburbs() {
            var state = $("#billing_state").val();
            var $city = $("#billing_city");
            var state_shipping = $("#shipping_state").val();
            var $city_shipping = $("#shipping_city");

            if (!$city.length) return;

            if (state && suburbMap[state]) {
                $city.val(suburbMap[state]);
            } else {
                $city.val("");
            }

            if (state_shipping && suburbMap[state_shipping]) {
                $city_shipping.val(suburbMap[state_shipping]);
            } else {
                $city_shipping.val("");
            }
        }

        function reorderFields() {
            var $billing_state = $("#billing_state_field");
            var $billing_city = $("#billing_city_field");
            var $shipping_state = $("#shipping_state_field");
            var $shipping_city = $("#shipping_city_field");

            if (
                ($billing_state.length && $billing_city.length && $billing_state.index() > $billing_city.index()) ||
                ($shipping_state.length && $shipping_city.length && $shipping_state.index() > $shipping_city.index())
            ) {
                $billing_state.insertBefore($billing_city);
                $shipping_state.insertBefore($shipping_city);
            }
        }

        // Run both functions when checkout updates
        $(document.body).on("updated_checkout", function() {
            updateSuburbs();
            reorderFields();
        });

        // Run once on initial load
        updateSuburbs();
        reorderFields();

        // When state changes, update suburb list
        $(document).on("change", "select.state_select", function() {
            updateSuburbs();
        });
        
        $("#billing_city, #shipping_city").prop({
            "readonly": true,
            "contenteditable": false
        });

        /* Ajax - Shipping charges calculation on state/city change */
        $(document).on("change", "select.state_select", function(){
            let $selectedState = $(this).val()?.trim();
            if (!$selectedState) return;
            
            $.post(ajaxURL, {action: "wise_getShippingDestinationState", state: $selectedState}, res => {
                if(res.success && res.data){
                    let icf40FtPrice = Number(res.data.full40_icf),
                        reolok40FtPrice = Number(res.data.full40_reolok),
                        city = res.data.city,
                        state = res.data.state;

                    // Calculate totals
                    let volumeIcf = 0,
                        volumeReolok = 0;

                    // Get and extract variation data
                    let variationsData = $("#checkout_variations_data")?.text();
                    let {summary_icf, summary_reolok} = variationsData ? JSON.parse(variationsData) : [];

                    $.each(summary_icf || [], (i, variation) => {
                        const volume = parseFloat(variation.volume);
                        volumeIcf += volume;
                    });

                    $.each(summary_reolok || [], (i, variation) => {
                        const volume = parseFloat(variation.volume);
                        volumeReolok += volume;
                    });

                    // ======== ICF
                    volumeIcf = parseFloat((volumeIcf).toFixed(3));
                    let totalPalletsIcf = calcTotalPallets(volumeIcf, palletWidthIcf, palletHeightIcf);
                    let containersIcf = parseFloat(calcContainers(totalPalletsIcf, palletsPerContainerIcf, perPalletLoadMarkupIcf));
                    let chargesIcf = parseFloat((containersIcf * icf40FtPrice).toFixed(2));
                    console.log('ICF');
                    const debugObj = {
                        ['Volume']: volumeIcf,
                        ['Pallets']: totalPalletsIcf,
                        ['Containers']: containersIcf,
                        ['Charges']: chargesIcf,
                    };
                    console.table(debugObj);

                    // ======== REOLOK
                    let totalPalletsRL = calcTotalPallets(volumeReolok, palletWidthReolok, palletHeightReolok);
                    let containersRL = calcContainers(totalPalletsRL, palletsPerContainerRL, perPalletLoadMarkupRL);
                    let chargesReolok = (containersRL * reolok40FtPrice).toFixed(2);
                    
                    doShipping(chargesIcf, chargesReolok);
                }
            });
        });

        function calcTotalPallets(totalVolume, palletWidth, palletHeight) {
            return Math.ceil(totalVolume / 1.2 / palletWidth / palletHeight);
        }

        function calcContainers(totalPallets, palletsPerContainer, perPalletLoadMarkup){
            return parseFloat(Math.floor(totalPallets / palletsPerContainer) + Math.min(1, (totalPallets / palletsPerContainer % 1) * perPalletLoadMarkup)).toFixed(3);
        }

        // Updated doShipping function - Use FEES approach (simpler)
        function doShipping(_icfCharges = 0, _reolokCharges = 0){
            let icfCharges = parseFloat(_icfCharges) || 0;
            let reolokCharges = parseFloat(_reolokCharges) || 0;
            
            // UPDATE WOOCOMMERCE SYSTEM - Add shipping as fees
            const payload = {
                action: "wise_update_shipping_fees",
                icf_cost: icfCharges,
                reolok_cost: reolokCharges,
                nonce: shippingNonce
            };
            console.log("Payload");
            console.table(payload);
            $.post(ajaxURL, payload, function(response) {
                if(response.success) {
                    // Force a complete checkout refresh
                    $(document.body).trigger('update_checkout');
                } else {
                    console.error('Failed to update shipping fees:', response);
                }
            }).fail(function(xhr, status, error) {
                console.error('AJAX error:', error);
            });
        }
    });
    </script>
    <?php
});

// 2. AJAX handler for getting shipping destination
function wise_getShippingDestinationState(){
    if(!isset($_POST['state'])) wp_send_json_error('State not received in ajax');
    $state = sanitize_text_field($_POST['state']);
    
    $shipping_destinations = get_option('shipping_destinations');
    $state_row = null;
    if ($shipping_destinations && is_array($shipping_destinations)) {
        foreach($shipping_destinations as $destination){
            if($destination['state'] === $state){
                $state_row = $destination;
            }
        }
    }
    wp_send_json_success($state_row);
}
add_action('wp_ajax_wise_getShippingDestinationState', 'wise_getShippingDestinationState');
add_action('wp_ajax_nopriv_wise_getShippingDestinationState', 'wise_getShippingDestinationState');

// 3. SIMPLIFIED FEE APPROACH - Use this instead of shipping method
add_action('woocommerce_cart_calculate_fees', 'wise_add_custom_shipping_fees', 20, 1);
function wise_add_custom_shipping_fees($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (!isset(WC()->session)) return;

    $icf_cost = WC()->session->get('icf_shipping_cost');
    $reolok_cost = WC()->session->get('reolok_shipping_cost');

    // Remove any existing fees with same names to avoid duplicates
    $existing_fees = $cart->get_fees();
    foreach ($existing_fees as $fee_key => $fee) {
        if (in_array($fee->name, array('FormPro® ICF EE Interstate Shipping', 'Reolok Interstate Shipping'))) {
            $cart->remove_fee($fee->name);
        }
    }

    // Add new fees
    if ($icf_cost && $icf_cost > 0) {
        $cart->add_fee('FormPro® ICF EE Interstate Shipping', floatval($icf_cost), false);
    }
    
    if ($reolok_cost && $reolok_cost > 0) {
        $cart->add_fee('Reolok Interstate Shipping', floatval($reolok_cost), false);
    }
}

// 4. AJAX handler to update shipping fees
add_action('wp_ajax_wise_update_shipping_fees', 'wise_update_shipping_fees');
add_action('wp_ajax_nopriv_wise_update_shipping_fees', 'wise_update_shipping_fees');
function wise_update_shipping_fees() {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'update_shipping_nonce')) {
        wp_send_json_error('Security verification failed');
    }
    
    $icf_cost = floatval($_POST['icf_cost']);
    $reolok_cost = floatval($_POST['reolok_cost']);
    
    // Store in WooCommerce session
    WC()->session->set('icf_shipping_cost', $icf_cost);
    WC()->session->set('reolok_shipping_cost', $reolok_cost);
    
    // Clear any chosen shipping methods to prevent conflicts
    WC()->session->set('chosen_shipping_methods', array());
    
    wp_send_json_success(array(
        'message' => 'Shipping fees updated',
        'icf_cost' => $icf_cost,
        'reolok_cost' => $reolok_cost,
        'total_cost' => $icf_cost + $reolok_cost
    ));
}

// 5. Remove other shipping methods when we have custom shipping
add_filter('woocommerce_package_rates', 'wise_remove_other_shipping_when_custom_exists', 100, 2);
function wise_remove_other_shipping_when_custom_exists($rates, $package) {
    $icf_cost = WC()->session->get('icf_shipping_cost');
    $reolok_cost = WC()->session->get('reolok_shipping_cost');
    
    // If we have custom shipping costs, remove all other shipping methods
    if (($icf_cost && $icf_cost > 0) || ($reolok_cost && $reolok_cost > 0)) {
        foreach ($rates as $rate_id => $rate) {
            // Keep only free shipping or keep all except others? Let's keep only flat rate for now
            if (strpos($rate_id, 'free_shipping') === false && strpos($rate_id, 'flat_rate') === false) {
                unset($rates[$rate_id]);
            }
        }
        
        // Add a minimal flat rate to satisfy WooCommerce
        if (empty($rates)) {
            $rates['minimal_flat_rate'] = new WC_Shipping_Rate(
                'minimal_flat_rate',
                'Shipping',
                0.01,
                array(),
                'flat_rate'
            );
        }
    }
    
    return $rates;
}

// 6. Save shipping breakdown to order meta
add_action('woocommerce_checkout_create_order', 'wise_save_custom_shipping_breakdown');
function wise_save_custom_shipping_breakdown($order) {
    $icf_cost = WC()->session->get('icf_shipping_cost');
    $reolok_cost = WC()->session->get('reolok_shipping_cost');
    
    if ($icf_cost || $reolok_cost) {
        $icf_cost = floatval($icf_cost) ?: 0;
        $reolok_cost = floatval($reolok_cost) ?: 0;
        
        $order->update_meta_data('_icf_shipping_cost', $icf_cost);
        $order->update_meta_data('_reolok_shipping_cost', $reolok_cost);
        $order->update_meta_data('_total_shipping_cost', $icf_cost + $reolok_cost);
        
        $breakdown = array();
        if ($icf_cost > 0) {
            $breakdown['icf'] = array(
                'label' => 'FormPro® ICF EE Interstate Shipping',
                'cost' => $icf_cost
            );
        }
        if ($reolok_cost > 0) {
            $breakdown['reolok'] = array(
                'label' => 'Reolok Interstate Shipping',
                'cost' => $reolok_cost
            );
        }
        $order->update_meta_data('_shipping_breakdown', $breakdown);
    }
}

// 7. Clear session data after order is created
add_action('woocommerce_checkout_order_created', 'wise_clear_custom_shipping_session');
function wise_clear_custom_shipping_session($order) {
    WC()->session->__unset('icf_shipping_cost');
    WC()->session->__unset('reolok_shipping_cost');
}

// 8. Display shipping breakdown in admin and customer views
add_action('woocommerce_admin_order_data_after_shipping_address', 'wise_display_shipping_breakdown_admin', 10, 1);
function wise_display_shipping_breakdown_admin($order) {
    $breakdown = $order->get_meta('_shipping_breakdown');
    if ($breakdown && is_array($breakdown)) {
        ?>
        <div class="order_data_column">
            <h3><?php _e('Shipping Breakdown', 'wise-cost-calculator'); ?></h3>
            <?php foreach ($breakdown as $item): ?>
                <p><strong><?php echo esc_html($item['label']); ?>:</strong> 
                   <?php echo wc_price($item['cost']); ?></p>
            <?php endforeach; ?>
        </div>
        <?php
    }
}

add_action('woocommerce_order_details_after_order_table', 'wise_display_shipping_breakdown_customer', 10, 1);
function wise_display_shipping_breakdown_customer($order) {
    $breakdown = $order->get_meta('_shipping_breakdown');
    if ($breakdown && is_array($breakdown)) {
        ?>
        <section class="woocommerce-shipping-breakdown">
            <h2><?php _e('Shipping Details', 'wise-cost-calculator'); ?></h2>
            <table class="woocommerce-table woocommerce-table--shipping-breakdown shop_table shipping_breakdown">
                <tbody>
                    <?php foreach ($breakdown as $item): ?>
                        <tr>
                            <th><?php echo esc_html($item['label']); ?></th>
                            <td><?php echo wc_price($item['cost']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <?php
    }
}

// 9. Clear shipping session when cart is emptied or products change
add_action('woocommerce_cart_emptied', 'wise_clear_shipping_session_on_cart_change');
add_action('woocommerce_remove_cart_item', 'wise_clear_shipping_session_on_cart_change');
add_action('woocommerce_before_cart_item_quantity_zero', 'wise_clear_shipping_session_on_cart_change');
add_action('woocommerce_cart_item_removed', 'wise_clear_shipping_session_on_cart_change');
add_action('woocommerce_cart_item_restored', 'wise_clear_shipping_session_on_cart_change');

function wise_clear_shipping_session_on_cart_change() {
    if (isset(WC()->session)) {
        WC()->session->__unset('icf_shipping_cost');
        WC()->session->__unset('reolok_shipping_cost');
    }
}

// 10. Also clear session when cart is updated via AJAX
add_action('wp_ajax_woocommerce_remove_from_cart', 'wise_clear_shipping_session_ajax', 5);
add_action('wp_ajax_nopriv_woocommerce_remove_from_cart', 'wise_clear_shipping_session_ajax', 5);
add_action('wp_ajax_woocommerce_update_cart', 'wise_clear_shipping_session_ajax', 5);
add_action('wp_ajax_nopriv_woocommerce_update_cart', 'wise_clear_shipping_session_ajax', 5);

function wise_clear_shipping_session_ajax() {
    if (isset(WC()->session)) {
        WC()->session->__unset('icf_shipping_cost');
        WC()->session->__unset('reolok_shipping_cost');
    }
}