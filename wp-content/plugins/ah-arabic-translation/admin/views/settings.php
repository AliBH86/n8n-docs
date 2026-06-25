<?php defined( 'ABSPATH' ) || exit;
$s = $settings;
function ah_checked( $key, $val, $settings ) {
    return isset( $settings[ $key ] ) && $settings[ $key ] == $val ? 'checked' : '';
}
function ah_selected( $key, $val, $settings ) {
    return isset( $settings[ $key ] ) && $settings[ $key ] == $val ? 'selected' : '';
}
// Ground-truth read straight from the database for the diagnostic panel
$db        = get_option( 'ah_arabic_settings', [] );
$db_prov   = $db['translation_provider'] ?? '(not set — defaults to mymemory)';
$key_map   = [
    'anthropic' => 'anthropic_api_key',
    'openai'    => 'openai_api_key',
    'gemini'    => 'gemini_api_key',
    'azure'     => 'azure_api_key',
    'deepl'     => 'deepl_api_key',
    'google'    => 'google_translate_key',
];
$ajax_nonce = wp_create_nonce( 'ah_admin_nonce' );
?>
<div class="wrap ah-admin-wrap">
    <h1>Settings — الإعدادات</h1>

    <!-- ── Live database status (read fresh, bypasses form state) ── -->
    <div style="background:#f0f6fc;border:1px solid #c3d9ed;border-radius:6px;padding:14px 18px;margin:16px 0;max-width:760px;">
        <strong style="color:#0a4b78;">🔍 Live status (read from database right now):</strong>
        <ul style="margin:8px 0 0;list-style:disc;padding-left:22px;font-size:13px;line-height:1.8;">
            <li>Saved provider: <code style="font-size:13px;"><?php echo esc_html( $db_prov ); ?></code></li>
            <li>API keys present:
                <?php
                $present = [];
                foreach ( $key_map as $prov => $opt ) {
                    if ( ! empty( $db[ $opt ] ) ) $present[] = strtoupper( $prov );
                }
                echo $present ? '<code>' . esc_html( implode( ', ', $present ) ) . '</code>' : '<em>none saved yet</em>';
                ?>
            </li>
        </ul>
        <p style="margin:8px 0 0;font-size:12px;color:#555;">After you click <strong>Save Settings</strong>, reload this page — these values should reflect your choice. If they don't change, the save is being blocked (security/cache plugin).</p>
    </div>

    <form method="post" id="ah-settings-form">
        <input type="hidden" name="action" value="ah_save_settings">
        <input type="hidden" name="nonce" value="<?php echo esc_attr( $ajax_nonce ); ?>">

        <table class="form-table">

            <tr>
                <th>Default Language</th>
                <td>
                    <select name="default_lang">
                        <option value="en" <?php echo ah_selected( 'default_lang', 'en', $s ); ?>>English (EN)</option>
                        <option value="ar" <?php echo ah_selected( 'default_lang', 'ar', $s ); ?>>Arabic (AR) — العربية</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th>Language Switcher Position</th>
                <td>
                    <select name="switcher_position">
                        <option value="header"  <?php echo ah_selected( 'switcher_position', 'header',  $s ); ?>>Flatsome Header (Auto)</option>
                        <option value="top_bar" <?php echo ah_selected( 'switcher_position', 'top_bar', $s ); ?>>Top Bar Only</option>
                        <option value="manual"  <?php echo ah_selected( 'switcher_position', 'manual',  $s ); ?>>Manual (Shortcode/Widget only)</option>
                    </select>
                    <p class="description">Use <code>[ah_language_switcher]</code> shortcode to place it anywhere manually.</p>
                </td>
            </tr>

            <tr>
                <th>Switcher Style</th>
                <td>
                    <select name="switcher_style">
                        <option value="flags"   <?php echo ah_selected( 'switcher_style', 'flags',   $s ); ?>>Flag + Code (🇧🇭 AR)</option>
                        <option value="minimal" <?php echo ah_selected( 'switcher_style', 'minimal', $s ); ?>>Minimal (AR | EN)</option>
                        <option value="full"    <?php echo ah_selected( 'switcher_style', 'full',    $s ); ?>>Full Name (العربية / English)</option>
                    </select>
                </td>
            </tr>

        </table>

        <h2 style="margin-top:32px; border-top:1px solid #ddd; padding-top:20px;">Language Switcher Labels &amp; Flags</h2>
        <p style="color:#555; max-width:600px;">Customize the text and flag emoji shown in the switcher button. Use any emoji flag or leave blank to hide.</p>

        <table class="form-table">

            <tr>
                <th>English Label</th>
                <td>
                    <input type="text" name="en_label" value="<?php echo esc_attr( $s['en_label'] ?? 'EN' ); ?>"
                           style="width:100px;" placeholder="EN" />
                    <p class="description">e.g. EN, English, Eng</p>
                </td>
            </tr>

            <tr>
                <th>English Flag</th>
                <td>
                    <input type="text" name="en_flag" value="<?php echo esc_attr( $s['en_flag'] ?? '🇺🇸' ); ?>"
                           style="width:80px;font-size:20px;" placeholder="🇺🇸" />
                    <p class="description">Paste a flag emoji: 🇺🇸 🇬🇧 🇧🇭 — or leave blank for no flag</p>
                </td>
            </tr>

            <tr>
                <th>Arabic Label</th>
                <td>
                    <input type="text" name="ar_label" value="<?php echo esc_attr( $s['ar_label'] ?? 'عربي' ); ?>"
                           style="width:100px;direction:rtl;font-family:Tajawal,Arial,sans-serif;" placeholder="عربي" />
                    <p class="description">e.g. عربي, ع, AR, Arabic</p>
                </td>
            </tr>

            <tr>
                <th>Arabic Flag</th>
                <td>
                    <input type="text" name="ar_flag" value="<?php echo esc_attr( $s['ar_flag'] ?? '🇧🇭' ); ?>"
                           style="width:80px;font-size:20px;" placeholder="🇧🇭" />
                    <p class="description">Paste a flag emoji: 🇧🇭 🇸🇦 🇦🇪 — or leave blank for no flag</p>
                </td>
            </tr>

        </table>

        <table class="form-table">

            <tr>
                <th>Floating Switcher Button</th>
                <td>
                    <label>
                        <input type="checkbox" name="switcher_floating" value="1"
                               <?php echo isset( $s['switcher_floating'] ) ? ah_checked( 'switcher_floating', true, $s ) : 'checked'; ?> />
                        Show a floating language button on all pages (on by default)
                    </label>
                </td>
            </tr>

            <tr>
                <th>Arabic Font</th>
                <td>
                    <select name="ar_font">
                        <option value="Tajawal" <?php echo ah_selected( 'ar_font', 'Tajawal', $s ); ?>>Tajawal (Recommended)</option>
                        <option value="Cairo"   <?php echo ah_selected( 'ar_font', 'Cairo',   $s ); ?>>Cairo</option>
                        <option value="Almarai" <?php echo ah_selected( 'ar_font', 'Almarai', $s ); ?>>Almarai</option>
                        <option value="Noto Kufi Arabic" <?php echo ah_selected( 'ar_font', 'Noto Kufi Arabic', $s ); ?>>Noto Kufi Arabic</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th>Auto-Detect Browser Language</th>
                <td>
                    <label>
                        <input type="checkbox" name="auto_detect_browser" value="1"
                               <?php echo ah_checked( 'auto_detect_browser', true, $s ); ?> />
                        Automatically switch to Arabic for Arabic-language browsers
                    </label>
                </td>
            </tr>

            <tr>
                <th>SEO Hreflang Tags</th>
                <td>
                    <label>
                        <input type="checkbox" name="seo_hreflang" value="1"
                               <?php echo ah_checked( 'seo_hreflang', true, $s ); ?> />
                        Output <code>hreflang</code> alternate link tags in <code>&lt;head&gt;</code>
                    </label>
                    <p class="description">Recommended for Google to index both Arabic and English versions.</p>
                </td>
            </tr>

            <tr>
                <th>Cookie Duration</th>
                <td>
                    <input type="number" name="cookie_expire_days" min="1" max="365"
                           value="<?php echo esc_attr( $s['cookie_expire_days'] ?? 30 ); ?>" style="width:80px;" /> days
                    <p class="description">How long to remember the visitor's language preference.</p>
                </td>
            </tr>

        </table>

        <h2 style="margin-top:32px; border-top:1px solid #ddd; padding-top:20px;">Auto-Translation API</h2>
        <p style="color:#555; max-width:600px;">Connect an auto-translation API to automatically translate product titles, descriptions, and WooCommerce strings into Arabic with one click.</p>

        <table class="form-table">

            <tr>
                <th>Translation Provider</th>
                <td>
                    <select name="translation_provider" id="ah-provider-select">
                        <option value="mymemory"  <?php echo ah_selected( 'translation_provider', 'mymemory',  $s ); ?>>MyMemory (Free — no key needed)</option>
                        <option value="anthropic" <?php echo ah_selected( 'translation_provider', 'anthropic', $s ); ?>>Anthropic / Claude — claude-haiku</option>
                        <option value="openai"    <?php echo ah_selected( 'translation_provider', 'openai',    $s ); ?>>OpenAI — gpt-4o-mini</option>
                        <option value="gemini"    <?php echo ah_selected( 'translation_provider', 'gemini',    $s ); ?>>Google Gemini — gemini-2.0-flash</option>
                        <option value="azure"     <?php echo ah_selected( 'translation_provider', 'azure',     $s ); ?>>Microsoft Azure Translator</option>
                        <option value="deepl"     <?php echo ah_selected( 'translation_provider', 'deepl',     $s ); ?>>DeepL</option>
                        <option value="google"    <?php echo ah_selected( 'translation_provider', 'google',    $s ); ?>>Google Translate (Cloud API)</option>
                    </select>
                    <p class="description">All AI providers (Claude, OpenAI, Gemini) give the best luxury-brand Arabic tone. MyMemory is free to start.</p>
                </td>
            </tr>

            <tr class="ah-api-row ah-row-openai" <?php if ( ( $s['translation_provider'] ?? 'mymemory' ) !== 'openai' ) echo 'style="display:none"'; ?>>
                <th>OpenAI API Key</th>
                <td>
                    <input type="password" name="openai_api_key" style="width:400px"
                           value="<?php echo esc_attr( $s['openai_api_key'] ?? '' ); ?>"
                           placeholder="sk-..." autocomplete="off" />
                    <p class="description">Get your key at platform.openai.com. Uses <code>gpt-4o-mini</code> — fast, affordable, excellent Arabic quality.</p>
                </td>
            </tr>

            <tr class="ah-api-row ah-row-gemini" <?php if ( ( $s['translation_provider'] ?? 'mymemory' ) !== 'gemini' ) echo 'style="display:none"'; ?>>
                <th>Gemini API Key</th>
                <td>
                    <input type="password" name="gemini_api_key" style="width:400px"
                           value="<?php echo esc_attr( $s['gemini_api_key'] ?? '' ); ?>"
                           placeholder="AIza..." autocomplete="off" />
                    <p class="description">Get your key at aistudio.google.com — free tier available.</p>
                    <p style="margin-top:10px;">
                        <label style="font-weight:600;">Model:&nbsp;</label>
                        <?php $gm = $s['gemini_model'] ?? ''; ?>
                        <select name="gemini_model" id="ah-gemini-model" style="width:300px">
                            <option value="" <?php selected( $gm, '' ); ?>>Auto — let the plugin pick a working model</option>
                            <?php if ( $gm ) : ?>
                                <option value="<?php echo esc_attr( $gm ); ?>" selected><?php echo esc_html( $gm ); ?> (saved)</option>
                            <?php endif; ?>
                        </select>
                        <button type="button" class="button" id="ah-detect-gemini-btn" style="margin-left:8px;">Detect Available Models</button>
                    </p>
                    <p class="description" id="ah-gemini-detect-status">
                        Leave on <strong>Auto</strong> and the plugin tries the best model your key supports. Or click
                        <strong>Detect Available Models</strong> to list exactly what your key can use right now.
                    </p>
                </td>
            </tr>

            <tr class="ah-api-row ah-row-azure" <?php if ( ( $s['translation_provider'] ?? 'mymemory' ) !== 'azure' ) echo 'style="display:none"'; ?>>
                <th>Azure Translator Key</th>
                <td>
                    <input type="password" name="azure_api_key" style="width:400px"
                           value="<?php echo esc_attr( $s['azure_api_key'] ?? '' ); ?>"
                           placeholder="32-character key..." autocomplete="off" />
                    <p class="description">Get your key in Azure Portal → Cognitive Services → Translator. Free tier: 2 million characters/month.</p>
                </td>
            </tr>

            <tr class="ah-api-row ah-row-azure" <?php if ( ( $s['translation_provider'] ?? 'mymemory' ) !== 'azure' ) echo 'style="display:none"'; ?>>
                <th>Azure Region</th>
                <td>
                    <input type="text" name="azure_region" style="width:200px"
                           value="<?php echo esc_attr( $s['azure_region'] ?? 'eastus' ); ?>"
                           placeholder="eastus" />
                    <p class="description">The Azure region your resource is in. e.g. <code>eastus</code>, <code>westeurope</code>, <code>uaenorth</code>.</p>
                </td>
            </tr>

            <tr class="ah-api-row ah-row-anthropic" <?php if ( ( $s['translation_provider'] ?? 'mymemory' ) !== 'anthropic' ) echo 'style="display:none"'; ?>>
                <th>Anthropic API Key</th>
                <td>
                    <input type="password" name="anthropic_api_key" style="width:400px"
                           value="<?php echo esc_attr( $s['anthropic_api_key'] ?? '' ); ?>"
                           placeholder="sk-ant-..." autocomplete="off" />
                    <p class="description">Get your key at console.anthropic.com. Uses <code>claude-haiku-4-5-20251001</code> (fast &amp; cheap).</p>
                </td>
            </tr>

            <tr class="ah-api-row ah-row-deepl" <?php if ( ( $s['translation_provider'] ?? 'mymemory' ) !== 'deepl' ) echo 'style="display:none"'; ?>>
                <th>DeepL API Key</th>
                <td>
                    <input type="password" name="deepl_api_key" style="width:400px"
                           value="<?php echo esc_attr( $s['deepl_api_key'] ?? '' ); ?>"
                           placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx:fx" autocomplete="off" />
                    <p class="description">Free plan keys end in <code>:fx</code>. Get yours at deepl.com/pro-api.</p>
                </td>
            </tr>

            <tr class="ah-api-row ah-row-google" <?php if ( ( $s['translation_provider'] ?? 'mymemory' ) !== 'google' ) echo 'style="display:none"'; ?>>
                <th>Google Translate API Key</th>
                <td>
                    <input type="password" name="google_translate_key" style="width:400px"
                           value="<?php echo esc_attr( $s['google_translate_key'] ?? '' ); ?>"
                           placeholder="AIza..." autocomplete="off" />
                    <p class="description">Enable Cloud Translation API in Google Cloud Console.</p>
                </td>
            </tr>

            <tr class="ah-api-row ah-row-mymemory" <?php if ( ( $s['translation_provider'] ?? 'mymemory' ) !== 'mymemory' ) echo 'style="display:none"'; ?>>
                <th>MyMemory Email (optional)</th>
                <td>
                    <input type="email" name="mymemory_email" style="width:300px"
                           value="<?php echo esc_attr( $s['mymemory_email'] ?? '' ); ?>"
                           placeholder="you@example.com" />
                    <p class="description">Leave blank to use the free tier. Adding a valid email raises the daily limit.</p>
                </td>
            </tr>

            <tr>
                <th>Test Connection</th>
                <td>
                    <button type="button" id="ah-test-api-btn" class="button">Test API Connection</button>
                    <span id="ah-test-api-result" style="margin-left:12px; font-size:13px;"></span>
                    <p class="description">Tests the provider &amp; key currently selected above — <strong>you do not need to Save first</strong>.</p>
                </td>
            </tr>

        </table>

        <div id="ah-settings-notice" style="display:none;margin:12px 0;padding:12px 18px;border-radius:6px;font-weight:600;font-size:14px;max-width:760px;"></div>
        <p class="submit">
            <button type="submit" id="ah-settings-save-btn" class="button button-primary button-large">Save Settings</button>
        </p>
    </form>

