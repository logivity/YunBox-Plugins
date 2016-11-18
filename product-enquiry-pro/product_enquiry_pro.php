<?php
/*
Plugin Name:     Product Enquiry Pro for WooCommerce (A.K.A QuoteUp)
Description:     Allows prospective customers to make enquiry about a WooCommerce product. Analyze product demands right from your dashboard.
  Version: 	4.1.1
Author:     WisdmLabs
Author URI:     https://wisdmlabs.com/
Plugin URI:     https://wisdmlabs.com/
License:     GPL
Text Domain:     quoteup
 */

/**
 * This file's name has underscore because making it (-) spaced will create an issue for existing users.
 * Plugin won't be activated automatically for them.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once 'quoteup-functions.php';

if (!defined('QUOTEUP_PLUGIN_DIR')) {
    define('QUOTEUP_PLUGIN_DIR', quoteupPluginDir());
}

if (!defined('QUOTEUP_WC_PLUGIN_DIR')) {
    define('QUOTEUP_WC_PLUGIN_DIR', quoteupWcPluginDir());
}

if (!defined('QUOTEUP_PLUGIN_URL')) {
    define('QUOTEUP_PLUGIN_URL', quoteupPluginUrl());
}

if (!defined('MPDF_ALL_FONTS_URL')) {
    define('MPDF_ALL_FONTS_URL', 'https://wisdmlabs.com/all-fonts/');
}

if (!defined('QUOTEUP_VERSION')) {
    define('QUOTEUP_VERSION', '4.1.1');
}
if (!defined('EDD_WPEP_STORE_URL')) {
    define('EDD_WPEP_STORE_URL', 'https://wisdmlabs.com');
}
if (!defined('EDD_WPEP_ITEM_NAME')) {
    define('EDD_WPEP_ITEM_NAME', 'Product Enquiry Pro');
}

if (!defined('REPLACE_QUOTE')){
    $form_data = quoteupSettings();
    define('REPLACE_QUOTE', isset($form_data['replace_quote']) ? $form_data['replace_quote'] : '');
}

/**
 * @global array Plugin data used throughout the plugin for various purposes
 */


$quoteup_plugin_data = array(
    'plugin_short_name' => EDD_WPEP_ITEM_NAME, //Plugins short name appears on the License Menu Page
    'plugin_slug' => 'pep', //this slug is used to store the data in db. License is checked using two options viz edd_<slug>_license_key and edd_<slug>_license_status
    'plugin_version' => QUOTEUP_VERSION, //Current Version of the plugin. This should be similar to Version tag mentioned in Plugin headers
    'plugin_name' => EDD_WPEP_ITEM_NAME, //Under this Name product should be created on WisdmLabs Site
    'store_url' => EDD_WPEP_STORE_URL, //Url where program pings to check if update is available and license validity
    'author_name' => 'WisdmLabs', //Author Name
);

require_once QUOTEUP_PLUGIN_DIR . '/install.php';
require_once QUOTEUP_PLUGIN_DIR . '/delete-old-pdfs.php';
require_once QUOTEUP_PLUGIN_DIR . '/expire-quotes.php';
require_once QUOTEUP_PLUGIN_DIR . '/ajax.php';
require_once QUOTEUP_PLUGIN_DIR . '/quoteup-redirect-admin-links.php';


/**
 * @global boolean $quoteup_enough_stock After approving the quote by customer,
 * if any of the product does not have enough stock, then this variable is set
 * to false. When this is set to false, no products are added in the WooCommerce's cart
 * after approving the Quote.
 */
$quoteup_enough_stock = true;


/**
 * @global int $quoteup_enough_stock_product_id Holds the product id which does
 * not have enough stock
 */
$quoteup_enough_stock_product_id = 0;

/**
 * @global string $quoteup_enough_stock_variation_details Holds the variation details
 * of the product which is out of stock
 */
$quoteup_enough_stock_variation_details = "";

