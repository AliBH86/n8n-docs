<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap ah-admin-wrap">
    <h1>Auto Translate — الترجمة التلقائية</h1>
    <p style="max-width:680px; color:#555;">
        Select products below and click <strong>Translate Selected</strong> to automatically translate their titles and descriptions into Arabic.
        Translations are saved as post meta and shown to Arabic visitors.
        The translation API used is set in <a href="<?php echo admin_url( 'admin.php?page=ah-arabic-settings' ); ?>">Settings</a>.
    </p>

    <div id="ah-bulk-status" style="display:none; margin-bottom:16px; padding:10px 16px; border-radius:4px; font-weight:600;"></div>

    <div style="margin-bottom:16px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <button type="button" id="ah-bulk-translate-btn" class="button button-primary" style="background:#540754; border-color:#3a0438;">
            Translate Selected Products
        </button>
        <button type="button" id="ah-select-all-btn" class="button">Select All</button>
        <button type="button" id="ah-deselect-all-btn" class="button">Deselect All</button>
        <span id="ah-bulk-progress" style="font-size:13px; color:#555;"></span>
    </div>

    <?php if ( empty( $products ) ) : ?>
        <p><em>No published products found.</em></p>
    <?php else : ?>
    <table class="wp-list-table widefat fixed striped" style="max-width:800px;">
        <thead>
            <tr>
                <th style="width:40px;"><input type="checkbox" id="ah-check-all" /></th>
                <th>Product</th>
                <th style="width:160px;">Arabic Title</th>
                <th style="width:120px;">Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $products as $product ) :
            $ar_title = get_post_meta( $product->ID, '_ah_title_ar', true );
        ?>
            <tr id="ah-row-<?php echo esc_attr( $product->ID ); ?>">
                <td><input type="checkbox" class="ah-product-check" value="<?php echo esc_attr( $product->ID ); ?>" /></td>
                <td>
                    <a href="<?php echo get_edit_post_link( $product->ID ); ?>" target="_blank">
                        <?php echo esc_html( $product->post_title ); ?>
                    </a>
                </td>
                <td class="ah-ar-title" dir="rtl" style="font-family:Tajawal,Arial,sans-serif;">
                    <?php echo esc_html( $ar_title ?: '—' ); ?>
                </td>
                <td class="ah-row-status">
                    <?php if ( $ar_title ) : ?>
                        <span style="color:green;">Translated</span>
                    <?php else : ?>
                        <span style="color:#888;">Not translated</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script>
(function($) {
    var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
    var nonce   = '<?php echo esc_js( wp_create_nonce( 'ah_admin_nonce' ) ); ?>';

    $('#ah-check-all').on('change', function() {
        $('.ah-product-check').prop('checked', this.checked);
    });
    $('#ah-select-all-btn').on('click', function() {
        $('.ah-product-check').prop('checked', true);
        $('#ah-check-all').prop('checked', true);
    });
    $('#ah-deselect-all-btn').on('click', function() {
        $('.ah-product-check').prop('checked', false);
        $('#ah-check-all').prop('checked', false);
    });

    $('#ah-bulk-translate-btn').on('click', function() {
        var ids = [];
        $('.ah-product-check:checked').each(function() { ids.push($(this).val()); });
        if (!ids.length) { alert('Please select at least one product.'); return; }

        var $btn  = $(this);
        var $prog = $('#ah-bulk-progress');
        var $status = $('#ah-bulk-status');
        var total = ids.length;
        var done  = 0;
        var errors = 0;

        $btn.prop('disabled', true).text('Translating...');
        $status.hide();
        $prog.text('0 / ' + total + ' done...');

        function translateNext() {
            if (!ids.length) {
                $btn.prop('disabled', false).text('Translate Selected Products');
                var msg = done + ' product(s) translated.';
                if (errors) msg += ' ' + errors + ' error(s).';
                $status.css({'background': errors ? '#fee2e2' : '#dcfce7', 'color': errors ? '#b91c1c' : '#166534'})
                       .text(msg).show();
                $prog.text('');
                return;
            }
            var id = ids.shift();
            $prog.text((done + errors + 1) + ' / ' + total + ' — translating product #' + id + '...');

            $.post(ajaxUrl, {
                action:  'ah_auto_translate_product',
                nonce:   nonce,
                post_id: id
            }, function(res) {
                if (res.success) {
                    done++;
                    var $row = $('#ah-row-' + id);
                    if (res.data.title) {
                        $row.find('.ah-ar-title').text(res.data.title);
                        $row.find('.ah-row-status').html('<span style="color:green;">Translated</span>');
                    }
                } else {
                    errors++;
                    $('#ah-row-' + id).find('.ah-row-status').html('<span style="color:red;">Error</span>');
                }
                setTimeout(translateNext, 300);
            }).fail(function() {
                errors++;
                $('#ah-row-' + id).find('.ah-row-status').html('<span style="color:red;">Failed</span>');
                setTimeout(translateNext, 300);
            });
        }

        translateNext();
    });
})(jQuery);
</script>
