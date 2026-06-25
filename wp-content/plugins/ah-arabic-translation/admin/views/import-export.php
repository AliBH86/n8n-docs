<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap ah-admin-wrap">
    <h1>Import / Export Translations</h1>

    <?php if ( isset( $_GET['imported'] ) ) : ?>
    <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html( (int) $_GET['imported'] ); ?> translations imported successfully.</p>
    </div>
    <?php endif; ?>

    <div class="ah-ie-grid">

        <div class="ah-ie-card">
            <h2>📤 Export Translations</h2>
            <p>Download all Arabic translations as a CSV file for backup or editing in Excel / Google Sheets.</p>
            <p><strong>CSV format:</strong> <code>source_key, translation, context</code></p>
            <a href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=ah_export_csv' ), 'ah_export_csv' ); ?>"
               class="button button-primary">Download CSV</a>
        </div>

        <div class="ah-ie-card">
            <h2>📥 Import Translations</h2>
            <p>Upload a CSV file to bulk-import translations. Existing entries with the same key will be updated.</p>
            <p><strong>Required columns:</strong> <code>source_key</code> (col A), <code>translation</code> (col B)</p>
            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field( 'ah_import_csv' ); ?>
                <input type="hidden" name="action" value="ah_import_csv" />
                <div style="margin-bottom:10px;">
                    <label>Context:
                        <select name="import_context">
                            <option value="woocommerce">WooCommerce</option>
                            <option value="ui">UI / General</option>
                            <option value="custom">Custom</option>
                        </select>
                    </label>
                </div>
                <input type="file" name="csv_file" accept=".csv" required style="margin-bottom:10px;" /><br>
                <button type="submit" class="button button-primary">Import CSV</button>
            </form>
        </div>

    </div>

    <div class="ah-info-box" style="margin-top:24px;">
        <h3>📝 CSV Template</h3>
        <p>Download the starter template with the most common WooCommerce strings pre-filled:</p>
        <table style="font-size:13px;border-collapse:collapse;">
            <tr style="background:#f0f0f0;">
                <th style="padding:6px 12px;border:1px solid #ddd;">source_key (English)</th>
                <th style="padding:6px 12px;border:1px solid #ddd;">translation (Arabic)</th>
            </tr>
            <tr>
                <td style="padding:6px 12px;border:1px solid #ddd;">Add to cart</td>
                <td style="padding:6px 12px;border:1px solid #ddd;font-family:Tajawal,Arial;direction:rtl;">أضف إلى السلة</td>
            </tr>
            <tr>
                <td style="padding:6px 12px;border:1px solid #ddd;">Out of stock</td>
                <td style="padding:6px 12px;border:1px solid #ddd;font-family:Tajawal,Arial;direction:rtl;">نفد من المخزون</td>
            </tr>
        </table>
    </div>
</div>
