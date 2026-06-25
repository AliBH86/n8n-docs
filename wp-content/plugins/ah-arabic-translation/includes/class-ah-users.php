<?php
defined( 'ABSPATH' ) || exit;

class AH_Users {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'ah_resolved_language', [ $this, 'load_preference' ], 5 );
        add_action( 'ah_language_switched', [ $this, 'save_preference' ] );
    }

    public function load_preference( $lang ) {
        if ( AH_LANG_FROM_URL !== '' ) return $lang;
        if ( ! is_user_logged_in() ) return $lang;

        $saved = get_user_meta( get_current_user_id(), '_ah_preferred_lang', true );
        if ( $saved && in_array( $saved, AH_Language::SUPPORTED, true ) ) {
            return $saved;
        }
        return $lang;
    }

    public function save_preference( $lang ) {
        if ( is_user_logged_in() ) {
            update_user_meta( get_current_user_id(), '_ah_preferred_lang', sanitize_key( $lang ) );
        }
    }
}
