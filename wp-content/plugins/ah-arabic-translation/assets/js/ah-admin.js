/* AH Arabic Translation — Admin Script */
(function($) {
    'use strict';

    // Inline edit for string translations table
    $(document).on('click', '.ah-edit-btn', function() {
        var $row = $(this).closest('tr');
        $row.find('.ah-trans-text').hide();
        $row.find('.ah-trans-input').show().focus();
        $(this).hide();
        $row.find('.ah-save-btn, .ah-cancel-btn').show();
    });

    $(document).on('click', '.ah-cancel-btn', function() {
        var $row = $(this).closest('tr');
        var original = $row.find('.ah-trans-text').text().trim();
        $row.find('.ah-trans-input').val(original).hide();
        $row.find('.ah-trans-text').show();
        $(this).hide();
        $row.find('.ah-save-btn').hide();
        $row.find('.ah-edit-btn').show();
    });

    $(document).on('click', '.ah-save-btn', function() {
        var $btn  = $(this);
        var $row  = $btn.closest('tr');
        var id    = $row.data('id');
        var newVal = $row.find('.ah-trans-input').val().trim();
        var source = $row.find('.ah-source-col code').text().trim();

        $btn.text('Saving...').prop('disabled', true);

        $.post(ahAdmin.ajaxUrl, {
            action:      'ah_save_string_inline',
            nonce:       ahAdmin.nonce,
            id:          id,
            source_key:  source,
            translation: newVal,
            context:     new URLSearchParams(window.location.search).get('context') || 'woocommerce'
        }, function(res) {
            if (res.success) {
                $row.find('.ah-trans-text').text(newVal).show();
                $row.find('.ah-trans-input').hide();
                $row.find('.ah-edit-btn').show();
                $btn.text('Save').prop('disabled', false).hide();
                $row.find('.ah-cancel-btn').hide();
                $row.find('.ah-source-col').css('background', '#dcfce7');
                setTimeout(function() { $row.find('.ah-source-col').css('background', ''); }, 1500);
            } else {
                alert('Error saving. Please try again.');
                $btn.text('Save').prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.ah-delete-btn', function() {
        if ( ! confirm('Delete this translation? This cannot be undone.') ) return;
        var $btn = $(this);
        var $row = $btn.closest('tr');
        var id   = $btn.data('id');

        $btn.text('...').prop('disabled', true);

        $.post(ahAdmin.ajaxUrl, {
            action: 'ah_delete_string_inline',
            nonce:  ahAdmin.nonce,
            id:     id
        }, function(res) {
            if (res.success) {
                $row.css('background', '#fee2e2');
                setTimeout(function() { $row.fadeOut(300, function() { $row.remove(); }); }, 400);
            } else {
                alert('Error deleting.');
                $btn.text('Delete').prop('disabled', false);
            }
        });
    });

    // Auto-translate button on product/post meta box
    $(document).on('click', '#ah-auto-translate-btn', function() {
        var $btn    = $(this);
        var postId  = $btn.data('post-id');
        var nonce   = $btn.data('nonce');
        var $status = $('#ah-auto-translate-status');

        $btn.prop('disabled', true).text('Translating...');
        $status.text('').css('color', '#555');

        $.post(ahAdmin.ajaxUrl, {
            action:  'ah_auto_translate_product',
            nonce:   nonce,
            post_id: postId
        }, function(res) {
            if (res.success) {
                if (res.data.title)   { $('#ah_title_ar').val(res.data.title); }
                if (res.data.excerpt) { $('#ah_excerpt_ar').val(res.data.excerpt); }
                if (res.data.content) { $('#ah_content_ar').val(res.data.content); }
                var count = Object.keys(res.data).length;
                $status.css('color', 'green').text(count + ' field(s) translated. Save the post to keep changes.');
            } else {
                $status.css('color', 'red').text('Error: ' + res.data);
            }
            $btn.prop('disabled', false).text('Translate All Fields Automatically');
        }).fail(function() {
            $status.css('color', 'red').text('Request failed. Check your API settings.');
            $btn.prop('disabled', false).text('Translate All Fields Automatically');
        });
    });

})(jQuery);
