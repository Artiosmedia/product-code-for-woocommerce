<?php 
namespace Artiosmedia\WC_Product_Code;

class Admin {
    public function actions()
    {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
        add_action( 'woocommerce_product_options_inventory_product_data', [ $this, 'add_inventory_field' ] );
        add_action( 'woocommerce_process_product_meta', [ $this, 'save_product_code_meta' ] );
        add_action( 'woocommerce_product_after_variable_attributes', [ $this, 'add_variation_field' ], 10, 3 );
        add_action( 'woocommerce_save_product_variation', [ $this, 'save_variation_field' ], 10, 1 );
        // add_action( 'woocommerce_product_quick_edit_end', [ $this, 'add_quick_edit_field' ] );
    }

    public function enqueue()
    {  
        $screen = get_current_screen();

        if ( $screen && 'product' === $screen->post_type ) {
            wp_enqueue_script( 'wc_product_code_admin', PRODUCT_CODE_URL . '/assets/js' . 'stl_admin_custom.js', [ 'jquery' ] );
        }
    }

    public function add_inventory_field()
    {
        global $post;
        $product = wc_get_product( $post->ID );

        if( !$product->is_type( 'variable' ) ) {
            return woocommerce_wp_text_input(
                [
                    'id'          => PRODUCT_CODE_FIELD_NAMES[ 'nonvariant' ],
                    'label'       => __( 'Product Code', 'product-code-for-woocommerce' ),
                    'desc_tip'    => true,
                    'description' => __( 'Product code refers to a company’s unique internal product identifier, needed for online product fulfillment', 'product-code-for-woocommerce' ),
                    'value'       => get_post_meta( $post->ID, PRODUCT_CODE_FIELD_NAMES[ 'nonvariant' ], true )
                ]
            );
        }
        return;
    }

    public function save_product_code_meta()
    {
        global $post;
   
        if( $post->post_type == 'product' && !empty( $_POST[ 'woocommerce_meta_nonce' ] ) && wp_verify_nonce( $_POST[ 'woocommerce_meta_nonce' ], 'woocommerce_save_data' ) ) {
            $field_name = PRODUCT_CODE_FIELD_NAMES[ 'nonvariant' ];

            if( !empty( $_POST[ $field_name ] ) ) {
                $code = sanitize_text_field( $_POST[ $field_name ] );
                if( !add_post_meta( $post->ID, $field_name, $code, true ) )
                    update_post_meta( $post->ID, $field_name, $code );
            } else {
                delete_post_meta( $post->ID, $field_name);
            }
        }
        return;
    }

    public function add_variation_field( $_, $__, $variation )
    {
        $field_name = PRODUCT_CODE_FIELD_NAMES[ 'variant' ];
        $code = get_post_meta( $variation->ID, $field_name, true );

        require( PRODUCT_CODE_TEMPLATE_PATH . '/variation-field.php' );
        return; 
    }

    public function save_variation_field( $variation_id )
    {
        $field_name = PRODUCT_CODE_FIELD_NAMES[ 'variant' ];

        if( !empty( $_POST[ $field_name ] ) ) {
            $code = sanitize_text_field( $_POST[ $field_name ] );
            if( !add_post_meta( $variation_id, $field_name, $code, true ) ) 
                    update_post_meta( $variation_id, $field_name, $code );
        } else {
            delete_post_meta( $variation_id, $field_name );
        }

        return;
    }

    public function add_quick_edit_field()
    {
       /* $field_name = PRODUCT_CODE_FIELD_NAMES[ 'nonvariant' ];
        $code = get_post_meta( $post->ID, $field_name, true );*/

        require_once( PRODUCT_CODE_TEMPLATE_PATH . '/quick-edit-text-field.php' );
        return;
    }
}