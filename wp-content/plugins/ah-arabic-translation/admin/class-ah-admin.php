<?php
defined( 'ABSPATH' ) || exit;

class AH_Admin {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu',              [ $this, 'add_menu'           ] );
        add_action( 'admin_enqueue_scripts',   [ $this, 'enqueue_assets'     ] );
        add_action( 'admin_post_ah_save_settings',    [ $this, 'save_settings' ] );
        add_action( 'wp_ajax_ah_save_settings',       [ $this, 'ajax_save_settings' ] );
        add_action( 'admin_post_ah_save_string',      [ $this, 'save_string'   ] );
        add_action( 'admin_post_ah_delete_string',    [ $this, 'delete_string' ] );
        add_action( 'admin_post_ah_import_csv',       [ $this, 'import_csv'    ] );
        add_action( 'admin_post_ah_export_csv',       [ $this, 'export_csv'    ] );
        add_action( 'admin_post_ah_save_glossary',    [ $this, 'save_glossary' ] );
        add_action( 'wp_ajax_ah_save_glossary',       [ $this, 'ajax_save_glossary' ] );
        add_action( 'wp_ajax_ah_save_string_inline',  [ $this, 'ajax_save_string' ] );
        add_action( 'wp_ajax_ah_delete_string_inline',[ $this, 'ajax_delete_string' ] );
        add_filter( 'plugin_action_links_' . AH_ARABIC_BASENAME, [ $this, 'plugin_links' ] );
    }

    public function add_menu() {
        add_menu_page( 'AH Arabic Translation', 'AH Arabic', 'manage_options', 'ah-arabic', [ $this, 'render_dashboard' ], 'dashicons-translation', 58 );
        add_submenu_page( 'ah-arabic', 'String Translations', 'String Translations', 'manage_options', 'ah-arabic-strings',       [ $this, 'render_strings'       ] );
        add_submenu_page( 'ah-arabic', 'Auto Translate',      'Auto Translate',      'manage_options', 'ah-arabic-autotranslate', [ $this, 'render_autotranslate' ] );
        add_submenu_page( 'ah-arabic', 'Settings',            'Settings',            'manage_options', 'ah-arabic-settings',      [ $this, 'render_settings'      ] );
        add_submenu_page( 'ah-arabic', 'Glossary',            'Glossary',            'manage_options', 'ah-arabic-glossary',      [ $this, 'render_glossary'      ] );
        add_submenu_page( 'ah-arabic', 'Import / Export',     'Import / Export',     'manage_options', 'ah-arabic-import-export', [ $this, 'render_import_export' ] );
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'ah-arabic' ) === false && ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;
        wp_enqueue_style( 'ah-admin',  AH_ARABIC_URL . 'assets/css/ah-admin.css', [], AH_ARABIC_VERSION );
        wp_enqueue_script( 'ah-admin', AH_ARABIC_URL . 'assets/js/ah-admin.js', [ 'jquery' ], AH_ARABIC_VERSION, true );
        wp_localize_script( 'ah-admin', 'ahAdmin', [ 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'ah_admin_nonce' ) ] );
    }

    public function render_dashboard() {
        global $wpdb;
        $table         = $wpdb->prefix . 'ah_translations';
        $total         = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
        $wc_count      = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE context = %s", 'woocommerce' ) );
        $product_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE context = %s", 'product' ) );
        $version       = AH_ARABIC_VERSION;
        $current       = AH_Language::current();
        require AH_ARABIC_DIR . 'admin/views/dashboard.php';
    }

    public function render_strings() {
        $context = isset( $_GET['context'] ) ? sanitize_key( $_GET['context'] ) : 'woocommerce';
        $paged   = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
        $limit   = 50; $offset = ( $paged - 1 ) * $limit;
        $strings = AH_Strings::get_all_strings( 'ar', $context, $limit, $offset );
        global $wpdb;
        $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}ah_translations WHERE lang = %s AND context = %s", 'ar', $context ) );
        require AH_ARABIC_DIR . 'admin/views/strings.php';
    }

    public function render_settings()      { $settings = get_option( 'ah_arabic_settings', [] ); require AH_ARABIC_DIR . 'admin/views/settings.php'; }
    public function render_autotranslate() {
        global $wpdb;
        $products = $wpdb->get_results( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish' ORDER BY post_title ASC LIMIT 200" );
        require AH_ARABIC_DIR . 'admin/views/autotranslate.php';
    }
    public function render_glossary()      { $protected = AH_Glossary::get_custom_protected(); $forced = AH_Glossary::get_forced( 'ar' ); require AH_ARABIC_DIR . 'admin/views/glossary.php'; }
    public function render_import_export() { require AH_ARABIC_DIR . 'admin/views/import-export.php'; }

    public function save_settings() {
        check_admin_referer( 'ah_settings_save' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        $settings = get_option( 'ah_arabic_settings', [] );
        $fields   = [ 'default_lang' => 'sanitize_key', 'ar_font' => 'sanitize_text_field', 'switcher_position' => 'sanitize_key', 'switcher_style' => 'sanitize_key', 'url_type' => 'sanitize_key', 'cookie_expire_days' => 'absint', 'translation_provider' => 'sanitize_key', 'anthropic_api_key' => 'sanitize_text_field', 'deepl_api_key' => 'sanitize_text_field', 'google_translate_key' => 'sanitize_text_field', 'mymemory_email' => 'sanitize_email', 'en_label' => 'sanitize_text_field', 'en_flag' => 'sanitize_text_field', 'ar_label' => 'sanitize_text_field', 'ar_flag' => 'sanitize_text_field', 'openai_api_key' => 'sanitize_text_field', 'gemini_api_key' => 'sanitize_text_field', 'gemini_model' => 'sanitize_text_field', 'azure_api_key' => 'sanitize_text_field', 'azure_region' => 'sanitize_text_field' ];
        $checkboxes = [ 'auto_detect_browser', 'seo_hreflang', 'translate_emails', 'switcher_floating' ];
        foreach ( $fields as $key => $sanitizer ) { if ( isset( $_POST[ $key ] ) ) $settings[ $key ] = call_user_func( $sanitizer, $_POST[ $key ] ); }
        foreach ( $checkboxes as $key ) { $settings[ $key ] = isset( $_POST[ $key ] ) && $_POST[ $key ] === '1'; }
        update_option( 'ah_arabic_settings', $settings );
        wp_safe_redirect( admin_url( 'admin.php?page=ah-arabic-settings&saved=1' ) ); exit;
    }

    public function ajax_save_settings() {
        check_ajax_referer( 'ah_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $settings = get_option( 'ah_arabic_settings', [] );
        $fields   = [ 'default_lang' => 'sanitize_key', 'ar_font' => 'sanitize_text_field', 'switcher_position' => 'sanitize_key', 'switcher_style' => 'sanitize_key', 'url_type' => 'sanitize_key', 'cookie_expire_days' => 'absint', 'translation_provider' => 'sanitize_key', 'anthropic_api_key' => 'sanitize_text_field', 'deepl_api_key' => 'sanitize_text_field', 'google_translate_key' => 'sanitize_text_field', 'mymemory_email' => 'sanitize_email', 'en_label' => 'sanitize_text_field', 'en_flag' => 'sanitize_text_field', 'ar_label' => 'sanitize_text_field', 'ar_flag' => 'sanitize_text_field', 'openai_api_key' => 'sanitize_text_field', 'gemini_api_key' => 'sanitize_text_field', 'gemini_model' => 'sanitize_text_field', 'azure_api_key' => 'sanitize_text_field', 'azure_region' => 'sanitize_text_field' ];
        $checkboxes = [ 'auto_detect_browser', 'seo_hreflang', 'translate_emails', 'switcher_floating' ];
        foreach ( $fields as $key => $sanitizer ) { if ( isset( $_POST[ $key ] ) ) $settings[ $key ] = call_user_func( $sanitizer, wp_unslash( $_POST[ $key ] ) ); }
        foreach ( $checkboxes as $key ) { $settings[ $key ] = ! empty( $_POST[ $key ] ) && '1' === $_POST[ $key ]; }
        update_option( 'ah_arabic_settings', $settings );
        wp_send_json_success( [ 'message' => 'Settings saved successfully.', 'provider' => $settings['translation_provider'] ?? 'mymemory' ] );
    }

    public function save_string() {
        check_admin_referer( 'ah_string_save' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        $source      = isset( $_POST['source_key'] )  ? wp_unslash( $_POST['source_key'] )  : '';
        $translation = isset( $_POST['translation'] )  ? wp_unslash( $_POST['translation'] )  : '';
        $context     = isset( $_POST['context'] )      ? sanitize_key( $_POST['context'] )     : 'woocommerce';
        if ( $source && $translation ) AH_Strings::save( 'ar', $context, $source, $translation );
        wp_safe_redirect( admin_url( 'admin.php?page=ah-arabic-strings&context=' . $context . '&saved=1' ) ); exit;
    }

    public function delete_string() {
        check_admin_referer( 'ah_string_delete' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        $id = absint( $_POST['string_id'] ?? 0 );
        if ( $id ) AH_Strings::delete( $id );
        wp_safe_redirect( admin_url( 'admin.php?page=ah-arabic-strings&deleted=1' ) ); exit;
    }

    public function import_csv() {
        check_admin_referer( 'ah_import_csv' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        if ( empty( $_FILES['csv_file']['tmp_name'] ) ) { wp_safe_redirect( admin_url( 'admin.php?page=ah-arabic-import-export&error=no_file' ) ); exit; }
        $file = $_FILES['csv_file']['tmp_name'];
        $context = sanitize_key( $_POST['import_context'] ?? 'woocommerce' );
        $count = 0;
        if ( ( $fh = fopen( $file, 'r' ) ) !== false ) {
            fgetcsv( $fh );
            while ( ( $row = fgetcsv( $fh ) ) !== false ) { if ( count( $row ) >= 2 ) { AH_Strings::save( 'ar', $context, trim( $row[0] ), trim( $row[1] ) ); $count++; } }
            fclose( $fh );
        }
        wp_safe_redirect( admin_url( 'admin.php?page=ah-arabic-import-export&imported=' . $count ) ); exit;
    }

    public function export_csv() {
        check_admin_referer( 'ah_export_csv' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $rows     = $wpdb->get_results( "SELECT source_key, translation, context, lang FROM {$wpdb->prefix}ah_translations ORDER BY context, source_key ASC", ARRAY_A );
        $filename = 'ah-translations-' . gmdate( 'Y-m-d' ) . '.csv';
        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        $out = fopen( 'php://output', 'w' );
        fwrite( $out, "\xEF\xBB\xBF" );
        fputcsv( $out, [ 'source_key', 'translation', 'context', 'lang' ] );
        foreach ( $rows as $row ) { fputcsv( $out, [ $row['source_key'], $row['translation'], $row['context'], $row['lang'] ] ); }
        fclose( $out ); exit;
    }

    public function ajax_save_string() {
        check_ajax_referer( 'ah_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $source      = isset( $_POST['source_key'] )  ? wp_unslash( $_POST['source_key'] )  : '';
        $translation = isset( $_POST['translation'] )  ? wp_unslash( $_POST['translation'] )  : '';
        $context     = isset( $_POST['context'] )      ? sanitize_key( $_POST['context'] )     : 'woocommerce';
        if ( ! $source || ! $translation ) wp_send_json_error( 'Missing data' );
        AH_Strings::save( 'ar', $context, $source, $translation );
        wp_send_json_success( [ 'message' => 'Saved' ] );
    }

    public function ajax_delete_string() {
        check_ajax_referer( 'ah_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $id = absint( $_POST['id'] ?? 0 );
        if ( $id ) AH_Strings::delete( $id );
        wp_send_json_success( [ 'message' => 'Deleted' ] );
    }

    public function save_glossary() {
        check_admin_referer( 'ah_glossary_save' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        $raw_protected = isset( $_POST['protected_terms'] ) ? wp_unslash( $_POST['protected_terms'] ) : '';
        $terms = array_filter( array_map( 'sanitize_text_field', explode( "\n", $raw_protected ) ) );
        AH_Glossary::save_protected( $terms );
        $sources = isset( $_POST['forced_source'] ) ? (array) $_POST['forced_source'] : [];
        $targets = isset( $_POST['forced_target'] ) ? (array) $_POST['forced_target'] : [];
        $map = [];
        foreach ( $sources as $i => $source ) {
            $source = sanitize_text_field( wp_unslash( $source ) );
            $target = sanitize_text_field( wp_unslash( $targets[ $i ] ?? '' ) );
            if ( $source && $target ) $map[ $source ] = $target;
        }
        AH_Glossary::save_forced( $map, 'ar' );
        wp_safe_redirect( admin_url( 'admin.php?page=ah-arabic-glossary&saved=1' ) ); exit;
    }

    public function ajax_save_glossary() {
        check_ajax_referer( 'ah_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $raw_protected = isset( $_POST['protected_terms'] ) ? wp_unslash( $_POST['protected_terms'] ) : '';
        $terms = array_filter( array_map( 'sanitize_text_field', explode( "\n", $raw_protected ) ) );
        AH_Glossary::save_protected( $terms );
        $sources = isset( $_POST['forced_source'] ) ? (array) $_POST['forced_source'] : [];
        $targets = isset( $_POST['forced_target'] ) ? (array) $_POST['forced_target'] : [];
        $map = [];
        foreach ( $sources as $i => $source ) {
            $source = sanitize_text_field( wp_unslash( $source ) );
            $target = sanitize_text_field( wp_unslash( $targets[ $i ] ?? '' ) );
            if ( $source && $target ) $map[ $source ] = $target;
        }
        AH_Glossary::save_forced( $map, 'ar' );
        wp_send_json_success( [ 'message' => 'Glossary saved.', 'protected_count' => count( $terms ), 'forced_count' => count( $map ) ] );
    }

    public function plugin_links( $links ) {
        $links[] = '<a href="' . admin_url( 'admin.php?page=ah-arabic-settings' ) . '">Settings</a>';
        $links[] = '<a href="' . admin_url( 'admin.php?page=ah-arabic-strings' ) . '">Translations</a>';
        return $links;
    }
}
