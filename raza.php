<?php
// ================
// RAZA.PHP
// ================

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

add_filter('woocommerce_states', function($states) {
    if (isset($states['AU']['ACT'])) {
        unset($states['AU']['ACT']);
    }
    return $states;
});

// Changing Suburb label to City
add_filter('woocommerce_get_country_locale', 'wise_fbm_change_suburb_label_to_city');
function wise_fbm_change_suburb_label_to_city($locale) {
    // 🌍 Apply globally
    foreach ($locale as $country_code => $fields) {
        if (isset($locale[$country_code]['city']['label'])) {
            $locale[$country_code]['city']['label'] = __('City', 'wise-cost-calculator');
        }
    }
    return $locale;
}

add_action('admin_menu', 'wise_shipping_destinations_admin_menu');
function wise_shipping_destinations_admin_menu() {
    add_submenu_page(
        'options-general.php',       // Parent menu (Settings)
        'Shipping Destinations',     // Page title
        'Shipping Destinations',     // Menu title
        'manage_options',            // Capability
        'shipping-destinations',     // Menu slug
        'wise_shipping_destinations_admin_page' // Callback
    );
}

add_action('admin_init', 'wise_shipping_destinations_register_settings');
function wise_shipping_destinations_register_settings() {
    register_setting('wise_shipping_destinations_group', 'shipping_destinations');
}

function wise_shipping_destinations_admin_page() {
    $data = get_option('shipping_destinations', []);
    ?>
    <div class="wrap">
        <h1>Shipping Destinations</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wise_shipping_destinations_group'); ?>
            
            <table class="widefat" id="shipping-table" style="max-width:800px;">
                <thead>
                    <tr>
                        <th rowspan="2">State</th>
                        <th rowspan="2">City</th>
                        <th colspan="2">Full 40ft ($)</th>
                        <th rowspan="2">Remove</th>
                    </tr>
                    <tr>
                        <th>ICF</th>
                        <th>Reolok</th>
                    </tr>
                </thead>
                <tbody id="shipping-rows">
                    <?php if (!empty($data)) : ?>
                        <?php foreach ($data as $index => $row) : ?>
                            <tr>
                                <td><input type="text" name="shipping_destinations[<?php echo $index; ?>][state]" value="<?php echo esc_attr($row['state']); ?>" /></td>

                                <td><input type="text" name="shipping_destinations[<?php echo $index; ?>][city]" value="<?php echo esc_attr($row['city']); ?>" /></td>

                                <td><input type="text" name="shipping_destinations[<?php echo $index; ?>][full40_icf]" value="<?php echo esc_attr($row['full40_icf']); ?>" /></td>

                                <td><input type="text" name="shipping_destinations[<?php echo $index; ?>][full40_reolok]" value="<?php echo esc_attr($row['full40_reolok']); ?>" /></td>

                                <td><button type="button" class="button remove-row">X</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <p>
                <button type="button" class="button button-primary" id="add-row">+ Add Destination</button>
            </p>

            <?php submit_button('Save Shipping Data'); ?>
        </form>
    </div>
    <style>
        #shipping-table thead th {
            text-align: center;
            border: 2px solid lightcoral;
            border-collapse: collapse !important;
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let addBtn = document.getElementById('add-row');
        let tableBody = document.getElementById('shipping-rows');
        let rowCount = tableBody.querySelectorAll('tr').length;

        addBtn.addEventListener('click', function() {
            let newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td><input type="text" name="shipping_destinations[${rowCount}][state]" /></td>
                <td><input type="text" name="shipping_destinations[${rowCount}][city]" /></td>
                <td><input type="text" name="shipping_destinations[${rowCount}][full40_icf]" /></td>
                <td><input type="text" name="shipping_destinations[${rowCount}][full40_reolok]" /></td>
                <td><button type="button" class="button remove-row">&times;</button></td>
            `;
            tableBody.appendChild(newRow);
            rowCount++;
        });

        tableBody.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-row')) {
                e.target.closest('tr').remove();
            }
        });
    });
    </script>
    <?php
}