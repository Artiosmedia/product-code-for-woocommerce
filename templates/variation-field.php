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
    <label for="product_code_<?php echo $variation->ID?>">
        <strong>
            <?php _e( 'Product Code', 'product-code-for-woocommerce' )?>
        </strong>
    </label>
    <input type="text" value="<?php echo $code?>" name="<?php echo $field_name?>" id="product_code_<?php echo $variation->ID?>" />
</div><div style="clear:both;"></div>
