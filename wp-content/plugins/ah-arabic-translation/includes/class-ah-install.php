<?php
defined( 'ABSPATH' ) || exit;

class AH_Install {

    public static function activate() {
        self::create_tables();
        self::seed_default_strings();
        add_option( 'ah_arabic_version', AH_ARABIC_VERSION );
        add_option( 'ah_arabic_settings', self::default_settings() );
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    private static function default_settings() {
        return [
            'default_lang'       => 'en',
            'languages'          => [ 'en', 'ar' ],
            'ar_font'            => 'Tajawal',
            'switcher_position'  => 'header',
            'switcher_style'     => 'flags',
            'url_type'           => 'param',   // 'param' = ?lang=ar  |  'prefix' = /ar/
            'cookie_expire_days' => 30,
            'auto_detect_browser'=> true,
            'seo_hreflang'       => true,
            'translate_emails'   => true,
            'translate_pdf'      => false,
        ];
    }

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $table   = $wpdb->prefix . 'ah_translations';

        // Check if table already exists — skip if so
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
            return;
        }

        // Use direct query for reliable table creation (dbDelta can fail silently on some hosts)
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `{$table}` (
              `id`          BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
              `lang`        VARCHAR(10)      NOT NULL DEFAULT 'ar',
              `context`     VARCHAR(100)     NOT NULL DEFAULT 'string',
              `source_key`  VARCHAR(255)     NOT NULL,
              `translation` LONGTEXT         NOT NULL,
              `object_id`   BIGINT UNSIGNED  NOT NULL DEFAULT 0,
              `created_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `unique_translation` (`lang`, `context`(50), `source_key`(200)),
              KEY `idx_object` (`object_id`),
              KEY `idx_context` (`context`(50))
            ) {$charset};"
        );
    }

    // Called on plugins_loaded as a safety net in case activation hook was missed
    public static function maybe_create_tables() {
        global $wpdb;
        $table = $wpdb->prefix . 'ah_translations';
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
            self::create_tables();
            self::seed_default_strings();
        }
    }

    private static function seed_default_strings() {
        global $wpdb;
        $table = $wpdb->prefix . 'ah_translations';

        // Only seed if table is empty
        if ( $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ) > 0 ) {
            return;
        }

        $strings = self::get_default_ar_strings();
        foreach ( $strings as $key => $val ) {
            $wpdb->replace( $table, [
                'lang'        => 'ar',
                'context'     => 'woocommerce',
                'source_key'  => $key,
                'translation' => $val,
                'object_id'   => 0,
            ], [ '%s', '%s', '%s', '%s', '%d' ] );
        }
    }

    public static function get_default_ar_strings() {
        return [
            'Shop'                          => 'المتجر',
            'Home'                          => 'الرئيسية',
            'About Us'                      => 'من نحن',
            'Contact Us'                    => 'اتصل بنا',
            'My Account'                    => 'حسابي',
            'Cart'                          => 'السلة',
            'Checkout'                      => 'الدفع',
            'Search'                        => 'بحث',
            'Add to cart'                   => 'أضف إلى السلة',
            'Add to Cart'                   => 'أضف إلى السلة',
            'Out of stock'                  => 'نفد من المخزون',
            'In stock'                      => 'متوفر',
            'Sale!'                         => 'تخفيض!',
            'Proceed to checkout'           => 'المتابعة إلى الدفع',
            'Place order'                   => 'تأكيد الطلب',
            'Login'                         => 'تسجيل الدخول',
            'Log in'                        => 'تسجيل الدخول',
            'Log out'                       => 'تسجيل الخروج',
            'Register'                      => 'إنشاء حساب',
            'New Arrivals'                  => 'وصل حديثاً',
            'Best Sellers'                  => 'الأكثر مبيعاً',
            'Free Shipping'                 => 'شحن مجاني',
        ];
    }
}
