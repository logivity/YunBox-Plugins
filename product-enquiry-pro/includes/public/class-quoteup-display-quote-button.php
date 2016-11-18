<?php

namespace Includes\Frontend;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class QuoteUpDisplayQuoteButton
{

    /**
     * @var Singleton The reference to *Singleton* instance of this class
     */
    private static $instance;
    public $add_to_cart_disabled_variable_products   = array();
    public $enquiry_disabled_variable_products       = array();
    public $price_disabled_variable_products         = array();

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
        $this->loadTemplates();
        add_action('wp_head', array( $this, 'bootstrapQuoteupButtonDisplay' ), 10);
    }

    public function bootstrapQuoteupButtonDisplay()
    {
        $this->hookAllRequiredActions();
    }

    private function hookAllRequiredActions()
    {
        do_action('quoteup_create_custom_field');
        do_action('pep_create_custom_field');
        add_filter('woocommerce_is_purchasable', array( $this, 'enableAddToCartForProduct' ), 10, 2);
        add_filter('woocommerce_get_price_html', array( $this, 'displayPrice' ), 10, 2);
        add_filter('woocommerce_variation_price_html', array( $this, 'displayPrice' ), 10, 2);
        add_filter('woocommerce_variation_sale_price_html', array( $this, 'displayPrice' ), 10, 2);
        add_action('wp_head', array( $this, 'decidePositionOfQuoteButton' ), 11);
        add_action('woocommerce_loop_add_to_cart_link', array( $this, 'displayQuoteButtonOnArchive' ), 10);
        add_action('wp_footer', array( $this, 'hideVariationForVariableProducts' ), 10);
        add_action('woocommerce_after_single_variation', array( $this, 'hideAddToCartForVariableProduct' ));
    }

    private function loadTemplates()
    {
        include_once(QUOTEUP_PLUGIN_DIR . '/templates/public/class-quoteup-multiproduct-quote-button.php');
        include_once(QUOTEUP_PLUGIN_DIR . '/templates/public/class-quoteup-single-product-modal.php');
        $GLOBALS[ 'quoteupSingleProductModal' ]      = $quoteupSingleProductModal;
        $GLOBALS[ 'quoteupMultiproductQuoteButton' ] = $quoteupMultiproductQuoteButton;
        unset($quoteupSingleProductModal);
        unset($quoteupMultiproductQuoteButton);
    }

    public function enableAddToCartForProduct($purchasable, $product)
    {

        $prod_id = $product->id;

        //Configuration set for Add to Cart should only work with Simple and Variable Products
        if ($product->product_type != 'variable' && $product->product_type != 'simple') {
            return $purchasable;
        }

        $current_status = get_post_meta($prod_id, '_enable_add_to_cart', true);

        $current_status = apply_filters('quoteup_display_add_to_cart', $current_status, $product);

        if ($current_status == 'yes') {
            return $purchasable;
        } else {
            if ($product->product_type == 'variable') {
                $this->setAddToCartDisabledVariableProducts($prod_id);
            }
            return false;
        }
        return $purchasable;
    }

    protected function setAddToCartDisabledVariableProducts($prod_id)
    {
        if (! in_array($prod_id, $this->add_to_cart_disabled_variable_products)) {
            $this->add_to_cart_disabled_variable_products[] = $prod_id;
        }
    }

    protected function setEnquiryDisabledVariableProducts($prod_id)
    {
        if (! in_array($prod_id, $this->enquiry_disabled_variable_products)) {
            $this->enquiry_disabled_variable_products[] = $prod_id;
        }
    }

    protected function setPriceDisabledVariableProducts($prod_id)
    {
        if (! in_array($prod_id, $this->price_disabled_variable_products)) {
            $this->price_disabled_variable_products[] = $prod_id;
        }
    }

    public function displayPrice($price, $product)
    {

        //If Product is neither simple nor variable, return original value
        if ($product->product_type != 'variable' && $product->product_type != 'simple') {
            return $price;
        }

        $prod_id                 = $product->id;
        $current_price_status    = get_post_meta($prod_id, '_enable_price', true);
        $final_price_status      = apply_filters('quoteup_display_price', $current_price_status, $prod_id);
        if ($final_price_status == 'yes') {
            return $price;
        } else {
            if (current_action() == 'woocommerce_variation_price_html' || current_action() == 'woocommerce_variation_sale_price_html' || current_action() =='woocommerce_get_price_html') {
                $this->setPriceDisabledVariableProducts($prod_id);
            }
            return false;
        }
    }

    /**
     * Decides whether Quote button should be displayed or not
     * @global object $post
     * @return boolean return true if button should be displayed. otherwise returns false.
     */
    protected function shouldQuoteButtonBeDisplayed()
    {

        $displayButton               = false;
        global $post, $product;
        $isProductObjectAvailable    = false;

        //Check if Global $product exists or not. If that exists, take information from $product
        //else read $post.

        if (isset($product->id)) {
            $product_id                  = $product->id;
            $isProductObjectAvailable    = true;
        } elseif (isset($post->ID)) {
            $product_id = $post->ID;
        } else {
            return false;
        }


        if (isset($product->product_type)) {
            $product_type = $product->product_type;
        } else {
            $product_object  = wc_get_product($product_id);
            $product_type    = $product_object->product_type;
        }

        //If product is neither simple nor variable, do not show Enquiry button
        if ($product_type != 'simple' && $product_type != 'variable') {
            return false;
        }

        $form_data = quoteupSettings();
        // show only when out of stock feature
        if (isset($form_data[ 'only_if_out_of_stock' ]) && $form_data[ 'only_if_out_of_stock' ] == 1) {
            if ($isProductObjectAvailable) {
                $isProductInStock = \quoteupIsProductInStock($product);
            } else {
                $isProductInStock = \quoteupIsProductInStock($product_id);
            }
            if ($isProductInStock) {
                if ($product_type == 'variable') {
                    $this->setEnquiryDisabledVariableProducts($product_id);
                }
                return false;
            }
        }

        $current_button_status = get_post_meta($product_id, '_enable_pep', true);
        if (empty($current_button_status)) {
            if ($product_type == 'variable') {
                $this->setEnquiryDisabledVariableProducts($product_id);
            }
            $displayButton = false;
        } elseif ($current_button_status == 'yes') {
            $displayButton = true;
        } else {
            $displayButton = true;
        }
        return $displayButton;
    }

    public function decidePositionOfQuoteButton()
    {
        global $post;

        /**
         * Check if Enquiry/Quote button should be shown or not
         */
        if (isset($post)) {

            if (! isset($post->post_type) || $post->post_type != 'product') {
                return;
            }

            $show_quoteup_button = apply_filters('quoteup_display_quote_button', $this->shouldQuoteButtonBeDisplayed(), $post->post_type, $post->ID);

            //Keeping old filter for Old PEP customers
            $show_quoteup_button = apply_filters('pep_before_deciding_position_of_enquiry_form', $show_quoteup_button, $post->post_type, $post->ID);

            if ($show_quoteup_button == false) {
                return;
            }
        }

        $default_vals = array(
            'pos_radio' => 'show_after_summary',
        );

        $form_init_data = get_option('wdm_form_data', $default_vals);

        if (isset($form_init_data[ 'pos_radio' ])) {
            if ($form_init_data[ 'pos_radio' ] == 'show_after_summary') {
                add_action('woocommerce_single_product_summary', array( $this, 'displayAddToQuoteButtonOnSingleProduct' ), 30);
            } elseif ($form_init_data[ 'pos_radio' ] == 'show_at_page_end') {
                add_action('woocommerce_after_single_product', array( $this, 'displayAddToQuoteButtonOnSingleProduct' ), 10);
            }
        } else {
            add_action('woocommerce_single_product_summary', array( $this, 'displayAddToQuoteButtonOnSingleProduct' ), 30);
        }
    }

    public function displayAddToQuoteButtonOnSingleProduct()
    {

        $this->instantiateViews();
        global $product, $quoteupMultiproductQuoteButton, $quoteupSingleProductModal;

        $btn_class = 'single_add_to_cart_button button alt wdm_enquiry';

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

        $this->enqueueScripts($form_data);

        $prod_id     = $product->id;
        // $url = get_permalink();
        // $img_url = wp_get_attachment_url(get_post_thumbnail_id());
        $prod_price  = $product->get_price_html();
        $prod_price  = strip_tags($prod_price);

        $price = $prod_price;

        if (isset($form_data[ 'enable_disable_mpe' ]) && $form_data[ 'enable_disable_mpe' ] == 1) {
            //No Modal for Multi Product
            $quoteupMultiproductQuoteButton->displayQuoteButton($prod_id, $btn_class, static::$instance);
        } else {
            $quoteupSingleProductModal->displayModal($prod_id, $price, $btn_class, static::$instance);
        }
    }

    public function enqueueScripts($form_data)
    {
        wp_enqueue_style('modal_css1', QUOTEUP_PLUGIN_URL . '/css/wdm-bootstrap.css', false, false);
        wp_enqueue_style('wdm-mini-cart-css2', QUOTEUP_PLUGIN_URL . '/css/common.css');
        wp_enqueue_style('wdm-quoteup-icon2', QUOTEUP_PLUGIN_URL . '/css/public/wdm-quoteup-icon.css');

        wp_enqueue_script('phone_validate', QUOTEUP_PLUGIN_URL . '/js/public/phone-format.js', array( 'jquery' ), false, true);

        // jQuery based MutationObserver library to monitor changes in attributes, nodes, subtrees etc
        wp_enqueue_script('quoteup-jquery-mutation-observer', QUOTEUP_PLUGIN_URL . '/js/admin/jquery-observer.js', array('jquery'));
        
        wp_enqueue_script('modal_validate', QUOTEUP_PLUGIN_URL . '/js/public/frontend.js', array( 'jquery', 'phone_validate' ), false, true);

        $redirect_url = $this->getRedirectUrl($form_data);

        if (isset($form_data[ 'phone_country' ])) {
            $country = $form_data[ 'phone_country' ];
        } else {
            $country = '';
        }
        //echo "qwqww <pre>";print_r($p);echo "</pre>";exit;
        $data = getLocalizationDataForJs($redirect_url, $country);

        wp_localize_script('modal_validate', 'wdm_data', $data);
    }

    public function getDialogTitleColor($form_data)
    {
        if (isset($form_data[ 'dialog_product_color' ])) {
            if ($form_data[ 'dialog_product_color' ] != '') {
                $pcolor = $form_data[ 'dialog_product_color' ];
            }
        } else {
            $pcolor = '#333';
        }

        return $pcolor;
    }

    public function getUserName()
    {
        $name = "";
        if (is_user_logged_in()) {
            global $current_user;
            wp_get_current_user();

            $name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
            if ($name == ' ') {
                $name = $current_user->user_login;
            }
        } else {
            if (isset($_COOKIE[ 'wdmusername' ])) {
                $name = $_COOKIE[ 'wdmusername' ];
            }
        }

        return $name;
    }

    public function getUserEmail()
    {
        $email = "";
        if (is_user_logged_in()) {
            global $current_user;
            wp_get_current_user();
            $email = $current_user->user_email;
        } else {
            if (isset($_COOKIE[ 'wdmuseremail' ])) {
                $email = $_COOKIE[ 'wdmuseremail' ];
            }
        }

        return $email;
    }

    public function getDialogColor($form_data)
    {
        if (isset($form_data[ 'dialog_color' ])) {
            if ($form_data[ 'dialog_color' ] != '') {
                $color = $form_data[ 'dialog_color' ];
            }
        } else {
            $color = '#fff';
        }

        return $color;
    }

    public function displayQuoteButtonOnArchive($addToCartLink)
    {

        $this->instantiateViews();
        global $product, $quoteupMultiproductQuoteButton, $quoteupSingleProductModal;

        $btn_class   = 'button wdm_enquiry';
        $pid         = $product->id;


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

        //checkbox value
        if (isset($form_data[ 'show_enquiry_on_shop' ])) {
            $shop_enq_btn = $form_data[ 'show_enquiry_on_shop' ];
        } else {
            $shop_enq_btn = '0';
        }

        $isPEPEnabledForProduct = get_post_meta($pid, '_enable_pep', true);
        if ($isPEPEnabledForProduct == 'yes') {
            $single_prod_quoteup_option = '';
        } else {
            $single_prod_quoteup_option = 'yes';
        }

        if ($shop_enq_btn === '0') {
            return $addToCartLink;
        }

        if (! $this->shouldQuoteButtonBeDisplayed()) {
            return $addToCartLink;
        }


        $this->enqueueScripts($form_data);

        $prod_id     = $product->id;
        $prod_price  = $product->get_price_html();
        $prod_price  = strip_tags($prod_price);
        $price       = $prod_price;

        ob_start();
        if ($product->product_type == 'variable') {
            //Display link to the product and not actual form
            $this->displayVariableProductLink($form_data, $btn_class);
        } else {
            if (isset($form_data[ 'enable_disable_mpe' ]) && $form_data[ 'enable_disable_mpe' ] == 1) {
                //No Modal for Multi Product
                $quoteupMultiproductQuoteButton->displayQuoteButton($prod_id, $btn_class, static::$instance);
            } else {
                $quoteupSingleProductModal->displayModal($prod_id, $price, $btn_class, static::$instance);
            }
        }

        $quoteButtonContent = ob_get_contents();
        ob_end_clean();
        return $quoteButtonContent . $addToCartLink;

        // return $addToCartLink;
    }

    public function instantiateViews()
    {
        global $quoteupMultiproductQuoteButton, $quoteupSingleProductModal;
        if ($quoteupMultiproductQuoteButton == null || $quoteupSingleProductModal == null) {
            $this->loadTemplates();
        }
    }

    public function getRedirectUrl($form_data)
    {
        if (! empty($form_data[ 'redirect_user' ]) && $form_data[ 'redirect_user' ] != '') {
            $redirect_url = $form_data[ 'redirect_user' ];
        } else {
            $redirect_url = 'n';
        }

        return $redirect_url;
    }

    public function returnButtonText($form_data)
    {
        return empty($form_data[ 'custom_label' ]) ? __('Make an Enquiry', 'quoteup') : $form_data[ 'custom_label' ];
    }

    /**
     * Displays a link to Variable Products Details page on shop page
     * @global object $product Global Product Object
     * @param array $form_data Settings set on the settings page
     * @param string $btn_class Class to be applied to a an Enquiry/Quote button
     */
    public function displayVariableProductLink($form_data, $btn_class)
    {
        global $product;
        $manual_css = 0;
        if (isset($form_data[ 'button_CSS' ]) && $form_data[ 'button_CSS' ] == 'manual_css') {
            $manual_css = 1;
        }
        echo '<div class="quote-form">';
        if (isset($form_data[ 'show_button_as_link' ]) && $form_data[ 'show_button_as_link' ] == 1) {
            ?>
			<a id="wdm-variable-product-trigger-<?php echo $product->id ?>" href='<?php echo esc_url($product->add_to_cart_url()) ?>' style='font-weight: bold;
			<?php
            if ($form_data[ 'button_text_color' ]) {
                echo 'color: ' . $form_data[ 'button_text_color' ] . ';';
            }
            ?>'>
                    <?php echo $this->returnButtonText($form_data); ?>
			</a>
			<?php
        } else {
            ?>
			<button class="<?php echo $btn_class ?>" id="wdm-variable-product-trigger-<?php echo $product->id ?>"  <?php echo ($manual_css == 1) ? getManualCSS($form_data) : ''; ?><?php echo 'onclick="location.href=\'' . esc_url($product->add_to_cart_url()) . '\'"'; ?>><?php echo $this->returnButtonText($form_data); ?></button>
			<?php
        }
        echo '</div>';
    }

    /**
     * Variations are already being hidden using CSS due to function hideAddToCartForVariableProduct
     *
     * This function enques a javascript file which removes variations and 'Add To Cart' button
     */
    public function hideVariationForVariableProducts()
    {
        //If there are no products for whom Add to cart and Quote request is disabled, then no need to load js file
        if (empty($this->add_to_cart_disabled_variable_products) && empty($this->enquiry_disabled_variable_products) && empty($this->price_disabled_variable_products)) {
            return;
        }
        /*
		 * Hide variation for variable products if 'Add to Cart' and Enquiry/Quote request is disabled for
		 * variable product
		 */
        wp_enqueue_script('hide-variation', QUOTEUP_PLUGIN_URL . '/js/public/hide-var.js', array( 'jquery' ));
        if (! empty($this->add_to_cart_disabled_variable_products)) {
            wp_localize_script('hide-variation', 'quoteup_add_to_cart_disabled_variable_products', $this->add_to_cart_disabled_variable_products);
        }

        /*
         * Hide price for variable products if 'Show Price' is disabled for variable product
         */
        if (! empty($this->price_disabled_variable_products)) {
            wp_localize_script('hide-variation', 'quoteup_price_disabled_variable_products', $this->add_to_cart_disabled_variable_products);
        }

        /**
         * Remove variations for such variable products for whom Add to Cart and Enquiry is disabled and Price
         * is hidden
         */
        if (! empty($this->add_to_cart_disabled_variable_products) && ! empty($this->enquiry_disabled_variable_products) && ! empty($this->price_disabled_variable_products)) {
            $common_product_ids = array_intersect($this->add_to_cart_disabled_variable_products, $this->enquiry_disabled_variable_products, $this->price_disabled_variable_products);
            wp_localize_script('hide-variation', 'quoteup_hide_variation_variable_products', $common_product_ids);
        }
    }

    /**
     * Returning woocommerce_is_purchasable as false on variable product does not hide 'Add to cart' button
     * Therefore, inline syling will be added to hide add to cart button if admin wants to disable
     * 'Add to Cart'.
     * If done by JS, it will display 'Add to Cart' button during page load till JS gets loaded and executed.
     *
     * @global object $product
     */
    public function hideAddToCartForVariableProduct()
    {
        global $product;
        echo '<!-- Adding inline style to Hide \'Add to Cart\' Button -->';
        if (in_array($product->id, $this->add_to_cart_disabled_variable_products)) {

            echo "<style type='text/css'>
					form.variations_form.cart[data-product_id='{$product->id}'] .single_add_to_cart_button,  form.variations_form.cart[data-product_id='{$product->id}'] .quantity {
						display : none;
					}
				  </style>";
        }

        echo '<!-- Adding inline style to Hide Variations if [Add To Cart and Enquiry is disabled] & [Price is Hidden] -->';
        if (in_array($product->id, $this->add_to_cart_disabled_variable_products) && in_array($product->id, $this->enquiry_disabled_variable_products) && in_array($product->id, $this->price_disabled_variable_products)) {

            echo "<style type='text/css'>
					form.variations_form.cart[data-product_id='{$product->id}']{
						display : none;
					}
				  </style>";
        }
    }
}

$this->displayQuoteButton = QuoteUpDisplayQuoteButton::getInstance();
