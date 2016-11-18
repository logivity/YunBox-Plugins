<?php
namespace Includes;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
/*
 * Handles all the tasks related to expiration of quotes
 */

class QuoteupManageExpiration
{
     /**
     * @var Singleton The reference to *Singleton* instance of this class
     */
    private static $instance;

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {
    }

    public function setExpirationDate($expiration_date, $enquiry_id)
    {
        global $wpdb;
         $date = date_create_from_format('Y-m-d H:i:s', $expiration_date);
                $mysql_datetime = date_format($date, 'Y-m-d H:i:s');
                $wpdb->update(
                    $wpdb->prefix . "enquiry_detail_new",
                    array(
                    'expiration_date' => $mysql_datetime,
                    ),
                    array(
                    'enquiry_id'    => $enquiry_id,
                    )
                );
    }
	
	public function getExpirationDate($enquiry_id){
		global $wpdb;
		$table = $wpdb->prefix . "enquiry_detail_new";
		$expiration_date = $wpdb->get_var($wpdb->prepare("SELECT expiration_date FROM $table WHERE enquiry_id = %d", $enquiry_id));
		if($expiration_date) {
			return $this->getHumanReadableDate($expiration_date);
		} 
		return '';
	}
    
    public function getHumanReadableDate($expiration_date_time)
    {
		if(empty($expiration_date_time) || $expiration_date_time == '0000-00-00 00:00:00'){
			return '';
		}
		
        $dateTime = date_create_from_format('Y-m-d H:i:s', $expiration_date_time);
        return apply_filters('quoteup_human_readable_expiration_date', date_format($dateTime, 'M d, Y'), $dateTime);
    }
    
    public function isQuoteExpired($enquiry_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . "enquiry_detail_new";
        $expirationDate = $wpdb->get_var($wpdb->prepare("SELECT expiration_date FROM $table WHERE enquiry_id = %d", $enquiry_id));
        if ($expirationDate == null || empty($expirationDate) || $expirationDate == '0000-00-00 00:00:00') {
            return false;
        } else {
            $currentTime = strtotime(current_time('Y-m-d'). ' 00:00:00' );
            $expirationTime = strtotime($expirationDate);
            if ($currentTime > $expirationTime) {
                return true;
            }
        }
        return false;
    }
}
$this->manageExpiration = QuoteupManageExpiration::getInstance();
