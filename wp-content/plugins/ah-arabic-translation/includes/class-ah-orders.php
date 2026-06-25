<?php
defined( 'ABSPATH' ) || exit;

class AH_Orders {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'force_english_item_name' ], 20, 4 );
        add_action( 'woocommerce_checkout_order_created', [ $this, 'save_customer_language' ] );
        add_action( 'woocommerce_email_before_order_table', [ $this, 'set_email_language'   ], 1, 4 );
        add_action( 'woocommerce_email_footer',             [ $this, 'reset_email_language' ], 999   );
    }

    public function force_english_item_name( $item, $cart_item_key, $values, $order ) {
        $product = $values['data'] ?? null;
        if ( ! is_a( $product, 'WC_Product' ) ) return;

        $post = get_post( $product->get_id() );
        if ( $post && $post->post_title ) {
            $item->set_name( $post->post_title );
        }
    }

    public function save_customer_language( $order ) {
        $lang = AH_Language::current();
        $order->update_meta_data( '_ah_customer_lang', $lang );
        $order->save();
    }

    private static $admin_email_ids = [
        'new_order',
        'cancelled_order',
        'failed_order',
        'customer_on_hold_order',
    ];

    public function set_email_language( $order, $sent_to_admin, $plain_text, $email ) {
        $email_id = is_a( $email, 'WC_Email' ) ? $email->id : '';

        if ( $sent_to_admin || in_array( $email_id, self::$admin_email_ids, true ) ) {
            AH_Language::force( 'en' );
        } else {
            $lang = is_a( $order, 'WC_Abstract_Order' )
                ? ( $order->get_meta( '_ah_customer_lang' ) ?: 'en' )
                : 'en';
            AH_Language::force( $lang );
        }
    }

    public function reset_email_language() {
        AH_Language::reset_force();
    }
}
