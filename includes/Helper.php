<?php

namespace WeLabs\WooPartialCheckout;

/**
 * Helper class for common functionality
 * 
 * @since 0.0.1
 */
class Helper {

    /**
     * Log a message to WooCommerce logs
     *
     * @since 0.0.1
     * 
     * @param string $message Message to log
     * @param string $type One of: emergency, alert, critical, error, warning, notice, info, debug
     * @param string $context_source Source identifier for the log (default: woo-partial-checkout)
     * 
     * @return void
     */
    public static function log($message, $type = 'debug', $context_source = 'woo-partial-checkout') {
        if (!function_exists('wc_get_logger')) {
            return;
        }

        $logger = wc_get_logger();
        $context = array('source' => $context_source);

        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }

        $logger->log($type, $message, $context);
    }

    /**
     * Log debug message
     *
     * @since 0.0.1
     * 
     * @param string $message Message to log
     * @param string $context_source Source identifier for the log
     * 
     * @return void
     */
    public static function debug($message, $context_source = 'woo-partial-checkout') {
        self::log($message, 'debug', $context_source);
    }

    /**
     * Log error message
     *
     * @since 0.0.1
     * 
     * @param string $message Message to log
     * @param string $context_source Source identifier for the log
     * 
     * @return void
     */
    public static function error($message, $context_source = 'woo-partial-checkout') {
        self::log($message, 'error', $context_source);
    }

    /**
     * Log info message
     *
     * @since 0.0.1
     * 
     * @param string $message Message to log
     * @param string $context_source Source identifier for the log
     * 
     * @return void
     */
    public static function info($message, $context_source = 'woo-partial-checkout') {
        self::log($message, 'info', $context_source);
    }

    /**
     * Log warning message
     *
     * @since 0.0.1
     * 
     * @param string $message Message to log
     * @param string $context_source Source identifier for the log
     * 
     * @return void
     */
    public static function warning($message, $context_source = 'woo-partial-checkout') {
        self::log($message, 'warning', $context_source);
    }
} 