<?php

namespace Includes\Admin;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * This class is used for handleing hover feature on enquiry page
 */
class QuoteupTooltipOnHover
{

    protected static $instance = null;

    /**
     * Function to create a singleton instance of class and return the same
     * @return [Object] [
     * description]
     */
    public static function getInstance()
    {
        if (! self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * constructor is used to add actions and filter
     */
    private function __construct()
    {
        add_filter('enquiry_list_table_data', array( $this, 'enquiryTooltipData' ), 10, 2);
        add_action('admin_enqueue_scripts', array( $this, 'addScript' ));
    }

    /**
     * This Function is used to add scripts in file
     */
    public function addScript($hook)
    {
        if ('toplevel_page_quoteup-details-new' == $hook) {
            
            
            //This is css for Tooltip
            wp_register_style('tooltipCSS', QUOTEUP_PLUGIN_URL . '/css/admin/tooltipster.css');
            wp_enqueue_style('tooltipCSS');

            //This is js for Tooltip
            wp_register_script('tooltip2', QUOTEUP_PLUGIN_URL . '/js/admin/jquery.tooltipster.min.js');
            wp_enqueue_script('tooltip2');
            
            //This is custom js file
            wp_register_script('addonJS', QUOTEUP_PLUGIN_URL . '/js/admin/trigger-tooltipster.js', array( 'jquery' ));
            wp_enqueue_script('addonJS');
            
        }
    }

    /**
     * This function is used to return title stored in database
     * @param  [array] $details         [array of enquiry products]
     * @param  [array] $singleQuoteData [Quote Products]
     * @return [type]                  [description]
     */
    public function getProductName($details, $singleQuoteData)
    {
        foreach ($details as $singleProductEnquiryDetails) {
            if ($singleQuoteData['product_id'] == $singleProductEnquiryDetails[0]['id']) {
                $productName = $singleProductEnquiryDetails[0]['title'];
            }
        }
        return $productName;
    }

    public function getVariationDetails($variation)
    {
        $allVariationDetails = array();
        if (!empty($variation)) {
            $variationDetails = maybe_unserialize($variation);
            foreach ($variationDetails as $singleVariationAttribute => $singleVariationValue) {
                $allVariationDetails[] = wc_attribute_label($singleVariationAttribute) . ': ' . $singleVariationValue;
            }
        }
        return $allVariationDetails;
    }

    public function removeProductIndex(&$details, $productID)
    {
        foreach ($details as $key => $singleProductEnquiryDetails) {
            if ($singleProductEnquiryDetails[0]['id'] == $productID) {
                unset($details[$key]);
                break;
            }
        }
    }

    // public function handleOldEnquiries($singleEnquiryData, $productName , &$tooltipProducts)
    // {
    //     if (!isset($singleEnquiryData[0]['variation_id'])) {
    //                 //this is a simple product
    //                 $tooltipProducts[] = array(
    //                 'product_id' => $singleEnquiryData[0]['id'],
    //                 'product_name' => $productName,
    //                 'variation_details' => '',
    //                 'quantity' => $singleEnquiryData[0]['quant'],
    //                 'variation_id' => 0
    //                 );
    //                 continue;
    //             }
    // }

    public function createTooltip($tooltipProducts, &$tooltip, &$deletedProductsTooltip)
    {
        if (!empty($tooltipProducts)) {
            foreach ($tooltipProducts as $singleProduct) {
                if (isset($singleProduct['variation_id']) && $singleProduct['variation_id'] !=0) {
                    $productAvailable = isProductAvailable($singleProduct['variation_id']);
                } else {
                    $productAvailable = isProductAvailable($singleProduct['product_id']);
                }

                if ($productAvailable) {
                    $tooltip.="<tr>";
                    $tooltip.="<td>" . $singleProduct[ 'product_name' ]  . $singleProduct[ 'variation_details' ] . "</td>";
                    $tooltip.="<td>" . $singleProduct[ 'quantity' ] . "</td>";
                    $tooltip.="</tr>";
                } else {
                    $deletedProductsTooltip.="<tr>";
                    $deletedProductsTooltip.="<td><del>" . $singleProduct[ 'product_name' ] . $singleProduct[ 'variation_details' ] . "</del></td>";
                    $deletedProductsTooltip.="<td><del>" . $singleProduct[ 'quantity' ] . "</del></td>";
                    $deletedProductsTooltip.="</tr>";
                }
            }
        }

        // return $productAvailable;
    }

    /**
     * This function is used to send the edited data to parent plugin using filter
     * @param  [object] $currentdata [old data of table]
     * @param  [object] $res         [values fetched from database]
     * @return [object]              new data for table with hover functionality
     */
    public function enquiryTooltipData($currentdata, $res)
    {
        global $wpdb;
        $enquiry     = $res[ 'enquiry_id' ];
        $admin_path  = get_admin_url();
        $tooltipProducts = array();
        static $productNames = array();
        
        $deletedProductsTooltip = '';
        $tooltip = "<table>";
        $tooltip.="<thead>";
        $tooltip.="<th> Items </th>";
        $tooltip.="<th> Quantity </th>";
        $tooltip.="</thead>";
        $details = maybe_unserialize($res[ 'product_details' ]);
        $totalNumberOfItems = count($details);

        $form_Data = quoteupSettings();

        //If quotation module is disabled then enquiry values will be displayed
        if ($form_Data['enable_disable_quote'] == 0) {
            $sql         = $wpdb->prepare("SELECT product_id, quantity, newprice, variation_id, variation, variation_index_in_enquiry  FROM {$wpdb->prefix}enquiry_quotation WHERE enquiry_id = %d", $enquiry);
            $result      = $wpdb->get_results($sql, ARRAY_A);
            if (!empty($result)) {
                //get list of all products for whom Quote is already created and remove them from enquiry array
                foreach ($result as $singleQuoteData) {

                    // Check if we have already figured out the product name
                    if (isset($productNames[$singleQuoteData['product_id']])) {
                        $productName = $productNames[$singleQuoteData['product_id']];
                    } else {
                        $productName = get_the_title($singleQuoteData['product_id']);
                
                        //If product does not exist, we will get blank title. In that case, lets find out title from Enquiry
                        if (empty($productName)) {
                            $productName = $this->getProductName($details, $singleQuoteData);
                        }

                        $productNames[$singleQuoteData['product_id']] = $productName;
                    }

                    //this is variable product for which quote is created
                    if ($singleQuoteData['variation_id'] != 0 &&  $singleQuoteData['variation_id'] != null) {
                
                        //Create array of variation details
                        $allVariationDetails = $this->getVariationDetails($singleQuoteData['variation']);
                        $tooltipProducts[] = array(
                        'product_id' => $singleQuoteData['product_id'],
                        'product_name' => $productName,
                        'variation_details' => ' ( ' . implode(', ', $allVariationDetails) . ' ) ',
                        'quantity' => $singleQuoteData['quantity'],
                        'variation_id' => $singleQuoteData['variation_id']
                        );
                        //remove index of that variation from enquiry array
                        if (isset($details[$singleQuoteData['variation_index_in_enquiry']])) {
                            unset($details[$singleQuoteData['variation_index_in_enquiry']]);
                        }
                
                    } else {
                        // This is simple product
    
                        $tooltipProducts[] = array(
                        'product_id' => $singleQuoteData['product_id'],
                        'product_name' => $productName,
                        'variation_details' => '',
                        'quantity' => $singleQuoteData['quantity'],
                        'variation_id' => 0

                        );

                        //Find this product in the enquiry array and remove its index
                        $this->removeProductIndex($details, $singleQuoteData['product_id']);
                    }
                }
            }
        }
        //End of quote products data


        //We have now prepared the data for quoataion products. Now lets work with remaining enquiry products
        if (!empty($details)) {
            foreach ($details as $singleEnquiryData) {
                $allVariationDetails = array();
                // Check if we have already figured out the product name
                if (isset($productNames[$singleEnquiryData[0]['id']])) {
                    $productName = $productNames[$singleEnquiryData[0]['id']];
                } else {
                    $productName = get_the_title($singleEnquiryData[0]['id']);
                    //If product does not exist, we will get blank title. In that case, lets find out title from Enquiry
                    if (empty($productName)) {
                        $productName = $singleEnquiryData[0]['title'];
                    }
                    $productNames[$singleEnquiryData[0]['id']] = $productName;
                }
                //Handle Old Enquiries
                // $this->handleOldEnquiries($singleEnquiryData, $productName , $tooltipProducts);
                if (!isset($singleEnquiryData[0]['variation_id'])) {
                    //this is a simple product
                    $tooltipProducts[] = array(
                    'product_id' => $singleEnquiryData[0]['id'],
                    'product_name' => $productName,
                    'variation_details' => '',
                    'quantity' => $singleEnquiryData[0]['quant'],
                    'variation_id' => 0
                    );
                    continue;
                }

                //this is variable product for which quote is not created
                
                if ($singleEnquiryData[0]['variation_id'] != 0 &&  $singleEnquiryData[0]['variation_id'] != null) {
                    //Create array of variation details
                    $allVariationDetails = $this->getVariationDetails($singleEnquiryData[0]['variation']);
                    $tooltipProducts[] = array(
                    'product_id' => $singleEnquiryData[0]['id'],
                    'product_name' => $productName,
                    'variation_details' => ' ( ' . implode(', ', $allVariationDetails) . ' ) ',
                    'quantity' => $singleEnquiryData[0]['quant'],
                    'variation_id' => $singleEnquiryData[0]['variation_id']
                    );
                } else {
                    //this is a simple product
                    $tooltipProducts[] = array(
                    'product_id' => $singleEnquiryData[0]['id'],
                    'product_name' => $productName,
                    'variation_details' => '',
                    'quantity' => $singleEnquiryData[0]['quant'],
                    'variation_id' => 0

                    );
                }
            }
        }

        //Create strings for tooltip
        $this->createTooltip($tooltipProducts, $tooltip, $deletedProductsTooltip);

        $tooltip.= $deletedProductsTooltip . "</table>";
        $currentdata[ 'product_details' ]    = "<a class = 'Items-hover' title='$tooltip'  href='$admin_path?page=quoteup-details-edit&id=$enquiry'> {$totalNumberOfItems} Items </a>";
        return $currentdata;

    }
}

QuoteupTooltipOnHover::getInstance();
