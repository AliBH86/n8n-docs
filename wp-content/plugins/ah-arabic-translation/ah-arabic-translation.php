<?php
/**
 * Plugin Name: AH Arabic Translation
 * Plugin URI:  https://github.com/alibh86/AH-Arabic-translation-Plugin-
 * Description: Complete Arabic/English bilingual support for AH Brands. /ar/ subdirectory URLs, RTL layout, Yoast/Schema/Sitemap integration, auto-translation (Claude/DeepL/Google/MyMemory), WooCommerce order language lock, brand-name glossary, and organic Arabic SEO.
 * Version:     2.0.5
 * Author:      AH Brands
 * Author URI:  https://www.ahbrandsbh.com
 * Text Domain: ah-arabic
 * Domain Path: /languages
 * License:     GPL-2.0+
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 9.9
 */

defined( 'ABSPATH' ) || exit;

// ── Early /ar/ URL detection ──────────────────────────────────────────────────
// Must run BEFORE WordPress parses REQUEST_URI in wp()->parse_request().
// Strips /ar/ prefix from the URL so WordPress routes correctly,
// and defines AH_LANG_FROM_URL so language detection picks it up.
(function () {
    if ( defined( 'AH_LANG_FROM_URL' ) ) return;

    $request  = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/';
    $home     = rtrim( (string) parse_url( get_option( 'home', '' ), PHP_URL_PATH ), '/' );
    $path     = (string) parse_url( $request, PHP_URL_PATH );
    $rel      = $home !== '' ? (string) substr( $path, strlen( $home ) ) : $path;

    if ( preg_match( '#^/ar(/.*)?$#', $rel, $m ) ) {
        define( 'AH_LANG_FROM_URL', 'ar' );
        $new_rel              = ( isset( $m[1] ) && $m[1] !== '' ) ? $m[1] : '/';
        $query                = parse_url( $request, PHP_URL_QUERY );
        $_SERVER['REQUEST_URI']     = $home . $new_rel . ( $query ? '?' . $query : '' );
        $_SERVER['AH_ORIGINAL_URI'] = $request;
    } else {
        define( 'AH_LANG_FROM_URL', '' );
    }
})();

// ── Constants ─────────────────────────────────────────────────────────────────
define( 'AH_ARABIC_VERSION',  '2.0.5' );
define( 'AH_ARABIC_FILE',     __FILE__ );
define( 'AH_ARABIC_DIR',      plugin_dir_path( __FILE__ ) );
define( 'AH_ARABIC_URL',      plugin_dir_url( __FILE__ ) );
define( 'AH_ARABIC_BASENAME', plugin_basename( __FILE__ ) );

// ── WooCommerce HPOS compatibility ────────────────────────────────────────────
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

// ── Activation / Deactivation ────────────────────────────────────────────────
require_once AH_ARABIC_DIR . 'includes/class-ah-install.php';
register_activation_hook( __FILE__,   [ 'AH_Install', 'activate'   ] );
register_deactivation_hook( __FILE__, [ 'AH_Install', 'deactivate' ] );

// ── Boot ──────────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', function () {
    require_once AH_ARABIC_DIR . 'includes/class-ah-install.php';
    require_once AH_ARABIC_DIR . 'includes/class-ah-core.php';
    AH_Install::maybe_create_tables();
    AH_Core::instance();
}, 5 );
