(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle select all checkbox
        $(document).on('change', '#choozy-select-all', function() {
            const isChecked = $(this).is(':checked');
            $('.choozy-cart-item-checkbox').prop('checked', isChecked).trigger('change');
        });

        // Handle individual cart item checkboxes
        $(document).on('change', '.choozy-cart-item-checkbox', function() {
            updateSelectAllCheckbox();
            saveCartSelection();
        });

        // Update "Select All" checkbox based on individual checkboxes
        function updateSelectAllCheckbox() {
            const totalCheckboxes = $('.choozy-cart-item-checkbox').length;
            const checkedCheckboxes = $('.choozy-cart-item-checkbox:checked').length;
            
            $('#choozy-select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
            
            // Update selected count text
            updateSelectedCount(checkedCheckboxes, totalCheckboxes);
            
            // Update info message visibility
            updateInfoMessage(checkedCheckboxes, totalCheckboxes);
        }
        
        // Update selected count display
        function updateSelectedCount(selectedCount, totalCount) {
            const $countElement = $('.choozy-selected-count');
            if ($countElement.length) {
                $countElement.text(selectedCount + ' of ' + totalCount + ' items selected');
            }
        }
        
        // Update info message visibility
        function updateInfoMessage(selectedCount, totalCount) {
            const $infoMessage = $('.choozy-info-message');
            
            if (selectedCount < totalCount && selectedCount > 0) {
                // Show info message if some but not all items are selected
                if ($infoMessage.length === 0) {
                    const message = '<div class="choozy-info-message">' +
                        '<span class="dashicons dashicons-info"></span>' +
                        'Only selected items will proceed to checkout. Unselected items will remain in your cart.' +
                        '</div>';
                    $('.choozy-select-controls').after(message);
                } else {
                    $infoMessage.show();
                }
            } else {
                // Hide info message if all or none are selected
                $infoMessage.hide();
            }
        }

        // Save cart selection to session via AJAX
        function saveCartSelection() {
            const selectedItems = [];
            
            $('.choozy-cart-item-checkbox').each(function() {
                const cartItemKey = $(this).data('cart-item-key');

                if($(this).is(':checked')){
                    selectedItems.push(cartItemKey);
                }
            });

            $.ajax({
                url: Choozy.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'choozy_save_cart_selection',
                    nonce: Choozy.nonce,
                    selected_items: JSON.stringify(selectedItems)
                },
                success: function(response) {
                    if (response.success) {
                        // Optionally update cart totals or show a message
                        console.log('Cart selection saved');
                        
                        // Add visual feedback for unchecked items
                        $('.choozy-cart-item-checkbox').each(function() {
                            const $checkbox = $(this);
                            const $row = $checkbox.closest('tr.cart_item');
                            
                            if ($checkbox.is(':checked')) {
                                $row.removeClass('choozy-unchecked');
                            } else {
                                $row.addClass('choozy-unchecked');
                            }
                        });

                        // Trigger cart update with multiple methods for better compatibility
                        const $updateButton = $('button[name="update_cart"]');
                        
                        if ($updateButton.length) {
                            // Enable and trigger the Update Cart button
                            $updateButton.prop('disabled', false).trigger('click');
                        } else {
                            // Fallback: Trigger WooCommerce's update event directly
                            $(document.body).trigger('wc_update_cart');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error saving cart selection:', error);
                }
            });
        }

        // Initialize the select all checkbox state on page load
        updateSelectAllCheckbox();
        
        // Initialize visual feedback on page load
        $('.choozy-cart-item-checkbox').each(function() {
            const $checkbox = $(this);
            const $row = $checkbox.closest('tr.cart_item');
            
            if ($checkbox.is(':checked')) {
                $row.removeClass('choozy-unchecked');
            } else {
                $row.addClass('choozy-unchecked');
            }
        });

        // Handle cart updates from WooCommerce (quantity changes, remove items, etc.)
        $(document.body).on('updated_cart_totals', function() {
            updateSelectAllCheckbox();
            
            // Re-apply visual feedback after cart update
            $('.choozy-cart-item-checkbox').each(function() {
                const $checkbox = $(this);
                const $row = $checkbox.closest('tr.cart_item');
                
                if ($checkbox.is(':checked')) {
                    $row.removeClass('choozy-unchecked');
                } else {
                    $row.addClass('choozy-unchecked');
                }
            });
        });
    });

})(jQuery);

