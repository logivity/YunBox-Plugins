<?php

namespace Includes\Settings;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * This is used to show redirect URL fieldset on settings page
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function redirectPageUrlSection($form_data)
{
    global $quoteupSettings;
    ?>
	<fieldset>            
		<!--Emailing information-->
		<?php echo '<legend>' . __('Redirect Page URL', 'quoteup') . '</legend>';
        ?>
		<div class="fd">
			<div class='left_div'>
				<label for="wdm_redirect_user"> <?php _e('Page Url', 'quoteup') ?></label>
			</div>
			<div class='right_div'>
			<?php
            $helptip = __('User will be forwarded to this on successful Quote request. Leave blank if not needed', 'quoteup');
				echo \quoteupHelpTip( $helptip, true );
            ?>
				<input type="url" class="wdm_wpi_input wdm_wpi_text" name="wdm_form_data[redirect_user]" id="wdm_redirect_user" placeholder = "http://something.com"
					   value="<?php echo empty($form_data[ 'redirect_user' ]) ? '' : $form_data[ 'redirect_user' ];
        ?>"  />
			</div>
			<div class='clear'></div>
		</div>
	</fieldset>
	<?php
}

redirectPageUrlSection($form_data);
