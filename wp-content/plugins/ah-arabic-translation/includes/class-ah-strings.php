<?php
defined( 'ABSPATH' ) || exit;

class AH_Strings {

    private static $instance = null;
    private static $cache    = [];

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
        if ( ! AH_Language::is_rtl() ) {
            return;
        }
        add_filter( 'gettext',              [ $this, 'translate_string'     ], 20, 3 );
        add_filter( 'gettext_with_context', [ $this, 'translate_string_ctx' ], 20, 4 );
        add_filter( 'ngettext',             [ $this, 'translate_plural'     ], 20, 5 );
    }

    public function translate_string( $translated, $original, $domain ) {
        $ar = $this->get( $original );
        return $ar ?: $translated;
    }

    public function translate_string_ctx( $translated, $original, $context, $domain ) {
        $ar = $this->get( $original );
        return $ar ?: $translated;
    }

    public function translate_plural( $translated, $single, $plural, $number, $domain ) {
        $ar = $this->get( $single );
        return $ar ?: $translated;
    }

    private function get( $key ) {
        if ( isset( self::$cache[ $key ] ) ) {
            return self::$cache[ $key ];
        }

        global $wpdb;
        $table  = $wpdb->prefix . 'ah_translations';
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT translation FROM {$table} WHERE lang = %s AND source_key = %s LIMIT 1",
                'ar',
                $key
            )
        );

        self::$cache[ $key ] = $result ?: false;
        return self::$cache[ $key ];
    }

    public static function save( $lang, $context, $source_key, $translation, $object_id = 0 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ah_translations';
        return $wpdb->replace(
            $table,
            [
                'lang'        => sanitize_key( $lang ),
                'context'     => sanitize_text_field( $context ),
                'source_key'  => wp_unslash( $source_key ),
                'translation' => wp_kses_post( $translation ),
                'object_id'   => absint( $object_id ),
            ],
            [ '%s', '%s', '%s', '%s', '%d' ]
        );
    }

    public static function get_all_strings( $lang = 'ar', $context = 'woocommerce', $limit = 200, $offset = 0 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ah_translations';
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, source_key, translation, updated_at FROM {$table}
                 WHERE lang = %s AND context = %s
                 ORDER BY source_key ASC
                 LIMIT %d OFFSET %d",
                $lang, $context, $limit, $offset
            ),
            ARRAY_A
        );
    }

    public static function lookup_direct( $lang, $key ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ah_translations';
        return (string) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT translation FROM {$table} WHERE lang = %s AND source_key = %s LIMIT 1",
                $lang,
                $key
            )
        );
    }

    public static function delete( $id ) {
        global $wpdb;
        return $wpdb->delete( $wpdb->prefix . 'ah_translations', [ 'id' => absint( $id ) ], [ '%d' ] );
    }
}