<script>
/* Pure vanilla JS — no jQuery dependency, cannot silently fail on load order */
(function () {
    var ajaxUrl = (typeof ajaxurl !== 'undefined') ? ajaxurl : '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
    var nonce   = '<?php echo esc_js( $ajax_nonce ); ?>';

    function $(sel) { return document.querySelector(sel); }

    // ── Show/hide the API-key row matching the selected provider ──
    var providerSelect = $('#ah-provider-select');
    function syncRows() {
        document.querySelectorAll('.ah-api-row').forEach(function (r) { r.style.display = 'none'; });
        document.querySelectorAll('.ah-row-' + providerSelect.value).forEach(function (r) { r.style.display = ''; });
    }
    providerSelect.addEventListener('change', syncRows);

    // The key field name for each provider
    var KEY_FIELD = {
        anthropic: 'anthropic_api_key', openai: 'openai_api_key', gemini: 'gemini_api_key',
        azure: 'azure_api_key', deepl: 'deepl_api_key', google: 'google_translate_key',
        mymemory: 'mymemory_email'
    };

    // ── Test Connection (tests the currently selected, unsaved values) ──
    $('#ah-test-api-btn').addEventListener('click', function () {
        var btn = this, res = $('#ah-test-api-result');
        var provider = providerSelect.value;
        var keyEl    = document.querySelector('[name="' + (KEY_FIELD[provider] || '') + '"]');
        var regionEl = document.querySelector('[name="azure_region"]');
        var modelEl  = document.querySelector('[name="gemini_model"]');

        btn.disabled = true; btn.textContent = 'Testing…';
        res.style.color = '#555'; res.textContent = 'Contacting ' + provider.toUpperCase() + '…';

        var body = new URLSearchParams();
        body.append('action', 'ah_test_translation_api');
        body.append('nonce', nonce);
        body.append('test_provider', provider);
        body.append('test_key', keyEl ? keyEl.value : '');
        body.append('test_region', regionEl ? regionEl.value : '');
        body.append('test_model', modelEl ? modelEl.value : '');

        fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (j.success) {
                    res.style.color = 'green';
                    res.textContent = '✓ ' + j.data.message + ' → ' + j.data.translation;
                } else {
                    res.style.color = '#b91c1c';
                    res.textContent = '✗ ' + j.data;
                }
            })
            .catch(function (err) {
                res.style.color = '#b91c1c';
                res.textContent = '✗ Request failed: ' + err;
            })
            .finally(function () {
                btn.disabled = false; btn.textContent = 'Test API Connection';
            });
    });

    // ── Detect available Gemini models for the typed key ──
    var detectBtn = $('#ah-detect-gemini-btn');
    if (detectBtn) {
        detectBtn.addEventListener('click', function () {
            var status = $('#ah-gemini-detect-status');
            var sel    = $('#ah-gemini-model');
            var keyEl  = document.querySelector('[name="gemini_api_key"]');
            detectBtn.disabled = true; detectBtn.textContent = 'Detecting…';
            status.style.color = '#555'; status.textContent = 'Asking Google which models your key supports…';

            var body = new URLSearchParams();
            body.append('action', 'ah_gemini_list_models');
            body.append('nonce', nonce);
            body.append('test_key', keyEl ? keyEl.value : '');

            fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    if (j.success && j.data.models && j.data.models.length) {
                        var current = sel.value;
                        sel.innerHTML = '<option value="">Auto — let the plugin pick a working model</option>';
                        j.data.models.forEach(function (m, i) {
                            var o = document.createElement('option');
                            o.value = m;
                            o.textContent = m + (i === 0 ? ' (fastest available)' : '');
                            if (m === current) o.selected = true;
                            sel.appendChild(o);
                        });
                        status.style.color = 'green';
                        status.textContent = '✓ ' + j.data.models.length + ' usable model(s) found. "Auto" is recommended — or pick one above, then Save.';
                    } else {
                        status.style.color = '#b91c1c';
                        status.textContent = '✗ ' + (j.data || 'No models found.');
                    }
                })
                .catch(function (err) {
                    status.style.color = '#b91c1c';
                    status.textContent = '✗ Request failed: ' + err;
                })
                .finally(function () {
                    detectBtn.disabled = false; detectBtn.textContent = 'Detect Available Models';
                });
        });
    }

    // ── Save Settings (vanilla fetch, instant on-page feedback) ──
    $('#ah-settings-form').addEventListener('submit', function (e) {
        e.preventDefault();
        var form   = this;
        var btn    = $('#ah-settings-save-btn');
        var notice = $('#ah-settings-notice');
        var BASE   = 'margin:12px 0;padding:12px 18px;border-radius:6px;font-weight:600;font-size:14px;max-width:760px;';

        btn.disabled = true; btn.textContent = 'Saving…';
        notice.setAttribute('style', BASE + 'display:none;');

        // Build URLSearchParams (same content-type as Test — bypasses multipart blocks).
        var params = new URLSearchParams();
        // All named inputs (text, password, select, hidden, textarea)
        Array.from(form.elements).forEach(function (el) {
            if (!el.name) return;
            if (el.type === 'checkbox') return; // handled separately below
            if (el.type === 'radio' && !el.checked) return;
            params.append(el.name, el.value);
        });
        // Checkboxes: send '1' if checked, '0' if not
        ['auto_detect_browser', 'seo_hreflang', 'translate_emails', 'switcher_floating'].forEach(function (name) {
            var el = form.elements[name];
            params.set(name, (el && el.checked) ? '1' : '0');
        });

        fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (j) {
                if (j.success) {
                    notice.setAttribute('style', BASE + 'display:block;background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;');
                    notice.textContent = '✓ ' + j.data.message + ' Provider: ' + (j.data.provider || '').toUpperCase() + '. Reloading…';
                    setTimeout(function () { location.href = location.href.split('?')[0] + '?page=ah-arabic-settings&_c=' + Date.now(); }, 1400);
                } else {
                    notice.setAttribute('style', BASE + 'display:block;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;');
                    notice.textContent = '✗ Save failed: ' + (j.data || 'Unknown error');
                }
            })
            .catch(function (err) {
                notice.setAttribute('style', BASE + 'display:block;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;');
                notice.textContent = '✗ Request error: ' + err + '. Check browser console (F12) for details.';
            })
            .finally(function () {
                btn.disabled = false; btn.textContent = 'Save Settings';
            });
    });
})();
</script>
</div>
