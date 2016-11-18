<?php
namespace Includes\Admin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * This class is used to send mail to customer.
 * Mail includes pdf and the unique link by which customer can approve or reject quote
 */
class SendQuoteMail
{

    /**
     * This function is used to send mail to customer
     */
    public static function sendMail()
    {
        if ($_POST['subject']=="") {
            $_POST['subject']= __('Quotation', 'quoteup');
        }
        $enquiry_id=$_POST['enquiry_id'];

         global $wpdb, $quoteupEmail;
        $table_name=$wpdb->prefix."enquiry_detail_new";
        $sql = $wpdb->prepare("SELECT name,enquiry_hash FROM $table_name WHERE enquiry_id=%d", $enquiry_id);
        $hash = $wpdb->get_row($sql, ARRAY_A);
        $uniqueURL=quoteLinkGenerator($hash['enquiry_hash']);
        $upload_dir = wp_upload_dir();

        //Copy pdf to make its name Quotation.pdf
        copy($upload_dir['basedir'] . '/QuoteUp_PDF/'.$enquiry_id.'.pdf', $upload_dir['basedir'] . '/QuoteUp_PDF/Quotation '.$hash['name'].'.pdf');

        $too = $_POST['email'];        // E-mail of customer

        $subject = $_POST['subject'];
        $original_message = $_POST['message'];
        $linkStatement = sprintf(__('Visit %s Link %s to Approve or Reject Quote', 'quoteup'), "<a href='$uniqueURL'>", "</a>");
        $message = $original_message . "<br><br>" . $linkStatement;
        // $headers = "MIME-Version: 1.0\r\n";
        // $headers = "Content-Type: text/html; charset=UTF-8\n";
        $attachments = array( $upload_dir['basedir'] . '/QuoteUp_PDF/Quotation '.$hash['name'].'.pdf' );
        $value=$upload_dir['basedir'] . '/QuoteUp_PDF/Quotation '.$hash['name'].'.pdf';

        // $message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');
        $message=stripcslashes($message);
        $subject=stripcslashes($subject);
        $quoteupEmail->send($too, $subject, $message, '', $attachments);
        // Delete the file Quotation.pdf once sent
        if (file_exists($value)) {
            unlink($value);
        }

        //update enquiry details table
        $wpdb->update(
            $table_name,
            array(
                'order_id' => null,
            ),
            array(
                'enquiry_id'=>$enquiry_id,
            )
        );

        //update History Table
        global $quoteupManageHistory;
        $quoteupManageHistory->addQuoteHistory($enquiry_id, $original_message, "Sent");

        _e('Mail Sent', 'quoteup');
        die;
    }
}
