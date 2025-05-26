<?php

namespace WeLabs\WooPartialCheckout;

use WeLabs\WooPartialCheckout\Frontend\CartUI;
use WeLabs\WooPartialCheckout\Frontend\AjaxHandler;
use WeLabs\WooPartialCheckout\Base\Assets;
use WeLabs\WooPartialCheckout\Cart\CartCalculator;
use WeLabs\WooPartialCheckout\Cart\CartStateManager;
use WeLabs\WooPartialCheckout\Checkout\CheckoutHandler;

/**
 * WooPartialCheckout class
 *
 * @class WooPartialCheckout The class that holds the entire WooPartialCheckout plugin
 */
final class WooPartialCheckout {

    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '0.0.1';

    /**
     * Instance of self
     *
     * @var WooPartialCheckout
     */
    private static $instance = null;

    /**
     * Holds various class instances
     *
     * @since 2.6.10
     *
     * @var array
     */
    private $container = [];

    /**
     * Constructor for the WooPartialCheckout class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     */
    private function __construct() {
        $this->define_constants();

        register_activation_hook( WOO_PARTIAL_CHECKOUT_FILE, [ $this, 'activate' ] );
        register_deactivation_hook( WOO_PARTIAL_CHECKOUT_FILE, [ $this, 'deactivate' ] );

        add_action( 'plugins_loaded', [ $this, 'init_plugin' ] );
        add_action( 'woocommerce_flush_rewrite_rules', [ $this, 'flush_rewrite_rules' ] );
    }

    /**
     * Initializes the WooPartialCheckout() class
     *
     * Checks for an existing WooPartialCheckout instance
     * and if it doesn't find one then create a new one.
     *
     * @return WooPartialCheckout
     */
    public static function init() {
        if ( self::$instance === null ) {
			self::$instance = new self();
		}

        return self::$instance;
    }

    /**
     * Magic getter to bypass referencing objects
     *
     * @since 2.6.10
     *
     * @param string $prop
     *
     * @return Class Instance
     */
    public function __get( $prop ) {
		if ( array_key_exists( $prop, $this->container ) ) {
            return $this->container[ $prop ];
		}
    }

    /**
     * Placeholder for activation function
     *
     * Nothing is being called here yet.
     */
    public function activate() {
        // Rewrite rules during woo_partial_checkout activation
        if ( $this->has_woocommerce() ) {
            $this->flush_rewrite_rules();
        }
    }

