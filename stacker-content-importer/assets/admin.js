/**
 * Stacker Content Importer - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle import button click
        $('#stacker_import_now_btn').on('click', function(e) {
            e.preventDefault();

            var $button = $(this);
            var originalText = $button.text();
            var $messageContainer = $('#stacker-import-message');

            // Clear previous messages
            $messageContainer.html('').removeClass('notice-success notice-error');

            // Disable button and show loading state
            $button.prop('disabled', true);
            $button.text('Importing...');

            // Make AJAX request
            $.ajax({
                url: stackerImporter.ajax_url,
                type: 'POST',
                data: {
                    action: 'stacker_manual_import',
                    nonce: stackerImporter.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $messageContainer
                            .html('<p>' + response.data.message + '</p>')
                            .addClass('notice notice-success')
                            .show();

                        // Update statistics if present
                        var statsCount = $('.stacker-info-box strong:contains("Total Articles:")').parent();
                        if (statsCount.length) {
                            // You might want to refresh the count here
                            location.reload();
                        }
                    } else {
                        $messageContainer
                            .html('<p>' + response.data.message + '</p>')
                            .addClass('notice notice-error')
                            .show();
                    }
                },
                error: function(xhr, status, error) {
                    $messageContainer
                        .html('<p>' + 'An error occurred during import. Please try again.' + '</p>')
                        .addClass('notice notice-error')
                        .show();
                },
                complete: function() {
                    // Re-enable button
                    $button.prop('disabled', false);
                    $button.text(originalText);
                }
            });
        });

        // Auto-save settings notification
        var $form = $('form[action="options.php"]');
        if ($form.length) {
            $form.on('submit', function() {
                var $submit = $(this).find('input[type="submit"]');
                var originalValue = $submit.val();

                $submit.val('Saving...');

                setTimeout(function() {
                    $submit.val(originalValue);
                }, 1000);
            });
        }

        // Validate feed URL
        $('#stacker_feed_url').on('blur', function() {
            var url = $(this).val();
            if (url && !isValidUrl(url)) {
                alert('Please enter a valid feed URL');
                $(this).focus();
            }
        });
    });

    /**
     * Simple URL validation
     */
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }

})(jQuery);