if (!class_exists('QuoteUp')) {

    final class QuoteUp
    {

        /**
         * Set to false when dependencies required to run plugin are not fulfilled
         *
         * @var boolean
         */
        private $dependenciesFullfilled = true;
        protected static $instance = null;

        /**
         * Function to create a singleton instance of class and return the same
         *
         * @return [Object] [
         * description]
         */
        public static function getInstance()
        {
            if (!self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        private function __construct()
        {
            $this->dependenciesFullfilled = $this->checkDependencies();

            if ($this->dependenciesFullfilled) {
                $this->triggerInstallation();
                $this->bootstrapLicense();
                $this->bootstrapPluginUpdater();
                $this->bootstrapEmailSender();
                add_action('init', array($this, 'loadWPMLCompatibility'));
                add_action('init', array($this, 'loadTextDomain'));
                add_action('init', array($this, 'includeFiles'));
                add_action('wp_logout', array($this, 'destroySession'));
                add_action('wp_login', array($this, 'destroySession'));
            }

            add_action('admin_init', array($this, 'deactivatePlugins'));
        }

        private function checkDependencies()
        {


            if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
                return false;
            }

            if (function_exists('phpversion')) {
                $phpVersion = phpversion();
            } elseif (defined('PHP_VERSION')) {
                $phpVersion = PHP_VERSION;
            }

            if (version_compare($phpVersion, '5.3.0', '<')) {
                return false;
            }

            return true;
        }

        private function triggerInstallation()
        {
            /**
             * ***********************************
             *
             * Flow is like this after installation:
             *  1.  Create these tables: wp_enquiry_detail_new, wp_enquiry_thread, wp_enquiry_quotation, wp_enquiry_history,
             *  2.  Create folder QuoteUp_PDF in uploads directory
             *  3.  Convert Multiple Product Enquiry Settings Dropdown to Checkbox because in versions before 4.0 'Enable Multiproduct Enquiry and Quote Request' setting was dropdown
             *  4.  Set option quoteup_settings_convert_to_checkbox to 1 because conversion of dropdown to checkbox of settings is done.
             *  5.  Convert dropdown 'Enable Enquiry Button' shown on single product (which were there before 4.0) to checkbox for all existing products
             *  6.  Set 'quoteup_convert_per_product_pep_dropdown' to 1 which confirms 'Enable Enquiry Button' dropdown is converted to checkbox
             *  7.  Convert dropdown 'Show Add to Cart button' shown on single product to checkbox for all existing products.
             *  8.  Set 'quoteup_convert_per_product_add_to_cart_dropdown' to 1 which confirms 'Enable Enquiry Button' dropdown is converted to checkbox
             *  9.  Set _enable_add_to_cart, _enable_price, _enable_pep meta fields to all those products who does not have those meta fields set. On fresh install, these meta fields are set to all products.
             *  10. Change order history messages from present tense to past tense.
             *  11. Set quoteup_convert_history_status to 1 which confirms that all history statuses have been changed
             *  12. Change all checkboxes on settings page to 1/0. They are 1/unavailable before this transformation
             *  13. Set default settings to newly introduced configuration.
             *  14. Add new QuoteUp version number in database
             *
             * Steps to be performed on new installation: 1, 2, 9, 12, 13
             *
             * *************************************
             */
            //Hooks which creates all the necessary tables.
            register_activation_hook(__FILE__, 'quoteupCreateTables');

            //Hooks which updates MPE Settings from yes to 1
            register_activation_hook(__FILE__, 'quoteupConvertMpeSettings');

            //Hook which toggles _disable_quoteup to _enable_quoteup.
            register_activation_hook(__FILE__, 'quoteupTogglePerProductDisablePepSettings');

            //Hook which toggles _disable_quoteup to _enable_quoteup.
            register_activation_hook(__FILE__, 'quoteupConvertPerProductAddToCart');

            //Hook which sets the per-product 'Add To Cart' settings on activation.
            register_activation_hook(__FILE__, 'quoteupSetAddToCartPepPriceOnActivation');

            //Hooks which updated history of previous enquiries.
            register_activation_hook(__FILE__, 'quoteupUpdateHistoryStatus');

            //Hooks converts checkboxes from 1/unavailable to 1/0.
            register_activation_hook(__FILE__, 'quoteupConvertOldCheckboxes');

            //Set Default settings
            register_activation_hook(__FILE__, 'quoteupSetDefaultSettings');

            //Updates new database version in database
            register_activation_hook(__FILE__, 'quoteupUpdateVersionInDb');

            // Hook for cron job to delete PDF
            register_activation_hook(__FILE__, 'quoteupCreateCronJobs');
        }

        public function bootstrapLicense()
        {
            global $quoteup_plugin_data;
            if (!class_exists('Combined\Includes\QuoteupAddDataInDB')) {
                require_once QUOTEUP_PLUGIN_DIR . '/includes/class-quoteup-add-data-in-db.php';
            }
        }

        public function bootstrapPluginUpdater()
        {
            global $quoteup_plugin_data;
            if (!class_exists('Includes\QuoteupPluginUpdater')) {
                require_once QUOTEUP_PLUGIN_DIR . '/includes/class-quoteup-plugin-updater.php';
            }

            $licenseKey = trim(get_option('edd_' . $quoteup_plugin_data['plugin_slug'] . '_license_key'));

            // setup the updater
            new Includes\QuoteupPluginUpdater($quoteup_plugin_data['store_url'], __FILE__, array(
                'version' => $quoteup_plugin_data['plugin_version'], // current version number
                'license' => $licenseKey, // license key (used get_option above to retrieve from DB)
                'item_name' => $quoteup_plugin_data['plugin_name'], // name of this plugin
                'author' => $quoteup_plugin_data['author_name'], //author of the plugin
            ));
        }

        public function bootstrapEmailSender()
        {
            //Defines a class QuoteupEmail which is used to trigger to send emails
            require_once QUOTEUP_PLUGIN_DIR . '/includes/class-quoteup-email.php';
        }


        public function loadWPMLCompatibility()
        {
            //Includes all required files for plugin
            if (file_exists(QUOTEUP_PLUGIN_DIR . '/includes/class-quoteup-wpml-compatibility.php')) {
                require_once QUOTEUP_PLUGIN_DIR . '/includes/class-quoteup-wpml-compatibility.php';
            }
        }

        public function loadTextDomain()
        {
            load_plugin_textdomain('quoteup', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }

        /**
         * If WooCommerce is not active, deactivates itself.
         * Also checks if Free plugin is active and if it is, deactivates the free plugin.
         */
        public function deactivatePlugins()
        {

            if (!is_plugin_active('woocommerce/woocommerce.php')) {
                deactivate_plugins(plugin_basename(__FILE__));
                unset($_GET['activate']);
                //Display notice that plugin is deactivated because WooCommerce is not activated.
                add_action('admin_notices', array(
                    $this,
                    'wcNotActiveNotice'));
                return;
            }

            if (is_plugin_active('product-enquiry-for-woocommerce/product-enquiry-for-woocommerce.php')) {
                deactivate_plugins('product-enquiry-for-woocommerce/product-enquiry-for-woocommerce.php');
            }
        }

        public function wcNotActiveNotice()
        {
            ?>
			<div class='error'>
				<p>
					<?php echo __("WooCommerce plugin is not active. In order to make the Product Enquiry Pro for WooCommerce (A.K.A QuoteUp) plugin work, you need to install and activate WooCommerce first.", 'quoteup'); ?>
				</p>
			</div>
			<?php
}

        public function includeFiles()
        {
            require_once QUOTEUP_PLUGIN_DIR . '/file-includes.php';
        }

        public function destroySession()
        {
            if (!isset($_SESSION)) {
                @session_start();
            }
            @session_destroy();
        }

    }

}

$GLOBALS['quoteup'] = QuoteUp::getInstance();

add_action('admin_enqueue_scripts', 'quoteupEnqueueScriptsStyles', 10, 1);

//Enqueue styles and scripts on individual product create/edit screen
function quoteupEnqueueScriptsStyles($hook)
{
    $screen = get_current_screen();
    if (($hook == 'post.php' || $hook == 'post-new.php') && $screen->id == 'product') {
        wp_enqueue_script('price-add-to-cart-relation', QUOTEUP_PLUGIN_URL . '/js/admin/single-product.js', array(
            'jquery'));
        wp_enqueue_style('wdm_style_for_individual_product_screen', QUOTEUP_PLUGIN_URL . '/css/admin/dashboard-single-product.css', false, false, false);
    }
}



