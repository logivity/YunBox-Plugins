<?php

namespace Templates\Frontend;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handle the view of approval and Rejection of Quote
 */
use Includes\Frontend\QuoteupHandleEnquiryCart;

class QuoteupHandleEnquiryCartView
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
        add_action('quoteup_enquiry_cart_content', array( $this, 'enquiryCartView' ));
    }

    public function enqueueScripts()
    {
        global $quoteup;
        $form_data = quoteupSettings();

        if (isset($form_data[ 'enable_disable_mpe' ]) && $form_data[ 'enable_disable_mpe' ] == 1) {
            $url = get_permalink($form_data[ 'mpe_cart_page' ]);

            wp_enqueue_script('quoteup-quote-cart', QUOTEUP_PLUGIN_URL . '/js/public/quote-cart.js', array( 'jquery', 'jquery-ui-draggable' ), time(), true);


            $country         = $form_data[ 'phone_country' ];
            $redirect_url    = $quoteup->displayQuoteButton->getRedirectUrl($form_data);

            $data = getLocalizationDataForJs($redirect_url, $country);

            wp_localize_script('quoteup-quote-cart', 'wdm_data', $data);

            unset($url);
        }
        wp_enqueue_script('phone_validate', QUOTEUP_PLUGIN_URL . '/js/public/phone-format.js', array( 'jquery' ), false, true);
    }

    /**
     * This function prints cart head
     * @return [type] [description]
     */
    public function getCartHead()
    {
        $str = "<thead>
                                  <tr class='cart_item cart_header'>
                                     <th class='product-remove cart-remove'>&nbsp;</th>
                                     <th class='product-thumbnail cart-thumbnail'>&nbsp;</th>
                                     <th class='product-name cart-name'>" . __('Product', 'quoteup') . "</th>
                                     <th class='product-price cart-price'>" . __('Price', 'quoteup') . "</th>
                                     <th class='product-quantity cart-quantity'>" . __('Quantity', 'quoteup') . "</th>";
        if (isset($form_data[ 'enable_disable_quote' ]) && $form_data[ 'enable_disable_quote' ] ==0) {
            $str .= "<th class='product-subtotal cart-subtotal'>" . __('Expected Price', 'quoteup');
        } else {
            $str .= "<th class='product-subtotal cart-subtotal'>" . __('Remarks', 'quoteup');
        }
        echo $str .= "</th></tr></thead>";

    }

    public function enquiryCartView()
    {
        @session_start();
        $this->enqueueScripts();
        if ($_SESSION[ 'wdm_product_count' ] > 0) {
            $manual_css      = '';
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
            if (isset($form_data[ 'button_CSS' ]) && $form_data[ 'button_CSS' ] == 'manual_css') {
                $manual_css = 1;
            }
            echo "
                <div class='quoteup-quote-cart'>
                    <div class='woocommerce wdm-quoteup-woo'>
                        <div class='error-quote-cart' id='error-quote-cart'></div>
                            <table class='shop_table cart wdm_shop_tbl wdm-quote-cart-table' cellspacing='0'>";
            $this->getCartHead();
            echo "<tbody>";
//<th class='product-total cart-total'>" . __('Total Price', 'quoteup') . "</th>
            foreach ($_SESSION[ 'wdm_product_info' ] as $element) {
                foreach ($element as $product) {
                    $pro             = new \WC_Product($product[ 'id' ]);
                    $current_status  = get_post_meta($product[ 'id' ], '_enable_price', true);
                    // $product['img']
                    $img_content = "";
                    if (isset($prod[ 'variation_id' ]) && $product[ 'variation_id' ] != '') {
                        $img_content = wp_get_attachment_url(get_post_thumbnail_id($prod[ 'variation_id' ]));
                    }
                    if (!$img_content || $img_content == "") {
                        $img_content = wp_get_attachment_url(get_post_thumbnail_id($product[ 'id' ]));
                    }
                    if (!$img_content || $img_content == "") {
                        $img_content = WC()->plugin_url() . "/assets/images/placeholder.png";
                    }

                    if ($current_status == 'yes') {
                        $totalPrice  = $product['price'];
                        $totalPrice  = wc_price($totalPrice * $product[ 'quant' ]);
                    } else {
                        $totalPrice = '-';
                    }
                    $url                     = get_permalink($product[ 'id' ]);
                    $soldIndividually        = '';
                    $status                  = isSoldIndividually($product[ 'id' ]);
                    $soldIndividuallyClass   = '';

                    if ($status == true) {
                        $soldIndividually        = "disabled";
                        $soldIndividuallyClass   = "sold_individually";
                    }
                    echo $str = "<tr class='cart_item cart_product'>
	        <td class='product-remove'><a href='#' class='remove' data-product_id='{$product[ 'id' ]}' data-variation_id='{$product['variation_id']}' data-variation = '".json_encode($product['variation'])."'
                 >&times;</a>
            </td>
			<td class='product-thumbnail'>
            <img width='180' height='180' src='{$img_content}' class='attachment-shop_thumbnail size-shop_thumbnail wp-post-image'  sizes='(max-width: 180px) 100vw, 180px' />

            </td>
            <td class='product-name'><a href='{$url}'>{$product[ 'title' ]}</a>";
                    if ($product['variation'] !='') {
                        foreach ($product['variation'] as $attributeName => $attributeValue) {
                            echo "<br>".wc_attribute_label($attributeName).":".$attributeValue;
                        }
                    }
                    echo $str = "</td>
            <td class='product-price'>" . $totalPrice . "</td>
            <td class='product-quantity'><div class='quantity wdm-quantity'><input type='number' min='1' step='1' data-product_id='{$product[ 'id' ]}' data-variation_id='{$product['variation_id']}' data-variation = '".json_encode($product['variation'])."'
                name='wdm_product_quantity' value='{$product[ 'quant' ]}' class='" . $soldIndividuallyClass . " wdm-prod-quant input-text qty'" . $soldIndividually . "></div>
            </td>";
                    if (isset($form_data[ 'enable_disable_quote' ]) && $form_data[ 'enable_disable_quote' ] ==0) {
                        $placeholder = __('Expected price and remarks', 'quoteup');
                    } else {
                        $placeholder = __('Remarks', 'quoteup');
                    }
                    echo $str = "<td class='product-subtotal'><textarea placeholder='$placeholder' rows='2' cols='5' class='wdm-remark' data-product_id='{$product[ 'id' ]}'>{$product[ 'remark' ]}</textarea>
            </td>
        </tr>";
                }
            }
//<td class='Total-price'>" . $totalPrice . "</td>
            $ajax_nonce  = wp_create_nonce('nonce_for_enquiry');
            $url         = admin_url();
            $optionData = quoteupSettings();
            if (isset($optionData['enable_disable_quote']) && $optionData['enable_disable_quote']==1) {
                echo $str        = "<tr>
                <td colspan='6' class='td-btn-update'><input type='button' class='update wdm-update' value='". __('Update Enquiry Cart', 'quoteup') ."'><span class='load-ajax'></span></td>
                </tr>";
            } else {
                echo $str        = "<tr>
                <td colspan='6' class='td-btn-update'><input type='button' class='update wdm-update' value='". __('Update Enquiry & Quote Cart', 'quoteup') ."'><span class='load-ajax'></span></td>
                </tr>";
            }
            echo $str        = "</tbody>

                </table>
                </div>
                <div class='wdm-enquiry-form'>
                <h4 class='wdm-enquiry-form-title'>
                " . __('Requestor Information', 'quoteup') . "
                </h4>
 	              <form method='post' id='frm_mpe_enquiry' name='frm_enquiry' class='wdm-mpe-form' >

                        <input type='hidden' name='mpe_ajax_nonce' id='mpe_ajax_nonce' value='{$ajax_nonce}'>
                        <input type='hidden' name='submit_value' id='submit_value'>

                        <input type='hidden' name='site_url' id='site_url' value='{$url}'>
                        <input type='hidden' name='tried' id='tried' value='yes' />
                        <!--<div class='ck_msg wdm-enquiry-form-indication'><sup class='req'>*</sup> " . __('Indicates required fields', 'quoteup') . "</div>-->
                        <div id='error' class='error' >
                        </div>
                        <div id='wdm_nonce_error'>
                            <div  class='wdmquoteup-err-display'>
                                <span class='wdm-quoteupicon wdm-quoteupicon-exclamation-circle'></span><?php _e('Unauthorized enquiry', 'quoteup') ?>
                            </div>
                        </div>";
            do_action('mpe_add_custom_field_in_form');

            $enable_mc = '';
            if (isset($form_data[ 'enable_send_mail_copy' ])) {
                $enable_mc = $form_data[ 'enable_send_mail_copy' ];
            }
            if ($enable_mc == 1) {
                echo "<div class='ck mpe_form_input'><div class='mpe-left' style='height: 1px;'></div>";
                echo "<label class='mpe-right contact-cc-wrap'";
                if (isset($dialogue_text_color)) {
                    echo " style=' color: " . $dialogue_text_color . ";'";
                }
                echo "><input type='checkbox' id='contact-cc'  name='cc' value='yes' /><span class='contact-cc-txt'>" . __('Send me a copy', 'quoteup') . "</span></label>";
                echo "</div>";
            }
            echo "
    <div class='form_input btn_div wdm-enquiryform-btn-wrap clearfix'>
                            <div class='mpe-left' style='height: 1px;'></div>
                                <div class='mpe-right'>

                            <input type='submit' value='" . __('Send', 'quoteup') . "
                                ' name='btnSend'  id='btnMPESend' class='button_example'";


            if ($manual_css == 1) {
                echo getManualCSS($form_data);
            }
            $url = get_permalink(get_option('woocommerce_shop_page_id'));
            echo ">
	<span class='load-send-quote-ajax'></span>
          </div>
          </div>
          <div class='form-errors-wrap mpe_form_input' id='wdm-quoteupform-error'>
            <div class='mpe-left' style='height: 1px;'></div>
            <div class='mpe-right form-errors'>
                <ul class='error-list'>
                </ul>
            </div>
          </div>
         </form>
        </div>
        </div>
        <div class='success'><div class='wdm-enquiry-success'><span class='wdm-quoteupicon wdm-quoteupicon-done wdm-enquiry-success-icon'></span>" . __('Enquiry sent successfully!', 'quoteup') . "</div><div class='woocommerce'><p class='return-to-shop'><a class='button wc-backward' href='{$url}'>". __('Return To Shop', 'quoteup') ."</a></p></div></div>";
        } else {
            $url = get_permalink(get_option('woocommerce_shop_page_id'));
            $optionData = quoteupSettings();
            if (isset($optionData['enable_disable_quote']) && $optionData['enable_disable_quote']==1) {
                echo "<div class='woocommerce'>
    <p class='cart-empty'>" . __('Your enquiry cart is currently empty.', 'quoteup') . "</p>
    <p class='return-to-shop'><a class='button wc-backward' href='{$url}'>". __('Return To Shop', 'quoteup') ."</a></p>
    </div>";
            } else {
                echo "<div class='woocommerce'>
    <p class='cart-empty'>" . __('Your enquiry and quotation cart is currently empty.', 'quoteup') . "</p>
    <p class='return-to-shop'><a class='button wc-backward' href='{$url}'>". __('Return To Shop', 'quoteup') ."</a></p>
    </div>";
            }

        }
    }
}

$quoteupHandleEnquiryCartView = QuoteupHandleEnquiryCartView::getInstance();
