<?php

namespace Includes\Admin;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * This class is used to genrate pdf file of quotation
 */
class QuoteupGeneratePdf {
    
    /**
     * This is header for PDF
     * @param  [array] $pdfSetting Settings done by admin in settings panel
     * @param  [string] $mail       [Email id of customer]
     * @param  [string] $name       [Name of customer]
     * @return [type]             [description]
     */
	public static function header( $pdfSetting, $mail, $name ) {
		global $quoteup;
        ?>
        <div id='header'>
            <div class="PDFLogo">
        <?php if (isset($pdfSetting[ 'company_logo' ]) && $pdfSetting[ 'company_logo' ] != "") { ?>
                    <img  src='<?php echo $pdfSetting[ 'company_logo' ]; ?>' height="150px" width="150px">
                <?php } ?>
            </div>
            <div class="content">
                <h2> <?php _e('Quote', 'quoteup'); ?>  </h2>
        <?php
        self::fromData($pdfSetting);
        ?>
                <div class="clear"></div>
                <div class="to-info">
                    <div class="to-title">
        <?php _e('Quote For', 'quoteup'); ?>
                    </div>
                    <div class="to-data">
                        <?php
                        echo $name . "<br>";
                        echo $mail . "<br>";
                        ?>
                    </div>

                </div>
                <div class="clear"></div>
        <?php
				$expiration_date = $quoteup->manageExpiration->getExpirationDate( $_POST[ 'enquiry_id' ] );
        if (! empty($expiration_date)) {
            ?>
                    <div class="expiration-info">
                        <div class="expiration-title">
                    <?php _e('Expiration Date', 'quoteup'); ?>
                        </div>
                        <div class="expiration-data">
                            <?php
                            echo $expiration_date . "<br>";
                            ?>
                        </div>
                    </div>
                    <div class="clear"></div>
            <?php
        }
        ?>
            </div>
        </div>
                <?php
    }

            /**
             * This function displays from data in PDF header
             * @param  [array] $pdfSetting Settings done by admin in settings panel
             * @return [type]             [description]
             */
	public static function fromData( $pdfSetting ) {
        ?>
        <div class="from-info">
    <div class="from-title">
        <?php _e('From', 'quoteup'); ?>
    </div>
    <div class="from-data">
        <?php
        echo isset($pdfSetting[ 'company_name' ]) ? $pdfSetting[ 'company_name' ] . "<br>" : '';
        echo isset($pdfSetting[ 'company_address' ]) ? $pdfSetting[ 'company_address' ] . "<br>" : '';
        echo isset($pdfSetting[ 'company_email' ]) ? $pdfSetting[ 'company_email' ] . "<br>" : '';
        ?>

    </div>
        </div>
        <?php
    }

    public static function addEachProductInQuotation($quotation,$show_price)
    {
        $total_price = 0;
        foreach ($quotation as $quoteProduct) {
            $_product    = wc_get_product($quoteProduct['product_id']);
            if (empty($_product)) {
                continue;
            }
            $price       = $quoteProduct['oldprice'];
                ?>
                                <tr>
                                <?php
                                if ($show_price == 1) {
                                    ?>
                                    <td class="product-price"><?php echo get_the_title($quoteProduct['product_id']);
                                    if ($_product->is_type('variable')) {
                                        $variationArray = unserialize($quoteProduct['variation']);
                                        foreach ($variationArray as $attributeName => $attributeValue) {
                                            $taxonomy = trim($attributeName);
                                            echo "<br>".wc_attribute_label($taxonomy).":".$attributeValue;
                                        }
                                    }
                                    ?>
                                    </td>
                                    <?php
                                } else {
                                    ?>
                                    <td class="product"><?php echo get_the_title($quoteProduct['product_id']);
                                    if ($_product->is_type('variable')) {
                                        $variationArray = unserialize($quoteProduct['variation']);
                                        foreach ($variationArray as $attributeName => $attributeValue) {
                                            // $variation = explode(':', $variation);
                                            $taxonomy = trim($attributeName);
                                            echo "<br>".wc_attribute_label($taxonomy).":".$attributeValue;
                                        }

                                    }
                                    ?>
                                    </td>
                                    <?php
                                }

                                if ($show_price == 1) {
                                    ?>
                                    <td class="sku-price"><?php
                                    if ($_product->is_type('variable')) {
                                        $_product_variation    = wc_get_product($quoteProduct['variation_id']);
                                        echo $_product_variation->get_sku();
                                    } else {
                                        echo $_product->get_sku();
                                    }
                                        ?></td>
                                    
                                <?php
                                } else {
                                    ?>
                                    <td class="sku"><?php
                                    if ($_product->is_type('variable')) {
                                        $_product_variation    = wc_get_product($quoteProduct['variation_id']);
                                        echo $_product_variation->get_sku();
                                    } else {
                                        echo $_product->get_sku();
                                    }
                                        ?>
                                    </td>
                                <?php
                                }
                                if ($show_price == 1) {
                                    ?>
                                        <td class="old-price-content"><?php echo wc_price($price); ?></td>
                                        <?php
                                }
                                    ?>
                                    <td class="new-price-content"><?php echo wc_price($quoteProduct['newprice']); ?></td>
                                    <td class="qty-content"><?php echo $quoteProduct['quantity']; ?></td>
                                    <td class="amount-content"><?php
                                    echo wc_price($quoteProduct['newprice'] * $quoteProduct['quantity']);
                                    $total_price = $total_price + $quoteProduct['newprice'] * $quoteProduct['quantity']     ;
                                    ?></td>
                                </tr>
                                        <?php
        }

        return $total_price;

    }

