<?php

namespace WeLabs\WooPartialCheckout\Cart;

use WeLabs\WooPartialCheckout\Base\SessionHandler;

/**
 * Class CartCalculator
 * 
 * Handles cart calculations for selected items
 * 
 * @since 0.0.1
 */
class CartCalculator {

    /**
     * Initialize the cart calculator
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
        // Filter cart totals
        add_filter('woocommerce_calculated_total', [$this, 'calculate_selected_total'], 10, 2);
        
        // Filter subtotal
        add_filter('woocommerce_cart_subtotal', [$this, 'filter_cart_subtotal'], 10, 3);
        
        // Filter item subtotal
        add_filter('woocommerce_cart_item_subtotal', [$this, 'filter_item_subtotal'], 10, 3);
        
        // Filter shipping packages
        add_filter('woocommerce_cart_shipping_packages', [$this, 'filter_shipping_packages']);
        
        // Filter tax totals
        add_filter('woocommerce_cart_get_taxes', [$this, 'filter_cart_taxes']);
        
        // Filter coupon discounts
        add_filter('woocommerce_coupon_get_discount_amount', [$this, 'filter_coupon_discount'], 10, 5);
    }

    /**
     * Calculate total for selected items
     *
     * @since 0.0.1
     * 
     * @param float $total Original cart total
     * @param WC_Cart $cart Cart object
     * 
     * @return float
     */
    public function calculate_selected_total($total, $cart) {
        if (!SessionHandler::has_selected_items()) {
            return $total;
        }

        $selected_total = 0;
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (SessionHandler::is_item_selected($cart_item_key)) {
                $selected_total += $cart_item['line_total'];
            }
        }

        // Add shipping if needed
        $selected_total += $this->get_selected_shipping_total();

        // Add taxes
        $selected_total += $this->get_selected_tax_total();

        return $selected_total;
    }

    /**
     * Filter cart subtotal display
     *
     * @since 0.0.1
     * 
     * @param string $cart_subtotal Cart subtotal
     * @param bool $compound Is compound
     * @param WC_Cart $cart Cart object
     * 
     * @return string
     */
    public function filter_cart_subtotal($cart_subtotal, $compound, $cart) {
        if (!SessionHandler::has_selected_items()) {
            return $cart_subtotal;
        }

        $selected_subtotal = 0;
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (SessionHandler::is_item_selected($cart_item_key)) {
                $selected_subtotal += $cart_item['line_subtotal'];
            }
        }

        return wc_price($selected_subtotal);
    }

    /**
     * Filter individual item subtotal
     *
     * @since 0.0.1
     * 
     * @param string $subtotal Item subtotal
     * @param array $cart_item Cart item data
     * @param string $cart_item_key Cart item key
     * 
     * @return string
     */
    public function filter_item_subtotal($subtotal, $cart_item, $cart_item_key) {
        if (!SessionHandler::is_item_selected($cart_item_key)) {
            return '<span class="wpc-unselected">' . $subtotal . '</span>';
        }
        return $subtotal;
    }

    /**
     * Filter shipping packages to only include selected items
     *
     * @since 0.0.1
     * 
     * @param array $packages Shipping packages
     * 
     * @return array
     */
    public function filter_shipping_packages($packages) {
        if (!SessionHandler::has_selected_items()) {
            return $packages;
        }

        foreach ($packages as &$package) {
            foreach ($package['contents'] as $cart_item_key => $cart_item) {
                if (!SessionHandler::is_item_selected($cart_item_key)) {
                    unset($package['contents'][$cart_item_key]);
                }
            }
        }

        return $packages;
    }

    /**
     * Filter cart taxes for selected items
     *
     * @since 0.0.1
     * 
     * @param array $taxes Cart taxes
     * 
     * @return array
     */
    public function filter_cart_taxes($taxes) {
        if (!SessionHandler::has_selected_items()) {
            return $taxes;
        }

        $selected_taxes = [];
        $cart = WC()->cart;

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (SessionHandler::is_item_selected($cart_item_key)) {
                $tax_data = $cart_item['line_tax_data'];
                foreach ($tax_data['total'] as $tax_id => $tax) {
                    if (!isset($selected_taxes[$tax_id])) {
                        $selected_taxes[$tax_id] = 0;
                    }
                    $selected_taxes[$tax_id] += $tax;
                }
            }
        }

        return $selected_taxes;
    }

    /**
     * Filter coupon discount amount for selected items
     *
     * @since 0.0.1
     * 
     * @param float $discount Discount amount
     * @param float $discounting_amount Amount being discounted
     * @param array $cart_item Cart item being discounted
     * @param bool $single True if discounting individually
     * @param WC_Coupon $coupon Coupon object
     * 
     * @return float
     */
    public function filter_coupon_discount($discount, $discounting_amount, $cart_item, $single, $coupon) {
        if (!$cart_item || !isset($cart_item['key']) || !SessionHandler::is_item_selected($cart_item['key'])) {
            return 0;
        }
        return $discount;
    }

    /**
     * Get shipping total for selected items
     *
     * @since 0.0.1
     * 
     * @return float
     */
    private function get_selected_shipping_total() {
        $shipping_total = 0;
        $packages = WC()->shipping()->get_packages();
        
        foreach ($packages as $package) {
            if (isset($package['rates']) && !empty($package['contents'])) {
                $chosen_method = WC()->session->get('chosen_shipping_methods')[0] ?? '';
                if (isset($package['rates'][$chosen_method])) {
                    $shipping_total += $package['rates'][$chosen_method]->cost;
                }
            }
        }

        return $shipping_total;
    }

    /**
     * Get tax total for selected items
     *
     * @since 0.0.1
     * 
     * @return float
     */
    private function get_selected_tax_total() {
        $taxes = $this->filter_cart_taxes(WC()->cart->get_cart_contents_taxes());
        return array_sum($taxes);
    }
} 