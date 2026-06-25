<?php
defined( 'ABSPATH' ) || exit;

/**
 * Manages /ar/ subdirectory URL prefix for Arabic mode.
 */
class AH_URL {

    private static $instance = null;
    private static $home     = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', [ $this, 'register_hooks' ], 3 );
    }

    public function register_hooks() {
        add_filter( 'post_link',           [ $this, 'maybe_prefix' ] );
        add_filter( 'page_link',           [ $this, 'maybe_prefix' ] );
        add_filter( 'post_type_link',      [ $this, 'maybe_prefix' ] );
        add_filter( 'term_link',           [ $this, 'maybe_prefix' ] );
        add_filter( 'the_permalink',       [ $this, 'maybe_prefix' ] );
        add_filter( 'get_pagenum_link',    [ $this, 'maybe_prefix' ] );
        add_filter( 'paginate_links',      [ $this, 'maybe_prefix' ] );
        add_filter( 'wp_nav_menu_objects', [ $this, 'prefix_nav_items' ], 10, 2 );
        add_filter( 'woocommerce_get_cart_url',         [ $this, 'maybe_prefix' ] );
        add_filter( 'woocommerce_get_checkout_url',     [ $this, 'maybe_prefix' ] );
        add_filter( 'wc_get_page_permalink',            [ $this, 'maybe_prefix' ] );
        add_filter( 'woocommerce_account_endpoint_url', [ $this, 'maybe_prefix' ] );
        add_filter( 'woocommerce_get_myaccount_page_permalink', [ $this, 'maybe_prefix' ] );
        add_filter( 'home_url', [ $this, 'maybe_prefix_home' ], 10, 4 );
    }

    public function maybe_prefix( $url ) {
        if ( $this->should_skip() || AH_Language::current() !== 'ar' ) {
            return $url;
        }
        return self::prefix( $url );
    }

    public function maybe_prefix_home( $url, $path, $scheme, $blog_id ) {
        if ( $this->should_skip() || AH_Language::current() !== 'ar' ) {
            return $url;
        }
        if ( $path && $path !== '/' ) {
            return $url;
        }
        return self::prefix( $url );
    }

    public function prefix_nav_items( $items, $args ) {
        if ( $this->should_skip() || AH_Language::current() !== 'ar' ) {
            return $items;
        }
        $home = self::base();
        foreach ( $items as &$item ) {
            if ( ! empty( $item->url ) && strpos( $item->url, $home ) === 0 ) {
                $item->url = self::prefix( $item->url );
            }
        }
        unset( $item );
        return $items;
    }

    public static function prefix( $url ) {
        $home    = self::base();
        $home_ar = $home . '/ar';

        if ( strpos( $url, $home_ar . '/' ) === 0 || $url === $home_ar ) {
            return $url;
        }
        if ( $url === $home || $url === $home . '/' ) {
            return $home . '/ar/';
        }
        if ( strpos( $url, $home . '/' ) === 0 ) {
            return $home . '/ar/' . substr( $url, strlen( $home ) + 1 );
        }
        return $url;
    }

    public static function strip( $url ) {
        $home    = self::base();
        $home_ar = $home . '/ar';

        if ( strpos( $url, $home_ar . '/' ) === 0 ) {
            return $home . '/' . substr( $url, strlen( $home_ar ) + 1 );
        }
        if ( $url === $home_ar || $url === $home_ar . '/' ) {
            return $home . '/';
        }
        return $url;
    }

    public static function lang_url( $lang ) {
        $current = isset( $_SERVER['AH_ORIGINAL_URI'] )
            ? ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['AH_ORIGINAL_URI']
            : ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        $clean   = remove_query_arg( [ 'lang', 'ah_switch_lang' ], $current );
        $english = self::strip( $clean );

        return $lang === 'ar' ? self::prefix( $english ) : $english;
    }

    private static function base() {
        if ( null === self::$home ) {
            self::$home = untrailingslashit( get_option( 'home' ) );
        }
        return self::$home;
    }

    private function should_skip() {
        return is_admin()
            || ( defined( 'DOING_AJAX' )  && DOING_AJAX  )
            || ( defined( 'REST_REQUEST' ) && REST_REQUEST )
            || ( defined( 'WP_CLI' )       && WP_CLI      )
            || ( defined( 'DOING_CRON' )   && DOING_CRON  );
    }
}
