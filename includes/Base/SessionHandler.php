<?php

namespace WeLabs\WooPartialCheckout\Base;

/**
 * Class SessionHandler
 * 
 * Handles session management for the plugin
 * 
 * @since 0.0.1
 */
class SessionHandler {

    /**
     * Session key for selected items
     */
    const SELECTED_ITEMS_KEY = 'wpc_selected_items';

    /**
     * Get selected items from session
     *
     * @since 0.0.1
     * 
     * @return array
     */
    public static function get_selected_items() {
        if (!self::is_wc_session_available()) {
            return [];
        }

        return WC()->session->get(self::SELECTED_ITEMS_KEY, []);
    }

    /**
     * Set selected items in session
     *
     * @since 0.0.1
     * 
     * @param string $cart_item_key Cart item key
     * @param bool $selected Whether the item is selected
     * 
     * @return void
     */
    public static function set_selected_item($cart_item_key, $selected) {
        if (!self::is_wc_session_available()) {
            return;
        }

        $selected_items = self::get_selected_items();

        if ($selected) {
            $selected_items[$cart_item_key] = true;
        } else {
            unset($selected_items[$cart_item_key]);
        }

        WC()->session->set(self::SELECTED_ITEMS_KEY, $selected_items);
    }

    /**
     * Check if an item is selected
     *
     * @since 0.0.1
     * 
     * @param string $cart_item_key Cart item key
     * 
     * @return bool
     */
    public static function is_item_selected($cart_item_key) {
        if (!self::is_wc_session_available()) {
            return false;
        }

        $selected_items = self::get_selected_items();
        return isset($selected_items[$cart_item_key]);
    }

    /**
     * Check if any items are selected
     *
     * @since 0.0.1
     * 
     * @return bool
     */
    public static function has_selected_items() {
        if (!self::is_wc_session_available()) {
            return false;
        }

        return !empty(self::get_selected_items());
    }

    /**
     * Clear all selected items
     *
     * @since 0.0.1
     * 
     * @return void
     */
    public static function clear_selected_items() {
        if (!self::is_wc_session_available()) {
            return;
        }

        WC()->session->set(self::SELECTED_ITEMS_KEY, []);
    }

    /**
     * Check if WooCommerce session is available
     *
     * @since 0.0.1
     * 
     * @return bool
     */
    private static function is_wc_session_available() {
        return isset(WC()->session) && WC()->session !== null;
    }
} 