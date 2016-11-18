<?php
namespace Includes\Frontend;

$variationIdIndexes = array();
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
/**
 * Handles the cart activities on frontend part
 */

class QuoteupHandleCart
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
     *
     * Used to add various action and filter
     */
    protected function __construct()
    {

         add_filter('woocommerce_get_price', array($this,'addCustomPrice'), 10, 2);
         add_action('woocommerce_new_order', array($this,'unsetSession'), 10, 1);
         add_action('wp', array($this, 'redirectToCheckoutPage'), 1);
         add_action('init', array($this,'removeAddToCart'), 15);
         add_action('wp_ajax_clearsession', array($this,'unsetSession'));
         add_action('wp_ajax_nopriv_clearsession', array($this,'unsetSession'));
         add_action('wp_enqueue_scripts', array($this, 'loadScript'));
         add_action('woocommerce_before_calculate_totals', array($this,'add_custom_price'));
        

    }


	function add_custom_price( $cart_object ) {
		global $quoteup;
		$quotationProducts = $quoteup->wcCartSession->get( 'quotationProducts' );
        foreach ($cart_object->cart_contents as $key => $value) {
            if ($quotationProducts) {
                foreach ($quotationProducts as $row) {
                    $variations = unserialize($row['variation']);
                    $newVariation = array();
                    // if ($variations !="" || !empty($variations)) {
                    if (!empty($variations)) {
                        foreach ($variations as $attributeName => $attributeValue) {
                            $newVariation['attribute_'.trim($attributeName)] = trim($attributeValue);
                        }
                        if ($value['product_id']==$row['product_id'] && $value['variation_id']==$row['variation_id'] && $value['variation'] === $newVariation) {
                            $value['data']->price = $row['newprice'];
                        }
                    }
                }
            }
            unset($key);
        }
    }



    /**
     * Used to enqueue script
     * To localize script for using ajax url and cart page url in js file
     * @return [type] [description]
     */
	public function loadScript() {
		wp_enqueue_script( 'quoteup-end-approval-script', QUOTEUP_PLUGIN_URL . '/js/public/end-approval-quote-session.js', array(
			'jquery' ) );
        $url=get_permalink(get_option('woocommerce_cart_page_id'));
         wp_localize_script(
		'quoteup-end-approval-script', 'quote_data', array(
             'ajax_url' => admin_url('admin-ajax.php'),
             'URL' => $url,
             )
         );

    }

    /**
     * This function is used to remove add to cart button from all products.
     *
     * Add custom store notice when our session is started
     */
    public function removeAddToCart()
    {

		global $quoteup;
		$quotationProduct = $quoteup->wcCartSession->get( 'quotationProducts' );
        if ($quotationProduct) {
            remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart');
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
            add_action('wp_footer', array($this,'customStoreNotice' ));
        }
    }

    /**
     * Adds products mentioned in the Enquiry in the user's cart
     * @param Int $enquiryId Enquiry id of the Enquiry
     * @param String $enquiryEmail Email id of a user who enquired
     * @param String $enquiryHash Hash of the enquiry
     */
	public function addProductsToCart( $enquiryId ) {
		global $quoteup;
		global $wpdb, $quoteup_enough_stock;

        $table_name=$wpdb->prefix."enquiry_quotation";
        $sql = "SELECT ID,product_id, quantity, newprice, enquiry_id,variation_id,variation FROM $table_name WHERE enquiry_id=$enquiryId";
        $result = $wpdb->get_results($sql, ARRAY_A);
        $replace_order = new \WC_Cart();
        $replace_order->empty_cart(true);

        foreach ($result as $k => $v) {
            $product_id[$k] = $v['product_id'];
            $quantity[$k] = $v['quantity'];
            $variation_id[$k] = $v['variation_id'];
            $variationDetails[$k] = $v['variation'];
        }

        canProductBeAddedInQuotation($product_id, $quantity, $variation_id, $variationDetails, "cart");

		$quoteup->wcCartSession->set( 'quotationProducts', $result );

        foreach ((array)$result as $row) {
            $variations = unserialize($row['variation']);
            $newVariation = array();
            if ($variations !="") {
                foreach ($variations as $attributeName => $attributeValue) {
                    // $keyValue = explode(':', $individualVariation);
                    $newVariation['attribute_'.trim($attributeName)] = trim($attributeValue);
                }
            }
            if ('product_variation' == get_post_type($row['variation_id'])) {
                $variationProduct = new \WC_Product_Variation($row['variation_id']);
                $variations = $variationProduct->get_variation_attributes();
                $replace_order->add_to_cart($row['product_id'], $row['quantity'], $row['variation_id'], $newVariation);

            } else {
                $replace_order->add_to_cart($row['product_id'], $row['quantity']);
            }

        }

    }


    /**
     * Redirects user to the checkout page when user is in our session
     */
	public function redirectToCheckoutPage( $defaultLink = null ) {
        
        if (current_action() == 'wp') {
			global $quoteup;
			$quotationProduct = $quoteup->wcCartSession->get( 'quotationProducts' );
            
            $cartPageId = get_option('woocommerce_cart_page_id');
            global $wp_query;
            
            if (isset($wp_query->post->ID)) {
                if ($cartPageId == $wp_query->post->ID && isset($quotationProduct[0]['enquiry_id'])) {
                    //Add products in the cart if not added already
                    if (WC()->cart->get_cart_contents_count() == 0) {
                            $this->addProductsToCart($quotationProduct[0]['enquiry_id']);
                    }
                    
                    //Force Redirect to checkout
                    wp_redirect(get_permalink(get_option('woocommerce_checkout_page_id')));
                    exit;
                }
            }
        }

        if (!empty($defaultLink) && $defaultLink == 'ManualRedirect') {
			global $quoteup;
			$quotationProduct = $quoteup->wcCartSession->get( 'quotationProducts' );
            if (!empty($quotationProduct)) {
                wp_redirect(get_permalink(get_option('woocommerce_checkout_page_id')));
                exit;
            }
        }
    }

    /**
     * Add custom price for the products in the cart.
     *
     * Here custom price is the price quoted in quotaion for that product
     *
     * @param [float] $price   Orignal price of the product
     * @param [object] $product product details which is added in cart
     */
	public function addCustomPrice( $price, $product ) {
		global $quoteup;
        global $variationIdIndexes;
		$quotationProducts = $quoteup->wcCartSession->get( 'quotationProducts' );
        if ($quotationProducts) {
            foreach ($quotationProducts as $index => $row) {
                //If row's product id equals to current product, then process. Else skip to next row
                if ($product->id != $row['product_id']) {
                    continue;
                }

                //Check if Variable Product
                if ($row['variation_id']!= 0 && $row['variation_id']!= null) {
                    //Check if current variation is appearing more than once
                    if (isset($variationIdIndexes[$row['variation_id']][$row['ID']])) {
                            //If Row id for current variation already exists, fetch price from our array and return that pric
                            continue;
                    }
                    //If this is new variation
                     $variationIdIndexes[$row['variation_id']][$row['ID']] = $row['newprice'];
                    return $row['newprice'];
                } else {
                        //Simple Product
                        return $row['newprice'];
                }
                unset($index);
            }
        }
        return $price;
    }

    /**
     * Unset session once the order is  completed.
     *
     * unset session if user clicks on end session in custom store notice
     * @param  [int] $order Order id if the order is completed
     */
	public function unsetSession( $order ) {
		global $quoteup, $quoteupManageHistory;
        if ($order!=="") {
			$quotationProduct = $quoteup->wcCartSession->get( 'quotationProducts' );
            if ($quotationProduct) {
                foreach ($quotationProduct as $row) {
                    $enquiry_id=$row['enquiry_id'];
                    break;
                }
                //update History Table
                $quoteupManageHistory->addQuoteHistory($enquiry_id, '-', 'Order Placed');
                //Add Enquiry id in order meta
                update_post_meta($order, 'quoteup_enquiry_id', $enquiry_id);
                //Add order note
                $orderObject = new \WC_Order($order);
                $orderObject->add_order_note("This order is related to Enquiry Id #".$enquiry_id);
                
                

                


				\Includes\QuoteupOrderQuoteMapping::updateOrderIDOfQuote( $enquiry_id, $order );
            }
        }
        remove_action('wp_footer', array($this,'customStoreNotice' ));
		$quoteup->wcCartSession->unsetSession();
        if (WC()->cart->get_cart_contents_count() !== 0) {
            $replace_order = new \WC_Cart();
            $replace_order->empty_cart(true);
        }
        //When 'Click Here' button inside customer notice is clicked, Ajax is fired and hence die after execution
        if (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['action']) && $_POST['action'] == 'clearsession') {
            die();
        }
    }

    /**
     * Custom Store notice Displayed once Session is started.
     */
	public function customStoreNotice() {

        $notice = __(' Your Quotation Session has started. Hence, you cannot add any more products. To end the session, <input type="button" id="endsession" title="Ending Session will clear the current cart." value="Click Here">', 'quoteup');


        echo '<p class="demo_store">' .$notice. '</p>';
    }
}

$this->wcCart = QuoteupHandleCart::getInstance();
