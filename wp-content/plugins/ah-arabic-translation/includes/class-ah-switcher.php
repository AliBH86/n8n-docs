<?php
defined( 'ABSPATH' ) || exit;

class AH_Switcher {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode( 'ah_language_switcher', [ $this, 'shortcode' ] );
        add_action( 'widgets_init',            [ $this, 'register_widget' ] );

        $position = AH_Core::setting( 'switcher_position', 'header' );

        if ( 'header' === $position ) {
            add_filter( 'wp_nav_menu_items', [ $this, 'nav_menu_items' ], 10, 2 );
            add_action( 'flatsome_header_top',  [ $this, 'render_header_switcher' ], 20 );
            add_action( 'flatsome_header_main', [ $this, 'render_header_switcher' ], 20 );
            add_action( 'wp_head', [ $this, 'nav_switcher_styles' ], 99 );
        }

        if ( 'top_bar' === $position ) {
            add_action( 'flatsome_header_top', [ $this, 'render_header_switcher' ], 20 );
        }

        add_action( 'wp_footer', [ $this, 'render_floating_switcher' ], 100 );
    }

    public function nav_menu_items( $items, $args ) {
        $header_locations = [
            'primary', 'main-menu', 'primary-menu', 'main_menu',
            'header', 'top', 'navigation', 'menu-1', 'top-bar-nav',
        ];

        if ( ! empty( $args->theme_location ) ) {
            if ( ! in_array( $args->theme_location, $header_locations, true ) ) {
                return $items;
            }
        }

        if ( ! class_exists( 'AH_Language' ) ) {
            return $items;
        }

        $current = AH_Language::current();
        $other   = ( 'ar' === $current ) ? 'en' : 'ar';

        $labels = [
            'en' => AH_Core::setting( 'en_label', 'EN' ),
            'ar' => AH_Core::setting( 'ar_label', 'عربي' ),
        ];
        $flags = [
            'en' => AH_Core::setting( 'en_flag', '🇺🇸' ),
            'ar' => AH_Core::setting( 'ar_flag', '🇧🇭' ),
        ];

        $switch_url = AH_Language::switch_url( $other );

        $html  = '<li class="menu-item ah-nav-lang-item">';
        $html .= '<a href="' . esc_url( $switch_url ) . '" class="ah-nav-lang-link" ';
        $html .= 'hreflang="' . esc_attr( $other ) . '" lang="' . esc_attr( $other ) . '" ';
        $html .= 'title="' . esc_attr( $labels[ $other ] ) . '">';
        $html .= '<span class="ah-nav-flag" aria-hidden="true">' . $flags[ $other ] . '</span>';
        $html .= '<span class="ah-nav-label">' . esc_html( $labels[ $other ] ) . '</span>';
        $html .= '</a></li>';

        return $items . $html;
    }

    public function nav_switcher_styles() {
        ?>
        <style>
        .ah-nav-lang-item > a.ah-nav-lang-link {
            display: inline-flex !important;
            align-items: center;
            gap: 4px;
            font-weight: 600;
            letter-spacing: .03em;
            white-space: nowrap;
        }
        .ah-nav-flag { font-size: 1.1em; line-height: 1; }
        .ah-nav-label { font-size: .9em; }
        </style>
        <?php
    }

    public function shortcode( $atts ) {
        $atts = shortcode_atts( [
            'style'     => AH_Core::setting( 'switcher_style', 'flags' ),
            'show_flag' => 'yes',
            'show_text' => 'yes',
        ], $atts );
        return $this->get_switcher_html( $atts );
    }

    public function render_header_switcher() {
        echo $this->get_switcher_html( [ 'style' => 'minimal', 'class' => 'ah-switcher-header' ] );
    }

    public function render_floating_switcher() {
        if ( 'header' === AH_Core::setting( 'switcher_position', 'header' ) ) {
            return;
        }
        if ( false === AH_Core::setting( 'switcher_floating', true ) ) {
            return;
        }
        echo $this->get_switcher_html( [ 'style' => 'floating' ] );
    }

    public function get_switcher_html( $args = [] ) {
        $current   = AH_Language::current();
        $style     = isset( $args['style'] ) ? $args['style'] : 'default';
        $extra_cls = isset( $args['class'] ) ? ' ' . esc_attr( $args['class'] ) : '';

        $languages = [
            'en' => [
                'label' => AH_Core::setting( 'en_label', 'EN' ),
                'full'  => 'English',
                'flag'  => AH_Core::setting( 'en_flag',  '🇺🇸' ),
            ],
            'ar' => [
                'label' => AH_Core::setting( 'ar_label', 'عربي' ),
                'full'  => 'العربية',
                'flag'  => AH_Core::setting( 'ar_flag',  '🇧🇭' ),
            ],
        ];

        ob_start();
        ?>
        <div class="ah-lang-switcher ah-switcher-<?php echo esc_attr( $style ); ?><?php echo $extra_cls; ?>" role="navigation" aria-label="Language switcher">
            <?php foreach ( $languages as $code => $info ) :
                $is_active = ( $code === $current );
                $url       = AH_Language::switch_url( $code );
            ?>
            <a href="<?php echo esc_url( $url ); ?>"
               class="ah-lang-item<?php echo $is_active ? ' ah-lang-active' : ''; ?>"
               hreflang="<?php echo esc_attr( $code ); ?>"
               lang="<?php echo esc_attr( $code ); ?>"
               aria-current="<?php echo $is_active ? 'true' : 'false'; ?>"
               title="<?php echo esc_attr( $info['full'] ); ?>">
                <span class="ah-lang-flag" aria-hidden="true"><?php echo $info['flag']; ?></span>
                <span class="ah-lang-label"><?php echo esc_html( $info['label'] ); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function register_widget() {
        register_widget( 'AH_Language_Widget' );
    }
}

class AH_Language_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'ah_language_widget',
            'AH Language Switcher',
            [ 'description' => 'Arabic / English language switcher for AH Brands.' ]
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }
        echo AH_Switcher::instance()->get_switcher_html( [ 'style' => 'widget' ] );
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">Title:</label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        return [ 'title' => sanitize_text_field( $new_instance['title'] ) ];
    }
}
