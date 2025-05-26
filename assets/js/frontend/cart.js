(function ($) {
  "use strict";

  const WPCCart = {
    init: function () {
      this.bindEvents();
      this.initializeCheckboxes();
    },

    bindEvents: function () {
      $(document).on("change", ".wpc-item-checkbox", this.handleCheckboxChange);
      $(document.body).on("updated_cart_totals", this.updateCartItemStyles);
    },

    initializeCheckboxes: function () {
      this.updateCartItemStyles();
    },

    handleCheckboxChange: function (e) {
      const $checkbox = $(this);
      const cartItemKey = $checkbox.data("cart-item-key");
      const isChecked = $checkbox.is(":checked");

      WPCCart.updateCartItemSelection(cartItemKey, isChecked);
    },

    updateCartItemSelection: function (cartItemKey, isChecked) {
      $.ajax({
        url: wpcCart.ajaxurl,
        type: "POST",
        data: {
          action: "wpc_update_cart_selection",
          cart_item_key: cartItemKey,
          is_checked: isChecked ? 1 : 0,
          nonce: wpcCart.nonce,
        },
        beforeSend: function () {
          // Add loading state
          $("body").addClass("wpc-updating-cart");
        },
        success: function (response) {
          if (response.success) {
            // Update cart totals
            $(document.body).trigger("update_checkout");
            $(document.body).trigger("wc_update_cart");

            // Update visual states
            WPCCart.updateCartItemStyles();
          } else {
            // Handle error
            console.error("Error updating cart selection");
          }
        },
        error: function (xhr, status, error) {
          console.error("Ajax error:", error);
        },
        complete: function () {
          // Remove loading state
          $("body").removeClass("wpc-updating-cart");
        },
      });
    },

    updateCartItemStyles: function () {
      $(".wpc-item-checkbox").each(function () {
        const $checkbox = $(this);
        const $row = $checkbox.closest("tr");

        if ($checkbox.is(":checked")) {
          $row.removeClass("unselected");
        } else {
          $row.addClass("unselected");
        }
      });
    },
  };

  // Initialize on document ready
  $(document).ready(function () {
    WPCCart.init();
  });
})(jQuery);
