<?php
defined( 'ABSPATH' ) || exit;

class AH_SEO {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if ( AH_Core::setting( 'seo_hreflang', true ) ) {
            add_action( 'wp_head', [ $this, 'output_hreflang' ], 2 );
            add_action( 'wp_head', [ $this, 'output_meta'     ], 3 );
        }
        add_action( 'init', [ $this, 'register_yoast_hooks'  ], 20 );
        add_action( 'init', [ $this, 'register_schema_hooks' ], 20 );
        add_action( 'init', [ $this, 'register_sitemap_hooks'], 20 );
    }

    public function output_hreflang() {
        if ( $this->yoast_premium_active() ) return;
        $en_url = AH_URL::lang_url( 'en' );
        $ar_url = AH_URL::lang_url( 'ar' );
        echo "\n<!-- AH Arabic: hreflang -->\n";
        printf( '<link rel="alternate" hreflang="en-BH" href="%s">' . "\n", esc_url( $en_url ) );
        printf( '<link rel="alternate" hreflang="ar-BH" href="%s">' . "\n", esc_url( $ar_url ) );
        printf( '<link rel="alternate" hreflang="x-default" href="%s">' . "\n\n", esc_url( $en_url ) );
    }

    public function output_meta() {
        if ( 'ar' === AH_Language::current() ) {
            echo '<meta http-equiv="Content-Language" content="ar-BH">' . "\n";
        }
    }

    public function register_yoast_hooks() {
        if ( ! defined( 'WPSEO_VERSION' ) ) return;
        if ( AH_Language::is_rtl() ) {
            add_filter( 'wpseo_title',              [ $this, 'yoast_title'        ] );
            add_filter( 'wpseo_metadesc',           [ $this, 'yoast_metadesc'     ] );
            add_filter( 'wpseo_opengraph_title',    [ $this, 'yoast_title'        ] );
            add_filter( 'wpseo_opengraph_desc',     [ $this, 'yoast_metadesc'     ] );
            add_filter( 'wpseo_twitter_title',      [ $this, 'yoast_title'        ] );
            add_filter( 'wpseo_twitter_description',[ $this, 'yoast_metadesc'     ] );
        }
    }

    public function yoast_title( $title ) {
        $id = $this->get_queried_id();
        if ( ! $id ) return $title;
        $ar = get_post_meta( $id, '_ah_title_ar', true );
        if ( ! $ar ) return $title;
        $post = get_post( $id );
        if ( $post ) {
            $title = str_replace( $post->post_title, $ar, $title );
        }
        return $title;
    }

    public function yoast_metadesc( $desc ) {
        $id = $this->get_queried_id();
        if ( ! $id ) return $desc;
        $ar_excerpt = get_post_meta( $id, '_ah_excerpt_ar', true );
        if ( $ar_excerpt ) {
            return wp_trim_words( wp_strip_all_tags( $ar_excerpt ), 35 );
        }
        $ar_content = get_post_meta( $id, '_ah_content_ar', true );
        if ( $ar_content ) {
            return wp_trim_words( wp_strip_all_tags( $ar_content ), 35 );
        }
        return $desc;
    }

    public function register_schema_hooks() {
        if ( ! AH_Language::is_rtl() ) return;
        add_filter( 'woocommerce_structured_data_product', [ $this, 'filter_product_schema' ], 10, 2 );
        add_filter( 'woocommerce_structured_data_breadcrumblist', [ $this, 'filter_breadcrumb_schema' ] );
        add_filter( 'wpseo_schema_webpage', [ $this, 'filter_yoast_webpage_schema' ] );
        add_filter( 'wpseo_schema_article', [ $this, 'filter_yoast_article_schema' ] );
        add_filter( 'wpseo_schema_product', [ $this, 'filter_yoast_product_schema' ] );
    }

    public function filter_product_schema( $markup, $product ) {
        if ( ! is_a( $product, 'WC_Product' ) ) return $markup;
        $id = $product->get_id();
        $ar_name = get_post_meta( $id, '_ah_title_ar', true );
        $ar_desc = get_post_meta( $id, '_ah_excerpt_ar', true ) ?: get_post_meta( $id, '_ah_content_ar', true );
        if ( $ar_name ) $markup['name']        = $ar_name;
        if ( $ar_desc ) $markup['description'] = wp_strip_all_tags( $ar_desc );
        return $markup;
    }

    public function filter_breadcrumb_schema( $markup ) { return $markup; }

    public function filter_yoast_webpage_schema( $schema ) {
        $id = $this->get_queried_id();
        if ( ! $id ) return $schema;
        $ar_title = get_post_meta( $id, '_ah_title_ar', true );
        if ( $ar_title ) {
            $schema['name']     = $ar_title;
            $schema['headline'] = $ar_title;
        }
        return $schema;
    }

    public function filter_yoast_article_schema( $schema ) {
        return $this->filter_yoast_webpage_schema( $schema );
    }

    public function filter_yoast_product_schema( $schema ) {
        $id = $this->get_queried_id();
        if ( ! $id ) return $schema;
        $ar_name = get_post_meta( $id, '_ah_title_ar', true );
        $ar_desc = get_post_meta( $id, '_ah_excerpt_ar', true );
        if ( $ar_name ) $schema['name']        = $ar_name;
        if ( $ar_desc ) $schema['description'] = wp_strip_all_tags( $ar_desc );
        return $schema;
    }

    public function register_sitemap_hooks() {
        if ( defined( 'WPSEO_VERSION' ) ) {
            add_filter( 'wpseo_sitemap_entry', [ $this, 'yoast_sitemap_entry' ], 10, 3 );
            add_action( 'wpseo_sitemap_index', [ $this, 'yoast_sitemap_index_entry' ] );
        }
        add_filter( 'wp_sitemaps_posts_entry', [ $this, 'core_sitemap_entry' ], 10, 3 );
    }

    public function yoast_sitemap_entry( $url, $type, $object ) {
        if ( ! in_array( $type, [ 'post', 'page' ], true ) ) return $url;
        $base   = $url['loc'] ?? '';
        if ( ! $base ) return $url;
        $ar_url = AH_URL::prefix( $base );
        $url['alternates'] = [
            [ 'hreflang' => 'en-BH', 'href' => $base   ],
            [ 'hreflang' => 'ar-BH', 'href' => $ar_url ],
        ];
        return $url;
    }

    public function yoast_sitemap_index_entry( $sitemap_index ) {}
    public function core_sitemap_entry( $entry, $post, $post_type ) { return $entry; }

    private function get_queried_id() {
        $id = get_queried_object_id();
        return $id ?: ( function_exists( 'get_the_ID' ) ? get_the_ID() : 0 );
    }

    private function yoast_premium_active() {
        return defined( 'WPSEO_PREMIUM_PLUGIN_FILE' ) || class_exists( 'WPSEO_Premium' );
    }
}
