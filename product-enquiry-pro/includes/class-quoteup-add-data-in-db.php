<?php
namespace Includes;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


if (! class_exists('QuoteupAddDataInDB')) {

    class QuoteupAddDataInDB
    {

        /**
         *
         * @var string Short Name for plugin.
         */
        private $pluginShortName = '';

        /**
         *
         * @var string Slug to be used in url and functions name
         */
        private $pluginSlug = '';

        /**
         *
         * @var string stores the current plugin version
         */
        private $pluginVersion = '';

        /**
         *
         * @var string Handles the plugin name
         */
        private $pluginName = '';

        /**
         *
         * @var string  Stores the URL of store. Retrieves updates from
         *              this store
         */
        private $storeUrl = '';

        /**
         *
         * @var string  Name of the Author
         */
        private $authorName = '';

        public function __construct($plugin_data)
        {

            $this->authorName       = $plugin_data[ 'author_name' ];
            $this->pluginName       = $plugin_data[ 'plugin_name' ];
            $this->pluginShortName = $plugin_data[ 'plugin_short_name' ];
            $this->pluginSlug       = $plugin_data[ 'plugin_slug' ];
            $this->pluginVersion    = $plugin_data[ 'plugin_version' ];
            $this->storeUrl         = $plugin_data[ 'store_url' ];

            add_action('admin_menu', array( $this, 'lMenu' ));
        }

        public function lMenu()
        {

            $this->addData();

            add_plugins_page("{$this->pluginShortName} License", "{$this->pluginShortName} License", 'manage_options', $this->pluginSlug . '-license', array(
                $this, 'lPage', ));
        }

        public function lPage()
        {

            include_once QUOTEUP_PLUGIN_DIR . '/templates/lPage_display.php';
        }


        public function statusUpdate($licenseData)
        {
            $status = "";
            if ((empty($licenseData->success)) && isset($licenseData->error) && ($licenseData->error == "expired")) {
                $status = 'expired';
            } elseif ($licenseData->license == 'invalid' && isset($licenseData->error) && $licenseData->error == "revoked") {
                $status = 'disabled';
            } elseif ($licenseData->license == 'invalid' && $licenseData->activations_left == "0") {
                include_once(plugin_dir_path(__FILE__) . 'class-quoteup-get-data.php');

                $active_site = \Includes\QuoteupGetData::getSiteList($this->pluginSlug);
                
                if (! empty($active_site) || $active_site != "") {
                    $status = "invalid";
                } else {
                    $status = 'valid';
                }
            } elseif ($licenseData->license == 'failed') {
                $status = 'failed';
                $GLOBALS[ 'wdm_license_activation_failed' ] = true;
            } else {
                $status = $licenseData->license;
            }
            
            update_option('edd_' . $this->pluginSlug . '_license_status', $status);
            return $status;
        }

        public function checkIfNoData($licenseData, $currentResponseCode, $validResponseCode)
        {
            if ($licenseData == null || ! in_array($currentResponseCode, $validResponseCode)) {
                $GLOBALS[ 'wdm_server_null_response' ] = true;
                set_transient('wdm_' . $this->pluginSlug . '_license_trans', 'server_did_not_respond', 60 * 60 * 24);
                return false;
            }
            return true;
        }

        public function activateLicense()
        {

            $license_key = trim($_POST[ 'edd_' . $this->pluginSlug . '_license_key' ]);

            if ($license_key) {
                update_option('edd_' . $this->pluginSlug . '_license_key', $license_key);
                $apiParams = array(
                    'edd_action'         => 'activate_license',
                    'license'            => $license_key,
                    'item_name'          => urlencode($this->pluginName),
                    'current_version'    => $this->pluginVersion
                );

                $response = wp_remote_get(add_query_arg($apiParams, $this->storeUrl), array(
                    'timeout'    => 15, 'sslverify'  => false, 'blocking'    => true ));

                if (is_wp_error($response)) {
                    return false;
                }

                $licenseData = json_decode(wp_remote_retrieve_body($response));

                $validResponseCode = array( '200', '301' );

                $currentResponseCode = wp_remote_retrieve_response_code($response);

                $isDataAvailable = $this->checkIfNoData($licenseData, $currentResponseCode, $validResponseCode);
                //cspPrintDebug($licenseData); exit;
                if ($isDataAvailable == false) {
                    return;
                }

                $exp_time = 0;
                if (isset($licenseData->expires)) {
                    $exp_time = strtotime($licenseData->expires);
                }
                $cur_time = time();

                if (isset($licenseData->expires) && ($licenseData->expires !== false) && $exp_time <= $cur_time && $exp_time != 0) {
                    $licenseData->error = "expired";
                }

                if (isset($licenseData->renew_link) && ( ! empty($licenseData->renew_link) || $licenseData->renew_link != "")) {
                    update_option('wdm_' . $this->pluginSlug . '_product_site', $licenseData->renew_link);
                }
                
                $this->updateNumberOfSitesUsingLicense($licenseData);

                $licenseStatus = $this->statusUpdate($licenseData);
                $this->setTransientOnActivation($licenseStatus);
            }
        }

        public function updateNumberOfSitesUsingLicense($licenseData)
        {
            
            if (isset($licenseData->sites) && ( ! empty($licenseData->sites) || $licenseData->sites != "" )) {
                update_option('wdm_' . $this->pluginSlug . '_license_key_sites', $licenseData->sites);
                update_option('wdm_' . $this->pluginSlug . '_license_max_site', $licenseData->license_limit);
            } else {
                update_option('wdm_' . $this->pluginSlug . '_license_key_sites', '');
                update_option('wdm_' . $this->pluginSlug . '_license_max_site', '');
            }
        }

        public function setTransientOnActivation($licenseStatus)
        {
            $trans_var = get_transient('wdm_' . $this->pluginSlug . '_license_trans');
            if (isset($trans_var)) {
                delete_transient('wdm_' . $this->pluginSlug . '_license_trans');
                if (! empty($licenseStatus)) {
                    set_transient('wdm_' . $this->pluginSlug . '_license_trans', $licenseStatus, 60 * 60 * 24);
                }
            }
        }

        public function deactivateLicense()
        {
            $quoteupLicenseKey = trim(get_option('edd_' . $this->pluginSlug . '_license_key'));

            if ($quoteupLicenseKey) {
                $apiParams = array(
                    'edd_action'         => 'deactivate_license',
                    'license'            => $quoteupLicenseKey,
                    'item_name'          => urlencode($this->pluginName),
                    'current_version'    => $this->pluginVersion
                );

                $response = wp_remote_get(add_query_arg($apiParams, $this->storeUrl), array(
                    'timeout'    => 15, 'sslverify'  => false, 'blocking'    => true ));

                if (is_wp_error($response)) {
                    return false;
                }


                $licenseData = json_decode(wp_remote_retrieve_body($response));

                $validResponseCode = array( '200', '301' );

                $currentResponseCode = wp_remote_retrieve_response_code($response);

                $isDataAvailable = $this->checkIfNoData($licenseData, $currentResponseCode, $validResponseCode);

                if ($isDataAvailable == false) {
                    return;
                }

                if ($licenseData->license == 'deactivated' || $licenseData->license == 'failed') {
                    update_option('edd_' . $this->pluginSlug . '_license_status', 'deactivated');
                }
                //delete_transient( 'wdm_' . $this->pluginSlug . '_license_trans' );
                delete_transient('wdm_' . $this->pluginSlug . '_license_trans');

                set_transient('wdm_' . $this->pluginSlug . '_license_trans', $licenseData->license, 0);
            }
        }

        public function addData()
        {
            if (isset($_POST[ 'edd_' . $this->pluginSlug . '_license_activate' ])) {
                if (! check_admin_referer('edd_' . $this->pluginSlug . '_nonce', 'edd_' . $this->pluginSlug . '_nonce')) {
                    return;
                }
                $this->activateLicense();
            } elseif (isset($_POST[ 'edd_' . $this->pluginSlug . '_license_deactivate' ])) {
                if (! check_admin_referer('edd_' . $this->pluginSlug . '_nonce', 'edd_' . $this->pluginSlug . '_nonce')) {
                    return;
                }
                $this->deactivateLicense();
            }
        }
    }

}
global $quoteup_plugin_data;
new QuoteupAddDataInDB($quoteup_plugin_data);
