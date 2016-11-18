<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * This is used to create tabs on settings page. all the settings options will be shown in this tabs
 * @param  [type] $form_data [description]
 * @return [type]            [description]
 */
function settingsTabs( $form_data ) {
	$hide = '';
	if ( ! isset( $form_data[ 'enable_disable_quote' ] ) || $form_data[ 'enable_disable_quote' ] == 'no' || $form_data[ 'enable_disable_quote' ] != 0 ) {
		$hide = "style='display:none'";
	}
	?>
	<ul class='etabs'>
		<li class='tab nav-tab'><a href="#wdm_general"><?php _e( 'General', 'quoteup' ); ?></a></li>
		<li class='tab nav-tab'><a href="#wdm_email"><?php _e( 'Email', 'quoteup' ); ?></a></li>
		<li class='tab nav-tab'><a href="#wdm_display"><?php _e( 'Display', 'quoteup' ); ?></a></li>
		<li <?php echo $hide ?> id="quote-settings" class='tab nav-tab'><a href="#wdm_quote"><?php _e( 'Quotation  ', 'quoteup' ); ?></a></li>
			<?php
			do_action( 'quoteup_add_product_enquiry_tab_link', $form_data );
			do_action( 'wdm_pep_add_product_enquiry_tab_link', $form_data );
			?>
	</ul>
	<?php
}
?>