<?php
defined( 'ABSPATH' ) || exit;

/**
 * Protects brand names and custom terms from being machine-translated.
 */
class AH_Glossary {

    private static $instance = null;

    private static $built_in = [
        'Louis Vuitton', 'LV', 'LVMH',
        'Chanel', 'Gucci', 'Prada',
        'Hermès', 'Hermes',
        'Christian Dior', 'Dior',
        'Burberry', 'Fendi', 'Balenciaga',
        'Saint Laurent', 'YSL', 'Yves Saint Laurent',
        'Givenchy', 'Versace', 'Valentino',
        'Bottega Veneta', 'Celine', 'Céline',
        'Loewe', 'Alexander McQueen',
        'Stella McCartney', 'Marc Jacobs',
        'Tom Ford', 'Maison Margiela',
        'Off-White', 'Palm Angels',
        'Coach', 'Michael Kors', 'Kate Spade', 'Tory Burch',
        'Cartier', 'Bulgari', 'Bvlgari',
        'Rolex', 'Omega', 'Patek Philippe',
        'Tiffany', 'Tiffany & Co', 'Van Cleef & Arpels', 'Chopard',
        'AH Brands', 'AH Arabic',
        'BHD', 'USD', 'EUR', 'GBP', 'SAR', 'AED',
    ];

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public static function protect( $text ) {
        $custom = get_option( 'ah_glossary_protect', [] );
        $all    = array_unique( array_filter( array_merge( self::$built_in, (array) $custom ) ) );

        usort( $all, static function ( $a, $b ) {
            return strlen( $b ) - strlen( $a );
        } );

        $placeholders = [];
        foreach ( $all as $i => $term ) {
            if ( stripos( $text, $term ) !== false ) {
                $ph                  = "\x02P{$i}\x03";
                $text                = str_ireplace( $term, $ph, $text );
                $placeholders[ $ph ] = $term;
            }
        }

        return [ $text, $placeholders ];
    }

    public static function restore( $translated, array $placeholders ) {
        if ( empty( $placeholders ) ) return $translated;
        return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $translated );
    }

    public static function forced( $term, $lang = 'ar' ) {
        $map = get_option( 'ah_glossary_forced', [] );
        $key = mb_strtolower( trim( $term ) );
        return $map[ $lang ][ $key ] ?? null;
    }

    public static function get_custom_protected() {
        return (array) get_option( 'ah_glossary_protect', [] );
    }

    public static function get_forced( $lang = 'ar' ) {
        $map = (array) get_option( 'ah_glossary_forced', [] );
        return $map[ $lang ] ?? [];
    }

    public static function save_protected( array $terms ) {
        $clean = array_values( array_filter( array_map( 'sanitize_text_field', $terms ) ) );
        update_option( 'ah_glossary_protect', $clean );
    }

    public static function save_forced( array $map, $lang = 'ar' ) {
        $all          = (array) get_option( 'ah_glossary_forced', [] );
        $all[ $lang ] = [];
        foreach ( $map as $source => $target ) {
            $source = sanitize_text_field( $source );
            $target = sanitize_text_field( $target );
            if ( $source && $target ) {
                $all[ $lang ][ mb_strtolower( $source ) ] = $target;
            }
        }
        update_option( 'ah_glossary_forced', $all );
    }
}
