<?php
/**
 * A template for row of a product detail on a single product page.
 *
 * @package PcfWooCommerce
 *
 * @var $c callable The function used to retrieve context values.
 */

?>
<span class="wo_productcode">
    <input type="hidden" value="<?php echo $post->ID?>" id="product_id" />
	<span><?php echo __( 'Product Code', 'product-code-for-woocommerce' )?>:</span>
	<span class="stl_codenum"><?php echo !$value ? __( 'N/A', 'product-code-for-woocommerce' ) : $value?></span>
</span>
