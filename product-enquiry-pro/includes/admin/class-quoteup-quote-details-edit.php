<?php

namespace Includes\Admin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * This class is used to add extra features in PEP
 * - shows quotation quantity on hover
 * - shows button for save and preview quotation and send quotation
 * - Handles everything about the quotation
 */
class QuoteupQuoteDetailsEdit
{

    protected static $instance = null;
    public $enquiry_details = null;

    /**
     * Function to create a singleton instance of class and return the same
     * @return [Object] [
     *                  description]
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * constructor is used to add actions and filter
     */
    private function __construct()
    {
        add_filter('enquiry_details_table_data', array($this, 'quotationTable'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'addScript'));
        add_action('wp_ajax_save_quotation', array($this, 'saveQuotation'));
        add_action('wp_ajax_action_pdf', array('Includes\Admin\QuoteupGeneratePdf', 'generatePdf'));
        add_action('wp_ajax_action_send', array('Includes\Admin\SendQuoteMail', 'sendMail'));
        add_action('wp_ajax_get_last_history_data', array($this, 'getLastUpdatedHistoryRow'));
    }

    /**
     * This Function is used to add scripts in file.
     */
    public function addScript($hook)
    {
        if ('dashboard_page_quoteup-details-edit' != $hook) {
            return;
        }
        global $wp_scripts;

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');

        // jQuery based MutationObserver library to monitor changes in attributes, nodes, subtrees etc
        wp_enqueue_script('quoteup-jquery-mutation-observer', QUOTEUP_PLUGIN_URL . '/js/admin/jquery-observer.js', array(
            'jquery' ));

        //This is custom js file
        wp_enqueue_script('quoteup-edit-quote', QUOTEUP_PLUGIN_URL . '/js/admin/edit-quote.js', array(
            'jquery', 'jquery-ui-datepicker' ));
                global $wp_locale;

                $aryArgs = array(
                    'closeText'         => __('Done', 'quoteup'),
                    'currentText'       => __('Today', 'quoteup'),
                    'monthNames'        => $this->stripArrayIndices($wp_locale->month),
                    'monthNamesShort'   => $this->stripArrayIndices($wp_locale->month_abbrev),
                    'monthStatus'       => __('Show a different month', 'quoteup'),
                    'dayNames'          => $this->stripArrayIndices($wp_locale->weekday),
                    'dayNamesShort'     => $this->stripArrayIndices($wp_locale->weekday_abbrev),
                    'dayNamesMin'       => $this->stripArrayIndices($wp_locale->weekday_initial),
                    // set the date format to match the WP general date settings
                    'dateFormat'        => $this->dateFormatTojQueryUIDatePickerFormat(get_option('date_format')),
                    // get the start of week from WP general setting
                    'firstDay'          => get_option('start_of_week'),
                    // is Right to left language? default is false
                    'isRTL'             => is_rtl(),
                );

                wp_localize_script('quoteup-edit-quote', 'dateData', $aryArgs);

        /*
         * Include WooCommerce's add-to-cart-variation.js which is used by WooCommerce on Frontend
         * (On variable product) to get appropriate variations from database and filter values
         * in variations dropdown
         * 
         * Mimic the way WooCommerce handles variation dropdown. Therefore enqueing the script
         * it requires and localizing scripts with object names which are used in add-to-cart-variation.js
         * 
         * This was figured out after studying woocommerce/includes/class-wc-frontend-scripts.php
         */
                $assets_path = str_replace(array('http:', 'https:'), '', WC()->plugin_url()).'/assets/';
                $frontend_script_path = $assets_path.'js/frontend/';
                wp_enqueue_script('wc-add-to-cart-variation', $frontend_script_path . 'add-to-cart-variation.js', array(
                'jquery', 'wp-util' ));

                $located = wc_locate_template('single-product/add-to-cart/variation.php');
                if (file_exists($located)) {
                // We also need the wp.template for this script :)
                    wc_get_template('single-product/add-to-cart/variation.php');
                }

                $wc_ajax_url = '';

        /**
         * For WooCommerce below 2.6, there is no method like get_endpoint in WC_AJAX class
         */
                if (method_exists('\WC_AJAX', 'get_endpoint')) {
                    $wc_ajax_url = \WC_AJAX::get_endpoint("%%endpoint%%");
                }

                wp_localize_script('wc-add-to-cart-variation', 'wc_cart_fragments_params', array(
                'ajax_url' => WC()->ajax_url(),
                'wc_ajax_url'    => $wc_ajax_url,
                'fragment_name'  => apply_filters('woocommerce_cart_fragment_name', 'wc_fragments')
                ));

                wp_localize_script('wc-add-to-cart-variation', 'wc_add_to_cart_variation_params', array(
                'i18n_no_matching_variations_text' => esc_attr__('Sorry, no products matched your selection. Please choose a different combination.', 'woocommerce'),
                'i18n_make_a_selection_text' => esc_attr__('Please select some product options before adding this product to your cart.', 'woocommerce'),
                'i18n_unavailable_text'              => esc_attr__('Sorry, this product is unavailable. Please choose a different combination.', 'woocommerce')
                ));

                wp_enqueue_script('quoteup-ajax', QUOTEUP_PLUGIN_URL . '/js/admin/ajax.js', array(
                'jquery', 'jquery-ui-core', 'jquery-effects-highlight' ));

        // get registered script object for jquery-ui
                $uiVersion = $wp_scripts->query('jquery-ui-core');
        // tell WordPress to load the Smoothness theme from Google CDN
                $protocol = is_ssl() ? 'https' : 'http';
                $url = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/{$uiVersion->ver}/themes/smoothness/jquery-ui.min.css";
                wp_enqueue_style('jquery-ui-smoothness', $url, false, null);
                wp_enqueue_style('jquery-ui-datepicker', QUOTEUP_PLUGIN_URL.'/css/admin/datepicker.css');

                $lastGeneratedPDFExists = false;
                if (isset($_GET[ 'id' ]) && intval($_GET[ 'id' ])) {
                    $upload_dir = wp_upload_dir();
                    if (file_exists($upload_dir[ 'basedir' ].'/QuoteUp_PDF/'.$_GET[ 'id' ].'.pdf')) {
                        $lastGeneratedPDFExists = true;
                    }
                }

                wp_enqueue_script('quoteup-functions', QUOTEUP_PLUGIN_URL.'/js/admin/functions.js');

                $dateTime = date_create_from_format('Y-m-d H:i:s', current_time('Y-m-d H:i:s'));

                wp_localize_script(
                    'quoteup-ajax',
                    'quote_data',
                    array(
                    'decimal_separator' => wc_get_price_decimal_separator(),
                    'thousand_separator' => wc_get_price_thousand_separator(),
                    'decimals' => wc_get_price_decimals(),
                    'price_format' => get_woocommerce_price_format(),
                    'currency_symbol' => get_woocommerce_currency_symbol(),
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'path' => WP_CONTENT_URL.'/uploads/QuoteUp_PDF/',
                    'save'                       => __(
                        'Saving Data',
                        'quoteup'
                    ),
                    'generatePDF'                => __(
                        'Generating PDF',
                        'quoteup'
                    ),
                    'errorPDF'                   => sprintf(
                        __(
                            'Please select the Approval/Rejection page %s here %s to create quote',
                            'quoteup'
                        ),
                        "<a href='admin.php?page=quoteup-for-woocommerce#wdm_quote'>",
                        "</a>"
                    ),
                    'generatedPDF'               => __(
                        'PDF Generated',
                        'quoteup'
                    ),
                    'sendmail'                   => __(
                        'Sending Mail',
                        'quoteup'
                    ),
                    'quantity_less_than_0'       => __(
                        'Total Quantity can not be less than or equal to 0',
                        'quoteup'
                    ),
                    'pdf_generation_aborted'     => __(
                        'PDF generation process aborted due to security issue.',
                        'quoteup'
                    ),
                    'data_update_aborted'        => __(
                        'Data update aborted due to security issue.',
                        'quoteup'
                    ),
                    'saved_successfully'         => __(
                        'Saved Successfully',
                        'quoteup'
                    ),
                    'data_updated'               => __(
                        'Customer Data updated.',
                        'quoteup'
                    ),
                    'quantity_invalid'           => __(
                        'Quantity can not be in decimal.',
                        'quoteup'
                    ),
                    'data_not_updated_name'      => __(
                        'Enter valid name. Customer Data not updated.',
                        'quoteup'
                    ),
                    'data_not_updated_email'     => __(
                        'Enter valid email address. Customer Data not updated.',
                        'quoteup'
                    ),
                    'invalid_variation'          => __(
                        'Please select a valid variation for',
                        'quoteup'
                    ),
                    'same_variation'             => __(
                        'Same variation of a product cannot be added twice.',
                        'quoteup'
                    ),
                    'lastGeneratedPDFExists' => $lastGeneratedPDFExists,
                    'todays_date'                => apply_filters(
                        'quoteup_human_readable_expiration_date',
                        date_format(
                            $dateTime,
                            'M d, Y'
                        ),
                        $dateTime
                    ),
                    'quote_expired'              => __(
                        'Quote can not be saved because it is already expired. Kindly, change the date to future date.',
                        'quoteup'
                    ),
                    'save_and_preview_quotation' => __(
                        'Create Quotation',
                        'quoteup'
                    ),
                    'preview_quotation'          => __(
                        'Preview Quotation',
                        'quoteup'
                    ),
                    'save_and_send_quotation'    => __(
                        'Save & Send Quotation',
                        'quoteup'
                    ),
                    'send_quotation'             => __(
                        'Send Quotation',
                        'quoteup'
                    ),
                    )
                );

                wp_enqueue_script('bootstrap-modal', QUOTEUP_PLUGIN_URL . '/js/admin/bootstrap-modal.js', array(
                'jquery' ), false, true);
                wp_enqueue_style('modal_css1', QUOTEUP_PLUGIN_URL.'/css/wdm-bootstrap.css', false, false);
                wp_enqueue_style('wdm-mini-cart-css2', QUOTEUP_PLUGIN_URL.'/css/common.css');
                wp_enqueue_script('postbox');
                wp_enqueue_style('wdm_data_css', QUOTEUP_PLUGIN_URL.'/css/admin/edit-quote.css');
    }

