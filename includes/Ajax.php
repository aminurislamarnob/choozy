<?php

namespace WeLabs\Choozy;

/**
 * Ajax class
 * 
 * Handles AJAX requests
 */
class Ajax {
	/**
	 * The constructor.
	 */
	public function __construct() {
		// Save cart selection
		add_action( 'wp_ajax_choozy_save_cart_selection', array( $this, 'save_cart_selection' ) );
		add_action( 'wp_ajax_nopriv_choozy_save_cart_selection', array( $this, 'save_cart_selection' ) );
	}

	/**
	 * Save cart selection to session
	 *
	 * @return void
	 */
	public function save_cart_selection() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'choozy_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce', 'choozy' ) ) );
		}

		// Get selected items from POST
		$selected_items = isset( $_POST['selected_items'] ) ? json_decode( stripslashes( $_POST['selected_items'] ), true ) : array();

		if ( ! is_array( $selected_items ) ) {
			$selected_items = array();
		}

		// Save to session
		if ( WC()->session ) {
			WC()->session->set( 'choozy_selected_items', $selected_items );
			
			wp_send_json_success( array(
				'message' => __( 'Cart selection saved', 'choozy' ),
				'selected_items' => $selected_items,
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Session not available', 'choozy' ) ) );
		}
	}
}

