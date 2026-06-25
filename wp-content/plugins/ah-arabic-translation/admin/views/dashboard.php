<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap ah-admin-wrap">
    <div class="ah-admin-header">
        <div class="ah-admin-logo">🌐</div>
        <div>
            <h1>AH Arabic Translation</h1>
            <p class="ah-subtitle">Bilingual Arabic / English support for AH Brands — v<?php echo esc_html( $version ); ?></p>
        </div>
    </div>

    <div class="ah-stats-grid">
        <div class="ah-stat-card">
            <div class="ah-stat-number"><?php echo esc_html( $total ); ?></div>
            <div class="ah-stat-label">Total Translations</div>
        </div>
        <div class="ah-stat-card">
            <div class="ah-stat-number"><?php echo esc_html( $wc_count ); ?></div>
            <div class="ah-stat-label">WooCommerce Strings</div>
        </div>
        <div class="ah-stat-card">
            <div class="ah-stat-number"><?php echo esc_html( $product_count ); ?></div>
            <div class="ah-stat-label">Product Translations</div>
        </div>
        <div class="ah-stat-card">
            <div class="ah-stat-number" style="color:<?php echo $current === 'ar' ? '#16a34a' : '#540754'; ?>">
                <?php echo strtoupper( esc_html( $current ) ); ?>
            </div>
            <div class="ah-stat-label">Current Language</div>
        </div>
    </div>

    <div class="ah-quick-links">
        <h2>Quick Actions</h2>
        <div class="ah-link-grid">
            <a href="<?php echo admin_url( 'admin.php?page=ah-arabic-autotranslate' ); ?>" class="ah-link-card">
                <span class="dashicons dashicons-translation"></span>
                <strong>Auto Translate</strong>
                <p>Bulk translate all products automatically</p>
            </a>
            <a href="<?php echo admin_url( 'admin.php?page=ah-arabic-strings' ); ?>" class="ah-link-card">
                <span class="dashicons dashicons-editor-table"></span>
                <strong>Manage Strings</strong>
                <p>Add or edit WooCommerce &amp; UI translations</p>
            </a>
            <a href="<?php echo admin_url( 'admin.php?page=ah-arabic-settings' ); ?>" class="ah-link-card">
                <span class="dashicons dashicons-admin-settings"></span>
                <strong>Settings</strong>
                <p>Switcher, fonts, SEO, translation API</p>
            </a>
            <a href="<?php echo admin_url( 'admin.php?page=ah-arabic-import-export' ); ?>" class="ah-link-card">
                <span class="dashicons dashicons-upload"></span>
                <strong>Import / Export</strong>
                <p>Bulk import translations from CSV</p>
            </a>
            <a href="<?php echo esc_url( AH_URL::prefix( home_url( '/' ) ) ); ?>" target="_blank" class="ah-link-card">
                <span class="dashicons dashicons-visibility"></span>
                <strong>Preview Arabic</strong>
                <p>View the site in Arabic mode</p>
            </a>
        </div>
    </div>

    <div class="ah-info-box">
        <h3>How translations work</h3>
        <p><strong>Automatic:</strong> When you publish a product with no Arabic translation yet, the plugin automatically translates it using your configured API (Settings → Auto-Translation API).</p>
        <p><strong>Bulk:</strong> Go to <a href="<?php echo admin_url( 'admin.php?page=ah-arabic-autotranslate' ); ?>">Auto Translate</a> to translate all existing products at once.</p>
        <p><strong>Manual override:</strong> Open any Product/Page → scroll to the <strong>"Arabic Translation"</strong> meta box → edit or click "Translate All Fields Automatically" → Save.</p>
        <p><strong>WooCommerce strings</strong> (cart, checkout, buttons) are translated automatically from the built-in dictionary. Add custom strings under <a href="<?php echo admin_url( 'admin.php?page=ah-arabic-strings' ); ?>">Manage Strings</a>.</p>
        <p><strong>Shortcode:</strong> <code>[ah_language_switcher]</code> — paste anywhere to show the language switcher.</p>
    </div>
</div>
