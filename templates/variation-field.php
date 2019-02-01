<?php
/**
 * A template for the variation field.
 *
 * @package PcfWooCommerce
 *
 * @var $c callable The function used to retrieve context values.
 */

?>
<div class="form-row form-row-first">
    <?php 
    woocommerce_wp_text_input([
        'id'          => sprintf( '%s_%d', $field_name, $i ),
        'label'       => __( 'Product Code', 'product-code-for-woocommerce' ),
        'desc_tip'    => true,
        'description' => __( 'Product code refers to a company’s unique internal product identifier, needed for online product fulfillment', 'product-code-for-woocommerce' ),
        'value'       => $code,
    ]);
    ?>
    <!-- <label for="product_code_<?php echo $variation->ID?>">
        <strong>
            <?php _e( 'Product Code', 'product-code-for-woocommerce' )?>
        </strong>
    </label>
    <input type="text" value="<?php echo $code?>" name="<?php echo $field_name?>" id="product_code_<?php echo $variation->ID?>" /> -->
</div><div style="clear:both;"></div>
