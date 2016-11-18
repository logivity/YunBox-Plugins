<?php

namespace Includes\Settings;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * It displays PDF settings on general settings page. It shows following fields
 * - Company Name
 * - Company Email
 * - Company Address
 * - Logo for PDF
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function pdfSettingsSection($form_data)
{
	global $quoteupSettings;
    if (! isset($form_data[ 'company_name' ])) {
        $form_data[ 'company_name' ] = get_bloginfo();
    }

    if (! isset($form_data[ 'company_logo' ])) {
        $form_data[ 'company_logo' ] = "";
    }
    if (! isset($form_data[ 'company_address' ])) {
        $form_data[ 'company_address' ] = "";
    }

    if (! isset($form_data[ 'company_email' ])) {
        $form_data[ 'company_email' ] = get_option('admin_email');
    }
    ?>
	<fieldset>
		<?php 
		 $helptip = __('These settings will add details to the generated quote when the Quotation System is enabled.', 'quoteup');
		$tip	 = \quoteupHelpTip( $helptip, true );
		echo '<legend>' . __('PDF Settings ', 'quoteup') . $tip .  '</legend>'; 
		
			?>

		<div class="fd">
			<div class='left_div'>
				<label for="wdm_company_name"> <?php _e('Company Name', 'quoteup') ?> </label>
			</div>
			<div class='right_div'>
				<input type="text" class="wdm_wpi_text input-without-tip" name="wdm_form_data[company_name]" value="<?php echo $form_data[ 'company_name' ] ?>">
			</div>
			<div class='clear'></div>
		</div >

		<div class="fd">
			<div class='left_div'>
				<label for="wdm_company_email"> <?php _e('Company Email', 'quoteup') ?> </label>
			</div>
			<div class='right_div'>
				<input class="wdm_wpi_text input-without-tip" type="text" name="wdm_form_data[company_email]" value="<?php echo $form_data[ 'company_email' ] ?>">
			</div>
			<div class='clear'></div>
		</div >

		<div class="fd">
			<div class='left_div'>
				<label for="wdm_company_add"> <?php _e('Company Address', 'quoteup') ?> </label>
			</div>
			<div class='right_div'>
				<textarea class="wdm_wpi_text input-without-tip"  name="wdm_form_data[company_address]" rows="5"><?php echo $form_data[ 'company_address' ] ?></textarea>
			</div>
			<div class='clear'></div>
		</div >

		<div class="fd">
			<div class='left_div'>
            <br>
				<label for="wdm_company_logo"> <?php _e('Logo for PDF', 'quoteup') ?> </label>
			</div>
			<div class='right_div'>
				<div id="tgm-new-media-settings">
					<p><input class="wdm_wpi_text input-without-tip" type="text" id="tgm-new-media-image" size="70" value="<?php echo $form_data[ 'company_logo' ] ?>" name="wdm_form_data[company_logo]" /><a href="#" class="tgm-open-media button button-primary wdm-media-upload-button" title="Upload Logo"><?php _e('Upload Image', 'quoteup') ?> </a> 
				</div>
			</div>
			<div class='clear'></div>
		</div >
	</fieldset>
	<?php
}

pdfSettingsSection($form_data);
