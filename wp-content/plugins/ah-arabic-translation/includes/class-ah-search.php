<?php
defined( 'ABSPATH' ) || exit;

class AH_Search {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', [ $this, 'register_hooks' ], 2 );
    }

    public function register_hooks() {
        if ( ! AH_Language::is_rtl() ) return;

        add_filter( 'posts_search',  [ $this, 'extend_to_arabic_fields' ], 10, 2 );
        add_filter( 'posts_join',    [ $this, 'join_arabic_postmeta'     ], 10, 2 );
        add_filter( 'posts_groupby', [ $this, 'prevent_duplicate_results'], 10, 2 );
    }

    public function extend_to_arabic_fields( $search, $wp_query ) {
        if ( ! $this->is_front_search( $wp_query ) ) return $search;

        global $wpdb;
        $like = '%' . $wpdb->esc_like( $wp_query->get( 's' ) ) . '%';

        $search .= $wpdb->prepare(
            " OR ( `ahspm`.`meta_key` IN ('_ah_title_ar','_ah_excerpt_ar','_ah_content_ar')
                AND `ahspm`.`meta_value` LIKE %s )",
            $like
        );

        return $search;
    }

    public function join_arabic_postmeta( $join, $wp_query ) {
        if ( ! $this->is_front_search( $wp_query ) ) return $join;
        global $wpdb;
        $join .= " LEFT JOIN {$wpdb->postmeta} AS ahspm ON ( {$wpdb->posts}.ID = ahspm.post_id ) ";
        return $join;
    }

    public function prevent_duplicate_results( $groupby, $wp_query ) {
        if ( ! $this->is_front_search( $wp_query ) ) return $groupby;
        global $wpdb;
        return $groupby ?: "{$wpdb->posts}.ID";
    }

    private function is_front_search( $wp_query ) {
        return $wp_query->is_search()
            && ! $wp_query->is_admin
            && $wp_query->get( 's' );
    }
}
