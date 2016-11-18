<?php

namespace Includes\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function quoteEmailInformationSection( $form_data ) {
	global $quoteupSettings;
	?>
	<fieldset>            
		<!--Emailing information-->
		<?php echo '<legend>' . __( 'Emailing Information', 'quoteup' ) . '</legend>';
		recipientEmailID($form_data);
		sendMailToAdmin($form_data);
		sendMailToAuthor($form_data);
		defaultSubject($form_data);
		?>
	</fieldset>
	<?php
}

function recipientEmailID($form_data)
{
	global $quoteupSettings;
	?>
	<div class="fd">
			<div class='left_div'>
				<label for="wdm_user_email"> <?php _e( 'Recipient Email ID', 'quoteup' ) ?> </label>
			</div>
			<div class='right_div'>
				<?php
				$helptip = __( 'You can add multiple email addresses separated by comma', 'quoteup' );
				echo \quoteupHelpTip( $helptip, true );
				?>
				<input type="text" class="wdm_wpi_input wdm_wpi_text email" name="wdm_form_data[user_email]" id="wdm_user_email" value="<?php echo empty( $form_data[ 'user_email' ] ) ? get_option( 'admin_email' ) : $form_data[ 'user_email' ];
				?>"/>
				<span class='email_error'  style="vertical-align:top" > </span>
			</div>
			<div class='clear'></div>
		</div >
	<?php
}

function sendMailToAdmin($form_data)
{
	global $quoteupSettings;
	?>
	<div class="fd">
		<div class='left_div'>
			<label for="send-mail-to-admin"> <?php _e( 'Send mail to Admin', 'quoteup' ) ?> </label>
		</div>
		<div class='right_div'>
			<?php
			$helptip = __( 'When checked, sends the enquiry email to the Admin email address specified under Settings -> General', 'quoteup' );
				echo \quoteupHelpTip( $helptip, true );
			?>
			<input type="checkbox" class="wdm_wpi_input wdm_wpi_checkbox" value="1" <?php checked( 1, isset( $form_data[ 'send_mail_to_admin' ] ) ? $form_data[ 'send_mail_to_admin' ] : 0  );
			?> id="send-mail-to-admin" /> 
			<input type="hidden" name="wdm_form_data[send_mail_to_admin]" value="<?php echo isset( $form_data[ 'send_mail_to_admin' ] ) && $form_data[ 'send_mail_to_admin' ] == 1 ? $form_data[ 'send_mail_to_admin' ] : 0 ?>" /> 

		</div>
		<div class='clear'></div>
	</div >
	<?php
}

function sendMailToAuthor($form_data)
{
	global $quoteupSettings;
	?>
	<div class="fd">
		<div class='left_div'>
			<label for="send-mail-to-product-author"> <?php _e( 'Send mail to Product Author', 'quoteup' ) ?> </label>
		</div>
		<div class='right_div'>
			<?php
			$helptip = __( 'When checked, sends enquiry email to author/owner of the Product', 'quoteup' );
				echo \quoteupHelpTip( $helptip, true );
			?>
			<input type="checkbox" class="wdm_wpi_input wdm_wpi_checkbox" value="1" <?php checked( 1, isset( $form_data[ 'send_mail_to_author' ] ) ? $form_data[ 'send_mail_to_author' ] : 0  );
			?> id="send-mail-to-admin" /> 
			<input type="hidden" name="wdm_form_data[send_mail_to_author]" value="<?php echo isset( $form_data[ 'send_mail_to_author' ] ) && $form_data[ 'send_mail_to_author' ] == 1 ? $form_data[ 'send_mail_to_author' ] : 0 ?>" />
		</div>
		<div class='clear'></div>
	</div >
	<?php
}

function defaultSubject($form_data)
{
	global $quoteupSettings;
	?>
	<div class="fd">
		<div class='left_div'>
			<label for="wdm_default_sub"> <?php _e( 'Default Subject', 'quoteup' ) ?></label>
		</div>
		<div class='right_div'>
			<?php
			$helptip = __( 'Subject to be used if customer does not enter a subject', 'quoteup' );
				echo \quoteupHelpTip( $helptip, true );
			?>
			<input type="text" class="wdm_wpi_input wdm_wpi_text" name="wdm_form_data[default_sub]" id="wdm_default_sub"
				   value="<?php echo empty( $form_data[ 'default_sub' ] ) ? _e( 'Quote request for a product from ', 'quoteup' ) . get_bloginfo( 'name' ) : $form_data[ 'default_sub' ];
			?>"  />
		</div>
		<div class='clear'></div>
	</div>
	</fieldset>
	<?php
}
quoteEmailInformationSection( $form_data );
