<?php
defined( 'ABSPATH' ) || exit;

/**
 * Auto-translation engine.
 * Supports: Anthropic/Claude, DeepL, Google Translate, MyMemory (free/no key).
 */
class AH_AutoTranslate {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_ajax_ah_auto_translate_text',    [ $this, 'ajax_translate_text'    ] );
        add_action( 'wp_ajax_ah_auto_translate_product', [ $this, 'ajax_translate_product'  ] );
        add_action( 'wp_ajax_ah_auto_translate_strings', [ $this, 'ajax_translate_strings'  ] );
        add_action( 'wp_ajax_ah_test_translation_api',   [ $this, 'ajax_test_api'           ] );
        add_action( 'wp_ajax_ah_bulk_translate',         [ $this, 'ajax_bulk_translate'     ] );
        add_action( 'wp_ajax_ah_gemini_list_models',     [ $this, 'ajax_gemini_list_models' ] );
    }

    public static function translate( $text, $target = 'ar', $source = 'en' ) {
        if ( empty( trim( $text ) ) ) return '';
        [ $protected, $placeholders ] = AH_Glossary::protect( $text );
        $provider = AH_Core::setting( 'translation_provider', 'mymemory' );
        switch ( $provider ) {
            case 'anthropic': $result = self::translate_anthropic( $protected, $target, $source ); break;
            case 'openai':    $result = self::translate_openai( $protected, $target, $source ); break;
            case 'gemini':    $result = self::translate_gemini( $protected, $target, $source ); break;
            case 'azure':     $result = self::translate_azure( $protected, $target, $source ); break;
            case 'deepl':     $result = self::translate_deepl( $protected, $target, $source ); break;
            case 'google':    $result = self::translate_google( $protected, $target, $source ); break;
            default:          $result = self::translate_mymemory( $protected, $target, $source );
        }
        if ( is_wp_error( $result ) ) return $result;
        return AH_Glossary::restore( $result, $placeholders );
    }

    public static function translate_post( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) return new WP_Error( 'not_found', 'Post not found' );
        $results = [];
        if ( $post->post_title ) {
            $ar = self::translate( $post->post_title );
            if ( ! is_wp_error( $ar ) && $ar ) { update_post_meta( $post_id, '_ah_title_ar', $ar ); $results['title'] = $ar; }
        }
        if ( $post->post_excerpt ) {
            $ar = self::translate( $post->post_excerpt );
            if ( ! is_wp_error( $ar ) && $ar ) { update_post_meta( $post_id, '_ah_excerpt_ar', $ar ); $results['excerpt'] = $ar; }
        }
        if ( $post->post_content ) {
            $clean = wp_strip_all_tags( $post->post_content );
            if ( strlen( $clean ) > 10 ) {
                $ar = self::translate( $clean );
                if ( ! is_wp_error( $ar ) && $ar ) { update_post_meta( $post_id, '_ah_content_ar', $ar ); $results['content'] = $ar; }
            }
        }
        return $results;
    }

    private static function translate_anthropic( $text, $target, $source ) {
        $api_key = self::get_anthropic_key();
        if ( ! $api_key ) return new WP_Error( 'no_key', 'Anthropic API key not set.' );
        $lang_names  = [ 'ar' => 'Arabic', 'en' => 'English' ];
        $prompt = "You are a professional Arabic/English translator for a luxury fashion brand in Bahrain called AH Brands. Translate the following {$lang_names[$source]} text to {$lang_names[$target]}. Keep the luxury, elegant tone. Preserve any product names, brand names, and numbers exactly. Return ONLY the translated text.\n\n" . $text;
        $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
            'timeout' => 30,
            'headers' => [ 'Content-Type' => 'application/json', 'x-api-key' => $api_key, 'anthropic-version' => '2023-06-01' ],
            'body' => wp_json_encode( [ 'model' => 'claude-haiku-4-5-20251001', 'max_tokens' => 2048, 'messages' => [ [ 'role' => 'user', 'content' => $prompt ] ] ] ),
        ] );
        if ( is_wp_error( $response ) ) return $response;
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['content'][0]['text'] ) ) return trim( $body['content'][0]['text'] );
        return new WP_Error( 'anthropic_error', $body['error']['message'] ?? 'Unknown Anthropic API error' );
    }

    private static function get_anthropic_key() {
        $key = AH_Core::setting( 'anthropic_api_key', '' );
        if ( $key ) return $key;
        $key = get_option( 'anthropic_api_key', '' );
        if ( $key ) return $key;
        foreach ( [ 'wp_ai_anthropic_api_key', 'ai_anthropic_key', 'claude_api_key' ] as $opt ) {
            $key = get_option( $opt, '' );
            if ( $key ) return $key;
        }
        return '';
    }

    private static function translate_openai( $text, $target, $source ) {
        $api_key = AH_Core::setting( 'openai_api_key', '' );
        if ( ! $api_key ) return new WP_Error( 'no_key', 'OpenAI API key not set.' );
        $lang_names = [ 'ar' => 'Arabic', 'en' => 'English' ];
        $prompt = "Translate the following {$lang_names[$source]} text to {$lang_names[$target]} for a luxury fashion brand. Return ONLY the translated text.\n\n" . $text;
        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
            'timeout' => 30,
            'headers' => [ 'Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $api_key ],
            'body' => wp_json_encode( [ 'model' => 'gpt-4o-mini', 'max_tokens' => 2048, 'messages' => [ [ 'role' => 'user', 'content' => $prompt ] ] ] ),
        ] );
        if ( is_wp_error( $response ) ) return $response;
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['choices'][0]['message']['content'] ) ) return trim( $body['choices'][0]['message']['content'] );
        return new WP_Error( 'openai_error', $body['error']['message'] ?? 'Unknown OpenAI error' );
    }

    private static function translate_gemini( $text, $target, $source ) {
        $api_key = AH_Core::setting( 'gemini_api_key', '' );
        if ( ! $api_key ) return new WP_Error( 'no_key', 'Gemini API key not set.' );
        $lang_names = [ 'ar' => 'Arabic', 'en' => 'English' ];
        $prompt = "Translate the following {$lang_names[$source]} text to {$lang_names[$target]} for a luxury fashion brand. Return ONLY the translated text.\n\n" . $text;
        $preferred  = AH_Core::setting( 'gemini_model', '' );
        $discovered = self::gemini_available_models( $api_key );
        $models     = array_values( array_unique( array_filter( array_merge( [ $preferred ], $discovered, [ 'gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-flash-latest' ] ) ) ) );
        $payload    = wp_json_encode( [ 'contents' => [ [ 'parts' => [ [ 'text' => $prompt ] ] ] ] ] );
        $last_error = 'Unknown Gemini error';
        foreach ( $models as $model ) {
            $url      = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode( $model ) . ':generateContent?key=' . $api_key;
            $response = wp_remote_post( $url, [ 'timeout' => 30, 'headers' => [ 'Content-Type' => 'application/json' ], 'body' => $payload ] );
            if ( is_wp_error( $response ) ) { $last_error = $response->get_error_message(); continue; }
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( ! empty( $body['candidates'][0]['content']['parts'][0]['text'] ) ) return trim( $body['candidates'][0]['content']['parts'][0]['text'] );
            $err = $body['error']['message'] ?? '';
            if ( $err && ! preg_match( '/quota|not found|not supported/i', $err ) ) return new WP_Error( 'gemini_error', $err );
            if ( $err ) $last_error = $err;
        }
        return new WP_Error( 'gemini_error', $last_error );
    }

    private static function gemini_available_models( $api_key ) {
        if ( ! $api_key ) return [];
        $cache_key = 'ah_gemini_models_' . md5( $api_key );
        $cached    = get_transient( $cache_key );
        if ( is_array( $cached ) ) return $cached;
        $models = self::gemini_fetch_models( $api_key );
        set_transient( $cache_key, $models, $models ? 12 * HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS );
        return $models;
    }

    private static function gemini_fetch_models( $api_key ) {
        $url      = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $api_key . '&pageSize=200';
        $response = wp_remote_get( $url, [ 'timeout' => 20 ] );
        if ( is_wp_error( $response ) ) return [];
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['models'] ) ) return [];
        $ids = [];
        foreach ( $body['models'] as $m ) {
            $methods = $m['supportedGenerationMethods'] ?? [];
            if ( ! in_array( 'generateContent', $methods, true ) ) continue;
            $id = preg_replace( '#^models/#', '', $m['name'] ?? '' );
            if ( ! $id || preg_match( '/embedding|aqa|vision|exp|preview|thinking/i', $id ) ) continue;
            $ids[] = $id;
        }
        usort( $ids, function ( $a, $b ) {
            $af = stripos( $a, 'flash' ) !== false ? 0 : 1;
            $bf = stripos( $b, 'flash' ) !== false ? 0 : 1;
            if ( $af !== $bf ) return $af - $bf;
            return strcmp( $b, $a );
        } );
        return array_values( array_unique( $ids ) );
    }

    public function ajax_gemini_list_models() {
        check_ajax_referer( 'ah_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $key = isset( $_POST['test_key'] ) ? sanitize_text_field( wp_unslash( $_POST['test_key'] ) ) : AH_Core::setting( 'gemini_api_key', '' );
        if ( ! $key ) wp_send_json_error( 'Enter your Gemini API key first.' );
        delete_transient( 'ah_gemini_models_' . md5( $key ) );
        $models = self::gemini_fetch_models( $key );
        if ( empty( $models ) ) wp_send_json_error( 'No usable models returned.' );
        wp_send_json_success( [ 'models' => $models ] );
    }

    private static function translate_azure( $text, $target, $source ) {
        $api_key = AH_Core::setting( 'azure_api_key', '' );
        $region  = AH_Core::setting( 'azure_region', 'eastus' );
        if ( ! $api_key ) return new WP_Error( 'no_key', 'Azure Translator API key not set.' );
        $url = 'https://api.cognitive.microsofttranslator.com/translate?' . http_build_query( [ 'api-version' => '3.0', 'from' => $source, 'to' => $target ] );
        $response = wp_remote_post( $url, [ 'timeout' => 20, 'headers' => [ 'Content-Type' => 'application/json', 'Ocp-Apim-Subscription-Key' => $api_key, 'Ocp-Apim-Subscription-Region' => $region ], 'body' => wp_json_encode( [ [ 'Text' => $text ] ] ) ] );
        if ( is_wp_error( $response ) ) return $response;
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body[0]['translations'][0]['text'] ) ) return trim( $body[0]['translations'][0]['text'] );
        return new WP_Error( 'azure_error', $body['error']['message'] ?? 'Unknown Azure error' );
    }

    private static function translate_deepl( $text, $target, $source ) {
        $api_key = AH_Core::setting( 'deepl_api_key', '' );
        if ( ! $api_key ) return new WP_Error( 'no_key', 'DeepL API key not set.' );
        $endpoint = ( strpos( $api_key, ':fx' ) !== false ) ? 'https://api-free.deepl.com/v2/translate' : 'https://api.deepl.com/v2/translate';
        $response = wp_remote_post( $endpoint, [ 'timeout' => 20, 'headers' => [ 'Authorization' => 'DeepL-Auth-Key ' . $api_key, 'Content-Type' => 'application/json' ], 'body' => wp_json_encode( [ 'text' => [ $text ], 'target_lang' => strtoupper( $target ), 'source_lang' => strtoupper( $source ) ] ) ] );
        if ( is_wp_error( $response ) ) return $response;
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['translations'][0]['text'] ) ) return trim( $body['translations'][0]['text'] );
        return new WP_Error( 'deepl_error', $body['message'] ?? 'Unknown DeepL error' );
    }

    private static function translate_google( $text, $target, $source ) {
        $api_key = AH_Core::setting( 'google_translate_key', '' );
        if ( ! $api_key ) return new WP_Error( 'no_key', 'Google Translate API key not set.' );
        $url = add_query_arg( [ 'key' => $api_key, 'q' => $text, 'target' => $target, 'source' => $source, 'format' => 'text' ], 'https://translation.googleapis.com/language/translate/v2' );
        $response = wp_remote_get( $url, [ 'timeout' => 20 ] );
        if ( is_wp_error( $response ) ) return $response;
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['data']['translations'][0]['translatedText'] ) ) return trim( html_entity_decode( $body['data']['translations'][0]['translatedText'], ENT_QUOTES, 'UTF-8' ) );
        return new WP_Error( 'google_error', $body['error']['message'] ?? 'Unknown Google Translate error' );
    }

    private static function translate_mymemory( $text, $target, $source ) {
        if ( mb_strlen( $text ) > 499 ) {
            $chunks = self::split_text( $text, 490 );
            $translated_chunks = [];
            foreach ( $chunks as $chunk ) {
                $result = self::translate_mymemory( $chunk, $target, $source );
                if ( is_wp_error( $result ) ) return $result;
                $translated_chunks[] = $result;
            }
            return implode( ' ', $translated_chunks );
        }
        $email = AH_Core::setting( 'mymemory_email', '' );
        $url   = 'https://api.mymemory.translated.net/get?' . http_build_query( [ 'q' => $text, 'langpair' => $source . '|' . $target, 'de' => $email ] );
        $response = wp_remote_get( $url, [ 'timeout' => 15 ] );
        if ( is_wp_error( $response ) ) return $response;
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['responseData']['translatedText'] ) ) {
            $translated = $body['responseData']['translatedText'];
            foreach ( [ 'MYMEMORY WARNING', 'INVALID EMAIL', 'INVALID REQUEST', 'QUERY LENGTH LIMIT' ] as $signal ) {
                if ( stripos( $translated, $signal ) !== false ) return new WP_Error( 'mymemory_error', 'MyMemory error: ' . $translated );
            }
            return trim( $translated );
        }
        return new WP_Error( 'mymemory_error', 'MyMemory could not translate this text.' );
    }

    private static function split_text( $text, $max_chars ) {
        $sentences = preg_split( '/(?<=[.!?])\s+/', $text );
        $chunks = []; $current = '';
        foreach ( $sentences as $sentence ) {
            if ( mb_strlen( $current . ' ' . $sentence ) > $max_chars ) {
                if ( $current ) $chunks[] = trim( $current );
                $current = $sentence;
            } else {
                $current .= ( $current ? ' ' : '' ) . $sentence;
            }
        }
        if ( $current ) $chunks[] = trim( $current );
        return $chunks ?: [ mb_substr( $text, 0, $max_chars ) ];
    }

    public function ajax_translate_text() {
        check_ajax_referer( 'ah_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Unauthorized' );
        $text = isset( $_POST['text'] ) ? wp_unslash( $_POST['text'] ) : '';
        if ( ! $text ) wp_send_json_error( 'No text provided' );
        $result = self::translate( $text );
        is_wp_error( $result ) ? wp_send_json_error( $result->get_error_message() ) : wp_send_json_success( [ 'translation' => $result ] );
    }

    public function ajax_translate_product() {
        check_ajax_referer( 'ah_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Unauthorized' );
        $post_id = absint( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) wp_send_json_error( 'No post ID' );
        $result = self::translate_post( $post_id );
        is_wp_error( $result ) ? wp_send_json_error( $result->get_error_message() ) : wp_send_json_success( $result );
    }

    public function ajax_translate_strings() {
        check_ajax_referer( 'ah_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $strings = isset( $_POST['strings'] ) ? (array) $_POST['strings'] : [];
        $context = sanitize_key( $_POST['context'] ?? 'woocommerce' );
        $saved = 0; $errors = [];
        foreach ( $strings as $source ) {
            $source = sanitize_text_field( wp_unslash( $source ) );
            if ( ! $source ) continue;
            $ar = self::translate( $source );
            if ( is_wp_error( $ar ) ) { $errors[] = $source . ': ' . $ar->get_error_message(); continue; }
            AH_Strings::save( 'ar', $context, $source, $ar );
            $saved++;
        }
        wp_send_json_success( [ 'saved' => $saved, 'errors' => $errors ] );
    }

    public function ajax_bulk_translate() {
        check_ajax_referer( 'ah_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Unauthorized' );
        $post_ids = array_map( 'absint', (array) ( $_POST['post_ids'] ?? [] ) );
        if ( empty( $post_ids ) ) wp_send_json_error( 'No posts selected' );
        $done = 0; $errors = 0;
        foreach ( $post_ids as $id ) {
            $result = self::translate_post( $id );
            is_wp_error( $result ) ? $errors++ : $done++;
        }
        wp_send_json_success( [ 'done' => $done, 'errors' => $errors ] );
    }

    public function ajax_test_api() {
        check_ajax_referer( 'ah_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $provider = isset( $_POST['test_provider'] ) ? sanitize_key( $_POST['test_provider'] ) : '';
        if ( $provider ) {
            $override = [ 'translation_provider' => $provider ];
            $key      = isset( $_POST['test_key'] )    ? sanitize_text_field( wp_unslash( $_POST['test_key'] ) )    : '';
            $region   = isset( $_POST['test_region'] ) ? sanitize_text_field( wp_unslash( $_POST['test_region'] ) ) : '';
            $model    = isset( $_POST['test_model'] )  ? sanitize_text_field( wp_unslash( $_POST['test_model'] ) )  : '';
            switch ( $provider ) {
                case 'anthropic': $override['anthropic_api_key']    = $key; break;
                case 'openai':    $override['openai_api_key']       = $key; break;
                case 'gemini':    $override['gemini_api_key']       = $key; if ( $model ) $override['gemini_model'] = $model; break;
                case 'azure':     $override['azure_api_key']        = $key; $override['azure_region'] = $region ?: 'eastus'; break;
                case 'deepl':     $override['deepl_api_key']        = $key; break;
                case 'google':    $override['google_translate_key'] = $key; break;
                case 'mymemory':  $override['mymemory_email']       = $key; break;
            }
            AH_Core::set_override( $override );
        }
        $used_provider = AH_Core::setting( 'translation_provider', 'mymemory' );
        $result        = self::translate( 'Hello, welcome to our store.' );
        AH_Core::clear_override();
        if ( is_wp_error( $result ) ) wp_send_json_error( 'Provider tested: ' . strtoupper( $used_provider ) . ' — ' . $result->get_error_message() );
        wp_send_json_success( [ 'translation' => $result, 'provider' => $used_provider, 'message' => 'API working correctly via ' . strtoupper( $used_provider ) ] );
    }
}
