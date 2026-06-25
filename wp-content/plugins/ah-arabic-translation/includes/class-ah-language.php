<?php
defined( 'ABSPATH' ) || exit;

class AH_Language {

    private static $instance = null;
    private static $current  = null;
    private static $forced   = null;

    const SUPPORTED = [ 'en', 'ar' ];
    const COOKIE    = 'ah_lang';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init',              [ $this, 'detect_and_set' ], 1 );
        add_filter( 'locale',            [ $this, 'filter_locale'  ] );
        add_filter( 'query_vars',        [ $this, 'add_query_var'  ] );
        add_action( 'template_redirect', [ $this, 'handle_switch'  ] );
    }

    public function add_query_var( $vars ) {
        $vars[] = 'lang';
        return $vars;
    }

    public function detect_and_set() {
        if ( is_admin() && ! wp_doing_ajax() ) return;

        $lang = $this->resolve_language();
        $lang = apply_filters( 'ah_resolved_language', $lang );

        if ( ! in_array( $lang, self::SUPPORTED, true ) ) {
            $lang = AH_Core::setting( 'default_lang', 'en' );
        }

        self::$current = $lang;

        if ( ! headers_sent() ) {
            $expires = time() + ( (int) AH_Core::setting( 'cookie_expire_days', 30 ) * DAY_IN_SECONDS );
            setcookie( self::COOKIE, $lang, $expires, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
        }
    }

    private function resolve_language() {
        if ( AH_LANG_FROM_URL !== '' ) {
            return AH_LANG_FROM_URL;
        }
        if ( isset( $_COOKIE[ self::COOKIE ] ) ) {
            $cookie = sanitize_key( $_COOKIE[ self::COOKIE ] );
            if ( in_array( $cookie, self::SUPPORTED, true ) ) {
                return $cookie;
            }
        }
        if ( AH_Core::setting( 'auto_detect_browser', true ) ) {
            $accept = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
            if ( preg_match( '/\bar\b/i', $accept ) ) {
                return 'ar';
            }
        }
        return AH_Core::setting( 'default_lang', 'en' );
    }

    public function handle_switch() {
        if ( ! isset( $_GET['ah_switch_lang'] ) ) return;

        $lang = sanitize_key( $_GET['ah_switch_lang'] );
        if ( ! in_array( $lang, self::SUPPORTED, true ) ) {
            wp_safe_redirect( remove_query_arg( 'ah_switch_lang' ) );
            exit;
        }

        $expires = time() + ( (int) AH_Core::setting( 'cookie_expire_days', 30 ) * DAY_IN_SECONDS );
        setcookie( self::COOKIE, $lang, $expires, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
        do_action( 'ah_language_switched', $lang );

        $redirect = remove_query_arg(
            'ah_switch_lang',
            class_exists( 'AH_URL' ) ? AH_URL::lang_url( $lang ) : home_url( '/' )
        );
        wp_safe_redirect( $redirect );
        exit;
    }

    public function filter_locale( $locale ) {
        return 'ar' === self::current() ? 'ar' : $locale;
    }

    public static function current() {
        if ( null !== self::$forced  ) return self::$forced;
        if ( null === self::$current ) {
            self::$current = AH_Core::setting( 'default_lang', 'en' );
        }
        return self::$current;
    }

    public static function is_rtl() {
        return self::current() === 'ar';
    }

    public static function force( $lang ) {
        self::$forced = in_array( $lang, self::SUPPORTED, true ) ? $lang : 'en';
    }

    public static function reset_force() {
        self::$forced = null;
    }

    public static function switch_url( $lang ) {
        if ( class_exists( 'AH_URL' ) ) {
            $base = AH_URL::lang_url( $lang );
            return add_query_arg( 'ah_switch_lang', $lang, $base );
        }
        $url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        return add_query_arg( 'ah_switch_lang', $lang, remove_query_arg( [ 'lang', 'ah_switch_lang' ], $url ) );
    }

    public static function label( $lang ) {
        return [ 'en' => 'English', 'ar' => 'العربية' ][ $lang ] ?? strtoupper( $lang );
    }

    public static function flag( $lang ) {
        return [ 'en' => '🇬🇧', 'ar' => '🇧🇭' ][ $lang ] ?? '';
    }
}
