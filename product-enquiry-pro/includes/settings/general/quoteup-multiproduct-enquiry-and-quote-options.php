<?php

namespace Includes\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function enableDisableMultiproductQuoteSection($form_data)
{
	$mpe_cart_page_id = '';
	if ( isset( $form_data[ 'mpe_cart_page' ] ) && ! empty( $form_data[ 'mpe_cart_page' ] ) ) {
		$mpe_cart_page_id = $form_data[ 'mpe_cart_page' ];
	}
	?>
	<fieldset>
		<?php
		echo '<legend>' . __( 'Multiproduct Enquiry & Quote Options', 'quoteup' ) . '</legend>';
		enableMultiproductQuote( $form_data );
		quoteupCartPage( $form_data );
		?>
	</fieldset>
	<?php
}

function enableMultiproductQuote($form_data)
{
	global $quoteupSettings
	?>
	<div class="fd">
		<div class='left_div'>
			<label for="enable_disable_mpe"> <?php _e( 'Enable Multiproduct Enquiry and Quote Request', 'quoteup' ) ?> </label>
		</div>
		<div class='right_div'>
			<?php
			$helptip = __( 'You can enable/disable multiproduct enquiry or quote. At a time single or multiproduct enquiry or quote will be available.', 'quoteup' );
			echo \quoteupHelpTip( $helptip, true );
			?>			
			<input type="checkbox" class="wdm_wpi_input wdm_wpi_checkbox" value="1" <?php checked( 1, isset( $form_data[ 'enable_disable_mpe' ] ) ? $form_data[ 'enable_disable_mpe' ] : 0  ); ?> id="enable-multiproduct" /> 
			<input type="hidden" name="wdm_form_data[enable_disable_mpe]" value="<?php echo isset( $form_data[ 'enable_disable_mpe' ] ) && $form_data[ 'enable_disable_mpe' ] == 1 ? $form_data[ 'enable_disable_mpe' ] : 0 ?>" />
		</div>
		<div class='clear'></div>
	</div>
	<?php
}

function quoteupCartPage( $form_data ) {
	global $quoteupSettings;
	if ( ! isset( $form_data[ 'mpe_cart_page' ] ) || empty( $form_data[ 'mpe_cart_page' ] ) ) {
		$mpe_cart_page_id = '';
	} else {
		$mpe_cart_page_id = $form_data[ 'mpe_cart_page' ];
	}
	?>
	<div class="fd quote_cart">
		<div class='left_div'>
			<label for="mpe_cart_page"> <?php _e( 'Enquiry and Quote Cart Page', 'quoteup' ) ?> </label>
		</div>
		<div class='right_div'>
			<?php
			$cart_page	 = get_option( 'woocommerce_cart_page_id' );
			$helptip	 = __( 'Select Enquiry & Quote cart page. This is a page where Enquiry & Quote Cart is shown.', 'quoteup' );
			echo \quoteupHelpTip( $helptip, true );
			?>
			<?php wp_dropdown_pages(array( 'name' => 'wdm_form_data[mpe_cart_page]', 'selected' => $mpe_cart_page_id, 'show_option_none' => __('Select Page', 'quoteup'), 'exclude_tree' => $cart_page ));
			$admin_path  = get_admin_url();
			?>
            <a class="new_page_link" href="<?php echo $admin_path."post-new.php?post_type=page"; ?>"> Add New Page </a>
		</div>
		<div class='clear'></div>
	</div >
	<?php
}

enableDisableMultiproductQuoteSection( $form_data );
