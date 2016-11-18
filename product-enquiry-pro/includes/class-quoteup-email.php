<?php
namespace Includes;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('QuoteUpEmail')) {

    class QuoteUpEmail
    {
    /**
     * Constructor.
     */
        public function __construct()
        {

        }

    /**
     * Get email content type.
     *
     * @return string
     */
        public function getContentType()
        {
            return 'text/html';
        }

    /**
     * Get the from name for outgoing emails.
     * @return string
     */
        public function getFromName()
        {
            $blog_name = get_option('blogname');
            $from_name = apply_filters('pep_email_from_name', $blog_name);
            return wp_specialchars_decode(esc_html($from_name), ENT_QUOTES);
        }

    /**
     * Get the from address for outgoing emails.
     * @return string
     */
        public function getFromAddress()
        {
            $from_address = get_option('admin_email');
            $from_address = apply_filters('pep_email_from_address', $from_address);
            return sanitize_email($from_address);
        }

    /**
     * Send an email.
     * @param string $to
     * @param string $subject
     * @param string $message
     * @param string $headers
     * @param string $attachments
     * @return bool success
     */
        public function send($to, $subject, $message, $headers, $attachments = '')
        {
            add_filter('wp_mail_from', array( $this, 'getFromAddress' ));
            add_filter('wp_mail_from_name', array( $this, 'getFromName' ));
            add_filter('wp_mail_content_type', array( $this, 'getContentType' ));
        
            $return  = wp_mail($to, $subject, $message, $headers, $attachments);

            remove_filter('wp_mail_from', array( $this, 'getFromAddress' ));
            remove_filter('wp_mail_from_name', array( $this, 'getFromName' ));
            remove_filter('wp_mail_content_type', array( $this, 'getContentType' ));

            return $return;
        }
    }
}

$GLOBALS[ 'quoteupEmail' ] = new QuoteUpEmail();
