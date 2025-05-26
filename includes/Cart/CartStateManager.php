<?php

namespace WeLabs\WooPartialCheckout\Cart;

use WeLabs\WooPartialCheckout\Base\SessionHandler;

/**
 * Class CartStateManager
 * 
 * Handles cart state management
 * 
 * @since 0.0.1
 */
class CartStateManager {

    /**
     * Initialize the cart state manager
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
        // Handle cart updates
        add_action('woocommerce_cart_updated', [$this, 'handle_cart_update']);
        
        // Handle quantity changes
        add_action('woocommerce_after_cart_item_quantity_update', [$this, 'handle_quantity_update'], 10, 4);
        
        // Handle item removal
        add_action('woocommerce_cart_item_removed', [$this, 'handle_item_removal'], 10, 2);
        
        // Handle cart item restore
        add_action('woocommerce_cart_item_restored', [$this, 'handle_item_restore'], 10, 2);
        
        // Clear selections when cart is emptied
        add_action('woocommerce_cart_emptied', [$this, 'handle_cart_emptied']);
        
        // Save cart state on page unload
        add_action('wp_footer', [$this, 'add_state_persistence_script']);
    }

    /**
     * Handle cart updates
     *
     * @since 0.0.1
     * 
     * @return void
     */
    public function handle_cart_update() {
        $cart = WC()->cart->get_cart();
        $selected_items = SessionHandler::get_selected_items();

        // Remove selections for items that no longer exist in cart
        foreach ($selected_items as $cart_item_key => $selected) {
            if (!isset($cart[$cart_item_key])) {
                SessionHandler::set_selected_item($cart_item_key, false);
            }
        }
    }

    /**
     * Handle quantity updates
     *
     * @since 0.0.1
     * 
     * @param string $cart_item_key Cart item key
     * @param int $quantity New quantity
     * @param int $old_quantity Old quantity
     * @param WC_Cart $cart Cart object
     * 
     * @return void
     */
    public function handle_quantity_update($cart_item_key, $quantity, $old_quantity, $cart) {
        if ($quantity == 0) {
            SessionHandler::set_selected_item($cart_item_key, false);
        }
    }

    /**
     * Handle item removal
     *
     * @since 0.0.1
     * 
     * @param string $cart_item_key Cart item key
     * @param WC_Cart $cart Cart object
     * 
     * @return void
     */
    public function handle_item_removal($cart_item_key, $cart) {
        SessionHandler::set_selected_item($cart_item_key, false);
    }

    /**
     * Handle item restore
     *
     * @since 0.0.1
     * 
     * @param string $cart_item_key Cart item key
     * @param WC_Cart $cart Cart object
     * 
     * @return void
     */
    public function handle_item_restore($cart_item_key, $cart) {
        // Optionally restore selection state
        // For now, restored items start unselected
        SessionHandler::set_selected_item($cart_item_key, false);
    }

    /**
     * Handle cart emptied
     *
     * @since 0.0.1
     * 
     * @return void
     */
    public function handle_cart_emptied() {
        SessionHandler::clear_selected_items();
    }

    /**
     * Add JavaScript for state persistence
     *
     * @since 0.0.1
     * 
     * @return void
     */
    public function add_state_persistence_script() {
        if (!is_cart()) {
            return;
        }

        ?>
        <script type="text/javascript">
            jQuery(function($) {
                // Save state before page unload
                $(window).on('beforeunload', function() {
                    if ($('.wpc-item-checkbox:checked').length) {
                        // State will be maintained by SessionHandler
                        return;
                    }
                });

                // Handle browser back/forward
                $(window).on('pageshow', function(event) {
                    if (event.originalEvent.persisted) {
                        // Refresh the page to ensure correct state
                        window.location.reload();
                    }
                });
            });
        </script>
        <?php
    }
} 