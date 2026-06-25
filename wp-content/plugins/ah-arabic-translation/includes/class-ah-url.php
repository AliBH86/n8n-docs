<?php
defined( 'ABSPATH' ) || exit;

/**
 * Manages /ar/ subdirectory URL prefix for Arabic mode.
 *
 * Every Arabic-mode frontend link gets /ar/ injected after the domain:
 *   https://ahbrandsbh.com/shop/  →  https://ahbrandsbh.com/ar/shop/
 *
 * Admin, AJAX, REST API, WP-Cron, and asset URLs are never touched.
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

    // ── Hook registration ─────────────────────────────────────────

    public function register_hooks() {
        // Standard WordPress content links
        add_filter( 'post_link',           [ $this, 'maybe_prefix' ] );
        add_filter( 'page_link',           [ $this, 'maybe_prefix' ] );
        add_filter( 'post_type_link',      [ $this, 'maybe_prefix' ] );
        add_filter( 'term_link',           [ $this, 'maybe_prefix' ] );
        add_filter( 'the_permalink',       [ $this, 'maybe_prefix' ] );
        add_filter( 'get_pagenum_link',    [ $this, 'maybe_prefix' ] );
        add_filter( 'paginate_links',      [ $this, 'maybe_prefix' ] );

        // Navigation menus (handles custom-URL items too)
        add_filter( 'wp_nav_menu_objects', [ $this, 'prefix_nav_items' ], 10, 2 );

        // WooCommerce page/endpoint URLs
        add_filter( 'woocommerce_get_cart_url',         [ $this, 'maybe_prefix' ] );
        add_filter( 'woocommerce_get_checkout_url',     [ $this, 'maybe_prefix' ] );
        add_filter( 'wc_get_page_permalink',            [ $this, 'maybe_prefix' ] );
        add_filter( 'woocommerce_account_endpoint_url', [ $this, 'maybe_prefix' ] );
        add_filter( 'woocommerce_get_myaccount_page_permalink', [ $this, 'maybe_prefix' ] );

        // Home URL — only for bare home (avoids breaking wp-content, REST, AJAX)
        add_filter( 'home_url', [ $this, 'maybe_prefix_home' ], 10, 4 );

        // Prevent redirect loop: the IIFE strips /ar/ from REQUEST_URI so
        // redirect_canonical() would otherwise redirect back to /ar/ endlessly.
        add_filter( 'redirect_canonical', [ $this, 'suppress_ar_canonical_redirect' ], 10, 2 );
    }

    // ── Filters ───────────────────────────────────────────────────

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
        // home_url() is called by parse_request() to strip the base path from
        // REQUEST_URI. Prefixing it there causes /ar/ requests to 404 because the
        // IIFE has already rewritten REQUEST_URI back to /.  Only prefix after
        // WordPress has finished routing (i.e. after the 'wp' action fires).
        if ( ! did_action( 'wp' ) ) {
            return $url;
        }
        // Only modify bare home URL — not wp-content, wp-json, admin-ajax etc.
        if ( $path && $path !== '/' ) {
            return $url;
        }
        return self::prefix( $url );
    }

    public function suppress_ar_canonical_redirect( $redirect_url, $requested_url ) {
        if ( defined( 'AH_LANG_FROM_URL' ) && 'ar' === AH_LANG_FROM_URL ) {
            return false;
        }
        return $redirect_url;
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

    // ── Static helpers ────────────────────────────────────────────

    /**
     * Add /ar/ after the home domain in a URL.
     */
    public static function prefix( $url ) {
        $home    = self::base();
        $home_ar = $home . '/ar';

        // Already prefixed?
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

    /**
     * Remove /ar/ prefix — returns canonical English URL.
     */
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

    /**
     * Get the URL for switching TO a given language, based on the current page.
     */
    public static function lang_url( $lang ) {
        // Use original URL if /ar/ was stripped from REQUEST_URI
        $current = isset( $_SERVER['AH_ORIGINAL_URI'] )
            ? ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['AH_ORIGINAL_URI']
            : ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        // Remove any existing language params
        $clean   = remove_query_arg( [ 'lang', 'ah_switch_lang' ], $current );
        // Strip /ar/ to get canonical English base
        $english = self::strip( $clean );

        return $lang === 'ar' ? self::prefix( $english ) : $english;
    }

    // ── Private ───────────────────────────────────────────────────

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
