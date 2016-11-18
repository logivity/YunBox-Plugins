<?php

namespace Includes;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Manage Quote history
 */
class QuoteupManageHistory {

    /**
     * @var Singleton The reference to *Singleton* instance of this class
     */
    private static $instance;

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
	public static function getInstance() {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
	protected function __construct() {
        add_action('quoteup_add_custom_field_in_db', array( $this, 'updateRequest' ), 15);
    }

	public function updateRequest( $insert ) {
        $this->addQuoteHistory($insert, "-", "Requested");
    }

    /**
     * Add message to Enquiry
     * @param Int $enquiryId Enquiry id associated with enquiry
     * @param String $message message to be added in the enquiry
     * @param String $status action performed in the enquiry
     */
	public function addQuoteHistory( $enquiryId, $message, $status ) {
 
        global $wpdb;
        //date_default_timezone_set("Asia/Kolkata");
        $date        = current_time('mysql');
        $table_name  = $wpdb->prefix . "enquiry_history";
        $performedBy = null;
        if (is_user_logged_in()) {
            $performedBy = get_current_user_id();
        }
        do_action('quoteup_before_adding_history', $enquiryId, $message, $status);
        $insert_id = $wpdb->insert(
	    $table_name, array(
            'enquiry_id'     => $enquiryId,
            'date'           => $date,
            'message'        => $message,
            'status'         => $status,
            'performed_by'   => $performedBy,
            )
        );
        do_action('quoteup_after_adding_history', $insert_id, $enquiryId, $message, $status);
    }

    /**
     * Returns the last entry added in the history table
     * @return Returns NULL if record is not found in the database. Otherwise returns the data
     */
	public function getLastAddedHistory( $enquiry_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . "enquiry_history";
        return $wpdb->get_row($wpdb->prepare("SELECT date, message, status, performed_by FROM $table_name WHERE enquiry_id = %d ORDER BY date DESC LIMIT 1", $enquiry_id), ARRAY_A);
    }
}

/*
 * Creating $quoteupManageHistory as a Global variable because it is going to be needed in Cron Job too.
 * Creating it as a separate variable eliminates bootstraping of $quoteup variable and hence can be used
 * directly whenever needed 
 */
$GLOBALS['quoteupManageHistory'] = QuoteupManageHistory::getInstance();
