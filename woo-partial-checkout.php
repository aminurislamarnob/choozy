<?php
/**
 * Plugin Name: Woo Partial Checkout
 * Plugin URI:  https://welabs.dev
 * Description: Easily enhance your WooCommerce cart experience with Selective Checkout for WooCommerce. This plugin allows customers to selectively choose which items in their cart they want to purchase using a simple checkbox interface. Only the checked items will proceed to checkout, while the rest remain in the cart for future purchases.
 * Version: 0.0.1
 * Author: Aminur Islam Arnob
 * Author URI: https://welabs.dev
 * Text Domain: woo-partial-checkout
 * WC requires at least: 5.0.0
 * Domain Path: /languages/
 * Requires Plugins: woocommerce
 * License: GPL2
 */
use WeLabs\WooPartialCheckout\WooPartialCheckout;

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WOO_PARTIAL_CHECKOUT_FILE' ) ) {
    define( 'WOO_PARTIAL_CHECKOUT_FILE', __FILE__ );
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Load Woo_Partial_Checkout Plugin when all plugins loaded
 *
 * @return \WeLabs\WooPartialCheckout\WooPartialCheckout
 */
function welabs_woo_partial_checkout() {
    return WooPartialCheckout::init();
}

// Lets Go....
welabs_woo_partial_checkout();
