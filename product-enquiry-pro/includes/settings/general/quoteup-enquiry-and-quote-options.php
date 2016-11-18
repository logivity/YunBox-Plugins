<?php

namespace Includes\Settings;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function enableDisableQuoteUpSection($form_data)
{
   

    ?>
	<fieldset>
		<?php
        echo '<legend>' . __('Enquiry & Quote Options', 'quoteup') . '</legend>';
        quotemoduleEnableDisable($form_data);
        enquiryOutofStock($form_data);
        enquiryButtonShopPage($form_data);
        ?>
	</fieldset>
	<?php
}

function quotemoduleEnableDisable($form_data)
{
    global $quoteupSettings;
    ?>
    <div class="fd">
        <div class='left_div'>
            <label for="quote-enable-disable"> <?php _e('Disable Quotation System', 'quoteup') ?> </label>
        </div>
        <div class='right_div'>
        <?php
        $helptip = __('Disable quote management system', 'quoteup');
			echo \quoteupHelpTip( $helptip, true );
        ?>
            <input type="checkbox" class="wdm_wpi_input wdm_wpi_checkbox" value="1" <?php checked(1, isset($form_data[ 'enable_disable_quote' ]) ? $form_data[ 'enable_disable_quote' ] : 0);
    ?> id="quote-enable-disable" /> 
			<input type="hidden" name="wdm_form_data[enable_disable_quote]" value="<?php echo isset($form_data[ 'enable_disable_quote' ]) && $form_data[ 'enable_disable_quote' ] == 1 ? $form_data[ 'enable_disable_quote' ] : 0?>" /> 
    
        </div>
        <div class='clear'></div>
    </div >
    <?php
}

function enquiryOutofStock( $form_data ) {
    global $quoteupSettings;
    ?>

	<!--enambe only when out of stock -->
	<div class="fd">
		<div class='left_div'>
			<label for="only_if_out_of_stock">
	<?php _e('Display Enquiry or Quote button only when \'Out of Stock\'', 'quoteup') ?>
			</label>
		</div>
		<div class='right_div'>
        <?php
        $helptip = __('Display enquiry and quote button only after the product runs out of stock', 'quoteup');
			echo \quoteupHelpTip( $helptip, true );
        ?>
			<input type="checkbox" class="wdm_wpi_input wdm_wpi_checkbox" value="1" <?php checked(1, isset($form_data[ 'only_if_out_of_stock' ]) ? $form_data[ 'only_if_out_of_stock' ] : 0);
    ?> id="only_if_out_of_stock" /> 
			<input type="hidden" name="wdm_form_data[only_if_out_of_stock]" value="<?php echo isset($form_data[ 'only_if_out_of_stock' ]) && $form_data[ 'only_if_out_of_stock' ] == 1 ? $form_data[ 'only_if_out_of_stock' ] : 0?>" />
		</div>
		<div class="clear"></div>
	</div>
	<!--end-->

	<?php
}

function enquiryButtonShopPage( $form_data ) {
    global $quoteupSettings;
    ?>
	<div class="fd">
		<div class='left_div'>
			<label for="show_enquiry_on_shop">
	<?php _e("Display Enquiry or Quote button on Archive Page ", 'quoteup') ?>
			</label>
		</div>
		<div class='right_div'>
        <?php
        $helptip = __('Display enquiry and quote button on Archive (Shop, Categories) Page', 'quoteup');
			echo \quoteupHelpTip( $helptip, true );
        ?>
			<input type="checkbox" class="wdm_wpi_input wdm_wpi_checkbox" value="1" <?php checked(1, isset($form_data[ "show_enquiry_on_shop" ]) ? $form_data[ "show_enquiry_on_shop" ] : 0); ?> id="show_enquiry_on_shop" />
			<input type="hidden" name="wdm_form_data[show_enquiry_on_shop]" value="<?php echo isset($form_data[ 'show_enquiry_on_shop' ]) && $form_data[ 'show_enquiry_on_shop' ] == 1 ? $form_data[ 'show_enquiry_on_shop' ] : 0; ?>" />
				<?php //echo '<em>'.__('  Enable/Disable powered by link ', 'quoteup').'</em>';  ?>

		</div>
		<div class="clear"></div>
	</div>

	<?php
}

enableDisableQuoteUpSection($form_data);