        /**
     * Format array for the datepicker
     *
     * WordPress stores the locale information in an array with a alphanumeric index, and
     * the datepicker wants a numerical index. This function replaces the index with a number
     */
    public function stripArrayIndices($ArrayToStrip)
    {
        foreach ($ArrayToStrip as $objArrayItem) {
            $NewArray[] =  $objArrayItem;
        }
     
        return( $NewArray );
    }

     /**
     * Convert a date format to a jQuery UI DatePicker format
     *
     * @param string $dateFormat a date format
     * @return string
     */
    function dateFormatTojQueryUIDatePickerFormat($dateFormat)
    {

        $chars = array(
            // Day
            'd' => 'dd', 'j' => 'd', 'l' => 'DD', 'D' => 'D',
            // Month
            'm' => 'mm', 'n' => 'm', 'F' => 'MM', 'M' => 'M',
            // Year
            'Y' => 'yy', 'y' => 'y',
        );

        return strtr((string)$dateFormat, $chars);
    }

    public function editQuoteDetails()
    {
        global $wpdb, $quoteup_admin_menu, $quoteupManageHistory;
        $form_data = quoteupSettings();
        $quoteModal = 1;
        if (isset($form_data[ 'enable_disable_quote' ]) && $form_data[ 'enable_disable_quote' ] == 1) {
            $quoteModal = 0;
        }
        $enquiry_tbl = $wpdb->prefix.'enquiry_detail_new';
        $enquiry_id = $_GET[ 'id' ];
        $quoteStatus = $quoteupManageHistory->getLastAddedHistory($enquiry_id);
        if ($quoteStatus != null && is_array($quoteStatus)) {
            $quoteStatus = $quoteStatus[ 'status' ];
        }
        $this->enquiry_details = $wpdb->get_row($wpdb->prepare("SELECT enquiry_id, name, email, message, phone_number, subject, enquiry_ip, product_details, enquiry_date, enquiry_hash, order_id, expiration_date, date_field FROM $enquiry_tbl WHERE enquiry_id = %d", $enquiry_id));
        if ($this->enquiry_details == null) {

            echo '<br /><br /><p><strong>'.__('No Enquiry Found.', 'quoteup').'</strong></p>';

            return;
        }
        ?>
		<div class="wrap">
		<?php screen_icon();
        ?>
			<h1>
		<?php
        $statusArray = array(
            'Requested' => __('Requested', 'quoteup'),
            'Saved' => __('Saved', 'quoteup'),
            'Sent' => __('Sent', 'quoteup'),
            'Approved' => __('Approved', 'quoteup'),
            'Rejected' => __('Rejected', 'quoteup'),
            'Order Placed' => __('Order Placed', 'quoteup'),
            'Expired' => __('Expired', 'quoteup'),
            );
        if ($quoteModal == 1) {
            echo esc_html_e('Quotation Details', 'quoteup').' #'.$this->enquiry_details->enquiry_id;
            ?> <span class="quote-status-span"><?php echo empty($quoteStatus) ? 'New' : $statusArray[$quoteStatus]; ?></span>
			<?php

        } else {
            echo esc_html_e('Enquiry Details', 'quoteup').' #'.$this->enquiry_details->enquiry_id;
        }
        ?>

			</h1>
			<form name="editQuoteDetailForm" method="post">
				<input type="hidden" name="action" value="editQuoteDetail" />
		<?php
        wp_nonce_field('editQuoteDetail-nonce');
        /* Used to save closed meta boxes and their order */
        wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);
        wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
        ?>
				<div id="poststuff">
					<div id="post-body" class="metabox-holder">

						<div id="post-body-content">
							<p>Admin Page for Editing Product Enquiry Detail.</p>
						</div>
						<div id="postbox-container-2" class="postbox-container">
		<?php
                            add_meta_box('editCustomerData', __('Customer Data', 'quoteup'), array(
                                $this,
                                'customerDataSection' ), $quoteup_admin_menu, 'normal');
                            add_meta_box('editProductDetailsData', __('Product Details', 'quoteup'), array(
                                $this, 'productDetailsSection' ), $quoteup_admin_menu, 'normal');

