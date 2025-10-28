<?php
/**
 * Plugin Name: Choozy
 * Plugin URI:  https://wordpress.org/plugins/choozy/
 * Description: Selective Checkout for WooCommerce.
 * Version: 0.0.1
 * Author: Aminur Islam
 * Author URI: https://wordpress.org/plugins/choozy/
 * Text Domain: choozy
 * WC requires at least: 5.0.0
 * Domain Path: /languages/
 * Requires Plugins: woocommerce
 * License: GPL2
 */
use WeLabs\Choozy\Choozy;

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'CHOOZY_FILE' ) ) {
    define( 'CHOOZY_FILE', __FILE__ );
}

if ( ! defined( 'CHOOZY_BASENAME' ) ) {
    define( 'CHOOZY_BASENAME', plugin_basename( __FILE__ ) );
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Load Choozy Plugin when all plugins loaded
 *
 * @return \WeLabs\Choozy\Choozy
 */
function welabs_choozy() {
    return Choozy::init();
}

// Lets Go....
welabs_choozy();
