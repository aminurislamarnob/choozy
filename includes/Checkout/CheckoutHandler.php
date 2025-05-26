<?php

namespace WeLabs\WooPartialCheckout\Checkout;

use WeLabs\WooPartialCheckout\Base\SessionHandler;
use WeLabs\WooPartialCheckout\Helper;

/**
 * Class CheckoutHandler
 * 
 * Handles the checkout process for selected items
 * 
 * @since 0.0.1
 */
class CheckoutHandler {

    /**
     * Initialize the checkout handler
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
        // Filter cart items before checkout
        add_action('woocommerce_checkout_create_order', [$this, 'filter_checkout_items'], 10);
        
        // Preserve unselected items after order completion
        add_action('woocommerce_thankyou', [$this, 'preserve_unselected_items'], 5);
        
        // Add notice for partial checkout
        add_action('woocommerce_before_checkout_form', [$this, 'add_partial_checkout_notice']);
        
        // Prevent checkout if no items selected
        add_action('woocommerce_checkout_process', [$this, 'validate_selected_items']);
        
        // Filter items shown on checkout page
        add_filter('woocommerce_checkout_cart_item_visible', [$this, 'filter_checkout_item_visible'], 10, 3);
        
        // Filter cart item quantity on checkout
        add_filter('woocommerce_checkout_cart_item_quantity', [$this, 'filter_checkout_item_quantity'], 10, 3);
        
        // Modify order item meta and filter items
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'handle_order_line_item'], 20, 4);
        
        // Filter order items before total calculation
        add_action('woocommerce_checkout_update_order_meta', [$this, 'update_order_meta'], 10, 2);
    }

    /**
     * Filter items during checkout creation
     *
     * @since 0.0.1
     * 
     * @param \WC_Order $order Order object
     * 
     * @return void
     */
    public function filter_checkout_items($order) {
        if (!SessionHandler::has_selected_items()) {
            return;
        }

        // Store original cart for later restoration
        $original_cart = WC()->cart->get_cart_contents();
        
        // Get selected items before they're cleared
        $selected_items = SessionHandler::get_selected_items();
        
        // Store both original cart and selected items in order meta
        $order->update_meta_data('_wpc_original_cart', $original_cart);
        $order->update_meta_data('_wpc_selected_items', $selected_items);
        $order->update_meta_data('_wpc_is_partial_checkout', 'yes');
    }

    /**
     * Handle order line item creation and filtering
     *
     * @since 0.0.1
     * 
     * @param \WC_Order_Item_Product $item Order item object
     * @param string $cart_item_key Cart item key
     * @param array $cart_item Cart item data
     * @param \WC_Order $order Order object
     * 
     * @return void
     */
    public function handle_order_line_item($item, $cart_item_key, $cart_item, $order) {
        if (!SessionHandler::has_selected_items()) {
            return;
        }

        if (!SessionHandler::is_item_selected($cart_item_key)) {
            // Remove the item from the order
            $item->set_quantity(0);
            $item->save();
            
            // Mark item for removal
            $item->add_meta_data('_wpc_remove_item', 'yes', true);
        } else {
            // Add meta for selected items
            $item->add_meta_data('_wpc_partial_checkout', 'yes', true);
        }
    }

    /**
     * Update order meta and remove unselected items
     *
     * @since 0.0.1
     * 
     * @param int $order_id Order ID
     * @param array $data Posted data
     * 
     * @return void
     */
    public function update_order_meta($order_id, $data) {
        if (!SessionHandler::has_selected_items()) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Remove items marked for removal
        foreach ($order->get_items() as $item_id => $item) {
            if ($item->get_meta('_wpc_remove_item') === 'yes') {
                wc_delete_order_item($item_id);
            }
        }

        // Force order to recalculate totals
        $order->calculate_totals();
    }

