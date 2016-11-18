<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Checks if provided product id is simple or not
 * @param  int $productId Product Id of the product
 * @return boolean            returns true if it is a simple produc, otherwise returns false.
 */
function isSimpleProduct($productId)
{
    $product = get_product($productId);
    if ($product->product_type == null || $product->is_type('simple')) {
        return true;
    }
    return false;
}

/**
 * Returns the Manual CSS Settings saved in the options table
 * @param type $form_data
 */
function getManualCSS( $form_data = array() ) {
    if (empty($form_data)) {
        $form_data = quoteupSettings();
    }

    $btn_text_color  = $form_data[ 'button_text_color' ];
    $btn_border      = $form_data[ 'button_border_color' ];

    $end         = $form_data[ 'end_color' ];
    $start       = $form_data[ 'start_color' ];
    $style_attr  = "style = '";
    $style_array = array();
    if (! empty($btn_text_color)) {
        //$style_attr .= "color:{$btn_text_color} !important;";
        $style_array[] = "color:{$btn_text_color} !important";
    }
    if (! empty($btn_border)) {
        //$style_attr .= "border-color:{$btn_border};";
        $style_array[] = "border-color:{$btn_border}";
    }
    if (! empty($start)) {
        //$style_attr .= "background: {$start};";
        $style_array[] = "background: {$start}";
    }
    if (! empty($btn_border)) {
        //$style_attr .= "border-color:{$btn_border};";
        $style_array[] = "border-color:{$btn_border}";
    }
    if (! empty($start) && ! empty($end)) {
        // $style_attr .= "background: -webkit-linear-gradient(bottom,{$start}, {$end});background: -o-linear-gradient(bottom,{$start}, {$end});background: -moz-linear-gradient(bottom,{$start}, {$end});filter:progid:DXImageTransform.Microsoft.gradient(GradientType=0,startColorstr=/'{$start}/', endColorstr=/'{$end}/');-ms-filter: progid:DXImageTransform.Microsoft.gradient(GradientType=0,startColorstr=/'{$start}/', endColorstr=/'{$end}/');background: linear-gradient({$start}, {$end});";
        $style_array[] = "background: -webkit-linear-gradient(bottom,{$start}, {$end})";
        $style_array[] = "background: -o-linear-gradient(bottom,{$start}, {$end})";
        $style_array[] = "background: -moz-linear-gradient(bottom,{$start}, {$end})";
        $style_array[] = "filter:progid:DXImageTransform.Microsoft.gradient(GradientType=0,startColorstr={$start}, endColorstr={$end})";
        $style_array[] = "-ms-filter: progid:DXImageTransform.Microsoft.gradient(GradientType=0,startColorstr={$start}, endColorstr={$end})";
        $style_array[] = "background: linear-gradient({$start}, {$end})";
    }
    $style_attr .= implode(';', $style_array) . "'";

    return htmlspecialchars($style_attr);
}

