<?php
defined( 'ABSPATH' ) || exit;

class AH_Products {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', [ $this, 'register_frontend_hooks' ], 2 );
        add_action( 'add_meta_boxes',    [ $this, 'register_meta_boxes'         ] );
        add_action( 'save_post_product', [ $this, 'save_product_translations'   ], 10, 2 );
        add_action( 'save_post',         [ $this, 'save_page_translations'      ], 10, 2 );
        add_action( 'save_post_product', [ $this, 'maybe_auto_translate'        ], 30, 2 );
    }

    public function register_frontend_hooks() {
        if ( ! AH_Language::is_rtl() ) return;
        add_filter( 'the_title',                     [ $this, 'translate_title'   ], 10, 2 );
        add_filter( 'the_content',                   [ $this, 'translate_content' ], 10    );
        add_filter( 'the_excerpt',                   [ $this, 'translate_excerpt' ], 10    );
        add_filter( 'woocommerce_product_title',     [ $this, 'translate_wc_title'], 10, 2 );
        add_filter( 'woocommerce_short_description', [ $this, 'translate_content' ], 10    );
        add_filter( 'get_the_terms',                     [ $this, 'translate_terms'         ], 10, 3 );
        add_filter( 'woocommerce_attribute_label',       [ $this, 'translate_attribute_label' ], 10, 3 );
        add_filter( 'woocommerce_variation_option_name', [ $this, 'translate_variation_option'], 10, 1 );
        add_filter( 'wp_get_attachment_image_attributes',[ $this, 'translate_image_alt'       ], 10, 3 );
    }

    public function maybe_auto_translate( $post_id, $post ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( wp_is_post_revision( $post_id ) ) return;
        if ( 'publish' !== $post->post_status ) return;
        if ( get_post_meta( $post_id, '_ah_title_ar', true ) ) return;
        AH_AutoTranslate::translate_post( $post_id );
    }

    public function translate_title( $title, $id = 0 ) {
        if ( ! $id ) return $title;
        $ar = get_post_meta( $id, '_ah_title_ar', true );
        return $ar ?: $title;
    }

    public function translate_wc_title( $title, $product ) {
        if ( ! $product ) return $title;
        $ar = get_post_meta( $product->get_id(), '_ah_title_ar', true );
        return $ar ?: $title;
    }

    public function translate_content( $content ) {
        $id = get_the_ID();
        if ( ! $id ) return $content;
        $ar = get_post_meta( $id, '_ah_content_ar', true );
        return $ar ?: $content;
    }

    public function translate_excerpt( $excerpt ) {
        $id = get_the_ID();
        if ( ! $id ) return $excerpt;
        $ar = get_post_meta( $id, '_ah_excerpt_ar', true );
        return $ar ?: $excerpt;
    }

    public function translate_terms( $terms, $post_id, $taxonomy ) {
        if ( ! is_array( $terms ) ) return $terms;
        foreach ( $terms as &$term ) {
            if ( isset( $term->term_id ) ) {
                $ar_name = get_term_meta( $term->term_id, '_ah_name_ar', true );
                if ( $ar_name ) $term->name = $ar_name;
            }
        }
        return $terms;
    }

    public function translate_attribute_label( $label, $name, $product ) {
        $ar = AH_Strings::lookup_direct( 'ar', 'attr:' . $name );
        return $ar ?: $label;
    }

    public function translate_variation_option( $value ) {
        $ar = AH_Strings::lookup_direct( 'ar', 'opt:' . $value );
        return $ar ?: $value;
    }

    public function translate_image_alt( $attr, $attachment, $size ) {
        if ( empty( $attr['alt'] ) ) return $attr;
        $ar = AH_Strings::lookup_direct( 'ar', 'alt:' . $attr['alt'] );
        if ( $ar ) $attr['alt'] = $ar;
        return $attr;
    }

    public function register_meta_boxes() {
        foreach ( [ 'product', 'page', 'post' ] as $screen ) {
            add_meta_box( 'ah_arabic_translation', '🌐 Arabic Translation (الترجمة العربية)', [ $this, 'render_meta_box' ], $screen, 'normal', 'default' );
        }
        add_action( 'product_cat_edit_form_fields', [ $this, 'render_term_fields' ], 10, 2 );
        add_action( 'edited_product_cat',           [ $this, 'save_term_translation' ], 10, 2 );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'ah_arabic_save', 'ah_arabic_nonce' );
        $title   = get_post_meta( $post->ID, '_ah_title_ar', true );
        $excerpt = get_post_meta( $post->ID, '_ah_excerpt_ar', true );
        $content = get_post_meta( $post->ID, '_ah_content_ar', true );
        ?>
        <div class="ah-translation-box">
            <div class="ah-auto-translate-bar">
                <strong>Auto-Translate</strong>
                <button type="button" id="ah-auto-translate-btn"
                        data-post-id="<?php echo esc_attr( $post->ID ); ?>"
                        data-nonce="<?php echo esc_attr( wp_create_nonce( 'ah_admin_nonce' ) ); ?>">
                    Translate All Fields Automatically
                </button>
                <span id="ah-auto-translate-status"></span>
            </div>
            <div style="margin-bottom:16px">
                <label for="ah_title_ar">Title — العنوان</label>
                <input type="text" id="ah_title_ar" name="ah_title_ar" value="<?php echo esc_attr( $title ); ?>" />
            </div>
            <div style="margin-bottom:16px">
                <label for="ah_excerpt_ar">Short Description — الوصف المختصر</label>
                <textarea id="ah_excerpt_ar" name="ah_excerpt_ar"><?php echo esc_textarea( $excerpt ); ?></textarea>
            </div>
            <div>
                <label for="ah_content_ar">Full Description — الوصف الكامل</label>
                <textarea id="ah_content_ar" name="ah_content_ar" style="min-height:200px"><?php echo esc_textarea( $content ); ?></textarea>
            </div>
        </div>
        <?php
    }

    public function save_product_translations( $post_id, $post ) { $this->save_post_meta( $post_id ); }
    public function save_page_translations( $post_id, $post ) {
        if ( in_array( $post->post_type, [ 'product' ], true ) ) return;
        $this->save_post_meta( $post_id );
    }

    private function save_post_meta( $post_id ) {
        if ( ! isset( $_POST['ah_arabic_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['ah_arabic_nonce'], 'ah_arabic_save' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;
        foreach ( [ 'ah_title_ar' => '_ah_title_ar', 'ah_excerpt_ar' => '_ah_excerpt_ar', 'ah_content_ar' => '_ah_content_ar' ] as $post_key => $meta_key ) {
            if ( isset( $_POST[ $post_key ] ) ) {
                $value = wp_kses_post( wp_unslash( $_POST[ $post_key ] ) );
                $value ? update_post_meta( $post_id, $meta_key, $value ) : delete_post_meta( $post_id, $meta_key );
            }
        }
    }

    public function render_term_fields( $term ) {
        $ar_name = get_term_meta( $term->term_id, '_ah_name_ar', true );
        ?>
        <tr class="form-field">
            <th><label for="ah_term_name_ar">Arabic Name — الاسم بالعربية</label></th>
            <td>
                <?php wp_nonce_field( 'ah_term_save', 'ah_term_nonce' ); ?>
                <input type="text" id="ah_term_name_ar" name="ah_term_name_ar" value="<?php echo esc_attr( $ar_name ); ?>" style="direction:rtl;font-family:Tajawal,Arial,sans-serif;" />
            </td>
        </tr>
        <?php
    }

    public function save_term_translation( $term_id ) {
        if ( ! isset( $_POST['ah_term_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['ah_term_nonce'], 'ah_term_save' ) ) return;
        if ( ! current_user_can( 'manage_categories' ) ) return;
        if ( isset( $_POST['ah_term_name_ar'] ) && $_POST['ah_term_name_ar'] !== '' ) {
            update_term_meta( $term_id, '_ah_name_ar', sanitize_text_field( wp_unslash( $_POST['ah_term_name_ar'] ) ) );
        } else {
            delete_term_meta( $term_id, '_ah_name_ar' );
        }
    }
}
