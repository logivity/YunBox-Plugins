<?php
namespace Includes\Admin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// if (!class_exists('Admin\Includes\QuoteupAdminNotices')) {
//     return;
// }
class QuoteupAdminNotices
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
     * Shows notices in the dashboard when one of the following condition occurs
     * - Enquiry Cart page is not set
     * - Enquiry Cart page is in trash
     * - Enquiry Cart page does not have required shortcode
     * - Enquiry Cart page does not exist
     *
     */
    public function showNoticesInDashboard()
    {
        $optionData = quoteupSettings();
        if (isset($optionData['enable_disable_mpe']) && $optionData['enable_disable_mpe']==1) {
            $pageId = $this->findMPECartPageId($optionData);
      // Enquiry Cart is not set in the Settings
            if ($pageId === false) {
                $html = '<div class="error">';
                    $html .= '<p>';
                        $html .= __('Please set a page for Enquiry & QuoteUp Cart', 'quoteup').'  <a href="admin.php?page=quoteup-for-woocommerce#wdm_general">'. __('on this page', 'quoteup') .'</a>.';
                    $html .= '</p>';
                $html .= '</div><!-- /.error -->';

                echo $html;
            } else {
            //get content of the page
                $selectedPage = get_post($pageId);
            // Check if page exists
                if ($selectedPage !== null) {
        //Check if selected page has the shortcode.
                    if (! $this->doesContentHaveShortcode($selectedPage->post_content, 'ENQUIRY_CART')) {
                         $html = '<div class="error">';
                        $html .= '<p>';
                        $html .= sprintf(__('Page you have set for QuoteUp Cart does not have the shortcode [ENQUIRY_CART]. Please add [ENQUIRY_CART] in the content of that page  %s here %s.', 'quoteup'), "<a href='post.php?post={$pageId}&action=edit'>", "</a>");
                        $html .= '</p>';
                        $html .= '</div><!-- /.error -->';
                        echo $html;
                    }
            // Check page status and show warning if page's status is Trash
                    if ($selectedPage->post_status == 'trash') {
                         $html = '<div class="error">';
                        $html .= '<p>';
                        $html .= sprintf(__('Page you have set for QuoteUp Cart is in Trash. Therefore, users won\'t be able to Check Cart and make Enquiry. You can change the status of that page %s here %s.', 'quoteup'), "<a href='edit.php?post_status=trash&post_type=page'>", "</a>");
                        $html .= '</p>';
                        $html .= '</div><!-- /.error -->';
                        echo $html;
                    }
                } else {
            //Show error if Approval/Rejection page does not exist
                    $html = '<div class="error">';
                    $html .= '<p>';
                    $html .= sprintf(__('Page you have set for QuoteUp Cart does not exist. Please set a new page for QuoteUp Cart  %s here %s.', 'quoteup'), '<a href="admin.php?page=quoteup-for-woocommerce#wdm_general">', '</a>');
                    $html .= '</p>';
                    $html .= '</div><!-- /.error -->';
                    echo $html;
                }
            }
        }
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
        // unset($oldValue);
        $this->removeShortcodeFromOldPage($oldValue);
        $this->addShortcodeOnPage($newValue);
    }


    /**
     * Triggers removing shortcode on the selected page on updating settings QuoteUp Cart page.
     * @param Array $optionData Settings Data
     */
    public function removeShortcodeFromOldPage($optionData)
    {
        $pageId = $this->findMPECartPageId($optionData);

        if ($pageId === false) {
            return;
        }

        //get content of the page
        $selectedPage = get_post($pageId);

        if ($selectedPage !== null) {
            if ($this->doesContentHaveShortcode($selectedPage->post_content, '[ENQUIRY_CART]')) {
                // Update Selected Page
                  $page_data = array(
                      'ID'           => $pageId,
                      'post_content'   => str_replace('[ENQUIRY_CART]', '', $selectedPage->post_content),
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
     * Finds out Page id set for MPE Cart settings
     * @param type $optionData Settings Data
     * @return mixed If page is set, returns page id. Else returns false.
     */
    public function findMPECartPageId($optionData)
    {
            //unserialize data
        $optionData = maybe_unserialize($optionData);
        if (isset($optionData['mpe_cart_page']) && intval($optionData['mpe_cart_page'])) {
            return $optionData['mpe_cart_page'];
        }
        return false;
    }

    /**
     * Checks if the content has the shortcode we are interested in.
     * @param String $content
     * @param String $shortcodeTag
     * @return boolean Returns true if shortcode is found
     */
    public function doesContentHaveShortcode($content, $shortcodeTag)
    {
        if (false === strstr($content, $shortcodeTag)) {
            return false;
        } else {
            return true;
        }
        // unset($shortcodeTag);
    }

    /**
     * Adds [ENQUIRY_CART] shortcode on selected page.
     * @param Array $optionData Settings Data
     */
    public function addShortcodeOnPage($optionData)
    {

        $pageId = $this->findMPECartPageId($optionData);
        if ($pageId === false) {
            return;
        }

        //get content of the page
        $selectedPage = get_post($pageId);

        if ($selectedPage !== null) {
            if (! $this->doesContentHaveShortcode($selectedPage->post_content, 'ENQUIRY_CART')) {
                // Update Selected Page
                  $page_data = array(
                      'ID'           => $pageId,
                      'post_content'   => $selectedPage->post_content . '<br /> [ENQUIRY_CART]' ,
                  );

                // Update the page into the database
                  wp_update_post($page_data);
            }
        }
        
    }
}
QuoteupAdminNotices::getInstance();