function getLocalizationDataForJs($redirect_url, $country)
{
    $product_id      = "";
    $quoteCart       = "";
    $quoteCartLink   = "";
    $mpe             = 'no';
    if (is_product()) {
        global $product;
        $product_id = $product->id;
    }
    $form_data = get_option("wdm_form_data");
    if (isset($form_data[ 'enable_disable_mpe' ]) && $form_data[ 'enable_disable_mpe' ] == 1) {
        $mpe = 'yes';
    }
    if (isset($form_data[ 'mpe_cart_page' ])) {
        $quoteCart       = $form_data[ 'mpe_cart_page' ];
        $quoteCartLink   = get_permalink($quoteCart);
    }

    if(isset($form_data[ 'cart_custom_label' ]) && !empty($form_data[ 'cart_custom_label' ])){
        $QuoteCartLinkWithText =  "<a href='$quoteCartLink'>" . $form_data[ 'cart_custom_label' ] . "</a>";
    }elseif (isset($form_data[ 'enable_disable_quote' ]) && $form_data[ 'enable_disable_quote' ] ==0) {
        $QuoteCartLinkWithText =  "<a href='$quoteCartLink'>" . __('View Enquiry & Quote Cart', 'quoteup') . "</a>";
    } else {
        $QuoteCartLinkWithText =  "<a href='$quoteCartLink'>" . __('View Enquiry Cart', 'quoteup') . "</a>";
    }


    return array(
        'ajax_admin_url'                 => admin_url('admin-ajax.php'),
        'name_req'                       => __('Please Enter Name', 'quoteup'),
        'valid_name'                     => __('Please Enter Valid Name', 'quoteup'),
        'e_req'                          => __('Please Enter Email Address', 'quoteup'),
        'email_err'                      => __('Please Enter Valid Email Address', 'quoteup'),
        'tel_err'                        => __('Please Enter Valid Telephone No', 'quoteup'),
        'msg_req'                        => __('Please Enter Message', 'quoteup'),
        'msg_err'                        => __('Message length must be between 15 to 500 characters', 'quoteup'),
        'nm_place'                       => __('Name*', 'quoteup'),
        'email_place'                    => __('Email*', 'quoteup'),
        'please_enter'                   => __('Please Enter', 'quoteup'),
        'please_select'                  => __('Please Select', 'quoteup'),
        'fields'                         => apply_filters('quoteup_get_custom_field', 'fields'),
        'redirect'                       => $redirect_url,
        'country'                        => $country,
        'product_id'                     => $product_id,
        'MPE'                            => $mpe,
        'view_quote_cart_link_with_text' => $QuoteCartLinkWithText,
        'view_quote_cart_link'           => $quoteCartLink,
        'products_added_in_quote_cart'   => __('products added in Quote Cart', 'quoteup'),
        'select_variation'   => __('Please select variation before sending enquiry', 'quoteup'),
        'product_added_in_quote_cart'    => __('product added in Quote Cart', 'quoteup'),
        'cart_not_updated'               => __('Enter valid Quantity', 'quoteup'),
        'spinner_img_url'                => admin_url('images/spinner.gif'),
    );
}

/**
 * Returns the  Base Url of the plugin without trailing slash
 * @return type
 */
function quoteupPluginUrl()
{
    return untrailingslashit(plugins_url('/', __FILE__));
}

/**
 * Returns the  Base dir of the plugin without trailing slash
 * @return type
 */
function quoteupPluginDir()
{
    return untrailingslashit(plugin_dir_path(__FILE__));
}

/**
 * Returns the Base dir of the WooCommerce plugin without trailing slash
 */
function quoteupWcPluginDir() {
	return untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) . 'woocommerce' );
}

/**
 * Generates a hash to be used for Enquiry
 * @param int $enquiryId
 * @return string enquiry hash
 */
function quoteupEnquiryHashGenerator($enquiryId)
{
    $hash = sha1(uniqid(rand(), true));
    list($usec, $sec) = explode(' ', microtime());
    $hash .= dechex($usec) . dechex($sec);
    return $enquiryId . '_' . $hash;
}

/**
 * Generates a link to be used to reach Approval/Rejection page
 * @param string $enquiryHash
 * @return mixed reutrns false or returns a generated link
 */
function quoteLinkGenerator($enquiryHash)
{
    $enquiryHash = trim($enquiryHash);
    if (empty($enquiryHash)) {
        return false;
    }
    $optionData = quoteupSettings();
    if (! isset($optionData[ 'approval_rejection_page' ]) || ! intval($optionData[ 'approval_rejection_page' ])) {
        return false;
    }
    return add_query_arg('quoteupHash', $enquiryHash, get_page_link($optionData[ 'approval_rejection_page' ]));
}

/**
 * Set hash to the enquiry in database
 */
function updateHash($enquiry_id, $hash)
{
    global $wpdb;
    $table_name = $wpdb->prefix . "enquiry_detail_new";
    $wpdb->update(
        $table_name,
        array(
        'enquiry_hash' => $hash,
        ),
        array(
        'enquiry_id' => $enquiry_id,
        )
    );
}

/**
 * Check if a product is sold individually (no quantities).
 *
 * @return bool
 */
