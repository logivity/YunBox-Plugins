<?php
namespace Includes\Admin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class QuoteupHandleQuoteApprovalRejection
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
        //Tried add_option_wdm_form_data and update_option_wdm_form_data but that did not work.
        //So writing code on add_option and update_option
        add_action('added_option', array($this, 'addShortcodeOnPageOnAddOption'), 10, 2);

        add_action('update_option', array($this, 'addShortcodeOnPageOnUpdateOption'), 10, 3);

        // Display the admin notification
        add_action('admin_notices', array( $this, 'showNoticesInDashboard' )) ;
    }

    /**
     * This function is used to display notice if approval rejection page is not set.
     */
    public function getNoticePageNotSelected()
    {
      if ($_SERVER['REQUEST_URI']!='/wp-admin/admin.php?page=quoteup-for-woocommerce') {
                $html = '<div class="error">';
                    $html .= '<p>';
                    $html .= sprintf(__('Please set a page where users can approve or reject the quote %s on this page %s', 'quoteup'), "<a href='admin.php?page=quoteup-for-woocommerce#wdm_quote'>", "</a>");
                    $html .= '</p>';
                $html .= '</div><!-- /.error -->';

                echo $html;
            }
    }

    public function shortcodeCheck($selectedPage, $pageId)
    {
      if (! $this->doesContentHaveShortcode($selectedPage->post_content, 'APPROVAL_REJECTION_CHOICE')) {
             $html = '<div class="error">';
            $html .= '<p>';
                $html .= sprintf(__('Page you have set for approval and rejection of quote does not have the shortcode [APPROVAL_REJECTION_CHOICE]. Please add [APPROVAL_REJECTION_CHOICE] in the content of that page  %s here %s.', 'quoteup'), "<a href='post.php?post={$pageId}&action=edit'>", "</a>");
            $html .= '</p>';
            $html .= '</div><!-- /.error -->';
            echo $html;
        }
    }

    /**
     * Shows notices in the dashboard when one of the following condition occurs
     * - Approval/Rejection page is not set
     * - Approval/Rejection page is in trash
     * - Approval/Rejection page does not have required shortcode
     * - Approval/Rejection page does not exist
     *
     */
    public function showNoticesInDashboard()
    {
        //Check if user has valid license key or not
        $this->checkLicenseNotice();

        $quoteup_lic_stat = get_option('edd_pep_license_status');
        if ($quoteup_lic_stat != 'valid' && $quoteup_lic_stat != 'expired') {
            return;
        }
        //Show notice to migrate enquiries on all pages when coming from old PEP.
        if ($_SERVER['REQUEST_URI']!='/wp-admin/admin.php?page=quoteup-for-woocommerce') {
            $migrated = get_option('wdm_enquiries_migrated');
            global $wpdb;
            $enquiry_tbl = $wpdb->prefix.'enquiry_details';
            if ($wpdb->get_var("SHOW TABLES LIKE '$enquiry_tbl'") == $enquiry_tbl && $migrated != 1) {
                $html = '<div class="error">';
                    $html .= '<p>';
                        $html .= sprintf(__('It seems you have upgraded from Product Enquiry Pro 2.x to QuoteUp. There have been database architectural changes in QuoteUp. We strongly recommend to migrate the previous enquiries to QuoteUp %s here %s before you start using Quoteup.', 'quoteup'), '<a href="admin.php?page=quoteup-for-woocommerce">', '</a>');
                    $html .= '</p>';
                $html .= '</div><!-- /.error -->';

                echo $html;
            }
        }
        $optionData = quoteupSettings();
        $pageId = $this->findApprovalRejectionPageId($optionData);
        if (isset($optionData['enable_disable_quote']) && $optionData['enable_disable_quote']==1) {
            return;
        }
    // Approval/Rejection page is not set in the Settings
        if ($pageId === false) {
            $this->getNoticePageNotSelected();
        } else {
        //get content of the page
            $selectedPage = get_post($pageId);
        // Check if page exists
            if ($selectedPage !== null) {
    //Check if selected page has the shortcode.
                $this->shortcodeCheck($selectedPage, $pageId);
        // Check page status and show warning if page's status is Trash
                if ($selectedPage->post_status == 'trash') {
                     $html = '<div class="error">';
                    $html .= '<p>';
                        $html .= sprintf(__('Page you have set for approval and rejection of quote is in Trash. Therefore, users won\'t be able to approve/reject quotes. You can change the status of that page %s here %s.', 'quoteup'), "<a href='edit.php?post_status=trash&post_type=page'>", "</a>");
                    $html .= '</p>';
                    $html .= '</div><!-- /.error -->';
                    echo $html;
                }
            } else {
        //Show error if Approval/Rejection page does not exist
                $html = '<div class="error">';
                    $html .= '<p>';
                    $html .= sprintf(__('Page you have set for approval and rejection of quote does not exist. Please set a new page for approval/rejection of quotes  %s here %s.', 'quoteup'), '<a href="admin.php?page=quoteup-for-woocommerce#wdm_approval_rejection_page">', '</a>');
                    $html .= '</p>';
                $html .= '</div><!-- /.error -->';
                echo $html;
            }
        }

    }
