<?php

namespace WeLabs\WooPartialCheckout\Base;

/**
 * Class Assets
 * 
 * Handles all asset loading for the plugin
 * 
 * @since 0.0.1
 */
class Assets {

    /**
     * Initialize the assets
     *
     * @since 0.0.1
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     *
     * @since 0.0.1
     * 
     * @return void
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'register_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'register_admin_assets']);
    }

    /**
     * Register frontend assets
     *
     * @since 0.0.1
     * 
     * @return void
     */
    public function register_frontend_assets() {
        // Only load on cart page
        if (!is_cart()) {
            return;
        }

        // Register styles
        wp_register_style(
            'wpc-cart-styles',
            plugins_url('assets/css/frontend/cart.css', WOO_PARTIAL_CHECKOUT_FILE),
            [],
            WOO_PARTIAL_CHECKOUT_PLUGIN_VERSION
        );

        // Register scripts
        wp_register_script(
            'wpc-cart-scripts',
            plugins_url('assets/js/frontend/cart.js', WOO_PARTIAL_CHECKOUT_FILE),
            ['jquery'],
            WOO_PARTIAL_CHECKOUT_PLUGIN_VERSION,
            true
        );

        // Localize script
        wp_localize_script('wpc-cart-scripts', 'wpcCart', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('wpc_cart_nonce'),
        ]);

        // Enqueue assets
        wp_enqueue_style('wpc-cart-styles');
        wp_enqueue_script('wpc-cart-scripts');
    }

    /**
     * Register admin assets
     *
     * @since 0.0.1
     * 
     * @return void
     */
    public function register_admin_assets() {
        $screen = get_current_screen();

        // Only load on specific admin pages if needed
        if (!$screen || !in_array($screen->id, $this->get_admin_screens())) {
            return;
        }

        // Register admin styles and scripts here
    }

    /**
     * Get admin screen IDs where assets should be loaded
     *
     * @since 0.0.1
     * 
     * @return array
     */
    private function get_admin_screens() {
        return [
            'woocommerce_page_wc-settings',
            // Add more screen IDs as needed
        ];
    }
} 