function isSoldIndividually($productId)
{
    $product = new WC_product($productId);
    return $product->is_sold_individually();
}

/**
 * [This function downloads the file from the specified url]
 * it is the copy of wordpress download URL function.
 * We have replaced wp_remote_safe_get to wp_remote_get
 * @param  [string]  $url     [URL from which we have to download file]
 * @param  integer $timeout [description]
 * @return [type]           [description]
 */
function quoteup_download_url($url, $timeout = 300)
{
    //WARNING: The file is not automatically deleted, The script must unlink() the file.
    if (! $url) {
        return new WP_Error('http_no_url', __('Invalid URL Provided.', 'quoteup'));
    }

    $tmpfname = wp_tempnam($url);
    if (! $tmpfname) {
        return new WP_Error('http_no_file', __('Could not create Temporary file.', 'quoteup'));
    }

	$response = wp_remote_get( $url, array( 'timeout'	 => $timeout, 'stream'	 => true,
		'filename'	 => $tmpfname ) );

    if (is_wp_error($response)) {
        unlink($tmpfname);
        return $response;
    }

    if (200 != wp_remote_retrieve_response_code($response)) {
        unlink($tmpfname);
        return new WP_Error('http_404', trim(wp_remote_retrieve_response_message($response)));
    }

    $content_md5 = wp_remote_retrieve_header($response, 'content-md5');
    if ($content_md5) {
        $md5_check = verify_file_md5($tmpfname, $content_md5);
        if (is_wp_error($md5_check)) {
            unlink($tmpfname);
            return $md5_check;
        }
    }

    return $tmpfname;
}

function getEnquiryIdFromHash( $quoteupHash ) {
    $enquiry_id = explode('_', $quoteupHash);
    return $enquiry_id[ 0 ];
}

function isProductAvailable($productID)
{
    $parentAvailable = "";
    $productType = get_post_type($productID);
    if($productType == "product_variation"){
        $parentID = wp_get_post_parent_id($productID);
        $parentAvailable = get_post_status($parentID);
    }
    $productAvailable = get_post_status($productID);

    if ($productAvailable) {
        if ($productAvailable == 'trash' || $parentAvailable == 'trash') {
            return false;
        } else {
            return true;
        }
    }
    return false;
}

function quoteupHelpTip( $helptip, $settings = false, $image = '', $title = '' ) {

	if ( $settings === true ) {
		$wooVersion	 = WC_VERSION;
		$wooVersion	 = floatval( $wooVersion );
		if ( $wooVersion < 2.5 ) {
			return '<img class="help_tip" data-tip="' . esc_attr( $helptip ) . '" src="' . WC()->plugin_url() . '/assets/images/help.png" height="16" width="16" />';
		} else {
			return \wc_help_tip( $helptip );
		}
	}
    if (! empty($image)) {
        return '<img class="help_tip tips" alt="' . esc_attr($title) . '" data-tip="' . esc_attr($helptip) . '" src="' . $image . '" height="25" width="25" />';
    }
    return '<span class="help_tip tips" data-tip="' . esc_attr($helptip) . '">' . esc_attr($title) . '</span>';
}

/**
 * This function is used to check all conditions related to stock management
 * @param  [INT] $product_id   [IDs of all products]
 * @param  [INT] $quantity     [Quantity of all products ]
 * @param  [INT] $variation_id [Variation IDs of all products]
 * @return [boolean]               [returns true if all conditions are satisfied]
 */
