<?php

namespace WeLabs\WooPartialCheckout\Frontend;

use WeLabs\WooPartialCheckout\Base\SessionHandler;

/**
 * Class CartUI
 * 
 * Handles the frontend UI modifications for the cart
 * 
 * @since 0.0.1
 */
class CartUI {

    /**
     * Initialize the cart UI modifications
     *
     * @since 0.0.1
     * 
     * @return void
     */
    public function __construct() {
        add_action('woocommerce_after_cart_item_name', [$this, 'add_item_checkbox'], 10, 2);
    }

    /**
     * Add checkbox after cart item name
     *
     * @since 0.0.1
     * 
     * @param array $cart_item Cart item data
     * @param string $cart_item_key Cart item key
     * 
     * @return void
     */
    public function add_item_checkbox($cart_item, $cart_item_key) {
        $checked = SessionHandler::is_item_selected($cart_item_key) ? 'checked' : '';
        
        printf(
            '<div class="wpc-item-checkbox-wrapper">
                <label class="wpc-checkbox-label">
                    <input type="checkbox" 
                           class="wpc-item-checkbox" 
                           name="wpc_selected_items[%s]" 
                           value="1" 
                           data-cart-item-key="%s"
                           %s
                    >
                    <span class="wpc-checkbox-text">%s</span>
                </label>
            </div>',
            esc_attr($cart_item_key),
            esc_attr($cart_item_key),
            esc_attr($checked),
            esc_html__('Select for checkout', 'woo-partial-checkout')
        );
    }
} 