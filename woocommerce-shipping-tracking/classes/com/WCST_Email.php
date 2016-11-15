<?php 
class WCST_Email
{
	public function __construct()
	{
	}
	
	public function force_status_email_sending($action, $order)
	{
		// Ensure gateways are loaded in case they need to insert data into the emails
		WC()->payment_gateways();
		WC()->shipping();

		// Load mailer
		$mailer = WC()->mailer();

		$email_to_send = str_replace( 'send_email_', '', $action );

		$mails = $mailer->get_emails();

		if ( ! empty( $mails ) ) {
			foreach ( $mails as $mail ) {
				if ( $mail->id == $email_to_send ) {
					$mail->trigger( $order->id );
					//$order->add_order_note( sprintf( __( '%s email notification manually sent.', 'woocommerce' ), $mail->title ), false, true );
				}
			}
		}
	}
}