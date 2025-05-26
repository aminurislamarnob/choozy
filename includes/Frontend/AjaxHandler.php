<?php

namespace WeLabs\WooPartialCheckout\Frontend;

use WeLabs\WooPartialCheckout\Base\SessionHandler;

/**
 * Class AjaxHandler
 * 
 * Handles AJAX requests for cart interactions
 * 
 * @since 0.0.1
 */
class AjaxHandler {

    /**
     * Initialize the AJAX handler
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
        add_action('wp_ajax_wpc_update_cart_selection', [$this, 'update_cart_selection']);
        add_action('wp_ajax_nopriv_wpc_update_cart_selection', [$this, 'update_cart_selection']);
    }

    /**
     * Update cart item selection
     *
     * @since 0.0.1
     * 
     * @return void
     */
    public function update_cart_selection() {
        if (!check_ajax_referer('wpc_cart_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Invalid security token sent.', 'woo-partial-checkout')]);
        }

        $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field($_POST['cart_item_key']) : '';
        $is_checked = isset($_POST['is_checked']) ? (bool) $_POST['is_checked'] : false;

        if (empty($cart_item_key)) {
            wp_send_json_error(['message' => __('Cart item key is required.', 'woo-partial-checkout')]);
        }

        // Update the selection state using SessionHandler
        SessionHandler::set_selected_item($cart_item_key, $is_checked);

        wp_send_json_success([
            'message' => __('Cart selection updated successfully.', 'woo-partial-checkout'),
            'cart_item_key' => $cart_item_key,
            'is_checked' => $is_checked,
        ]);
    }
} 