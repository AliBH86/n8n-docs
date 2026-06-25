<?php
defined( 'ABSPATH' ) || exit;

/**
 * Pushes language data to GA4 dataLayer.
 *
 * Setup in GA4:
 *  1. Admin → Data Streams → your stream → Enhanced Measurement (off for events)
 *  2. Admin → Custom Definitions → Custom Dimensions
 *     Name: "Site Language"  |  Scope: Event  |  Event parameter: site_language
 *  3. In Looker Studio / GA4 Explore, filter or segment by site_language = ar
 *     to see all Arabic visitor traffic, conversions, and revenue separately.
 */
class AH_Analytics {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_head', [ $this, 'push_data_layer' ], 1 );
    }

    public function push_data_layer() {
        $lang   = AH_Language::current();
        $is_rtl = AH_Language::is_rtl() ? 'true' : 'false';
        ?>
        <script>
        window.dataLayer = window.dataLayer || [];
        dataLayer.push({
            'event':         'ah_language',
            'site_language': '<?php echo esc_js( $lang ); ?>',
            'is_rtl':         <?php echo $is_rtl; ?>
        });
        </script>
        <?php
    }
}