function canProductBeAddedInQuotation( $product_id, $quantity, $variation_id, $variationDetails, $calledFrom ) {
    global $quoteup_enough_stock, $quoteup_enough_stock_product_id,$quoteup_enough_stock_variation_details;
    $size = sizeof($product_id);
    for ($i = 0; $i < $size; $i ++) {
        if ($product_id[ $i ] == "") {
            return true;
        }
        $isProductInStock = true;
        if ('-' == $variation_id[ $i ] || 0 == $variation_id[ $i ]) {
            $product = new \WC_Product($product_id[ $i ]);
            $isProductInStock = $product->is_in_stock();
        } else {
            $product = new \WC_Product($variation_id[ $i ]);
            $isProductInStock = $product->is_in_stock();
            if ($isProductInStock && !$product->managing_stock()) {
                $product = new \WC_Product($product_id[ $i ]);
            }
        }
        if ($isProductInStock) {
            if ($product->managing_stock()) {
                $stockquantity = $product->get_stock_quantity();
                if (! $product->backorders_allowed()) {
                    if (! $product->has_enough_stock($quantity[ $i ])) {
                        switch ($calledFrom) {
                            case 'quote':
                                if ('-' == $variation_id[ $i ] || 0 == $variation_id[ $i ]) {
                                    echo sprintf(__("Stock for %s is only %d. Please make quotation for less quantity", 'quoteup'), get_the_title($product_id[ $i ]), $stockquantity);
                                    die;
                                    break;
                                } else {
                                    $product = new \WC_Product_Variation($variation_id[ $i ]);
                                    if ($variationDetails[ $i ] != "") {
                                        $newVariation = array();
                                        foreach ($variationDetails[ $i ] as $individualVariation) {
                                            $keyValue                            = explode(':', $individualVariation);
                                            $newVariation[ trim($keyValue[ 0 ]) ]  = trim($keyValue[ 1 ]);
                                        }

                                        $variationDetails[ $i ] = $newVariation;
                                    }
                                    $variation_detail = $variationDetails[$i];
                                    $variationString ="";
                                    foreach ($variation_detail as $attributeName => $attributeValue) {
                                        if (!empty($variationString)) {
                                            $variationString .= ",";
                                        }
                                        $variationString .= "<b> ".wc_attribute_label(str_replace("attribute_", "", $attributeName))."</b> : ".$attributeValue;
                                    }
                                    echo sprintf(__("Stock for %s(%s) is only %d. Please make quotation for less quantity", 'quoteup'), get_the_title($product_id[ $i ]), $variationString, $stockquantity);
                                    die;
                                    break;
                                }

                            case 'cart':
                                $quoteup_enough_stock            = false;
                                $quoteup_enough_stock_product_id = $product_id[ $i ];
                                if ('-' != $variation_id[ $i ] && 0 != $variation_id[ $i ]) {
                                    $product = new \WC_Product_Variation($variation_id[ $i ]);
                                    if ($variationDetails[ $i ] != "") {
                                        $newVariation = array();
                                        foreach ($variationDetails[ $i ] as $individualVariation) {
                                            $keyValue                            = explode(':', $individualVariation);
                                            $newVariation[ trim($keyValue[ 0 ]) ]  = trim($keyValue[ 1 ]);
                                        }

                                        $variationDetails[ $i ] = $newVariation;
                                    }
                                    $variation_detail = $variationDetails[$i];
                                    $variationString ="";
                                    foreach ($variation_detail as $attributeName => $attributeValue) {
                                        if (!empty($variationString)) {
                                            $variationString .= ",";
                                        }
                                        $variationString .= "<b> ".wc_attribute_label(str_replace("attribute_", "", $attributeName))."</b> : ".$attributeValue;
                                    }
                                    $quoteup_enough_stock_variation_details = "(".$variationString.")";
                                    // echo sprintf(__("%s(%s) is out of stock. Please contact site admin for more information", 'quoteup'), get_the_title($product_id[ $i ]), $variationString);
                                    break;
                                }
                                break;
                        }
                    }
                }
            }
        } else {
            switch ($calledFrom) {
                case 'quote':
                    if ('-' == $variation_id[ $i ] || 0 == $variation_id[ $i ]) {
                        echo sprintf(__("Stock not available for %s.", 'quoteup'), get_the_title($product_id[ $i ]));
                        die;
                        break;
                    } else {
                        $product = new \WC_Product_Variation($variation_id[ $i ]);
                        if ($variationDetails[ $i ] != "") {
                            $newVariation = array();
                            foreach ($variationDetails[ $i ] as $individualVariation) {
                                $keyValue                            = explode(':', $individualVariation);
                                $newVariation[ trim($keyValue[ 0 ]) ]  = trim($keyValue[ 1 ]);
                            }

                            $variationDetails[ $i ] = $newVariation;
                        }
                        $variation_detail = $variationDetails[$i];
                        $variationString ="";
                        foreach ($variation_detail as $attributeName => $attributeValue) {
                            if (!empty($variationString)) {
                                $variationString .= ",";
                            }
                            $variationString .= "<b> ".wc_attribute_label(str_replace("attribute_", "", $attributeName))."</b> : ".$attributeValue;
                        }
                        echo sprintf(__("Stock not available for %s(%s).", 'quoteup'), get_the_title($product_id[ $i ]), $variationString);
                        die;
                        break;
                    }

                case 'cart':
                    $quoteup_enough_stock            = false;
                    $quoteup_enough_stock_product_id = $product_id[ $i ];
                    if ('-' != $variation_id[ $i ] && 0 != $variation_id[ $i ]) {
                                    $product = new \WC_Product_Variation($variation_id[ $i ]);
                        if ($variationDetails[ $i ] != "") {
                            $newVariation = array();
                            foreach ($variationDetails[ $i ] as $individualVariation) {
                                $keyValue                            = explode(':', $individualVariation);
                                $newVariation[ trim($keyValue[ 0 ]) ]  = trim($keyValue[ 1 ]);
                            }

                            $variationDetails[ $i ] = $newVariation;
                        }
                                    $variation_detail = $variationDetails[$i];
                                    $variationString ="";
                        foreach ($variation_detail as $attributeName => $attributeValue) {
                            if (!empty($variationString)) {
                                $variationString .= ",";
                            }
                            $variationString .= "<b> ".wc_attribute_label(str_replace("attribute_", "", $attributeName))."</b> : ".$attributeValue;
                        }
                                    $quoteup_enough_stock_variation_details = "(".$variationString.")";
                                    break;
                    }
                    break;
            }
        }
    }
    return true;
}

