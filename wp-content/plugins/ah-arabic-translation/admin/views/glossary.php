<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap ah-admin-wrap">
<h1>🔒 Glossary — Brand &amp; Term Protection</h1>

<?php if ( isset( $_GET['saved'] ) ) : ?>
<div class="notice notice-success is-dismissible"><p>Glossary saved successfully.</p></div>
<?php endif; ?>

<p style="max-width:700px;color:#555;">
    <strong>Protected terms</strong> are never sent to the translation API — they stay in their original English form.
    This prevents "Louis Vuitton" becoming "لويس فويتون" in machine translation.<br>
    <strong>Forced translations</strong> let you pin specific words to the exact Arabic you prefer.
</p>

<form method="post" id="ah-glossary-form">
<input type="hidden" name="action" value="ah_save_glossary">
<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'ah_admin_nonce' ) ); ?>">
<div id="ah-glossary-notice" style="display:none;margin:0 0 16px;padding:12px 18px;border-radius:6px;font-weight:600;font-size:14px;max-width:760px;"></div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;max-width:960px;">

<!-- ── Protected Terms ─── -->
<div>
    <h2 style="margin-top:0">Protected Brand Names</h2>
    <p style="color:#666;font-size:13px;">One term per line. These exact strings will be shielded from translation.<br>
    Built-in brands (Louis Vuitton, Gucci, Rolex, etc.) are always protected — add your custom ones below.</p>
    <textarea name="protected_terms" rows="20" style="width:100%;font-family:monospace;font-size:13px;"><?php
        echo esc_textarea( implode( "\n", $protected ) );
    ?></textarea>
</div>

<!-- ── Forced Translations ─── -->
<div>
    <h2 style="margin-top:0">Forced Translations (English → Arabic)</h2>
    <p style="color:#666;font-size:13px;">Override the machine translation for specific words.<br>
    Example: "Bag" → "حقيبة" ensures consistent Arabic terminology.</p>

    <table class="widefat" id="ah-forced-table" style="margin-bottom:12px;">
        <thead>
            <tr>
                <th>English Term</th>
                <th>Arabic Translation</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if ( empty( $forced ) ) : ?>
            <tr class="ah-forced-row">
                <td><input type="text" name="forced_source[]" value="" style="width:100%" placeholder="e.g. Bag"></td>
                <td><input type="text" name="forced_target[]" value="" style="width:100%;direction:rtl;font-family:Tajawal,Arial,sans-serif" placeholder="e.g. حقيبة"></td>
                <td><button type="button" class="button ah-remove-row">✕</button></td>
            </tr>
        <?php else : ?>
            <?php foreach ( $forced as $source => $target ) : ?>
            <tr class="ah-forced-row">
                <td><input type="text" name="forced_source[]" value="<?php echo esc_attr( $source ); ?>" style="width:100%"></td>
                <td><input type="text" name="forced_target[]" value="<?php echo esc_attr( $target ); ?>" style="width:100%;direction:rtl;font-family:Tajawal,Arial,sans-serif"></td>
                <td><button type="button" class="button ah-remove-row">✕</button></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    <button type="button" class="button" id="ah-add-row">+ Add Row</button>

    <template id="ah-row-template">
        <tr class="ah-forced-row">
            <td><input type="text" name="forced_source[]" value="" style="width:100%" placeholder="English term"></td>
            <td><input type="text" name="forced_target[]" value="" style="width:100%;direction:rtl;font-family:Tajawal,Arial,sans-serif" placeholder="الترجمة بالعربية"></td>
            <td><button type="button" class="button ah-remove-row">✕</button></td>
        </tr>
    </template>
</div>

</div><!-- /grid -->

<p style="margin-top:24px;">
    <button type="submit" class="button button-primary button-large">Save Glossary</button>
</p>
</form>

<hr style="margin:32px 0;">
<h2>Built-in Protected Brands (read-only)</h2>
<p style="color:#666;font-size:13px;">These are always protected regardless of the custom list above.</p>
<div style="columns:4;column-gap:24px;max-width:800px;font-size:13px;line-height:1.9;">
    Louis Vuitton · LV · LVMH · Chanel · Gucci · Prada · Hermès · Dior ·
    Burberry · Fendi · Balenciaga · Saint Laurent · YSL · Givenchy · Versace ·
    Valentino · Bottega Veneta · Celine · Loewe · Alexander McQueen ·
    Tom Ford · Off-White · Coach · Michael Kors · Kate Spade · Tory Burch ·
    Cartier · Rolex · Omega · Tiffany · BHD · USD · EUR · GBP · SAR · AED
</div>

<script>
(function () {
    var ajaxUrl = (typeof ajaxurl !== 'undefined') ? ajaxurl : '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';

    document.getElementById('ah-add-row').addEventListener('click', function () {
        var tpl = document.getElementById('ah-row-template').content.cloneNode(true);
        document.querySelector('#ah-forced-table tbody').appendChild(tpl);
    });
    document.querySelector('#ah-forced-table').addEventListener('click', function (e) {
        if (e.target.classList.contains('ah-remove-row')) {
            e.target.closest('tr').remove();
        }
    });

    // ── AJAX save (vanilla fetch) ──
    document.getElementById('ah-glossary-form').addEventListener('submit', function (e) {
        e.preventDefault();
        var notice = document.getElementById('ah-glossary-notice');
        var btn    = this.querySelector('button[type="submit"]');
        if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }
        notice.style.display = 'none';

        fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: new FormData(this) })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (j.success) {
                    notice.style.cssText += ';background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;';
                    notice.textContent = '✓ ' + j.data.message + ' (' + j.data.protected_count + ' protected, ' + j.data.forced_count + ' forced). Reloading…';
                    notice.style.display = 'block';
                    setTimeout(function () { location.reload(); }, 1200);
                } else {
                    notice.style.cssText += ';background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;';
                    notice.textContent = '✗ Save failed: ' + j.data;
                    notice.style.display = 'block';
                }
            })
            .catch(function (err) {
                notice.style.cssText += ';background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;';
                notice.textContent = '✗ Request blocked: ' + err + '. A security plugin may be blocking admin-ajax.php.';
                notice.style.display = 'block';
            })
            .finally(function () {
                if (btn) { btn.disabled = false; btn.textContent = 'Save Glossary'; }
            });
    });
})();
</script>
</div>
