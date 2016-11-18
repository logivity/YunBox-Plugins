<?php

namespace Includes\Admin;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class QuoteupQuoteDetails
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
        add_action('admin_enqueue_scripts', array( $this, 'enqueueScripts' ), 10, 1);
    }

    public function enqueueScripts($hook)
    {
        if (isset($_GET['status'])) {
            $status = $_GET['status'];
        } else {
            $status = "all";
        }
        if ('toplevel_page_quoteup-details-new' == $hook) {

            wp_enqueue_style('table_css', QUOTEUP_PLUGIN_URL . '/css/admin/dashboard-quotes-listing.css');
            wp_enqueue_script('dashboard-quotes-listing-bulk-actions', QUOTEUP_PLUGIN_URL . '/js/admin/dashboard-quotes-listing-bulk-actions.js', array( 'jquery' ));


            // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
            wp_localize_script(
                'dashboard-quotes-listing-bulk-actions',
                'dashboard_quote_listing_bulk_actions',
                array(
                'ajax_url'       => admin_url('admin-ajax.php'),
                'export_nonce'   => wp_create_nonce('quoteup-nonce'),
                'could_not_create_csv'  => __('Could not create CSV file because of Security issues', 'quoteup'),
                'select_one_enquiry'    => __('Select atleast one enquiry to export', 'quoteup'),
                'status'    => $status,
                )
            );
        }
    }

    public function displayQuoteDetails()
    {
        global $quoteupQuotesList,$quoteupEnquiriesList;
        $path = QUOTEUP_PLUGIN_URL . '/generate-csv.php';
        if (! class_exists('WP_List_Table')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        }
        $optionData = quoteupSettings();
        ?>
		<div class="wrap">
        <h2>
        <?php
        if (isset($optionData['enable_disable_quote']) && $optionData['enable_disable_quote'] == 1) {
            _e('Enquiry Details', 'quoteup');
        } else {
            _e('Enquiry & Quote Details', 'quoteup');
        }
        ?>
		</h2>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method='post' id='csv_form' action='<?php echo $path ?>'>
								<input type='hidden' name='data' id='data'>
							</form>
							<form method="post" id='wdm_list'>
								<input type="hidden" name='csv_path' id='csv_path' value="<?php echo $path;
        ?>" />
								<input type="hidden" name="page" value="my_list_test" />

								<?php
                                if (isset($optionData['enable_disable_quote']) && $optionData['enable_disable_quote'] == 1) {
                                    $quoteupEnquiriesList->prepare_items();
                                    $quoteupEnquiriesList->display();
                                } else {
                                    $quoteupQuotesList->prepare_items();
                                    $quoteupQuotesList->display();
                                }

                                ?>
							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
		<?php
    }
}

$this->quoteDetails = QuoteupQuoteDetails::getInstance();