            /**
             * Function is used to genrate pdf
             */
    public static function generatePdf()
    {
        global $wpdb;
        $enquiry_tbl     = $wpdb->prefix . 'enquiry_detail_new';
        $quotation_tbl   = $wpdb->prefix . 'enquiry_quotation';
        $enquiry_id      = $_POST[ 'enquiry_id' ];
        $show_price      = isset($_POST[ 'show-price' ]) ? $_POST[ 'show-price' ] : '0';
        //Get data of enquiry details
        $enquiry_details = $wpdb->get_row($wpdb->prepare("SELECT name, email, product_details FROM $enquiry_tbl WHERE enquiry_id = %d", $enquiry_id));
        //Get data of Quotation(Updated price and Quantity)
        $quotation       = $wpdb->get_results($wpdb->prepare("SELECT * FROM $quotation_tbl WHERE enquiry_id = %d", $enquiry_id), ARRAY_A);
        $products        = unserialize($enquiry_details->product_details);
        $pdfSetting      = get_option("wdm_form_data");
        // $site_name=get_bloginfo();
        // $tagline= get_bloginfo('description') ;
        // $admin_mail = get_option('admin_email');
        $name            = $enquiry_details->name;
        $mail            = $enquiry_details->email;

        //Genrate hash for this enquiry id
        $hash = quoteupEnquiryHashGenerator($enquiry_id);

        //update Hash in database(enquiry_detail_new) Table
        \updateHash($enquiry_id, $hash);
        //Genrate Unique URL For Approve or reject
        $uniqueURL = quoteLinkGenerator($hash);
        if (empty($uniqueURL)) {
            echo "ERROR";
            die();
        }


        ob_start();
        ?>
        <html>
    <body>
        <?php
        self::header($pdfSetting, $mail, $name);
        ?>
        <div id="head"><h2> <?php _e('Quote Request', 'quoteup'); ?> #<?php echo "$enquiry_id"; ?></h2></div>
        <div id="Enquiry">
    <table align="center" class="quote_table">
        <tr>
            <th class="product-head"><?php _e('Product', 'quoteup'); ?></th>
            <th class="sku-head"> <?php _e('Sku', 'quoteup'); ?> </th>
        <?php
        if ($show_price == 1) {
            ?>
                                <th class="old-price-head"> <?php _e('Old', 'quoteup'); ?> </th>
                                <?php
        }
            ?>

            <th class="new-price-head"> <?php _e('New', 'quoteup'); ?> </th>
                    <th class="qty-head"> <?php _e('Quantity', 'quoteup'); ?> </th>
                    <th class="amount-head"> <?php _e('Amount', 'quoteup'); ?> </th>
                </tr>
        <?php
        $products    = unserialize($enquiry_details->product_details);

       $total_price =  self::addEachProductInQuotation($quotation, $show_price);
                ?>
                <tr border="1">
                <?php
                if ($show_price == 1) {
                    ?>
                        <td colspan="4"></td>
                        <?php
                } else {
                    ?>
                    <td colspan="3"></td>
                    <?php
                }
                    ?>
                    <td> <?php _e('TOTAL', 'quoteup'); ?> </td>
                    <td align="right" ><?php echo wc_price($total_price); ?></td>
                </tr>

                    </table>
                    <table align="center">

                <tr>
                    <td> <?php _e('TAX', 'quoteup'); ?>: </td>
                    <td colspan="4"> <?php _e('Quote does not include default store tax', 'quoteup'); ?>.</td>
                </tr>

                <tr>
                    <td> <?php _e('SHIPPING', 'quoteup'); ?>: </td>
                    <td colspan="3"> <?php _e('Quote does not include shipping', 'quoteup'); ?>.</td>
                </tr>

                    </table>
                </div>
                <?php
                echo sprintf(__(' %s To Approve or Reject Quotation %s Click Here %s %s', 'quoteup'), "<p align='center'>", "<a class='button' href='$uniqueURL'>", "</a>", "</p>");
                ?>

            </body>
        </html>
        <?php
        $html = ob_get_clean();

        self::createFontsDirectory();

        require_once('mpdf/mpdf.php');
        $stylesheet              = file_get_contents(QUOTEUP_PLUGIN_DIR . '/css/admin/pdf-generation.css');
        $upload_dir              = wp_upload_dir();
        $path                    = $upload_dir[ 'basedir' ] . '/QuoteUp_PDF/';
        $mpdf                    = new \mPDF();
        $mpdf->useAdobeCJK       = true;
        $mpdf->autoScriptToLang  = true;
        $mpdf->autoLangToFont    = true;
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->WriteHtml($html, 2);

        $mpdf->Output($path . $enquiry_id . ".pdf", "F");
        die;
    }

	public static function createFontsDirectory() {
        if (! defined('_MPDF_CUSTOM_TTFONTPATH')) {
            $upload_dir              = wp_upload_dir();
            $quoteup_custom_font_dir = $upload_dir[ 'basedir' ] . '/custom_fonts/';
            define('_MPDF_CUSTOM_TTFONTPATH', $quoteup_custom_font_dir);
            if (! file_exists(_MPDF_CUSTOM_TTFONTPATH)) {
                wp_mkdir_p(_MPDF_CUSTOM_TTFONTPATH);
            }
        }
    }
}