                            add_meta_box('editPEDetailMsg', __('Enquiry Messages', 'quoteup'), array(
                                $this,
                                'editPEDetailMsgFn' ), $quoteup_admin_menu, 'normal');
                            do_action('PEDetailEdit', $this->enquiry_details);
                            do_action('quoteup_edit_details', $this->enquiry_details);
                            do_meta_boxes($quoteup_admin_menu, 'normal', '');
        ?>
						</div>
					</div>
				</div>
			</form>
		</div>
				<?php
    }

    public function customerDataSection()
    {
        global $quoteup;
        $form_data = quoteupSettings();
        ?>
		<div id="update-text" class="settings-error notice is-dismissible">
        <img src="<?php echo admin_url('images/spinner.gif'); ?>" id="update-customerdata-load">
		</div>
		<!-- <div id="update-text" class="updated settings-error notice is-dismissible"></div> -->
		<div class='cust_section'>
			<!-- <h2 class='wdm-tbl-gen-header'><?php echo __('General details', 'quoteup');
            ?></h2> -->
        <input type='hidden' class='wdm-enq-id' value="<?php echo $_GET[ 'id' ] ?>">
			<input type='hidden' class='admin-url' value='<?php echo admin_url('admin-ajax.php'); ?>'>
			<article class='wdm-tbl-gen clearfix'>
				<section class='wdm-tbl-gen-sec clearfix wdm-tbl-gen-sec-1'>
            <!-- <div class='wdm-tbl-gen-heading'>
            <strong class='wdm-tblgen-strong'>
                    <?php
                    $from = apply_filters('pep_customer_name_column_header', __('From ', 'quoteup'));
                    echo apply_filters('quoteup_customer_name_column_header', $from);
            ?></strong>
					</div> -->
					<div class='wdm-tbl-gen-detail'>
                <!-- <strong class='wdm-tblgen-strong wdm-tblgen-strong-alt'>:</strong> -->
                <div class='wdm-user'>
                    <input id="input-name" type='text' value='<?php echo $this->enquiry_details->name; ?>' class='wdm-input input-field input-name' name='cust_name' required>
							<label placeholder="<?php _e('Client\'s Full Name', 'quoteup') ?>" alt="<?php _e('Full Name', 'quoteup') ?>"></label>
				<!--                     <span class='wdm-edit-user'></span> -->
						</div>
						<div class='wdm-user-email'>
							<input id="input-email" type='email' value='<?php echo $this->enquiry_details->email; ?>' class='wdm-input input-field input-email' name='cust_email' required>
							<label placeholder="<?php _e('Client\'s Email Address', 'quoteup') ?>" alt="<?php _e('Email', 'quoteup') ?>"></label>
						</div>
		
                <div class='wdm-user-ip'>
                    <input type='text' value='<?php echo $this->enquiry_details->enquiry_ip; ?>' class='wdm-input-ip wdm-input' disabled name='cust_ip' required>
                    <label placeholder="<?php _e('Client\'s IP Address', 'quoteup') ?>" alt="<?php _e('IP Address', 'quoteup') ?>"></label>
                </div>
                <div class='wdm-user-enquiry-date'>
                    <input type='text' value='<?php echo date('M d, Y', strtotime($this->enquiry_details->enquiry_date)); ?>' class='wdm-input-enquiry-date wdm-input' disabled name='enquiry_date'>
                    <label placeholder="<?php _e('Enquiry Date', 'quoteup') ?>" alt="<?php _e('Enquiry Date', 'quoteup') ?>"></label>
                </div>

		<?php
        $enable_ph = 0;
        if (isset($form_data[ 'enable_telephone_no_txtbox' ])) {
            $enable_ph = $form_data[ 'enable_telephone_no_txtbox' ];
        } else {
            $enable_ph = 0;
        }

        if ($enable_ph == 1) {
            do_action('quoteup_before_customer_telephone_column');
            do_action('pep_before_customer_telephone_column');
            $phNumber = $this->enquiry_details->phone_number;
            if (empty($phNumber)) {
                $phNumber = "-";
            }
            ?>

                        <div class='wdm-user-telephone'>
                            <input type='text' value='<?php echo $phNumber;
                ?>' class='wdm-input-telephone wdm-input' disabled name='cust_telephone' required>
                            <label placeholder="<?php _e('Telephone', 'quoteup') ?>" alt="<?php _e('Telephone', 'quoteup') ?>"></label>
                        </div>
            <?php
        }
            do_action('quoteup_after_customer_telephone_column');
            do_action('pep_after_customer_telephone_column');
            $enable_dt = 0;
        if (isset($form_data[ 'enable_date_field' ])) {
            $enable_dt = $form_data[ 'enable_date_field' ];
        } else {
            $enable_dt = 0;
        }

        if ($enable_dt == 1) {
            do_action('quoteup_before_customer_date_field');
            do_action('pep_before_customer_date_field');
            $dateField = "";
            $dateLabel = "Date";

            if (isset($form_data[ 'date_field_label' ])) {
                $dateLabel = $form_data[ 'date_field_label' ];
            }

            // $dateField = $this->enquiry_details->date_field;
            // $createDate = new \DateTime($dateField);
            // $dateField = $createDate->format('Y-m-d');
            if (!empty($this->enquiry_details->date_field) && $this->enquiry_details->date_field != '0000-00-00 00:00:00') {
                $dateField = date('M d, Y', strtotime($this->enquiry_details->date_field));
            }


            if (empty($dateField)) {
                $dateField = "-";
            }
            ?>

                        <div class='wdm-user-date-field'>
                            <input type='text' value='<?php echo $dateField;
                ?>' class='wdm-input-telephone wdm-input' disabled name='cust_date_field' required>
                            <label placeholder="<?php _e($dateLabel, 'quoteup') ?>" alt="<?php _e($dateLabel, 'quoteup') ?>"></label>
                        </div>


                            <?php
                            do_action('quoteup_after_customer_date_field');
                            do_action('pep_after_customer_date_field');
        }
            do_action('mep_custom_fields', $this->enquiry_details->enquiry_id);
            ?>
					</div>
				</section>
			</article>
            </div>
            <?php

    }

    public function editPEDetailMsgFn()
    {
        global $pep_admin_menu;
        ?>
		<div id="postbox-container-1" class=""> 
		<?php
        $this->editPEDetailEnquiryNotesFn();
        do_meta_boxes($pep_admin_menu, 'side', '');
        ?>
		</div>
						<?php

    }

    public function editPEDetailEnquiryNotesFn()
    {
        global $enquiry_details, $wpdb;
        $id              = $_GET[ 'id' ];
        $enquiry_tbl = $wpdb->prefix.'enquiry_detail_new';
        $sql = $wpdb->prepare("SELECT * FROM $enquiry_tbl WHERE enquiry_id = '%d'",$id);
        $enquiry_details = $wpdb->get_row($sql);
        $enq_tbl = $wpdb->prefix.'enquiry_thread';
        $url = admin_url('admin-ajax.php');
        $sql = $wpdb->prepare("SELECT * FROM $enq_tbl WHERE enquiry_id=%d",$id);
        $reply   = $wpdb->get_results($sql);
        echo "<input type='hidden' class='wdm-enquiry-usr' value='{$enquiry_details->email}'/>";
        echo "<input type='hidden' class='admin-url' value='{$url}'/>";
        echo "<div class='msg-wrapper'><div class='wdm-input-ip wdm-enquirymsg'><em>$enquiry_details->subject</em></div>";
        echo "<div class='wdm-input-ip enquiry-message'>$enquiry_details->message</div>";
        echo " <hr class='msg-border'/>";
        $prev    = 0;
        foreach ($reply as $msg) {
            $id      = $msg->id;
            $sub = $msg->subject;
            $message = $msg->message;
            echo "<div class='msg-wrapper'><div class='wdm-input-ip hide wdm-enquirymsg'><em>{$sub}</em></div>";
            echo "<div wdm-input-ip>{$message}</div>";
            echo " <hr class='msg-border'/>";
            echo "</div>";
        }
        echo "<a href='#' class='rply-link'><button class = 'button'>" . __('Reply', 'quoteup') . " &crarr; </button></a>";
        $this->replyThreadSection($id);
        echo '</div>';
    }

    public function replyThreadSection($thr_id)
    {
        global $enquiry_details;
        $sub = $enquiry_details->subject;
        if ($sub == '') {
            $sub = 'Reply for Enquiry';
        }
        ?>
		<div class='reply-div' data-thred-id = '<?php echo $thr_id ?>'>
			<input type='hidden' class='parent-id' value='<?php echo $thr_id ?>'>

			<div class="reply-field-wrap hide" >

		        <input type='text' placeholder='Subject' value='<?php echo $sub; ?>' name='wdm_reply_subject' class='wdm_reply_subject_<?php echo $thr_id ?> wdm-field reply-field'/>
			</div>

			<div class="reply-field-wrap">
		        <textarea class='wdm-field wdm_reply_msg_<?php echo $thr_id ?> reply-field' name='wdm_reply_msg' placeholder="<?php _e('Message', 'quoteup') ?>"></textarea>
			</div>
			<div class="reply-field-wrap reply-field-submitwrap">
		        <input type='submit' value='<?php echo __('Send', 'quoteup');
        ?>' name='btn_submit' class='button button-rply-user button-primary' data_thread_id='<?php echo $thr_id ?>'/>
		        <span class='load-ajax'></span>
			</div>
		</div>

		<div class='msg-sent'>

			<div>
		        <span class="wdm-pepicon wdm-pepicon-done"></span> <?php echo __('Reply sent successfully', 'quoteup'); ?>
			</div>
		</div>
		<!--       <hr class="msg-border"/>
			  </div> -->
		<?php

    }

    public function productDetailsSection()
    {
        ?>
		<div class="wdmpe-detailtbl-wrap">
		<?php echo $this->quotationTable($this->enquiry_details); ?>
		</div>
		<?php

    }

    public function getQuotationStatus($res)
    {
        $quotationDownload = "";
        if ($res == null) {
            $quotationDownload = "style='display:none'";
        }

        return $quotationDownload;
    }

    /**
     * This function is used to get attribule value stored in database.
     * @return [type] [description]
     */
    public function getAttributeValue($attributes, $productData, $product)
    {
        $attribute_value_saved_in_db = "";
        foreach ($attributes as $attribute_name => $options) {
            if ("Not Set" != $productData['variation']) {
                foreach ($productData[ 'variation' ] as $key => $value) {
                    if (strcasecmp($attribute_name, trim($key)) == 0) {
                        $attribute_value_saved_in_db = $value;
                        break 1;
                    }
                }
            }
            $_REQUEST[ 'attribute_'.sanitize_title($attribute_name) ] = !empty($attribute_value_saved_in_db) ? $attribute_value_saved_in_db : $product->get_variation_default_attribute($attribute_name);

            unset($options);
        }
    }

    /**
     * This function is used to get image url
     * @return [type] [description]
     */
    public function getImageURL($prod)
    {
        $img_url = "";
        if (isset($prod[ 'variation_id' ]) && $prod[ 'variation_id' ] != '') {
            $img_url = wp_get_attachment_url(get_post_thumbnail_id($prod[ 'variation_id' ]));
        }
        if (!$img_url || $img_url == "") {
            $img_url = wp_get_attachment_url(get_post_thumbnail_id($prod[ 'id' ]));
        }
        if (!$img_url || $img_url == "") {
            $img_url = WC()->plugin_url() . "/assets/images/placeholder.png";
        }
        return $img_url;
    }

    /**
     * This function is used to print vatiations from the given variation array
     * @param  [array] $variationArray [Variation array]
     * @return [type]                 [description]
     */
    public function printVariations($variationArray)
    {
        foreach ($variationArray as $attributeKey => $attributeValue) {
            echo "<b>" . wc_attribute_label(trim($attributeKey)) . " :</b>" . $attributeValue . "<br>";
            // $_product_variation = wc_get_product($prod[ 'variation_id' ]);
        }
    }

    /**
     * This function is used to display quotation table is quotation module is enabled.
     * @param [object] $enquiry_details [values fetched from database]
     * @return [object] new data for table
     */
    public function quoteTableDisplay($enquiry_details)
    {
        // global $quoteupManageExpiration;
        $deletedProducts = array();
        $variableProducts = array();
        $result                  = \Includes\QuoteupOrderQuoteMapping::getOrderIdOfQuote($_GET[ 'id' ]);
        $quotationDisabled       = "";
        $hideEnquiryVariation    = "";
        $quotationbTN            = "";
        $inputOff                = "";
        $addToQuoteBtn = 0;
        $soldIndividually = '';
        $enquiry = $_GET[ 'id' ];
        if ($result != null && $result != 0) {
            $hideEnquiryVariation = "quotation-disabled";
            $quotationDisabled = "disabled";
            $quotationbTN = "style='display:none'";
            $inputOff = "style='border: none; color: #505560;'";
        }
        global $wpdb;
        $table_name = $wpdb->prefix . "enquiry_quotation";
        $sql = $wpdb->prepare("SELECT newprice, quantity,show_price FROM $table_name WHERE enquiry_id=%d", $enquiry);
        $res = $wpdb->get_row($sql, ARRAY_A);
        $quotationDownload = $this->getQuotationStatus($res);
        ob_start();
        ?>
		<table class='wdm-tbl-prod wdmpe-detailtbl wdmpe-quotation-table' id="Quotation">
			<?php $this->getQuoteTableHead();?>
			<tbody class="wdmpe-detailtbl-content">
		<?php
        $products = unserialize($enquiry_details->product_details);
        $email = $enquiry_details->email;
        $count = 0;
        $total_price = 0;
        foreach ($products as $product) {
            foreach ($product as $prod) {
                $varProduct = "";
                $disableInputboxes = "";
                        $strike              = "";
                $productDisabled = "";
                $deletedClass = "";
                $id = $prod[ 'id' ];
                $img_url = $this->getImageURL($prod);
                $url = admin_url("/post.php?post={$id}&action=edit");
                $productData = $this->getQuotationInfoOfProduct($_GET[ 'id' ], $id, $prod, $count);

                // If it is variable product then check if variaion is available
                if (isset($productData['variationID']) && $productData['variationID'] != 0) {
                    $productAvailable = isProductAvailable($productData['variationID']);
                    $product = wc_get_product($productData['variationID']);
                } else {
                    //Check avaiblity for simple product
                    $productAvailable = isProductAvailable($id);
                    $product = wc_get_product($id);
                }
                ++$count;
                //If product is available get latest data from database
                if ($productAvailable) {
                    $sku = $product->get_sku();
                    $ProductTitle = "<a href='" . $url . "' target='_blank' id='product-title-".$count."'>" . get_the_title($id) . "</a>";
                } else {
                    //If product is not available show old data and disabled
                            $strike          = '';
                    $productDisabled = "disabled";
                    $sku = $prod[ 'sku' ];
                    $ProductTitle = $prod[ 'title' ];
                    $deletedClass = "deleted-product";
                    ob_start();
                }

                $soldIndividually = $this->getProductSoldIndividially($id);
                if ($productData === false) {
                    continue;
                }
                if (!$res) {
                    $productData[ 'checked' ] = 'checked';
                }
                $price = $productData[ 'old_price' ];
                if ($productData[ 'newprice' ] == '') {
                    $productData[ 'newprice' ] = 0;
                }
                if ($productData['checked'] == "") {
                    $disableInputboxes = "disabled";
                } else {
                    $addToQuoteBtn++;
                }
                    ?>
							<tr class="wdmpe-detailtbl-content-row <?php echo $deletedClass; ?>">

						<?php
                        if ($productAvailable) {
                            ?>
								<td class="wdmpe-detailtbl-content-item item-content-add-to-quote">
									<input id="add-to-quote-<?php echo $count ?>" data-row-num="<?php echo $count;
                            ?>" class="wdm-checkbox-quote" type="checkbox" name="add_to_quote" value= "1" <?php
                                    echo $productData[ 'checked' ];
                    echo " " . $quotationDisabled
                            ?>  />
									<label style="margin-right: 1%;" for="add-to-quote-<?php echo $count ?>"></label>

								</td>
							<?php

                        } else {
                            ?>
								<td class="wdmpe-detailtbl-content-item item-content-add-to-quote">-</td>
							<?php

                        }
                ?>
							<td class="wdmpe-detailtbl-content-item item-content-img">
								<img src= '<?php echo $img_url; ?>' class='wdm-prod-img'/>
							</td>
							<td class="wdmpe-detailtbl-content-item item-content-link">

						<?php echo $ProductTitle; ?>

							</td>
							<td class="wdmpe-detailtbl-content-item item-content-variations" data-row-num="<?php echo $count; ?>" id="variations-<?php echo $count; ?>" data-product-link="<?php echo get_permalink($id); ?>">

						<?php
                        //Defining a global variable here because woocommerce_variable_add_to_cart() needs a global variable $product
                        $GLOBALS['product'] = wc_get_product($id);
                        /**
                         * Enquiries which are stored till version 4.1.0 do not have
                         * variation data. Therefore, we will first check if variaion_id
                         * exists or not. And if it does not exist, that means it is an
                         * old enquiry
                         */
                        
                        if (! isset($prod[ 'variation_id' ])) {
                            $GLOBALS[ 'product' ] = wc_get_product($id);

                            /**
                                     * Below WC 2.6, we also need global $post variable because it is used in variable.php
                                     */
                            $wooVersion  = WC_VERSION;
                            $wooVersion  = floatval($wooVersion);
                            if ($wooVersion < 2.6) {
                                $GLOBALS[ 'post' ] = get_post($id);
                            }
                            //Checking type of Product
                            if ($GLOBALS[ 'product' ]->is_type('variable')) {
                        /**
                                 * Print Dropdowns for Variable Product
                                 */
                                if (function_exists('woocommerce_variable_add_to_cart')) {
                                    //Defining a global variable here because woocommerce_variable_add_to_cart() needs a global variable $product
                                    $GLOBALS[ 'product' ] = wc_get_product($id);
                                    $product = $GLOBALS[ 'product' ];
                                    // Get Available variations?
                                    $get_variations = sizeof($product->get_children()) <= apply_filters('woocommerce_ajax_variation_threshold', 30, $product);
                                    $available_variations = $get_variations ? $product->get_available_variations() : false;
                                    $attributes = $product->get_variation_attributes();

                            /**
                                     * woocommerce_variable_add_to_cart() includes woocommerce/templates/single-product/add-to-cart/variable.php. This file has a form tag and dropdowns are shown in a form tag. Since we are already inside a table, form tag can not be used here and therefore, we are creating a div tag which is very similar to form tag created in variable.php
                                     */
                                    ?>
                                    <div class="product" <?php echo $quotationbTN ?>>
                                        <div id="variation-<?php echo $count ?>" class="variations_form cart" data-product_id="<?php echo absint($product->id); ?>" data-product_variations="<?php echo htmlspecialchars(json_encode($available_variations)) ?>">
                                            <input type="hidden" name="variation_id" class="variation_id" id="variation-id-<?php echo $count ?>" value="">
                                            <div class="images">
                                                <img class="variation_image" src="" data-o_src="<?php echo has_post_thumbnail(absint($product->id)) ? wp_get_attachment_image_url(get_post_thumbnail_id($product->id)) : wc_placeholder_img_src(); ?>"/>
                                            </div>
                                            <div class="product_meta">
                                                <span class="sku_wrapper">SKU: <span class="sku" itemprop="sku"><?php echo ($sku = $product->get_sku()) ? $sku : __('N/A', 'quoteup'); ?></span></span>

                                            </div>
                                            <!-- Content of this class is filled by WC automatically when variation is changed -->
                                            <div class="single_variation">
                                            </div>   
                                <?php
                                $this->getAttributeValue($attributes, $productData, $product);
                                add_filter('woocommerce_locate_template', array($this, 'changeVariableTemplatePath'), 10, 2);
                                woocommerce_variable_add_to_cart();
                                remove_filter('woocommerce_locate_template', array($this, 'changeVariableTemplatePath'), 10);
                            ?>
                                        </div>
                                    </div>
                                    <div id="variation-unchecked-<?php echo $count ?>" class = "<?php echo $hideEnquiryVariation ?>">
                                    <?php
                                        echo "Variation Not selected";

                                    ?>
                                        <input type="hidden" name="variationSKU" class="variation_sku" id="variationUnchecked-<?php echo $count ?>" value="<?php ?>">
                                    </div>
                                    <?php
                                    //This Block is displayed when order is completed for that enquiry.
                                    if ($quotationbTN != "") {
                                        ?>
                                        <div>
                                        <?php
                                        $this->printVariations($productData[ 'variation' ]);
                                        ?>                                                    
                                       </div>
                                        <?php

                                    }
                                }
                            } else {
                                echo "-";
                            }
                        } /**
                                 * Starting with version 4.1.0, QuoteUp stores variation_id and variation
                                 * details for all products. For simple products variation_id is blank or
                                 * null. For variable products, proper data is available
                                 */ else if (isset($prod[ 'variation_id' ]) && $prod[ 'variation_id' ] !== "") {
                                    /**
                                     * Print Dropdowns for Variable Product
                                     */
                            if (function_exists('woocommerce_variable_add_to_cart')) {
                                if ($productAvailable) {
                                    //Defining a global variable here because woocommerce_variable_add_to_cart() needs a global variable $product
                                    $GLOBALS[ 'product' ]    = wc_get_product($id);
                                    /**
                                             * Below WC 2.6, we also need global $post variable because it is used in variable.php
                                             */
                                    $wooVersion              = WC_VERSION;
                                    $wooVersion              = floatval($wooVersion);
                                    if ($wooVersion < 2.6) {
                                        $GLOBALS[ 'post' ] = get_post($id);
                                    }
                                    $product                 = $GLOBALS[ 'product' ];
                                    // Get Available variations?
                                    $get_variations          = sizeof($product->get_children()) <= apply_filters('woocommerce_ajax_variation_threshold', 30, $product);
                                    $available_variations    = $get_variations ? $product->get_available_variations() : false;
                                    $attributes              = $product->get_variation_attributes();

                                    /**
                                             * woocommerce_variable_add_to_cart() includes woocommerce/templates/single-product/add-to-cart/variable.php. This file has a form tag and dropdowns are shown in a form tag. Since we are already inside a table, form tag can not be used here and therefore, we are creating a div tag which is very similar to form tag created in variable.php
                                             */
                                    ?>
                                    <div class="product" <?php echo $quotationbTN ?>>
                                        <div id="variation-<?php echo $count ?>" class="variations_form cart" data-product_id="<?php echo absint($product->id); ?>" data-product_variations="<?php echo htmlspecialchars(json_encode($available_variations)) ?>">
                                            <input type="hidden" name="variation_id" class="variation_id" id="variation-id-<?php echo $count ?>" value="">
                                            <div class="images">
                                                <img class="variation_image" src="" data-o_src="<?php echo has_post_thumbnail(absint($product->id)) ? wp_get_attachment_image_url(get_post_thumbnail_id($product->id)) : wc_placeholder_img_src(); ?>"/>
                                            </div>
                                            <div class="product_meta">
                                                <span class="sku_wrapper">SKU: <span class="sku" itemprop="sku"><?php echo ( $sku                        = $product->get_sku() ) ? $sku : __('N/A', 'quoteup'); ?></span></span>

                                            </div>   
                                            <?php
                                            $attribute_value_saved_in_db = "";
                                            foreach ($attributes as $attribute_name => $options) {
                                                foreach ($productData[ 'variation' ] as $key => $value) {
                                                    if (strcasecmp($attribute_name, trim($key)) == 0) {
                                                        $attribute_value_saved_in_db = $value;
                                                        break 1;
                                                    }
                                                }
                                                if (! empty($attribute_value_saved_in_db)) {
                                                    $_REQUEST[ 'attribute_' . sanitize_title($attribute_name) ] = $attribute_value_saved_in_db;
                                                } else {
                            /**
                                                             * Above WC 2.6, we have a method get_variation_default_attribute which directly returns the default attributes
                                                             */
                                                    if (method_exists($product, 'get_variation_default_attribute')) {
                                                        $_REQUEST[ 'attribute_' . sanitize_title($attribute_name) ] = $product->get_variation_default_attribute($attribute_name);
                                                    } else {
                                                        $defaults                                                        = $product->get_variation_default_attributes();
                                                        $attribute_name                                                  = sanitize_title($attribute_name);
                                                        $_REQUEST[ 'attribute_' . sanitize_title($attribute_name) ]    = isset($defaults[ $attribute_name ]) ? $defaults[ $attribute_name ] : '';
                                                    }
                                                }
                                                //$_REQUEST[ 'attribute_' . sanitize_title( $attribute_name ) ] = ! empty( $attribute_value_saved_in_db ) ? $attribute_value_saved_in_db : $product->get_variation_default_attribute( $attribute_name );
                                            }
                                            woocommerce_variable_add_to_cart();
                                            ?>
                                        </div>
											</div>
											<?php
                                }
                                ?>
                                <div id="variation-unchecked-<?php echo $count ?>" class = "<?php echo $hideEnquiryVariation ?>">
                                    <?php
                                    foreach ($prod[ 'variation' ] as $attributeKey => $attributeValue) {
                                        echo "<b>" . wc_attribute_label(trim($attributeKey)) . " :</b>" . $attributeValue . "<br>";
                                        $_product_variation = wc_get_product($prod[ 'variation_id' ]);
                                    }
                                    ?>
                                    <input type="hidden" name="variationSKU" class="variation_sku" id="variationUnchecked-<?php echo $count ?>" value="<?php ?>">
										</div>
										<?php
                                        if ($quotationbTN != "") {
                                            ?>
											<div>
												<?php
                                                foreach ($productData[ 'variation' ] as $attributeKey => $attributeValue) {
                                                    echo "<b>" . wc_attribute_label(trim($attributeKey)) . " :</b>" . $attributeValue . "<br>";
                                                    $_product_variation = wc_get_product($prod[ 'variation_id' ]);
                                                }
                                                ?>                                                    
											</div>
											<?php
                                        }
                            }
} /**
                                 * For Simple products, variation data is not available and hence print
                                 * blank string
                                 */ else {
                                    echo "-";
}
                ?>

							</td>
							<td class="wdmpe-detailtbl-content-item item-content-sku">
								<?php echo empty($sku) ? '-' : $sku; ?>
							</td>
							<td class="wdmpe-detailtbl-content-item item-content-remark">
				<?php echo empty($prod['remark']) ? '-' : $prod['remark']; ?>
							</td>
							<td class="wdmpe-detailtbl-content-item item-content-old-cost" data-old_price="<?php echo $this->oldPriceData($products, $count - 1) ?>">
				<?php echo wc_price($price); ?>
								<input type="hidden" id="old-price-<?php echo $count ?>" value="<?php echo $price; ?>" <?php echo $productDisabled; ?> >
							</td>
							<td class="wdmpe-detailtbl-content-item item-content-newcost">
								<input id="content-new-<?php echo $count ?>" data-row-num="<?php echo $count; ?>" class="newprice <?php echo $varProduct; ?>" type="number" name="newprice" value="<?php echo $productData['newprice']; ?>" min="0" <?php
                                echo $quotationDisabled . " " . $inputOff;
                                echo " " . $productDisabled . " " . $disableInputboxes;
                ?> step="any" >
							</td>
							<td class="wdmpe-detailtbl-content-item item-content-qty" >
								<input data-row-num="<?php echo $count; ?>" id="content-qty-<?php echo $count ?>" class="newqty <?php echo $soldIndividually == 'disabled' ? 'sold-individual-quantity' : '' ?> <?php echo " " . $varProduct; ?>" type="number" name="newqty" value="<?php echo $productData['quantity']; ?>" min="0" <?php echo $quotationDisabled . " " . $soldIndividually . " " . $productDisabled . " " . $disableInputboxes . " " . $inputOff; ?> >
							</td>
							<td class="wdmpe-detailtbl-content-item item-content-cost" id="content-cost-<?php echo $count ?>">
										<?php
                                        if ($disableInputboxes == "") {
                                            echo wc_price($productData[ 'newprice' ] * $productData[ 'quantity' ]);
                                        } else {
                                            echo "-";
                                        }
                                        if ($productDisabled == "" && $disableInputboxes == "") {
                                            $total_price = $total_price + ($productData[ 'newprice' ] * $productData[ 'quantity' ]);
                                        }
                ?>
							</td>
					<input <?php echo $strike ?> data-row-num="<?php echo $count; ?>" id="content-amount-<?php echo $count ?>" class="amount_database" type="hidden" name="price" value="<?php
                    if ($productDisabled == "" && $disableInputboxes == "") {
                        echo $productData[ 'newprice' ] * $productData[ 'quantity' ];
                    } else {
                        echo 0;
                    }
                ?>">
					<input data-row-num="<?php echo $count; ?>" id="content-ID-<?php echo $count ?>" class="id_database" type="hidden" name="id" value="<?php echo $id; ?>">
					</tr>
								<?php
                                if (!$productAvailable) {
                                    $deletedRow = ob_get_contents();
                                    ob_end_clean();
                                    array_push($deletedProducts, $deletedRow);
                                }
            }
        }
        $deletedProducts = implode("\n", $deletedProducts);
        $variableProducts = implode("\n", $variableProducts);
        echo $variableProducts;
        echo $deletedProducts;
        ?>
			<tr class="total_amount_row">
				<td class="total_span" colspan="8">
				</td>
				<td  class='wdmpe-detailtbl-head-item amount-total-label' id="amount_total_label"> <?php _e('Total', 'quoteup');
        ?>  </td>
				<td class="wdmpe-detailtbl-content-item item-content-cost" id="amount_total"> <?php echo wc_price($total_price);
        ?></td>
			</tr>
		</tbody>
		</table>
		<?php
        $this->decideButtonOrOrderID($quotationbTN, $res, $quotationDownload, $result, $email, $addToQuoteBtn);
        ?>
		</div>
						<?php
                        $currentdata = ob_get_contents();
                        ob_end_clean();

                        return $currentdata;
    }

    public function changeVariableTemplatePath($template, $template_name)
    {
        if ('single-product/add-to-cart/variable.php' == $template_name) {
            $default_path = WC()->plugin_path() . '/templates/';
            return $default_path . $template_name;
        }
        return $template;
    }

    public function oldPriceData($enquiryData, $rowNumber)
    {
        return htmlspecialchars(json_encode(array(
            'price' => $enquiryData[$rowNumber][0]['price'],
            'variation' => isset($enquiryData[$rowNumber][0]['variation']) ? $enquiryData[$rowNumber][0]['variation'] : "",
        )));
    }

    /**
     * This function is used to send sku value.
     * If sku is blank then '-' is sent
     * @param  [string] $sku [sku value]
     * @return [string]      [updated sku value]
     */
    public function getSkuValue($sku)
    {
        return empty($sku) ? '-' : $sku;
    }

    public function getRemark($remark)
    {
        return empty($remark) ? '-' : $remark;
    }

    /**
     * This function is used to display Enquiry table is quotation module is disabled.
     * @param [object] $enquiry_details [values fetched from database]
     * @return [object] new data for table
     */
    public function enquiryTableDisplay($enquiry_details)
    {
        $deletedProducts = array();
        $img = QUOTEUP_PLUGIN_URL . "/images/table_header.png";
        ?>
			<!-- <div class="wdmpe-detailtbl-wrap"> -->
			<table class='wdm-tbl-prod wdmpe-detailtbl wdmpe-enquiry-table'>
		        <thead class="wdmpe-detailtbl-head">
					<tr class="wdmpe-detailtbl-head-row">
						<!-- <th class="wdmpe-detailtbl-head-item item-head-count">#</th> -->
						<th class="wdmpe-detailtbl-head-item item-head-img">
							<img src= '<?php echo $img; ?>' class='wdm-prod-img wdm-prod-head-img'/>
						</th>
						<th class="wdmpe-detailtbl-head-item item-head-detail"><?php echo __('Item', 'quoteup'); ?> </th>
						<th class="wdmpe-detailtbl-head-item item-head-sku"><?php echo __('SKU', 'quoteup');
        ?></th>
						<th class="wdmpe-detailtbl-head-item item-head-remark"><?php echo __('Remark', 'quoteup');
        ?></th>
						<th class="wdmpe-detailtbl-head-item item-head-cost"><?php echo __('Price', 'quoteup');
        ?></th>
						<th class="wdmpe-detailtbl-head-item item-head-qty"><?php echo __('Quantity', 'quoteup');
        ?></th>
						<th class="wdmpe-detailtbl-head-item item-head-cost"><?php echo __('Total price', 'quoteup');
        ?></th>

		            </tr>
				</thead>
				<tbody class="wdmpe-detailtbl-content">
			<?php
            $products = unserialize($enquiry_details->product_details);
            $count = 0;
            $total_price = 0;
            foreach ($products as $product) {
                foreach ($product as $prod) {
                    $id = $prod[ 'id' ];
                    $img_url = $this->getImageURL($prod);
                    $url = admin_url("/post.php?post={$id}&action=edit");
                    $deletedClass = "";

                    // Check avaiblity of variable product
                    if (isset($prod['variation_id']) && $prod['variation_id'] != "") {
                        $productAvailable = isProductAvailable($prod[ 'variation_id' ]);
                        $productData = wc_get_product($prod[ 'variation_id' ]);
                    } else {
                        //Avaiblity of simple product
                        $productAvailable = isProductAvailable($id);
                        $productData = wc_get_product($id);

                    }
                        // Get latest data from database for available product
                    if ($productAvailable) {
                        $sku = $productData->get_sku();
                        $ProductTitle = "<a href=" . $url . " target='_blank'>" . get_the_title($id) . "</a>";
                    } else {
                        // display old data for product not available
                        $sku = $prod[ 'sku' ];
                        $ProductTitle = $prod[ 'title' ];
                        $deletedClass = "deleted-product";
                        ob_start();
                    }

                    $sku = $this->getSkuValue($sku);
                    $remark = $this->getRemark($prod['remark']);

                    $price = $this->getSalePrice($prod[ 'price' ]);
                        ?>
								<tr class="wdmpe-detailtbl-content-row <?php echo $deletedClass; ?>">
                        <?php
                        ++$count;
                    ?>

								<td class="wdmpe-detailtbl-content-item item-content-img">
									<img src= '<?php echo $img_url; ?>' class='wdm-prod-img'/>
								</td>
								<td class="wdmpe-detailtbl-content-item item-content-link">
									<?php
                                    echo $ProductTitle;
                                    if (isset($prod[ 'variation_id' ]) && $prod[ 'variation_id' ] != '') {
                                        echo "<br>";
                                        $this->printVariations($prod[ 'variation' ]);
                                    }
                    ?>
								</td>
								<td class="wdmpe-detailtbl-content-item item-content-sku">
							<?php echo $sku; ?>
								</td>
								<td class="wdmpe-detailtbl-content-item item-content-remark">
							<?php echo $remark ?>
								</td>
								<td class="wdmpe-detailtbl-content-item item-content-cost">
							<?php echo wc_price($price); ?>
								</td>
								<td class="wdmpe-detailtbl-content-item item-content-qty">
							<?php echo $prod['quant']; ?>
								</td>
								<td class="wdmpe-detailtbl-content-item item-content-cost">
							<?php
                            echo wc_price($price * $prod[ 'quant' ]);
                            if ($productAvailable) {
                                $total_price = $total_price + ($price * $prod[ 'quant' ]);
                            }
                    ?>
								</td>

							</tr>
							<?php
                            if (!$productAvailable) {
                                $deletedRow = ob_get_contents();
                                ob_end_clean();
                                array_push($deletedProducts, $deletedRow);
                            }
                }
            }
            $deletedProducts = implode("\n", $deletedProducts);
            echo $deletedProducts;
                    // }
                    ?>
		            <tr>
						<td colspan="5">
						</td>
						<td class='wdmpe-detailtbl-head-item'>Total </td>
						<td class="wdmpe-detailtbl-content-item item-content-cost" id="amount_total"> 
						<?php echo wc_price($total_price); ?></td>
					</tr>
		        </tbody>
			</table>
			<input type="hidden" id="enquiry_id" value="<?php echo $_GET['id']; ?>">
			<input type="hidden" id="nonce" value="<?php echo wp_create_nonce('quoteup'); ?>">
		</div>
		<?php

    }

    /**
     * This function is used to send the edited data to parent plugin using filter
     * @param [object] $currentdata     [old data of table]
     * @param [object] $enquiry_details [values fetched from database]
     * @return [object] new data for table
     */
    public function quotationTable($enquiry_details)
    {
        $form_data = quoteupSettings();
        $showQuoteTable = 1;
        if (isset($form_data[ 'enable_disable_quote' ]) && $form_data[ 'enable_disable_quote' ] == 1) {
            $showQuoteTable = 0;
        }
        if ($showQuoteTable == 1) {
            return $this->quoteTableDisplay($enquiry_details);
        } else {
            return $this->enquiryTableDisplay($enquiry_details);
        }
    }

    /**
     * This function is used to get button text
     * @param  [string] $filepath            [Path of pdf file]
     * @param  [string] $preview_button_text [text for preview button]
     * @param  [string] $send_button_text    [text for send button]
     * @return [type]                      [description]
     */
    public function getButtonText($filepath, &$preview_button_text, &$send_button_text, $pdfStatus)
    {
        if (file_exists($filepath)) {
            $preview_button_text = __('Preview Quotation', 'quoteup');
            $send_button_text = __('Send Quotation', 'quoteup');
        } elseif ($pdfStatus == 1) {
            $preview_button_text = __('Regenerate PDF', 'quoteup');
            $send_button_text = __('Save & Send Quotation', 'quoteup');
        } else {
            $preview_button_text = __('Create Quotation', 'quoteup');
            $send_button_text = __('Save & Send Quotation', 'quoteup');
        }
    }

    public function getQuotationRelatedButtons($filepath, $sendQuotationStatus, $send_button_text, $addToQuoteBtnStatus, $path, $quotationDownload)
    {
        $displayNone = "";
        if (!file_exists($filepath)) {
            $displayNone = "style='display:none'";
        }
        ?>
            <input <?php echo $displayNone ?> id="send" type="button" <?php echo $sendQuotationStatus ?> class="button" value="<?php echo $send_button_text ?>" <?php echo $addToQuoteBtnStatus; ?> >
            <a href="<?php echo $path; ?>" <?php echo $quotationDownload ?> id="DownloadPDF" download <?php echo $displayNone ?> ><input id="downloadPDF" type="button" class="button" value="<?php _e('Download PDF', 'quoteup');?>" ></a>
        <?php
    }

    /**
     * This function is used to decide wheather to show buttons or to show order id and disable quotation edit
     * @param [string] $quotationbTN      [used as a flag]
     * @param [array]  $res               [details stored in enquiry quotation table]
     * @param [string] $quotationDownload [used as a flag]
     * @param [int]    $result            [link status of quotation]
     * @param [string] $email             [customers email id]
     * @return [type] [description]
     */
    public function decideButtonOrOrderID($quotationbTN, $res, $quotationDownload, $result, $email, $addToQuoteBtn)
    {
        global $quoteup;
        global $wpdb;
        $table_name = $wpdb->prefix . "enquiry_detail_new";
        $pdfStatus = $wpdb->get_var("SELECT pdf_deleted FROM {$table_name} WHERE enquiry_id = {$_GET[ 'id' ]}");
        $addToQuoteBtnStatus = "";
        if ($addToQuoteBtn <= 0) {
            $addToQuoteBtnStatus = 'disabled';
        }
        if ($quotationbTN == "") {
            $checked = '';
            if ($res[ 'show_price' ] == 'yes' || $res[ 'show_price' ] == '' || $res[ 'show_price' ] == null) {
                $checked = 'checked';
            }
            ?>
			<div style="margin-left: 1%;" align="left">
				<input id="show_price" class="wdm-checkbox" type="checkbox" name="show_price" value= "1" <?php echo $checked; ?>  />
				<label for="show_price"><?php _e('Show Old Price in Quotation', 'quoteup') ?></label> <br />
				<!-- <p class="save-quote-note"><em> Note: Saving a Quote after making modifications makes earlier created quote unusable. </em>
				</p> -->
								<?php
                                $sendQuotationStatus = '';
                                if (!empty($this->enquiry_details->expiration_date) && $this->enquiry_details->expiration_date != '0000-00-00 00:00:00') {
                                    $currentTime = strtotime(current_time('Y-m-d'));
                                    $expirationTime = strtotime($this->enquiry_details->expiration_date);
                                    if ($currentTime > $expirationTime) {
                                        $sendQuotationStatus = 'disabled';
                                        ?>
                                            <p class="save-quote-note send-quotation-button-disabled-note"><em><strong>Please set new expiration date.</strong></em>
                                            </p>
                                        <?php

                                    }
                                }
            ?>

			</div>
			<div align="left" class="quotation-related-buttons">
			<?php
            // global $get_data_from_db;
            // if ($get_data_from_db=="available") {
            // $this->addMessageQuote();
            $this->pdfPreviewModal();
            $this->addMessageQuoteModal();
            $upload_dir = wp_upload_dir();
            $path = $upload_dir[ 'baseurl' ].'/QuoteUp_PDF/'.$_GET[ 'id' ].'.pdf';
            $filepath = $upload_dir[ 'basedir' ].'/QuoteUp_PDF/'.$_GET[ 'id' ].'.pdf';
            $preview_button_text = "";
            $send_button_text = "";

            $this->getButtonText($filepath, $preview_button_text, $send_button_text, $pdfStatus);
            ?>
				<input type="button" id="btnPQuote" class="button" value="<?php echo $preview_button_text ?>" <?php echo $addToQuoteBtnStatus; ?> >
                <?php
                    $this->getQuotationRelatedButtons($filepath, $sendQuotationStatus, $send_button_text, $addToQuoteBtnStatus, $path, $quotationDownload);
                ?>
				<input type="hidden" id="enquiry_id" value="<?php echo $_GET['id']; ?>">
			    <input type="hidden" id="email" value="<?php echo $email; ?>">
			    <input type="hidden" id="nonce" value="<?php echo wp_create_nonce('quoteup'); ?>">
				<div class="wdm-status-box">
					<div id="text"></div>
					<img src="<?php echo admin_url('images/spinner.gif'); ?>" id="PdfLoad">
				</div>
			</div>
            <div class="quote-expiration-date">
                <?php
                if (!isset($form_data[ 'enable_disable_quote' ]) || $form_data[ 'enable_disable_quote' ] != 1) {
                ?>
                    <div class='wdm-user-expiration-date'>
                        <input type='hidden' name='expiration_date' class="expiration_date_hidden" value='<?php echo $this->enquiry_details->expiration_date; ?>'>
                        <?php
                            $expirationDisabled = '';
                            $result = \Includes\QuoteupOrderQuoteMapping::getOrderIdOfQuote($_GET[ 'id' ]);
                        if ($result != null && $result != 0) {
                            $expirationDisabled = 'disabled';
                        }
                            ?>
                            <input type='text' value='<?php echo $quoteup->manageExpiration->getHumanReadableDate($this->enquiry_details->expiration_date); ?>' class='wdm-input-expiration-date wdm-input' <?php echo $expirationDisabled ?>  required>
                            <label class="expiration-date" placeholder="<?php _e('Expiration Date', 'quoteup') ?>" alt="<?php _e('Expiration Date', 'quoteup') ?>"></label>
                        </div>
            <?php

                }
                ?>
            </div>
				<?php
        } else {
            $link = '<center><h3><label>';
            $link .= __('Order associated with the Quote  : ', 'quoteup');
            $link .= '</label><label>';
            $link .= '<a href="'.admin_url('post.php?post='.absint($result).'&action=edit').'" >';
            $link .= $result;
            $link .= '</a></label></h3></center>';
            echo $link;
        }
    }

    /**
     * Check if this product has any data in wp_enquiry_quotation.
     *
     * If data is not present in wp_enquiry_quotation, search for wp_enquiry_detail_new
     * @param [int]   $enuiryID       [enuity ID]
     * @param [int]   $productID      [id of the product]
     * @param [array] $productEnquiry [product details]
     * @return [type] [description]
     */
    public function getQuotationInfoOfProduct($enuiryID, $productID, $productEnquiry, $rowNumber)
    {
        static $productsArray = array();

        // $_product = wc_get_product($productID);
        if (!empty($productsArray)) {
            $productsString = implode(',', $productsArray);
            $previousProducts = " AND ID NOT IN (" . $productsString . ")";
        } else {
            $previousProducts = "";
        }

        $price = $this->getSalePrice($productEnquiry[ 'price' ]);
        global $wpdb;
        $table_name = $wpdb->prefix . "enquiry_quotation";
        $sql = $wpdb->prepare("SELECT ID, newprice, quantity, variation_id, variation, variation_index_in_enquiry FROM $table_name WHERE enquiry_id=%d AND product_id=%d $previousProducts", $enuiryID, $productID);
        $result = $wpdb->get_results($sql, ARRAY_A);

        if (!empty($result)) {
            foreach ($result as $singleQuotationRow) {
                //echo '<pre>' . print_r($rowNumber, true) . '</pre>';
                //echo '<pre>' . print_r($singleQuotationRow, true) . '</pre>';
                //this is a variable product
                if ($singleQuotationRow['variation_id'] != 0 && $singleQuotationRow['variation_id'] != null) {
                    // If the index of variation in enquiry matches with the 'variation_index_in_enquiry', then row being printed was selected for quote generation
                    if ($rowNumber == $singleQuotationRow['variation_index_in_enquiry']) {
                        array_push($productsArray, $singleQuotationRow[ 'ID' ]);
                        return array(
                        'old_price' => $price,
                        'newprice' => $singleQuotationRow[ 'newprice' ],
                        'quantity' => $singleQuotationRow[ 'quantity' ],
                        'total_amount' => $singleQuotationRow[ 'newprice' ] * $singleQuotationRow[ 'quantity' ],
                        'variationID' => $singleQuotationRow[ 'variation_id' ],
                        'variation' => unserialize($singleQuotationRow[ 'variation' ]),
                            'checked'        => 'checked'
                        );
                    }

                } else {
                    //This is a single product for which quotation is generated
                    array_push($productsArray, $singleQuotationRow[ 'ID' ]);

                    return array(
                    'old_price' => $price,
                    'newprice' => $singleQuotationRow[ 'newprice' ],
                    'quantity' => $singleQuotationRow[ 'quantity' ],
                    'total_amount' => $singleQuotationRow[ 'newprice' ] * $singleQuotationRow[ 'quantity' ],
                    'variationID' => $singleQuotationRow[ 'variation_id' ],
                    'variation' => unserialize($singleQuotationRow[ 'variation' ]),
                        'checked'        => 'checked'
                    );
                }

            }
            //This is a variable product for which one variation has Quote generated and other variation does not have Quote generated. When we find out a variation for which quote is generated, we return it as an enquiry
            return array(
                'old_price' => $price,
                'newprice' => $price,
                'quantity' => $productEnquiry[ 'quant' ],
                'total_amount' => $price * $productEnquiry[ 'quant' ],
                'variationID' => isset($productEnquiry['variation_id']) ? $productEnquiry['variation_id'] : "0",
                'variation' => isset($productEnquiry['variation']) ? $productEnquiry['variation'] : "Not Set",
                'checked'        => ''
            );

        } else {
            return array(
                'old_price' => $price,
                'newprice' => $price,
                'quantity' => $productEnquiry[ 'quant' ],
                'total_amount' => $price * $productEnquiry[ 'quant' ],
                'variationID' => isset($productEnquiry['variation_id']) ? $productEnquiry['variation_id'] : "0",
                'variation' => isset($productEnquiry['variation']) ? $productEnquiry['variation'] : "Not Set",
                'checked'        => ''
            );
        }
    }

    public function pdfPreviewModal()
    {
        ?>
		<div class="wdm-modal wdm-fade wdm-pdf-preview-modal" id="wdm-pdf-preview" tabindex="-1" role="dialog" style="display: none;">
        <div class="wdm-modal-dialog wdm-pdf-modal-dialog">
            <div class="wdm-modal-content wdm-pdf-modal-content" style="background-color:#ffffff">
                <div class="wdm-modal-header">
                    <button type="button" class="close" data-dismiss="wdm-modal" aria-hidden="true">&times;</button>
                    <h4 class="wdm-modal-title" style="color: #333;">
                        <span><?php _e('Quote PDF Preview', 'quoteup');?></span>
                    </h4>
                </div>
                <div class="wdm-modal-body wdm-pdf-modal-body">
                    <div class="wdm-pdf-body" style="text-align: center;">
                        <iframe class="wdm-pdf-iframe" frameborder="0" vspace="0" hspace="0" webkitallowfullscreen="" mozallowfullscreen="" allowfullscreen="" scrolling="auto"></iframe>
                    </div>
                </div>
                <!--/modal body-->
            </div>
            <!--/modal-content-->
			</div>
			<!--/modal-dialog-->
		</div>

		<?php

    }

    public function addMessageQuoteModal()
    {
        $site_name = get_bloginfo();
        ?>
		<div class="wdm-modal wdm-fade wdm-quote-modal" id="MessageQuote" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display:none">
			<div class="wdm-modal-dialog">
		        <div class="wdm-modal-content" style="background-color:#ffffff">
					<div class="wdm-modal-header">
						<button type="button" class="close" data-dismiss="wdm-modal" aria-hidden="true">&times;</button>
						<h4 class="wdm-modal-title" id="myModalLabel" style="color: #333;">
							<span><?php
                            _e('Quotation Details #', 'quoteup');
                            echo $_GET[ 'id' ];
        ?></span>
		                </h4>
					</div>
					<div class="wdm-modal-body">
		                <form class="send-quotes-to-customers wdm-quoteup-form form-horizontal">
							<div class="wdm-quoteup-form-inner">
								<div class="form_input">
									<div class="form-wrap">
										<label for="subject">Subject:</label>
										<div class="form-wrap-inner">
											<input type="text" name="mailsubject" id="subject" size="50" value="<?php echo sprintf(__('Quote Request sent from %s', 'quoteup'), $site_name); ?>" required="" placeholder="Subject">
										</div>
									</div>
								</div>
								<div class="row"></div>
								<div class="form_input">
									<div class="form-wrap">
										<label for="message">Message:</label>
										<div class="form-wrap-inner">
											<textarea rows="4" cols="50" id="wdm_message" required=""><?php _e('This email has the quotation attached for your enquiry', 'quoteup') ?></textarea>
										</div>
									</div>
								</div>
								<div class="row wdm-note-row">
									<em> <?php _e('The quotation PDF will be attached to this email', 'quoteup');?> </em>
								</div>
								<div class="form_input">
									<div class="form-wrap">
										<button type="button" class="button button-primary" id="btnSendQuote"><?php _e('Send Quote', 'quoteup');?></button>
									</div>
								</div>
							</div>
						</form>
						<div class="row send-row">
							<div id="txt" style="visibility: hidden;"></div>
							<img src="<?php echo admin_url('images/spinner.gif'); ?>" id="Load">
						</div>
					</div>
					<!--/modal body-->
				</div>
				<!--/modal-content-->
			</div>
			<!--/modal-dialog-->
		</div>
		<?php

    }

    /**
     * This function is to show pop-up window which takes subject and message to be sent in email
     */
    /*
	public function addMessageQuote() {
        $site_name = get_bloginfo();
        ?>
		<div id="MessageQuote" style="display:none" >
			<center> <h3><?php
            _e('Quotation Details #', 'quoteup');
            echo $_GET[ 'id' ];
        ?> </h3></center>
			<fieldset>
		        <br>

		        <legend><?php _e('Message', 'quoteup');?></legend>
				<label for="subject"> <?php _e('Subject', 'quoteup');?> </label> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" name="mailsubject" id="subject" size="50" value="<?php echo sprintf(__('Quote Request sent from %s', 'quoteup'), $site_name); ?>" required >
				<br><br>
				<div class="popup-modal-textarea-note">
					<label for="message"> <?php _e('Message', 'quoteup');?>   </label> &nbsp;&nbsp;  <textarea rows="4" cols="50" id="message" required> <?php _e('This email has the quotation attached for your enquiry', 'quoteup')?> </textarea>
					<br><br>
					<label for="note"> <?php _e('Note', 'quoteup');?> :   </label>  <label> <?php _e('The quotation PDF will be attached to this email', 'quoteup');?> </label>
				</div>
			   <!-- <input type="text" name="Logo"><input type="file" name="company_logo"> -->
				<br>
				<br>
				<div align="center">
					<input type="submit" id="btnSendQuote" class="button" value=" <?php _e('Send Quotation', 'quoteup');?>"> <img src="<?php echo admin_url('images/spinner.gif'); ?>" id="Load"><br><br>

					<div id="txt"></div>
				</div>
			</fieldset>
		</div>
		<?php

    }
    */

    /**
     * Function to check input currency and return only sale price.
     * @param [string] $original_price Original string containing price.
     * @return [int] Sale price
     */
    public function getSalePrice($original_price)
    {
        // Trim spaces
        $original_price = trim($original_price);
        // Extract Sale Price
        $price           = $this->extractSalePrice($original_price);
        $sanitized_price = filter_var($price, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        if (false == $sanitized_price) {
            return $original_price;
        }

        return $sanitized_price;
    }

    public function extractSalePrice($price)
    {
        //Check if more than 1 value is present
        $prices = explode(' ', $price);
        if (count($prices) > 1) {
            return $prices[ 1 ];   // If yes return sale price.
        }return $prices[0]; //  Else return same string.
    }

    /**
     * Check if product is sold individual or not.
     * @param [int] $id [Product id]
     * @return [boolean] [true if product is set to sold individually]
     */
    public function getProductSoldIndividially($product_id)
    {
        $soldIndividually = "";
        $status = isSoldIndividually($product_id);

        if ($status == true) {
            $soldIndividually = "disabled";
        }

        return $soldIndividually;
    }

    /**
     * Function to show table head on enquiry details edit page.
     * @return [type] [description]
     */
    public function getQuoteTableHead()
    {
        $img_url = QUOTEUP_PLUGIN_URL . "/images/table_header.png";
        ?>
		<thead class="wdmpe-detailtbl-head">
			<tr class="wdmpe-detailtbl-head-row">
			<!-- <th class="wdmpe-detailtbl-head-item item-head-count">#</th> -->
				<th class="wdmpe-detailtbl-head-item item-head-add-to-quote"><?php echo __('Add to Quote', 'quoteup');
        ?></th>
				<th class="wdmpe-detailtbl-head-item item-head-img"><img src= '<?php echo $img_url; ?>' class='wdm-prod-img wdm-prod-head-img'/></th>
				<th class="wdmpe-detailtbl-head-item item-head-detail"><?php echo __('Item', 'quoteup'); ?> </th>
				<th class="wdmpe-detailtbl-head-item item-head-Variations"><?php echo __('Variations', 'quoteup'); ?> </th>
				<th class="wdmpe-detailtbl-head-item item-head-sku"><?php echo __('SKU', 'quoteup');
        ?></th>
				<th class="wdmpe-detailtbl-head-item item-head-remark"><?php echo __('Expected Price and Remarks', 'quoteup');
        ?></th>
				<th class="wdmpe-detailtbl-head-item item-head-old-cost"><?php echo __('Price', 'quoteup');
        ?></th>
				<th class="wdmpe-detailtbl-head-item item-head-newcost"><?php echo __('New Price', 'quoteup');
        ?></th>
				<th class="wdmpe-detailtbl-head-item item-head-qty"><?php echo __('Quantity', 'quoteup');
        ?></th>
				<th class="wdmpe-detailtbl-head-item item-head-cost"><?php
                echo sprintf(__('Amount( %s )', 'quoteup'), get_woocommerce_currency_symbol());
        ?></th>
			</tr>
		</thead>
		<?php

    }

    public function getLastUpdatedHistoryRow()
    {
        global $quoteupManageHistory, $wpdb, $quoteup;
        $status = '';
        $history = $quoteupManageHistory->getLastAddedHistory($_POST[ 'enquiry_id' ]);
        if ($history == null) {
            $status = 'NO_NEW_HISTORY';
            echo json_encode(array('status' => $status));
            die();
        }
        $enquiry_tbl = $wpdb->prefix.'enquiry_detail_new';
        $enquiry_details = $wpdb->get_row($wpdb->prepare("SELECT enquiry_id, name, message FROM $enquiry_tbl WHERE enquiry_id = %d", $_POST[ 'enquiry_id' ]));
        ob_start();
        $quoteup->displayHistory->printSingleRow($history, $enquiry_details);
        $getContent = ob_get_contents();
        ob_end_clean();
        echo json_encode(array(
            'status' => $history[ 'status' ],
            'table_row' => $getContent,
        ));
        die();
    }

    /**
     * This function is used to format variation details in required format
     * @param  [array] $variation_details [description]
     * @return [array]                    [description]
     */
    public static function getQuoteVariationDetails($variation_details)
    {
        if ($variation_details != "") {
            $newVariation = array();
            foreach ($variation_details as $individualVariation) {
                $keyValue = explode(':', $individualVariation);
                $newVariation[ trim($keyValue[ 0 ]) ] = trim($keyValue[ 1 ]);
            }

            return $newVariation;
        }
        return $variation_details;
    }

    /**
     * This function is used to set expiration date in database
     * @param [int] $enquiry_id [Enquiry id]
     */
    public static function setExpiry($enquiry_id)
    {
        global $quoteup;
        $expiration_date = isset($_POST[ 'expiration-date' ]) ? $_POST[ 'expiration-date' ] : '0000-00-00 00:00:00';
        if (!empty($expiration_date)) {
            $quoteup->manageExpiration->setExpirationDate($expiration_date, $enquiry_id);
        }
    }

    /**
     * This function is used to update order id
     * @param [int] $enquiry_id [Enquiry ID]
     */
    public static function setOrderID($enquiry_id)
    {
        global $wpdb;
        $getOrderId = $wpdb->get_var($wpdb->prepare("SELECT order_id FROM {$wpdb->prefix}enquiry_detail_new WHERE enquiry_id = %d", $enquiry_id));
        if ($getOrderId != null || $getOrderId === 0) {
            $wpdb->update(
                "{$wpdb->prefix}enquiry_detail_new",
                array(
                'order_id' => null,
                ),
                array(
                'enquiry_id' => $enquiry_id,
                )
            );
        }
    }

    /**
     * This function is used to check if quantity is greater than 0
     * @param  [type] $size     [description]
     * @param  [type] $quantity [description]
     * @return [type]           [description]
     */
    public static function checkValidQuantity($size, $quantity)
    {
        $finalQuant = 0;
        for ($i = 0; $i < $size; $i++) {
            $finalQuant += $quantity[ $i ];
        }
        if ($finalQuant == 0) {
            _e("Total quantity is 0. Quotation is same as orignal quantity and price", 'quoteup');
            die();
        }

    }

    /**
     * Function for inserting data in enquiry_quotation table.
     */
    public static function saveQuotation()
    {
        if (!wp_verify_nonce($_POST[ 'security' ], 'quoteup')) {
            die('SECURITY_ISSUE');
        }

        if (!current_user_can('manage_options')) {
            die('SECURITY_ISSUE');
        }

        global $wpdb, $quoteup;
        $table_name = $wpdb->prefix . "enquiry_quotation";
        $enquiryTableName = $wpdb->prefix.'enquiry_detail_new';


        $enquiry_id = $_POST[ 'enquiry_id' ];
        $product_id = $_POST[ 'id' ];
        $newprice = $_POST[ 'newprice' ];
        $quantity = $_POST[ 'quantity' ];
        $oldprice = $_POST[ 'old-price' ];
        $variation_id = $_POST[ 'variations_id' ];
        $variation_details = $_POST[ 'variations' ];
        $show_price = $_POST[ 'show-price' ];
        $variation_index_in_enquiry = $_POST[ 'variation_index_in_enquiry' ];
        $total_price = 0;
        $size = sizeof($product_id);

        //This function is used to check if total quantity is greater than 0
        self::checkValidQuantity($size, $quantity);
        try {
            if (!canProductBeAddedInQuotation($product_id, $quantity, $variation_id, $variation_details, "quote")) {
                throw new Exception("Product Cannot be added in quotation", 1);
            }
            //Delete old quotation
            $wpdb->delete(
                $table_name,
                array(
                'enquiry_id' => $_POST[ 'enquiry_id' ],
                )
            );
            for ($i = 0; $i < $size; $i++) {
                $productAvailable = wc_get_product($product_id[ $i ]);
                if ($productAvailable == "") {
                    continue;
                }
                $variation_details[ $i ] = self::getQuoteVariationDetails($variation_details[$i]);
                $wpdb->insert(
                    $table_name,
                    array(
                    'enquiry_id' => $enquiry_id,
                    'product_id' => $product_id[ $i ],
                    'newprice' => $newprice[ $i ],
                    'quantity' => $quantity[ $i ],
                    'oldprice' => $oldprice[ $i ],
                    'variation_id' => $variation_id[ $i ],
                    'variation' => serialize($variation_details[ $i ]),
                    'show_price' => $show_price,
                    'variation_index_in_enquiry' => $variation_index_in_enquiry[$i],
                    )
                );
                $total_price += $newprice[ $i ] * $quantity[ $i ];
            }

            //Save Expiration Date
            self::setExpiry($enquiry_id);

            //Save total price of quotation in enquiry table
            $wpdb->update(
                $enquiryTableName,
                array(
                'total' => $total_price,
                'pdf_deleted' => 0,
                ),
                array(
                'enquiry_id' => $enquiry_id,
                )
            );

            //Set Order Id to NULL, so that it opens up a communication channel after rejecting the Quote
            self::setOrderID($enquiry_id);
            //Don't translate this string here. It's translation is handled in js
            echo "Saved Successfully.";
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        //Delete PDF if already exists
        $uploads_dir = wp_upload_dir();
        $base_uploads = $uploads_dir[ 'basedir' ].'/QuoteUp_PDF/';
        if ($enquiry_id != 0 && file_exists($base_uploads.$enquiry_id.'.pdf')) {
            unlink($base_uploads.$enquiry_id.'.pdf');
        }
        //update History Table
        global $quoteupManageHistory;
        $quoteupManageHistory->addQuoteHistory($enquiry_id, '-', 'Saved');

        die();
    }
}

$this->quoteDetailsEdit = QuoteupQuoteDetailsEdit::getInstance();
