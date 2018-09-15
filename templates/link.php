<?php
/**
 * A template for an HTML anchor.
 *
 * @package PcfWooCommerce
 *
 * @var $c callable The function used to retrieve context values.
 */

?>
<a
	href="<?php echo esc_attr( $c( 'href' ) ); ?>"
	class="<?php echo esc_attr( $c( 'class' ) ); ?>"
	title="<?php echo esc_attr( $c( 'title' ) ); ?>"
>
	<?php echo esc_html( $c( 'content' ) ); ?>
</a>
