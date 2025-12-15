/**
 * Stacker Content Importer - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle import form submission
        $('button[name="stacker_manual_import"]').on('click', function(e) {
            var $button = $(this);
            var originalText = $button.text();

            $button.prop('disabled', true);
            $button.text('Importing...');

            // Re-enable button after form submission
            setTimeout(function() {
                $button.prop('disabled', false);
                $button.text(originalText);
            }, 2000);
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
