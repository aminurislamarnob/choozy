<?php

namespace WeLabs\Choozy;

/**
 * Cart class
 * 
 * Handles cart checkbox functionality
 */
class Cart {
	/**
	 * The constructor.
	 */
	public function __construct() {
		// Add checkbox before product name in cart
		add_action( 'woocommerce_after_cart_item_name', array( $this, 'add_checkbox_to_cart_item' ), 10, 2 );
		
		// Add select all checkbox in cart table header
		add_action( 'woocommerce_before_cart_table', array( $this, 'add_select_all_checkbox' ) );
		
		// Initialize cart item selection in session
		add_action( 'wp_loaded', array( $this, 'init_cart_selection' ) );
		
		// Filter cart contents to show only selected items on checkout
		add_action( 'woocommerce_check_cart_items', array( $this, 'filter_cart_for_checkout' ) );
		
		// Restore unselected items when returning to cart from checkout (early priority)
		add_action( 'template_redirect', array( $this, 'restore_unselected_items_on_cart' ), 5 );
		
		// Store unselected items before checkout
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'handle_unselected_items' ), 10, 1 );

		// Consider unselected items price 0 for calculation
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'exclude_unselected_from_totals' ), 10, 1 );

		// Display original prices for unselected items only UI End
		add_filter( 'woocommerce_cart_item_price', array( $this, 'display_original_price' ), 10, 3 );
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'display_original_subtotal' ), 10, 3 );
		// add_filter( 'woocommerce_cart_product_subtotal', array( $this, 'display_original_subtotal' ), 10, 3 );
	}

	/**
	 * Add checkbox before product name
	 *
	 * @param string $product_name
	 * @param array  $cart_item
	 * @param string $cart_item_key
	 *
	 * @return string
	 */
	public function add_checkbox_to_cart_item( $cart_item, $cart_item_key ) {

		// Check if item is selected (default to true for new items)
		$selected_items = $this->get_selected_items();
		$is_checked = in_array( $cart_item_key, $selected_items ) ? true : false;

		$checkbox = sprintf(
			'<input type="checkbox" class="choozy-cart-item-checkbox" name="choozy_cart_items[]" value="%s" data-cart-item-key="%s" %s />',
			esc_attr( $cart_item_key ),
			esc_attr( $cart_item_key ),
			checked( $is_checked, true, false )
		);

		echo $checkbox;
	}

	/**
	 * Add select all checkbox before cart table
	 *
	 * @return void
	 */
	public function add_select_all_checkbox() {
		// Count selected items
		$selected_items = $this->get_selected_items();
		$cart_contents = WC()->cart->get_cart();
		$total_items = count( $cart_contents );
		$selected_count = 0;

		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			if ( isset( $selected_items[ $cart_item_key ] ) && $selected_items[ $cart_item_key ] ) {
				$selected_count++;
			}
		}

		?>
		<div class="choozy-select-all-wrapper">
			<div class="choozy-select-controls">
				<label>
					<input type="checkbox" id="choozy-select-all" <?php checked( $selected_count, $total_items ); ?> />
					<span><?php esc_html_e( 'Select All Items', 'choozy' ); ?></span>
				</label>
				<span class="choozy-selected-count">
					<?php
					printf(
						/* translators: 1: selected items count, 2: total items count */
						esc_html__( '%1$d of %2$d items selected', 'choozy' ),
						esc_html( $selected_count ),
						esc_html( $total_items )
					);
					?>
				</span>
			</div>
			<?php if ( $selected_count < $total_items && $selected_count > 0 ) : ?>
				<div class="choozy-info-message">
					<span class="dashicons dashicons-info"></span>
					<?php esc_html_e( 'Only selected items will proceed to checkout. Unselected items will remain in your cart.', 'choozy' ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get selected items from session
	 *
	 * @return array
	 */
	public function get_selected_items() {
		if ( ! WC()->session ) {
			return array();
		}

		$selected_items = WC()->session->get( 'choozy_selected_items', array() );

		// If no selection data exists, mark all current cart items as selected
		if ( empty( $selected_items ) && WC()->cart && ! WC()->cart->is_empty() ) {
			$cart_contents = WC()->cart->get_cart();
			foreach ( $cart_contents as $cart_item_key => $cart_item ) {
				$selected_items[ $cart_item_key ] = true;
			}
			WC()->session->set( 'choozy_selected_items', $selected_items );
		}

		return $selected_items;
	}

	/**
	 * Initialize cart selection
	 *
	 * @return void
	 */
	public function init_cart_selection() {
		// Initialize session for all cart items if not exists
		if ( WC()->cart && ! WC()->cart->is_empty() ) {
			$this->get_selected_items();
		}
	}

	/**
	 * Handle unselected items before calculating totals
	 *
	 * @param object $cart WC_Cart object
	 *
	 * @return void
	 */
	public function handle_unselected_items( $cart ) {
		// Only process on checkout page, not on cart page or AJAX
		if ( ! is_checkout() || is_admin() || wp_doing_ajax() ) {
			return;
		}

		// Don't process if we're on cart page (double check)
		if ( is_cart() ) {
			return;
		}

		// Prevent running multiple times per request
		static $already_processed = false;
		if ( $already_processed ) {
			return;
		}

		// Get selected items
		$selected_items = $this->get_selected_items();
		$unselected_items = array();
		$cart_contents = $cart->get_cart();

		// Separate unselected items
		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			if( ! in_array( $cart_item_key, $selected_items ) ){
				$cart_item_data = $cart_item;
				unset(
					$cart_item_data['key'],
					$cart_item_data['product_id'],
					$cart_item_data['variation_id'],
					$cart_item_data['variation'],
					$cart_item_data['quantity'],
					$cart_item_data['data'],
					$cart_item_data['data_hash'],
					$cart_item_data['line_tax_data'],
					$cart_item_data['line_subtotal'],
					$cart_item_data['line_subtotal_tax'],
					$cart_item_data['line_total'],
					$cart_item_data['line_tax']
				);

				$unselected_items[] = array(
					'product_id'     => $cart_item['product_id'],
					'quantity'       => $cart_item['quantity'],
					'variation_id'   => isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : 0,
					'variation'      => isset( $cart_item['variation'] ) ? $cart_item['variation'] : array(),
					'cart_item_data' => $cart_item_data,
				);

				$cart->remove_cart_item( $cart_item_key );
			}
		}

		if( ! empty( $unselected_items ) ){
			WC()->session->set( 'choozy_unselected_items', $unselected_items );
		}

		$already_processed = true;
	}

	/**
	 * Filter cart for checkout
	 *
	 * @return void
	 */
	public function filter_cart_for_checkout() {
		if ( ! is_checkout() ) {
			return;
		}

		// Ensure unselected items are stored and removed
		$selected_items = $this->get_selected_items();
		$has_unselected = false;

		foreach ( $selected_items as $cart_item_key => $is_selected ) {
			if ( ! $is_selected ) {
				$has_unselected = true;
				break;
			}
		}

		// If there are no selected items at all, show error
		$cart_contents = WC()->cart->get_cart();
		$selected_count = 0;

		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			if ( isset( $selected_items[ $cart_item_key ] ) && $selected_items[ $cart_item_key ] ) {
				$selected_count++;
			}
		}

		if ( $selected_count === 0 && $has_unselected ) {
			wc_add_notice( __( 'Please select at least one item to checkout.', 'choozy' ), 'error' );
		}
	}

	/**
	 * Restore unselected items when returning to cart page
	 *
	 * @return void
	 */
	public function restore_unselected_items_on_cart() {
		// Only run on all pages except checkout or admin
		if ( is_checkout() || is_admin() ) {
			return;
		}

		if ( ! WC()->session ) {
			return;
		}

		// Get unselected items stored during checkout
		$unselected_items = WC()->session->get( 'choozy_unselected_items', array() );

		if ( empty( $unselected_items ) ) {
			return;
		}

		// Prevent multiple restorations per request
		static $restored = false;
		if ( $restored ) {
			return;
		}
		$restored = true;

		// Restore unselected items back to cart
		foreach ( $unselected_items as $key => $cart_item ) {
			$product_id     = $cart_item['product_id'];
			$quantity       = $cart_item['quantity'];
			$variation_id   = isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : 0;
			$variation      = isset( $cart_item['variation'] ) ? $cart_item['variation'] : array();
			$cart_item_data = isset( $cart_item['cart_item_data'] ) ? $cart_item['cart_item_data'] : array();

			// Check if item already exists in cart
			$existing_key = WC()->cart->generate_cart_id(
				$product_id,
				$variation_id,
				$variation,
				$cart_item_data
			);

			// Only add if not already in cart
			if ( ! isset( WC()->cart->cart_contents[ $existing_key ] ) ) {
				// Verify product exists and is purchasable
				$product = wc_get_product( $variation_id ? $variation_id : $product_id );

				if ( $product && $product->is_purchasable() ) {
					// Add product back to cart
					WC()->cart->add_to_cart(
						$product_id,
						$quantity,
						$variation_id,
						$variation,
						$cart_item_data
					);
				}
			}
		}

		// Clear unselected items from session after restoration
		WC()->session->set( 'choozy_unselected_items', array() );
	}

	/**
	 * Exclude unselected items from cart total calculation on cart page
	 *
	 * @param object $cart WC_Cart object
	 *
	 * @return void
	 */
	public function exclude_unselected_from_totals( $cart ) {
		// Only process on cart page, not checkout or admin
		if ( ! is_cart() || is_admin() || wp_doing_ajax() ) {
			return;
		}

		// Prevent running multiple times per request
		static $already_processed = false;
		if ( $already_processed ) {
			return;
		}
		$already_processed = true;

		// Get selected items
		$selected_items = $this->get_selected_items();

		// Store original prices for display purposes
		$original_prices = array();
		
		// Loop through cart items
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			// Check if item is NOT selected
			if ( ! in_array( $cart_item_key, $selected_items ) ) {

				// Store the original price before setting to 0
				$original_prices[ $cart_item_key ] = $cart_item['data']->get_price();

				// Set price to 0 for unselected items so they don't affect the total
				$cart_item['data']->set_price( 0 );
			}
		}

		// Store original prices in session for display filters
		if ( ! empty( $original_prices ) ) {
			WC()->session->set( 'choozy_original_prices', $original_prices );
		} else {
			// All items are selected, clear the session to prevent stale data
			WC()->session->set( 'choozy_original_prices', array() );
		}
	}

	/**
	 * Display original price for unselected items in cart
	 *
	 * @param string $price HTML price string
	 * @param array  $cart_item Cart item data
	 * @param string $cart_item_key Cart item key
	 *
	 * @return string
	 */
	public function display_original_price( $price, $cart_item, $cart_item_key ) {
		if ( ! is_cart() ) {
			return $price;
		}

		// Get original prices from session
		$original_prices = WC()->session->get( 'choozy_original_prices', array() );
		
		// Check if this item has an original price stored (meaning it's unselected)
		if ( isset( $original_prices[ $cart_item_key ] ) ) {
			$original_price = $original_prices[ $cart_item_key ];
			$product = $cart_item['data'];
			
			// Format the price
			$price_html = wc_price( $original_price );
			
			// Add strikethrough and excluded label
			return '<del>' . $price_html . '</del> <span class="choozy-excluded-label">' . esc_html__( '(Excluded)', 'choozy' ) . '</span>';
		}
		
		return $price;
	}

	/**
	 * Display original subtotal for unselected items in cart
	 *
	 * @param string $subtotal HTML subtotal string
	 * @param array  $cart_item Cart item data
	 * @param string $cart_item_key Cart item key
	 *
	 * @return string
	 */
	public function display_original_subtotal( $subtotal, $cart_item, $cart_item_key ) {
		if ( ! is_cart() ) {
			return $subtotal;
		}

		// Get original prices from session
		$original_prices = WC()->session->get( 'choozy_original_prices', array() );
		
		// Check if this item has an original price stored (meaning it's unselected)
		if ( isset( $original_prices[ $cart_item_key ] ) ) {
			$original_price = $original_prices[ $cart_item_key ];
			$quantity = $cart_item['quantity'];
			
			// Calculate original subtotal
			$original_subtotal = $original_price * $quantity;
			
			// Format the subtotal
			$subtotal_html = wc_price( $original_subtotal );
			
			// Add strikethrough and excluded label
			return '<del>' . $subtotal_html . '</del> <span class="choozy-excluded-label">' . esc_html__( '(Excluded)', 'choozy' ) . '</span>';
		}
		
		return $subtotal;
	}
}

