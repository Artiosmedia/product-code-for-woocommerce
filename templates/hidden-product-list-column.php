<?php
/**
 * A template for the hidden column in the product list that contains the Product Code fields.
 *
 * @package PcfWooCommerce
 *
 * @var $c callable The function used to retrieve context values.
 */

?>
<div class="hidden product_code" id="product_code_inline_<?php echo $c( 'post_id' ); ?>">
	<div id="product_codeddd"><?php echo $c( 'product_code' ); ?></div>
</div>
