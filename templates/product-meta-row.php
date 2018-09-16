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
	<span><?php echo esc_html( $c( 'title' ) ); ?>:</span>
	<span class="stl_codenum"><?php echo esc_html( $c( 'value' ) ); ?></span>
</span>
