<?php 
namespace Artiosmedia\WC_Product_Code;

class Main {
    private $admin;

    public function __construct()
    {
        $this->admin = new Admin();

        $this->actions();
    }

    public function actions()
    {
        $this->admin->actions();

        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
        add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_code_to_cart_product' ], 10, 3 );
        add_filter( 'woocommerce_get_item_data', [ $this, 'retrieve_product_code_in_cart' ], 10, 2 );
        add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'process_order_item' ], 10, 4 );
        add_action( 'woocommerce_order_item_get_formatted_meta_data', [ $this, 'get_formatted_order_item_meta_data' ], 10, 2 );
        add_action( 'woocommerce_order_item_display_meta_key', [ $this, 'get_order_item_meta_display_key' ], 10, 3 );
        add_action( 'woocommerce_product_meta_start', [ $this, 'display_product_code' ] );
        add_filter( 'woocommerce_get_sections_products', [ $this, 'add_woocommerce_settings' ] );
        add_filter( 'woocommerce_get_settings_products', [ $this, 'add_product_code_settings' ], 10, 2 );
        add_action( 'wp_ajax_product_code', [ $this, 'ajax_get_product_code' ] );
        add_action( 'wp_ajax_nopriv_product_code', [ $this, 'ajax_get_product_code' ] );
    }

    public function enqueue()
    {
        global $post;

        if( is_single() && $post->post_type == 'product' ):
            //wp_enqueue_script( 'product-code-frontend', PRODUCT_CODE_URL . '/assets/js/stl_custom.js', [ 'wc-add-to-cart-variation', 'jquery' ]);

            wp_register_style( 'product-code-frontend', PRODUCT_CODE_URL . '/assets/css/single-product.css' );

            wp_enqueue_script( 'product-code-for-woocommerce', PRODUCT_CODE_URL . '/assets/js/editor.js', [ 'jquery' ] );
            wp_localize_script( 'product-code-for-woocommerce', 'PRODUCT_CODE', [ 'ajax' => admin_url( 'admin-ajax.php' ) ] );
        endif;
    }

    public function add_code_to_cart_product( $cart_item_data, $product_id, $variation_id )
    {
        $id = $variation_id ? $variation_id : $product_id;

        $variant_field_name = PRODUCT_CODE_FIELD_NAMES[ 'variant' ];
        $simple_field_name  = PRODUCT_CODE_FIELD_NAMES[ 'nonvariant' ];

        $variant_value = get_post_meta( $id, $variant_field_name, true );
        if ( $variant_value ) {
            $cart_item_data[ $variant_field_name ] = $variant_value;
        }

        $simple_value = get_post_meta( $id, $simple_field_name, true );
        if ( $simple_value ) {
            $cart_item_data[ $simple_field_name ] = $simple_value;
        }

        return $cart_item_data;
    }

    public function retrieve_product_code_in_cart( $cart_item_data, $cart_item )
    {
        $variant_field_name = PRODUCT_CODE_FIELD_NAMES[ 'variant' ];
        $simple_field_name  = PRODUCT_CODE_FIELD_NAMES[ 'nonvariant' ];

        $cart_data = [];
        if ( isset( $cart_item[ $variant_field_name ] ) ) {
            $cart_data[] = array(
                'name'  => __( 'Product Code', 'product-code-for-woocommerce' ),
                'value' => $cart_item[ $variant_field_name ],
            );
        }
        if ( isset( $cart_item[ $simple_field_name ] ) ) {
            $cart_data[] = array(
                'name'  => __( 'Product Code', 'product-code-for-woocommerce' ),
                'value' => $cart_item[ $simple_field_name ],
            );
        }

        return array_merge( $cart_item_data, $cart_data );
    }

    public function process_order_item( $item, $cart_item_key, $values, $order )
    {
        $variant_field_name = PRODUCT_CODE_FIELD_NAMES[ 'variant' ];
        $simple_field_name  = PRODUCT_CODE_FIELD_NAMES[ 'nonvariant' ];

        if ( isset( $values[ $variant_field_name ] ) ) {
            $item->add_meta_data( __( 'Product Code', 'product-code-for-woocommerce' ), $values[ $variant_field_name ], false );
        }

        if ( isset( $values[ $simple_field_name ] ) ) {
            $item->add_meta_data( __( 'Product Code', 'product-code-for-woocommerce' ), $values[ $simple_field_name ], false );
        }
    }

    public function get_formatted_order_item_meta_data( $formatted_meta, $item )
    {
        $field_name = PRODUCT_CODE_FIELD_NAMES[ 'nonvariant' ];

        foreach ( $formatted_meta as $idx => $meta ) {
            if ( $meta->key === $field_name ) {
                return $formatted_meta;
            }
        }

        $value = $item->get_meta( $field_name );
        if ( empty( $value ) ) {
            return $formatted_meta;
        }

        $formatted_meta[ $field_name ] = (object) [
            'key'           => $field_name,
            'value'         => $value,
            'display_key'   => __( 'Product Code', 'product-code-for-woocommerce' ),
            'display_value' => $value,
        ];

        return $formatted_meta;
    }

    public function get_order_item_meta_display_key( $display_key, $meta, $item )
    {
        if ( $meta->key === PRODUCT_CODE_FIELD_NAMES[ 'nonvariant' ] ) {
            return __( 'Product Code', 'product-code-for-woocommerce' );
        }

        return $display_key;
    }

    public function display_product_code()
    {
        if( get_option( 'product_code' ) == 'yes' ) {
            $post  = get_post();
            $value = get_post_meta( $post->ID, PRODUCT_CODE_FIELD_NAMES[ 'nonvariant' ], true );

            require_once( PRODUCT_CODE_TEMPLATE_PATH . '/product-meta-row.php' );
            return;
        }
    }

    public function add_woocommerce_settings( $sections )
    {
        $sections[ 'product_code_settings' ] = __( 'Product Code', 'product-code-for-woocommerce' );
        return $sections;
    }

    public function add_product_code_settings( $settings, $current_section )
    {
        if( $current_section == 'product_code_settings' ) {
            $settings_slider = array();
            // Add Title to the Settings
            $settings_slider[] = array( 'name' => __( 'Product Code Visibility', 'product-code-for-woocommerce' ), 'type' => 'title', 'desc' => __( 'Show or hide product code on product page', 'product-code-for-woocommerce' ), 'id' => 'product_code' );
            // Add first checkbox option
            $settings_slider[] = array(
                'name'     => __( 'Show Product', 'woocommerce-product-code' ),
                'desc_tip' => '',
                'id'       => 'product_code',
                'type'     => 'checkbox',
                'css'      => 'min-width:300px;',
                'desc'     => __( 'Show Product Code on Product Page', 'product-code-for-woocommerce' ),
            );
            $settings_slider[] = array( 'type' => 'sectionend', 'id' => 'product_code_settings' );
            return $settings_slider;
        }
        return $settings;
    }

    public function ajax_get_product_code()
    {
        $variant_field_name = PRODUCT_CODE_FIELD_NAMES[ 'variant' ];
        $simple_field_name  = PRODUCT_CODE_FIELD_NAMES[ 'nonvariant' ];
        
        if( !empty( $_POST[ 'is_variant' ] ) ) {
            $value = get_post_meta( $_POST[ 'product_code_id' ], $variant_field_name, true );
        }
        else 
            $value = get_post_meta( $_POST[ 'product_code_id' ], $simple_field_name, true );

        echo json_encode([
            'status' => !empty( $value ),
            'data' => $value
        ]);

        die;
    }
}