    /**
     * Flush rewrite rules after woo_partial_checkout is activated or woocommerce is activated
     *
     * @since 3.2.8
     */
    public function flush_rewrite_rules() {
        // fix rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Placeholder for deactivation function
     *
     * Nothing being called here yet.
     */
    public function deactivate() {     }

    /**
     * Define all constants
     *
     * @return void
     */
    public function define_constants() {
        defined( 'WOO_PARTIAL_CHECKOUT_PLUGIN_VERSION' ) || define( 'WOO_PARTIAL_CHECKOUT_PLUGIN_VERSION', $this->version );
        defined( 'WOO_PARTIAL_CHECKOUT_DIR' ) || define( 'WOO_PARTIAL_CHECKOUT_DIR', dirname( WOO_PARTIAL_CHECKOUT_FILE ) );
        defined( 'WOO_PARTIAL_CHECKOUT_INC_DIR' ) || define( 'WOO_PARTIAL_CHECKOUT_INC_DIR', WOO_PARTIAL_CHECKOUT_DIR . '/includes' );
        defined( 'WOO_PARTIAL_CHECKOUT_TEMPLATE_DIR' ) || define( 'WOO_PARTIAL_CHECKOUT_TEMPLATE_DIR', WOO_PARTIAL_CHECKOUT_DIR . '/templates' );
        defined( 'WOO_PARTIAL_CHECKOUT_PLUGIN_ASSET' ) || define( 'WOO_PARTIAL_CHECKOUT_PLUGIN_ASSET', plugins_url( 'assets', WOO_PARTIAL_CHECKOUT_FILE ) );
        defined( 'WOO_PARTIAL_CHECKOUT_PLUGIN_ADMIN_ASSET' ) || define( 'WOO_PARTIAL_CHECKOUT_PLUGIN_ADMIN_ASSET' , WOO_PARTIAL_CHECKOUT_PLUGIN_ASSET . '/admin' );
        defined( 'WOO_PARTIAL_CHECKOUT_PLUGIN_PUBLIC_ASSET' ) || define( 'WOO_PARTIAL_CHECKOUT_PLUGIN_PUBLIC_ASSET' , WOO_PARTIAL_CHECKOUT_PLUGIN_ASSET . '/public' );

        // give a way to turn off loading styles and scripts from parent theme
        defined( 'WOO_PARTIAL_CHECKOUT_LOAD_STYLE' ) || define( 'WOO_PARTIAL_CHECKOUT_LOAD_STYLE', true );
        defined( 'WOO_PARTIAL_CHECKOUT_LOAD_SCRIPTS' ) || define( 'WOO_PARTIAL_CHECKOUT_LOAD_SCRIPTS', true );
    }

    /**
     * Load the plugin after WP User Frontend is loaded
     *
     * @return void
     */
    public function init_plugin() {
        $this->includes();
        $this->init_hooks();

        do_action( 'woo_partial_checkout_loaded' );
    }

    /**
     * Initialize the actions
     *
     * @return void
     */
    public function init_hooks() {
        // initialize the classes
        add_action( 'init', [ $this, 'init_classes' ], 4 );
        add_action( 'plugins_loaded', [ $this, 'after_plugins_loaded' ] );
    }

    /**
     * Include all the required files
     *
     * @return void
     */
    public function includes() {
        // include_once STUB_PLUGIN_DIR . '/functions.php';
    }

    /**
     * Init all the classes
     *
     * @return void
     */
    public function init_classes() {
        if ( $this->is_woocommerce_active() ) {
            $this->container['assets'] = new Assets();
            $this->container['cart_ui'] = new CartUI();
            $this->container['ajax_handler'] = new AjaxHandler();
            $this->container['cart_calculator'] = new CartCalculator();
            $this->container['cart_state_manager'] = new CartStateManager();
            $this->container['checkout_handler'] = new CheckoutHandler();
        }
    }

    /**
     * Executed after all plugins are loaded
     *
     * At this point woo_partial_checkout Pro is loaded
     *
     * @since 2.8.7
     *
     * @return void
     */
    public function after_plugins_loaded() {
        // Initiate background processes and other tasks
        $this->load_textdomain();
    }

    /**
     * Check whether woocommerce is installed and active
     *
     * @since 2.9.16
     *
     * @return bool
     */
    public function has_woocommerce() {
        return class_exists( 'WooCommerce' );
    }

    /**
     * Check whether woocommerce is installed
     *
     * @since 3.2.8
     *
     * @return bool
     */
    public function is_woocommerce_installed() {
        return in_array( 'woocommerce/woocommerce.php', array_keys( get_plugins() ), true );
    }

    /**
     * Check if WooCommerce is active
     *
     * @since 0.0.1
     * 
     * @return bool
     */
    private function is_woocommerce_active() {
        return in_array(
            'woocommerce/woocommerce.php',
            apply_filters('active_plugins', get_option('active_plugins'))
        );
    }

    /**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', WOO_PARTIAL_CHECKOUT_FILE ) );
	}

    /**
     * Get the template file path to require or include.
     *
     * @param string $name
     * @return string
     */
    public function get_template( $name ) {
        $template = untrailingslashit( WOO_PARTIAL_CHECKOUT_TEMPLATE_DIR ) . '/' . untrailingslashit( $name );

        return apply_filters( 'woo-partial-checkout_template', $template, $name );
    }

    /**
     * Load plugin textdomain
     *
     * @since 0.0.1
     * 
     * @return void
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'woo-partial-checkout',
            false,
            dirname(plugin_basename(WOO_PARTIAL_CHECKOUT_FILE)) . '/languages'
        );
    }
}
