<?php
/**
 * Personalized Offers for Spacefast — Playground demo setup.
 *
 * Run by blueprint-pofsf.json after the product import (WXR-imports/pofsf-playground.xml):
 * 1. finishes the imported products (variable prices and stock, lookup tables);
 * 2. creates 20 fictional customers and 50 orders from WXR-imports/pofsf-customers-orders.json,
 *    dated relative to today, so the store's history is always recent;
 * 3. builds the customer profiles, captures the brand and creates demo offers.
 *
 * Every person, email and address in the data is fictional (example.com/.org/.net).
 */

if (!defined('ABSPATH')) {
    require_once '/wordpress/wp-load.php';
}

$data_file = WP_CONTENT_DIR . '/pofsf-customers-orders.json';
$data = json_decode((string) file_get_contents($data_file), true);

if (!is_array($data) || !function_exists('wc_create_order')) {
    echo "Demo data or WooCommerce missing.\n";
    return;
}

wp_set_current_user(1);
// No emails from a demo: order confirmations, new accounts, offers.
add_filter('pre_wp_mail', '__return_false');

global $wpdb;

/* 1. Products: variable prices and stock from their variations, lookup tables, SKU map. */
$skus = [];
foreach ($wpdb->get_results("SELECT p.ID, p.post_type, m.meta_value AS sku FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_sku' WHERE p.post_type IN ('product', 'product_variation') AND p.post_status = 'publish'") as $row) {
    $skus[(string) $row->sku] = (int) $row->ID;
}

$store = WC_Data_Store::load('product');
foreach (wc_get_products(['limit' => -1, 'status' => 'publish', 'type' => 'variable', 'return' => 'ids']) as $product_id) {
    WC_Product_Variable::sync($product_id);
}
foreach ($skus as $post_id) {
    if (method_exists($store, 'update_lookup_table')) {
        $store->update_lookup_table($post_id, 'wc_product_meta_lookup');
    }
    wc_delete_product_transients($post_id);
}

/* 2. Customers and orders. */
$now = time();
$users = [];

foreach ($data['customers'] as $c) {
    $login = sanitize_user(strtok($c['email'], '@'), true);
    $user_id = username_exists($login) ?: wp_insert_user([
        'user_login' => $login,
        'user_email' => $c['email'],
        'user_pass' => wp_generate_password(24),
        'first_name' => $c['first_name'],
        'last_name' => $c['last_name'],
        'display_name' => $c['first_name'] . ' ' . $c['last_name'],
        'role' => 'customer',
        'user_registered' => gmdate('Y-m-d H:i:s', $now - (int) $c['registered_days_ago'] * DAY_IN_SECONDS),
    ]);

    if (is_wp_error($user_id)) {
        continue;
    }

    $customer = new WC_Customer($user_id);
    foreach (['billing', 'shipping'] as $type) {
        $customer->{"set_{$type}_first_name"}($c['first_name']);
        $customer->{"set_{$type}_last_name"}($c['last_name']);
        $customer->{"set_{$type}_address_1"}($c['address_1']);
        $customer->{"set_{$type}_city"}($c['city']);
        $customer->{"set_{$type}_postcode"}($c['postcode']);
        $customer->{"set_{$type}_country"}($c['country']);
    }
    $customer->set_billing_email($c['email']);
    $customer->set_billing_phone($c['phone']);
    $customer->save();
    $users[$c['key']] = ['id' => $user_id] + $c;
}

$made = 0;
foreach ($data['orders'] as $i => $o) {
    $person = isset($o['customer']) ? ($users[$o['customer']] ?? null) : $o['guest'];

    if (null === $person) {
        continue;
    }

    $order = wc_create_order(['customer_id' => (int) ($person['id'] ?? 0)]);

    if (is_wp_error($order)) {
        continue;
    }

    foreach ($o['items'] as $item) {
        $product = isset($skus[$item['sku']]) ? wc_get_product($skus[$item['sku']]) : null;
        if ($product) {
            $order->add_product($product, (int) $item['qty']);
        }
    }

    $address = [
        'first_name' => $person['first_name'],
        'last_name' => $person['last_name'],
        'address_1' => $person['address_1'],
        'city' => $person['city'],
        'postcode' => $person['postcode'],
        'country' => $person['country'],
        'email' => $person['email'],
        'phone' => $person['phone'],
    ];
    $order->set_address($address, 'billing');
    $order->set_address(array_diff_key($address, ['email' => 1, 'phone' => 1]), 'shipping');
    $order->set_payment_method('bacs');
    $order->set_payment_method_title('Direct bank transfer');

    // A time of day that varies from order to order.
    $created = $now - (int) $o['days_ago'] * DAY_IN_SECONDS - (($i * 7919) % 36000);
    $order->set_date_created($created);
    $order->calculate_totals();

    if (in_array($o['status'], ['completed', 'processing', 'refunded'], true)) {
        $order->set_date_paid($created + HOUR_IN_SECONDS);
    }
    if ('completed' === $o['status']) {
        $order->set_date_completed($created + 2 * DAY_IN_SECONDS);
    }

    $order->set_status($o['status']);
    $order->save();
    $made++;
}

echo count($users) . " customers, {$made} orders.\n";

/* 3. The plugin: customer profiles, brand and demo offers. */
do_action('pofsf_backfill_profiles', 1);
do_action('pofsf_backfill_profiles', 2);

$run = static function (string $name, array $input) {
    $ability = function_exists('wp_get_ability') ? wp_get_ability('personalized-offers-for-spacefast/' . $name) : null;
    return $ability ? $ability->execute($input) : new WP_Error('pofsf_demo', 'Ability missing: ' . $name);
};

$run('lock-brand', []);

// Single offers for three of the best customers, composed from their profiles.
$best = array_values(array_filter(array_map(static fn ($key) => $users[$key]['id'] ?? 0, ['c01', 'c02', 'c03'])));
$single = $run('create-offers', ['user_ids' => $best]);
foreach (is_array($single) ? (array) ($single['offer_ids'] ?? []) : [] as $offer_id) {
    do_action('pofsf_build_offer', (int) $offer_id);
}

// A group offer for the "Best customers" segment and a public offer, with hand-picked products.
$pick = static function (array $skus_wanted) use ($skus): array {
    return array_values(array_filter(array_map(static fn ($sku) => isset($skus[$sku]) ? ['product_id' => $skus[$sku]] : null, $skus_wanted)));
};

$run('create-shared-offer', [
    'audience' => 'group',
    'segment' => 'loyal',
    'products' => $pick(['24-MB02', '24-WG088', '24-MG04', '24-UG05']),
    'overheadline' => 'For our best customers',
    'headline' => 'A thank-you weekend, just for you',
    'body' => 'You have been with us all year, so this weekend is on us: free shipping and a little extra on the gear our regulars love most.',
]);

$run('create-shared-offer', [
    'audience' => 'public',
    'products' => $pick(['24-MB04', '24-UG02', '24-MG01', '24-WG085']),
    'overheadline' => 'Winter training',
    'headline' => 'Gear up for the cold months',
    'body' => 'Bags, straps and watches for early runs and indoor sessions, with free shipping until the end of the month.',
]);

echo "Offers: " . (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pofsf_offers") . "\n";

@unlink($data_file);
