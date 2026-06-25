<?php
defined( 'ABSPATH' ) || exit;

class AH_Core {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_files();
        $this->init_components();
    }

    private function load_files() {
        $includes = [
            'class-ah-url.php',           // URL prefix system — load first
            'class-ah-language.php',
            'class-ah-rtl.php',
            'class-ah-glossary.php',
            'class-ah-strings.php',
            'class-ah-products.php',
            'class-ah-switcher.php',
            'class-ah-seo.php',
            'class-ah-autotranslate.php',
            'class-ah-orders.php',
            'class-ah-search.php',
            'class-ah-users.php',
            'class-ah-analytics.php',
        ];
        foreach ( $includes as $file ) {
            require_once AH_ARABIC_DIR . 'includes/' . $file;
        }
        if ( is_admin() ) {
            require_once AH_ARABIC_DIR . 'admin/class-ah-admin.php';
        }
    }

    private function init_components() {
        AH_URL::instance();
        AH_Language::instance();
        AH_RTL::instance();
        AH_Glossary::instance();
        AH_Strings::instance();
        AH_Products::instance();
        AH_Switcher::instance();
        AH_SEO::instance();
        AH_AutoTranslate::instance();
        AH_Orders::instance();
        AH_Search::instance();
        AH_Users::instance();
        AH_Analytics::instance();
        if ( is_admin() ) {
            AH_Admin::instance();
        }
    }

    // Temporary in-memory override — used by "Test Connection" to try
    // unsaved provider/key values without writing to the database.
    private static $override = [];

    public static function set_override( array $override ) {
        self::$override = $override;
    }

    public static function clear_override() {
        self::$override = [];
    }

    public static function setting( $key, $default = null ) {
        if ( array_key_exists( $key, self::$override ) ) {
            return self::$override[ $key ];
        }
        $settings = get_option( 'ah_arabic_settings', [] );
        return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
    }

    public static function update_setting( $key, $value ) {
        $settings         = get_option( 'ah_arabic_settings', [] );
        $settings[ $key ] = $value;
        update_option( 'ah_arabic_settings', $settings );
    }
}
