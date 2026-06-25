<?php
defined( 'ABSPATH' ) || exit;

class AH_RTL {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_enqueue_scripts',    [ $this, 'enqueue_assets'     ] );
        add_filter( 'body_class',            [ $this, 'body_classes'       ] );
        add_filter( 'language_attributes',   [ $this, 'html_lang_attr'     ] );
        add_action( 'wp_head',               [ $this, 'output_inline_vars' ], 1 );
    }

    public function enqueue_assets() {
        $ver = AH_ARABIC_VERSION;
        wp_enqueue_style( 'ah-fonts', AH_ARABIC_URL . 'assets/css/ah-fonts.css', [], $ver );

        if ( AH_Language::is_rtl() ) {
            wp_enqueue_style( 'ah-rtl', AH_ARABIC_URL . 'assets/css/ah-rtl.css', [ 'flatsome' ], $ver );
        }

        wp_enqueue_style( 'ah-switcher', AH_ARABIC_URL . 'assets/css/ah-switcher.css', [], $ver );
        wp_enqueue_script( 'ah-language', AH_ARABIC_URL . 'assets/js/ah-language.js', [ 'jquery' ], $ver, true );
        wp_localize_script( 'ah-language', 'ahLang', [
            'current'  => AH_Language::current(),
            'isRTL'    => AH_Language::is_rtl(),
            'switchUrl'=> AH_Language::switch_url( AH_Language::is_rtl() ? 'en' : 'ar' ),
            'arUrl'    => AH_Language::switch_url( 'ar' ),
            'enUrl'    => AH_Language::switch_url( 'en' ),
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'ah_arabic_nonce' ),
        ] );
    }

    public function body_classes( $classes ) {
        $classes[] = 'ah-lang-' . AH_Language::current();
        if ( AH_Language::is_rtl() ) {
            $classes[] = 'ah-rtl';
            $classes[] = 'rtl';
        } else {
            $classes[] = 'ah-ltr';
        }
        return $classes;
    }

    public function html_lang_attr( $output ) {
        if ( AH_Language::is_rtl() ) {
            $output  = 'lang="ar" dir="rtl"';
        } else {
            $output  = 'lang="en" dir="ltr"';
        }
        return $output;
    }

    public function output_inline_vars() {
        if ( AH_Language::is_rtl() ) {
            echo '<meta name="language" content="Arabic">' . "\n";
        }
    }
}
