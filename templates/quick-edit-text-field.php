<?php
/**
 * A template for the quick edit text field.
 *
 * @package PcfWooCommerce
 *
 * @var $c callable The function used to retrieve context values.
 */

?>
<div style="clear:both;"></div>
<label class="<?php echo esc_attr( $c( 'class' ) ); ?>">
	<span class="title"><?php echo esc_html( $c( 'title' ) ); ?></span>
	<span class="input-text-wrap">
		<input type="text" name="<?php echo esc_attr( $c( 'name' ) ); ?>" class="text" placeholder="<?php echo esc_attr( $c( 'title' ) ); ?>" value="" />
	</span>
</label>
