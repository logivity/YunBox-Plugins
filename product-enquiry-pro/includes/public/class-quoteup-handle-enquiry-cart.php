<?php

namespace Includes\Frontend;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class QuoteupHandleEnquiryCart
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

    protected function __construct()
    {
        
    }

    public static function quoteupEnquiryCartShortcodeCallback()
    {
        $form_data       = "";
        $default_vals    = array( 'show_after_summary'       => 1,
            'button_CSS'                 => 0,
            'pos_radio'                  => 0,
            'show_powered_by_link'       => 0,
            'enable_send_mail_copy'      => 0,
            'enable_telephone_no_txtbox' => 0,
            'only_if_out_of_stock'       => 0,
            'dialog_product_color'       => '#3079ED',
            'dialog_text_color'          => '#000000',
            'dialog_color'               => '#F7F7F7',
        );
        $form_data       = get_option('wdm_form_data', $default_vals);

        ob_start();
        do_action('quoteup_enquiry_cart_content');
        $getContent = ob_get_contents();
        ob_end_clean();
        return $getContent;
    }
}

QuoteupHandleEnquiryCart::getInstance();