    /**
     * Preserve unselected items after order completion
     *
     * @since 0.0.1
     * 
     * @param int $order_id Order ID
     * 
     * @return void
     */
    public function preserve_unselected_items($order_id) {
        // Get the order
        $order = wc_get_order($order_id);
        if (!$order || $order->get_meta('_wpc_is_partial_checkout') !== 'yes') {
            return;
        }

        // Check if we've already processed this order
        if ($order->get_meta('_wpc_items_preserved') === 'yes') {
            return;
        }

        // Get original cart data and selected items from order meta
        $original_cart = $order->get_meta('_wpc_original_cart');
        $selected_items = $order->get_meta('_wpc_selected_items');

        if (empty($original_cart) || empty($selected_items)) {
            return;
        }

        try {
            // Make sure WC is loaded
            if (!function_exists('WC') || !WC()->cart) {
                return;
            }

            // Clear current cart
            WC()->cart->empty_cart(true);

            // Track restored items
            $restored_items = array();

            // Restore unselected items
            foreach ($original_cart as $cart_item_key => $cart_item) {
                if (!isset($selected_items[$cart_item_key])) {
                    $product_id = $cart_item['product_id'];
                    $variation_id = isset($cart_item['variation_id']) ? $cart_item['variation_id'] : 0;
                    $variation = isset($cart_item['variation']) ? $cart_item['variation'] : array();
                    $cart_item_data = isset($cart_item['cart_item_data']) ? $cart_item['cart_item_data'] : array();

                    // Verify product exists
                    $product = wc_get_product($product_id);
                    if (!$product) {
                        Helper::warning("Product not found: " . $product_id);
                        continue;
                    }

                    // Add to cart
                    $new_key = WC()->cart->add_to_cart(
                        $product_id,
                        $cart_item['quantity'],
                        $variation_id,
                        $variation,
                        $cart_item_data
                    );

                    if ($new_key) {
                        $restored_items[] = $new_key;
                    } else {
                        Helper::error("Failed to add item to cart: " . $product_id);
                    }
                }
            }

            // Force cart save
            WC()->cart->set_session();
            WC()->session->save_data();

            // Mark order as processed
            $order->update_meta_data('_wpc_items_preserved', 'yes');
            $order->save();

            // Clear selected items from session
            SessionHandler::clear_selected_items();

            // Add notice if items were restored
            if (!empty($restored_items)) {
                $count = count($restored_items);
                wc_add_notice(
                    sprintf(
                        /* translators: %d: number of items */
                        _n(
                            '%d unselected item has been kept in your cart for later purchase.',
                            '%d unselected items have been kept in your cart for later purchase.',
                            $count,
                            'woo-partial-checkout'
                        ),
                        $count
                    ),
                    'success'
                );
            }

        } catch (\Exception $e) {
            Helper::error("Error in preserve_unselected_items: " . $e->getMessage());
            wc_add_notice(__('Error preserving unselected items.', 'woo-partial-checkout'), 'error');
        }
    }

    /**
     * Add notice for partial checkout
     *
     * @since 0.0.1
     * 
     * @return void
     */
    public function add_partial_checkout_notice() {
        if (!SessionHandler::has_selected_items()) {
            return;
        }

        $selected_count = count(SessionHandler::get_selected_items());
        $total_items = count(WC()->cart->get_cart());

        if ($selected_count < $total_items) {
            wc_print_notice(
                sprintf(
                    /* translators: 1: selected items count, 2: total items count */
                    __('You are checking out with %1$d out of %2$d items from your cart. The remaining items will stay in your cart for later purchase.', 'woo-partial-checkout'),
                    $selected_count,
                    $total_items
                ),
                'notice'
            );
        }
    }

    /**
     * Validate that items are selected before checkout
     *
     * @since 0.0.1
     * 
     * @return void
     */
    public function validate_selected_items() {
        if (!SessionHandler::has_selected_items()) {
            wc_add_notice(__('Please select at least one item for checkout.', 'woo-partial-checkout'), 'error');
        }
    }

    /**
     * Filter visibility of cart items on checkout page
     *
     * @since 0.0.1
     * 
     * @param bool $visible Whether the cart item should be visible
     * @param array $cart_item Cart item data
     * @param string $cart_item_key Cart item key
     * 
     * @return bool
     */
    public function filter_checkout_item_visible($visible, $cart_item, $cart_item_key) {
        if (!SessionHandler::has_selected_items()) {
            return $visible;
        }

        return SessionHandler::is_item_selected($cart_item_key);
    }

    /**
     * Filter cart item quantity shown on checkout
     *
     * @since 0.0.1
     * 
     * @param string $quantity Item quantity HTML
     * @param array $cart_item Cart item data
     * @param string $cart_item_key Cart item key
     * 
     * @return string
     */
    public function filter_checkout_item_quantity($quantity, $cart_item, $cart_item_key) {
        if (!SessionHandler::is_item_selected($cart_item_key)) {
            return '';
        }
        return $quantity;
    }
} 