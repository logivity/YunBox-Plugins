<?php

/**
 * This file sets quote expired who meet the expiration date
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('quoteupExpireQuotes', 'quoteupExpireQuotesCallback');

/**
 * Function that actually expires quotes
 */
function quoteupExpireQuotesCallback()
{
    require_once(QUOTEUP_PLUGIN_DIR . '/includes/class-quoteup-manage-history.php');
    global $wpdb, $quoteupManageHistory;
    
    $today = current_time('Y-m-d');
    //Search all enquiries whose expiration date is last day or before
    $table = $wpdb->prefix . 'enquiry_detail_new';
    $enquiry_ids = $wpdb->get_col($wpdb->prepare("SELECT enquiry_id FROM $table WHERE DATE(expiration_date)  < %s AND DATE(expiration_date) IS NOT NULL ", $today));

    
    if ($enquiry_ids) {
        $all_enquiries = implode(', ', $enquiry_ids);
        // Find all Enquries whose last status was either Sent, Saved or Approved.
        $getRequiredEnquiries = $wpdb->get_col("SELECT s1.enquiry_id FROM {$wpdb->prefix}enquiry_history s1 LEFT JOIN {$wpdb->prefix}enquiry_history s2 ON s1.enquiry_id = s2.enquiry_id AND s1.id < s2.id WHERE s2.enquiry_id IS NULL AND s1.status IN ('Sent','Saved','Approved') AND s1.enquiry_id IN ($all_enquiries) AND s1.id > 0");
        if ($getRequiredEnquiries) {
            foreach ($getRequiredEnquiries as $singleEnquiryId) {
                //Expire all such enquiries
                $quoteupManageHistory->addQuoteHistory($singleEnquiryId, '-', 'Expired');
            }
            
        }
    }
}