/**
 * this function checks if license is activated orr not, if not it displays a notice to activate license
 * @return [type] [description]
 */
    public function checkLicenseNotice()
    {
        $quoteup_lic_stat = get_option('edd_pep_license_status');
        if ($_SERVER['REQUEST_URI']!='/wp-admin/plugins.php?page=pep-license') {
            if ($quoteup_lic_stat != 'valid' && $quoteup_lic_stat != 'expired') {
                $message= '<div class="error">';
                $message .= '<p>';
                $message .= sprintf(__('Please enter license key %s here %s to activate Product Enquiry Pro.', 'quoteup'), '<a href="plugins.php?page=pep-license">', '</a>');
                $message .= '</p>';
                $message .= '</div><!-- /.error -->';
                echo $message;
                return;
            }
        }

        if ($quoteup_lic_stat != 'valid' && $quoteup_lic_stat != 'expired') {
            return;
        }

    }

    /**
     * Finds out Page id set for Approval/Rejection settings
     * @param type $optionData Settings Data
     * @return mixed If page is set, returns page id. Else returns false.
     */
    public function findApprovalRejectionPageId($optionData)
    {
            //unserialize data
        $optionData = maybe_unserialize($optionData);
        if (isset($optionData['approval_rejection_page']) && intval($optionData['approval_rejection_page'])) {
            return $optionData['approval_rejection_page'];
        }
        return false;
    }

    

    /**
     * Triggers adding shortcode on the selected page on adding settings for the first time.
     * @param String $hookName Name of the hook
     * @param Array $oldValue Old data of Setting
     * @param Array $newValue New data of Setting
     */
    public function addShortcodeOnPageOnUpdateOption($hookName, $oldValue, $newValue)
    {
        if ($hookName != 'wdm_form_data') {
            return;
        }
        $this->removeShortcodeFromOldPage($oldValue);
        $this->addShortcodeOnPage($newValue);
    }

    /**
     * Triggers removing shortcode on the selected page on updating settings Approval rejection page.
     * @param Array $optionData Settings Data
     */
    public function removeShortcodeFromOldPage($optionData)
    {
        $pageId = $this->findApprovalRejectionPageId($optionData);
        if ($pageId === false) {
            return;
        }

        //get content of the page
        $selectedPage = get_post($pageId);

        if ($selectedPage !== null) {
            if ($this->doesContentHaveShortcode($selectedPage->post_content, 'APPROVAL_REJECTION_CHOICE')) {
                // Update Selected Page
                  $page_data = array(
                      'ID'           => $pageId,
                      'post_content'   => str_replace('[APPROVAL_REJECTION_CHOICE]', '', $selectedPage->post_content),
                  );

                // Update the page into the database
                  wp_update_post($page_data);
            }
        }
        
    }

    /**
     * Triggers adding shortcode on the selected page on updating the settings.
     * @param type $hookName Name of the hook
     * @param type $newValue Settings data
     */
    public function addShortcodeOnPageOnAddOption($hookName, $newValue)
    {
        if ($hookName != 'wdm_form_data') {
            return;
        }
        $this->addShortcodeOnPage($newValue);
    }

    /**
     * Checks if the content has the shortcode we are interested in.
     * @param String $content
     * @param String $shortcodeTag
     * @return boolean Returns true if shortcode is found
     */
    public function doesContentHaveShortcode($content, $shortcodeTag)
    {
        
        if (false === strpos($content, '[')) {
            return false;
        }
               preg_match_all('/' . get_shortcode_regex() . '/', $content, $matches, PREG_SET_ORDER);
        if (empty($matches)) {
            // if ($shortcodeTag == 'ENQUIRY_CART') {
            //     echo '/' . get_shortcode_regex() . '/';
            //     exit;
            // }
             return false;
        }

        foreach ($matches as $shortcode) {
            if ($shortcodeTag === $shortcode[2]) {
                return true;
            } elseif (! empty($shortcode[5]) && $this->doesContentHaveShortcode($shortcode[5], $shortcodeTag)) {
                return true;
            }
        }
            
        return false;
    }




    /**
     * Adds [APPROVAL_REJECTION_CHOICE] shortcode on selected page.
     * @param Array $optionData Settings Data
     */
    public function addShortcodeOnPage($optionData)
    {

        $pageId = $this->findApprovalRejectionPageId($optionData);
        if ($pageId === false) {
            return;
        }

        //get content of the page
        $selectedPage = get_post($pageId);

        if ($selectedPage !== null) {
            if (! $this->doesContentHaveShortcode($selectedPage->post_content, 'APPROVAL_REJECTION_CHOICE')) {
                // Update Selected Page
                  $page_data = array(
                      'ID'           => $pageId,
                      'post_content'   => $selectedPage->post_content . '<br /> [APPROVAL_REJECTION_CHOICE]' ,
                  );

                // Update the page into the database
                  wp_update_post($page_data);
            }
        }
        
    }
}
QuoteupHandleQuoteApprovalRejection::getInstance();