/**
 * Checks whether provided product is in stock or not.
 * @param $product it can be a product id or a Product Object
 * @return boolean
 */
function quoteupIsProductInStock( $product ) {
    if (! is_object($product)) {
        $product = new \WC_Product($product);
    }
    if ($product->is_in_stock()) {
        return true;
    }
    return false;
}

/**
 * This function is used to replace 'enquiry', 'quote' and 'quotation' words if set by user in settings
 * @param  [string] $translatedText [Translated text of the orignal string]
 * @param  [string] $text           [Orignal string]
 * @param  [string] $domain         [text domain of the string]
 * @return [string]                 [Returns the final string]
 */
function replaceText($translatedText, $text, $domain)
{
    $form_data = quoteupSettings();
    // Check if string belongs to our plugin
    if($domain == 'quoteup'){
        if($translatedText == ' Alternate word for Enquiry ' || $translatedText == ' Alternate word for Quote ' || $translatedText == 'Quotation  ' ||  $translatedText == 'Product Enquiry' || $translatedText == 'Enquiry Details' || $translatedText == 'Enquiry & Quote Details' || $translatedText == 'Product Enquiry Pro for WooCommerce (A.K.A QuoteUp)' ||$translatedText == 'QuoteUp Settings' ){
            return $translatedText;
        }

        //Check if replace text for enquiry is set by the admin
        if (isset($form_data['replace_enquiry']) && !empty($form_data['replace_enquiry']))
        {
            //Replace the text
            $translatedText = str_ireplace("enquiry", $form_data['replace_enquiry'], $translatedText);
        }
        //Check if replace text for quote and quotation is set by the admin
        if (isset($form_data['replace_quote']) && !empty($form_data['replace_quote']))
        {
            //Replace the text
            $translatedText = str_ireplace("quote", $form_data['replace_quote'], $translatedText);
            $translatedText = str_ireplace("quotation", $form_data['replace_quote'], $translatedText);
        }
    }
    unset($text);
    return $translatedText;
}

function quoteupSettings()
{
    static $settings;
    if(!isset($settings) || empty($settings)){
        $settings = get_option('wdm_form_data');
    }
    return $settings;
}
add_filter('gettext', 'replaceText', 10, 3);
