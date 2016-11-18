<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('admin_init', 'quoteupUpdateCheck', 10);

function quoteupUpdateCheck()
{
    $get_plugin_version = get_option('wdm_quoteup_version');

    if ($get_plugin_version === false || $get_plugin_version != QUOTEUP_VERSION) {
        quoteupCreateTables();
        quoteupConvertMpeSettings();
        quoteupTogglePerProductDisablePepSettings();
        quoteupConvertPerProductAddToCart();
        quoteupSetAddToCartPepPriceOnActivation();
        quoteupUpdateHistoryStatus();
        quoteupConvertOldCheckboxes();
        quoteupSetDefaultSettings();
        quoteupUpdateVersionInDb();
        quoteupCreateCronJobs();
    }
}

/**
 * Changes _disable_quoteup for every proodct to _enable_quoteup.
 *
 * @global type $wpdb
 */
function quoteupTogglePerProductDisablePepSettings()
{

    //Not a fresh install and version is less than 4.1.0
    if (isFreshInstalledQuoteup() == false && isQuoteupLesserThanVersion('4.1.0')) {
        global $wpdb;

        //Check if Dropdowns in Settings are converted to Checkbox
        $convertPerProductPepDropdown = get_option('quoteup_convert_per_product_pep_dropdown', 0);

        if ($convertPerProductPepDropdown == 1) {
            return;
        }

        //Migrating Old PEP to New PEP
        $getDisabledPEPForProducts = $wpdb->get_results($wpdb->prepare("SELECT meta_value, post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = %s", '_disable_pep'));

        foreach ($getDisabledPEPForProducts as $singleProduct) {
            $trimMetaValue = trim($singleProduct->meta_value);
            if (!empty($trimMetaValue)) {
                if ($trimMetaValue == 'no') {
                    update_post_meta($singleProduct->post_id, '_enable_pep', 'yes');
                } else {
                    update_post_meta($singleProduct->post_id, '_enable_pep', '');
                }
                delete_post_meta($singleProduct->post_id, '_disable_pep');
            }
        }

        //Migrating from QuoteUp to PEP
        $getDisabledQuoteUpForProducts = $wpdb->get_results($wpdb->prepare("SELECT meta_value, post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = %s", '_disable_quoteup'));

        foreach ($getDisabledQuoteUpForProducts as $singleProduct) {
            $trimMetaValue = trim($singleProduct->meta_value);
            if (!empty($trimMetaValue)) {
                if ($trimMetaValue == 'no') {
                    update_post_meta($singleProduct->post_id, '_enable_pep', 'yes');
                } else {
                    update_post_meta($singleProduct->post_id, '_enable_pep', '');
                }
                delete_post_meta($singleProduct->post_id, '_disable_quoteup');
            }
        }

        update_option('quoteup_convert_per_product_pep_dropdown', 1);
    }
}

/*
 * Check products which have _enable_add_to_cart set to 'no' and sets them to ''
 */

function quoteupConvertPerProductAddToCart()
{
    if (isFreshInstalledQuoteup() == false && isQuoteupLesserThanVersion('4.1.0')) {
        global $wpdb;

        //Check if Dropdowns in Settings are converted to Checkbox
        $convertPerProductAddToCartDropdown = get_option('quoteup_convert_per_product_add_to_cart_dropdown', 0);

        if ($convertPerProductAddToCartDropdown == 1) {
            return;
        }

        //Migrating Old PEP to New PEP
        $products = $wpdb->get_results($wpdb->prepare("SELECT meta_value, post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = %s AND meta_value = %s", '_enable_add_to_cart', 'no'));

        foreach ($products as $singleProduct) {
            update_post_meta($singleProduct->post_id, '_enable_add_to_cart', '');
        }

        update_option('quoteup_convert_per_product_add_to_cart_dropdown', 1);
    }
}

/**
 * This function is used to enable or disable Add to cart button, Enable or disable PEP, show or hide Price on all products after activating the plugin again.
 *
 * @return [type] [description]
 */
function quoteupSetAddToCartPepPriceOnActivation()
{
    global $post;

    //Enable Enquiry, Add to cart and Price for all products who are not having the meta set
    $defaultAddToCart = $defaultPrice = $defaultEnquiry = 'yes';

    $productsWithoutAddToCart = quoteupSearchProductsNotHavingMeta('_enable_add_to_cart');
    if (!empty($productsWithoutAddToCart) && is_array($productsWithoutAddToCart)) {
        foreach ($productsWithoutAddToCart as $singleProduct) {
            update_post_meta($singleProduct, '_enable_add_to_cart', $defaultAddToCart);
        }
    }

    $productsWithoutPrice = quoteupSearchProductsNotHavingMeta('_enable_price');
    if (!empty($productsWithoutPrice) && is_array($productsWithoutPrice)) {
        foreach ($productsWithoutPrice as $singleProduct) {
            update_post_meta($singleProduct, '_enable_price', $defaultPrice);
        }
    }

    $productsWithoutPep = quoteupSearchProductsNotHavingMeta('_enable_pep');
    if (!empty($productsWithoutPep) && is_array($productsWithoutPep)) {
        foreach ($productsWithoutPep as $singleProduct) {
            update_post_meta($singleProduct, '_enable_pep', $defaultEnquiry);
        }
    }
}

/**
 * Returns the list of products which does not have specific meta value set.
 */
function quoteupSearchProductsNotHavingMeta($meta_key = '')
{
    global $wpdb;
    static $allProductsFromDb = null;
    if (empty($meta_key)) {
        return;
    }

    if (null === $allProductsFromDb) {
        $allProductsFromDb = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'product'");
        if (!$allProductsFromDb) {
            $allProductsFromDb = false;
        }
    }

    if ($allProductsFromDb) {
        $commaSeparatedProducts = implode(',', $allProductsFromDb);
        $productsToBeOmitted = $wpdb->get_col("SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE post_id IN ($commaSeparatedProducts) AND meta_key = '$meta_key'");
        if ($productsToBeOmitted) {
            $requiredProducts = array_diff($allProductsFromDb, $productsToBeOmitted);

            return $requiredProducts;
        } else {
            return $allProductsFromDb;
        }
    }

    return;
}

/**
 * This function updates history status and makes it past tense.
 *
 * @return [type] [description]
 */
function quoteupUpdateHistoryStatus()
{

    //Not a fresh install and version is less than 4.1.0
    if (isFreshInstalledQuoteup() == false && isQuoteupLesserThanVersion('4.1.0')) {
        global $wpdb;
        $convertHistoryStatus = get_option('quoteup_convert_history_status', 0);

        if ($convertHistoryStatus == 1) {
            return;
        }

        $table_name = $wpdb->prefix.'enquiry_history';
        $enquiryDetailTable = $wpdb->prefix.'enquiry_detail_new';
        $sql = "SELECT enquiry_id, status,message FROM $table_name";
        $result = $wpdb->get_results($sql, ARRAY_A);
        foreach ($result as $res) {
            if ($res[ 'status' ] == 'Reject') {
                $wpdb->update(
                $table_name, array(
                    'status' => 'Rejected', // string
                ), array('enquiry_id' => $res[ 'enquiry_id' ],
                    'status' => 'Reject',
                )
                );
            }
            if ($res[ 'status' ] == 'Accept') {
                $wpdb->update(
                $table_name, array(
                    'status' => 'Approved', // string
                ), array('enquiry_id' => $res[ 'enquiry_id' ],
                    'status' => 'Accept',
                )
                );
            }
            if ($res[ 'status' ] == 'Completed') {
                $wpdb->update(
                $table_name, array(
                    'status' => 'Order Placed', // string
                ), array('enquiry_id' => $res[ 'enquiry_id' ],
                    'status' => 'Completed',
                )
                );
            }
            if ($res[ 'message' ] == 'Approved but payment pending') {
                $wpdb->update(
                $table_name, array(
                    'message' => 'Approved but order not yet placed', // string
                ), array('enquiry_id' => $res[ 'enquiry_id' ],
                    'message' => 'Approved but payment pending',
                )
                );
            }
        }

        $sql = "SELECT enquiry_id,enquiry_date FROM  $enquiryDetailTable WHERE  enquiry_id NOT IN (SELECT enquiry_id FROM $table_name)";
        $oldEnquiryIDs = $wpdb->get_results($sql, ARRAY_A);
        foreach ($oldEnquiryIDs as $enquiryID) {
            $enquiry = $enquiryID[ 'enquiry_id' ];
            $date = $enquiryID[ 'enquiry_date' ];
            $table_name = $wpdb->prefix.'enquiry_history';
            $performedBy = null;
            $wpdb->insert(
            $table_name, array(
                'enquiry_id' => $enquiry,
                'date' => $date,
                'message' => '-',
                'status' => 'Requested',
                'performed_by' => $performedBy,
            )
            );
        }

        update_option('quoteup_convert_history_status', 1);
    }
}

/**
 * This function converts old checkboxes that is 1/unavailable to 1/0.
 */
function quoteupConvertOldCheckboxes()
{
    //Run only if not fresh installed and upgrading from version below 4.1.0
    if (isFreshInstalledQuoteup() == false && isQuoteupLesserThanVersion('4.1.0')) {
        $oldSettings = quoteupSettings();
        //Check if Dropdowns in Settings are converted to Checkbox
        $settingsConversion = get_option('quoteup_settings_convert_old_checkboxes', 0);

        if ($settingsConversion == 1) {
            return;
        }

        $settingsAvailable = array(
            'enable_disable_quote' => '0',
            'only_if_out_of_stock' => '0',
            'show_enquiry_on_shop' => '0',
            'show_button_as_link' => '0',
            'enable_send_mail_copy' => '0',
            'enable_telephone_no_txtbox' => '0',
            'make_phone_mandatory' => '0',
        );

        $settingsToBeSet = array_diff_assoc($settingsAvailable, $oldSettings);

        if (empty($settingsToBeSet)) {
            update_option('quoteup_settings_convert_old_checkboxes', 1);

            return;
        }

        $newSettings = $oldSettings + $settingsToBeSet;

        update_option('wdm_form_data', $newSettings);

        update_option('quoteup_settings_convert_old_checkboxes', 1);
    }
}

/**
 * THis function is used to toggle settings of previous pep.
 *
 * @return [type] [description]
 */
function quoteupConvertMpeSettings()
{

    //Run only if not fresh installed and upgrading from version below 4.1.0
    if (isFreshInstalledQuoteup() == false && isQuoteupLesserThanVersion('4.1.0')) {
        $oldSettings = quoteupSettings();
        //Check if Dropdowns in Settings are converted to Checkbox
        $settingsConversion = get_option('quoteup_settings_convert_to_checkbox', 0);

        if ($settingsConversion == 1) {
            return;
        }

        if (isset($oldSettings[ 'enable_disable_mpe' ]) && ($oldSettings[ 'enable_disable_mpe' ] == 'yes' || $oldSettings[ 'enable_disable_mpe' ] == 1)) {
            $oldSettings[ 'enable_disable_mpe' ] = 1;
        } else {
            $oldSettings[ 'enable_disable_mpe' ] = 0;
        }
        update_option('wdm_form_data', $oldSettings);

        //All Settings is converted to checkbox
        update_option('quoteup_settings_convert_to_checkbox', 1);
    }
}

/**
 * THis function creates the table required by QuoteUp.
 *
 * @return [type] [description]
 */
function quoteupCreateTables()
{
    $pe_plugin = 'product-enquiry-for-woocommerce/product-enquiry-for-woocommerce.php';

    if (is_plugin_active($pe_plugin)) {
        add_action('update_option_active_plugins', 'deactivateDependentProductEnquiry');
    }

    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    global $wpdb;
    $charset_collate = '';
    if (!empty($wpdb->charset)) {
        $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
    }
    if (!empty($wpdb->collate)) {
        $charset_collate .= " COLLATE $wpdb->collate";
    }
    $wdm_enq_table = $wpdb->prefix.'enquiry_detail_new';
    $wdm_reply_message = $wpdb->prefix.'enquiry_thread';
    $check_if_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$wdm_enq_table'"); //Adding this statement so that dbDelta function can understand that structure of this table need to be scanned and redesigned. Otherwise it would throw the error that Table already exists
    $add_extra_column = false;
    $custom_column_in_db = apply_filters('pep_custom_column_in_quoteup_db', $add_extra_column);
    $custom_column_in_db = apply_filters('quoteup_custom_column_in_quoteup_db', $custom_column_in_db);

    $quoteup_query_elements = tableStructure($custom_column_in_db);

    $enq_sql = 'CREATE TABLE '.$wdm_enq_table." (
                    $quoteup_query_elements
		) $charset_collate;";
    $enq_sql = apply_filters('pep_create_table_query', $enq_sql);
    $enq_sql = apply_filters('quoteup_create_table_query', $enq_sql);
    if ($enq_sql == false) {
        return;
    }
    do_action('quoteup_before_creating_table_in_db');
    do_action('pep_before_creating_table_in_db');
    dbDelta($enq_sql);
    do_action('quoteup_after_creating_table_in_db');
    do_action('pep_after_creating_table_in_db');

    //creating enquiry_meta table
    do_action('quoteup_create_enquiry_meta_db');
    createEnquiryMetaTable();

    do_action('pep_create_enquiry_meta_db');
    $trans_var = get_transient('wdm_quoteup_license_trans');
    deleteTrans($trans_var);

    set_transient('wdm_quoteup_license_trans', 'inactive', 0);

    $check_if_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$wdm_reply_message'");
    createEnquiryThread($check_if_table_exists, $wdm_reply_message, $charset_collate);

    global $wpdb;
    $table_name = "{$wpdb->prefix}enquiry_quotation";
    $enquiryTableName = "{$wpdb->prefix}enquiry_detail_new";

    $charset_collate = '';

    if (!empty($wpdb->charset)) {
        $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
    }

    if (!empty($wpdb->collate)) {
        $charset_collate .= " COLLATE $wpdb->collate";
    }

    $sql = "CREATE TABLE $table_name (
      ID bigint(20) NOT NULL AUTO_INCREMENT,
      enquiry_id bigint(20) NOT NULL,
      product_id bigint(20) NOT NULL,
      newprice float NOT NULL,
      quantity bigint(20) NOT NULL,
      oldprice float NOT NULL,
      variation_id bigint(20),
      variation longtext,
      show_price VARCHAR(5),
      variation_index_in_enquiry bigint(20),
      PRIMARY KEY  (ID)
      ) $charset_collate;";

    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    /* Create Enquiry History table */

    $history_table_name = $wpdb->prefix.'enquiry_history';

    $sql = "CREATE TABLE $history_table_name (
      ID bigint(20) NOT NULL AUTO_INCREMENT,
      enquiry_id bigint(12) NOT NULL,
      date datetime NOT NULL,
      message longtext NOT NULL,
      status text NOT NULL,
      performed_by bigint(20) NULL,
      PRIMARY KEY  (ID), 
      KEY enquiry_id (enquiry_id)
      ) $charset_collate;";

    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    //Alter Table to add a new column to store Enquiry Hash
    addEnquiryHash($enquiryTableName);

    //Alter Table to add a new column to store Enquiry Status
    addOrderID($enquiryTableName);
    //Alter Table to add a new column to show price or not
    // addShowPrice($table_name);
    //Alter Table to add a new column to store Total amount of the Quote
    addTotal($enquiryTableName);

    $upload_dir = wp_upload_dir();
    if (!file_exists($upload_dir[ 'basedir' ].'/QuoteUp_PDF')) {
        $success = wp_mkdir_p($upload_dir[ 'basedir' ].'/QuoteUp_PDF');
        if (!$success) {
            exit('Could not create directory to store PDF files');
        }
    }
}

function deleteTrans($trans_var)
{
    if (isset($trans_var)) {
        delete_transient('wdm_quoteup_license_trans');
    }
}

function createEnquiryThread($check_if_table_exists, $wdm_reply_message, $charset_collate)
{
    if ($check_if_table_exists == null) {
        $message_tbl = "CREATE TABLE $wdm_reply_message
                        (id INT NOT NULL AUTO_INCREMENT,
                         enquiry_id int,
                         subject varchar(200),
                         message varchar(1000),
                         parent_thread int,
                         date datetime,
                          primary key  (id)) $charset_collate;";

        dbDelta($message_tbl);
    }
}

function createEnquiryMetaTable()
{
    global $wpdb;
    $table_name = $wpdb->prefix.'enquiry_meta';
    $max_index_length = 191;
    $charset_collate = '';

    if (!empty($wpdb->charset)) {
        $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
    }

    if (!empty($wpdb->collate)) {
        $charset_collate .= " COLLATE $wpdb->collate";
    }

    $sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (
        meta_id INT NOT NULL AUTO_INCREMENT,
		enquiry_id int,
		meta_key varchar(500),
		meta_value varchar(500),
		PRIMARY KEY  (meta_id),
                KEY enquiry_id (enquiry_id),
                KEY meta_key (meta_key('.$max_index_length.'))
                )'.$charset_collate.';';
    dbDelta($sql);
}

function addEnquiryHash($enquiryTableName)
{
    global $wpdb;
    if (!$wpdb->get_var("SHOW COLUMNS FROM `$enquiryTableName` LIKE 'enquiry_hash';")) {
        $wpdb->query("ALTER TABLE $enquiryTableName ADD enquiry_hash VARCHAR(75)");
        $wpdb->query("ALTER TABLE $enquiryTableName ADD UNIQUE INDEX `enquiry_hash` (`enquiry_hash`)");
    }
}

function addTotal($enquiryTableName)
{
    global $wpdb;
    if (!$wpdb->get_var("SHOW COLUMNS FROM `$enquiryTableName` LIKE 'total';")) {
        $wpdb->query("ALTER TABLE `$enquiryTableName` ADD `total` INT(50)");
    }
    //Update total of all Quotes Previously created
    $totalStatus = get_option('quoteup_total_updated');
    if (!$totalStatus) {
        updateTotal();
    }
}

function updateTotal()
{
    global $wpdb;
    $enquiryTableName = $wpdb->prefix.'enquiry_detail_new';
    $quotationTableName = $wpdb->prefix.'enquiry_quotation';

    $sql = 'SELECT DISTINCT enquiry_id FROM '.$quotationTableName;
    $quotationIDs = $wpdb->get_col($sql);

    foreach ($quotationIDs as $quoteEnquiries) {
        $totalAmount = 0;
        $Quantity = array();
        $newprice = array();

        $sql = 'SELECT * FROM '.$quotationTableName.' WHERE enquiry_id ='.$quoteEnquiries;
        $result = $wpdb->get_results($sql, ARRAY_A);
        foreach ($result as $key) {
            $Quantity[] = $key[ 'quantity' ];
            $newprice[] = $key[ 'newprice' ];
        }

        $size = sizeof($newprice);
        for ($i = 0; $i < $size; ++$i) {
            if (isset($newprice) && isset($Quantity)) {
                $totalAmount = $totalAmount + ($newprice[ $i ] * $Quantity[ $i ]);
            }
        }

        $wpdb->update(
        $enquiryTableName, array(
            'total' => $totalAmount,
        ), array(
            'enquiry_id' => $quoteEnquiries,
        )
        );
    }
    update_option('quoteup_total_updated', 1);
}

function addOrderID($enquiryTableName)
{
    global $wpdb;
    if (!$wpdb->get_var("SHOW COLUMNS FROM `$enquiryTableName` LIKE 'order_id';")) {
        $wpdb->query("ALTER TABLE $enquiryTableName ADD order_id VARCHAR(20)");
    }
}

function addShowPrice($table_name)
{
    global $wpdb;
    if (!$wpdb->get_var("SHOW COLUMNS FROM `$table_name` LIKE 'show_price';")) {
        $wpdb->query("ALTER TABLE $table_name ADD show_price VARCHAR(5)");
    }
}

function tableStructure($custom_column_in_db)
{
    if ($custom_column_in_db == false) {
        $quoteup_query_elements = '
                enquiry_id INT NOT NULL AUTO_INCREMENT,
        name varchar(100),
        email varchar(75),
        message varchar(500),
        phone_number varchar(16),
        subject varchar(50),
        enquiry_ip varchar(35),     
        product_details longtext,
        enquiry_date datetime,
		expiration_date datetime,
        pdf_deleted bigint(20),
        date_field datetime,
                PRIMARY KEY  (enquiry_id)
                ';
    } else {
        $quoteup_query_elements = "
                enquiry_id INT NOT NULL AUTO_INCREMENT,
        name varchar(100),
        email varchar(75),
        message varchar(500),
        phone_number varchar(16),
        subject varchar(50),
        enquiry_ip varchar(35),     
        product_details longtext,
        enquiry_date datetime,
		expiration_date datetime,
        pdf_deleted bigint(20),
        date_field datetime,
                PRIMARY KEY  (enquiry_id),
                {$custom_column_in_db}
                ";
    }

    return $quoteup_query_elements;
}

/**
 * Deactivate product enquiry free as well as pro version if activated.
 *
 * @return [type] [description]
 */
function deactivateDependentProductEnquiry()
{
    $pe_plugin = 'product-enquiry-for-woocommerce/product-enquiry-for-woocommerce.php';

    deactivate_plugins($pe_plugin);
}

/**
 * Returns true if it plugin is freshly installed and not yet configured i.e. if plugin
 * is activated for first time. NOT TO BE USED DIRECTLY.
 *
 * This function is used only during performing installation procedures. After installation
 * is complete, wdm_form_data will always have some value and hence this function will
 * return false.
 *
 * @staticvar array $settings
 *
 * @return bool
 */
function isFreshInstalledQuoteup()
{
    static $settings;

    if (null !== $settings) {
        if ($settings == false) {
            return true;
        }

        return false;
    }

    $settings = quoteupSettings();

    if ($settings == false) {
        return true;
    }

    return false;
}

function quoteupUpdateVersionInDb()
{
    update_option('wdm_quoteup_version', QUOTEUP_VERSION);
}

/**
 * Checks whether QuoteUp version is greater than or equal to specified version.
 *
 * NOT TO BE USED DIRECTLY
 *
 * This is used during installation procedure before updating new version number
 * in the database. That means this function returns true when last installed plugin's
 * version number is greater than specified $versionNumber variable
 *
 * @param string $versionNumber
 *
 * @return bool
 */
function isQuoteupGreaterThanVersion($versionNumber)
{
    $installedVersionNumber = get_option('wdm_quoteup_version');

    if ($installedVersionNumber == false) {
        return false;
    }

    if (quoteupVersionCompare($installedVersionNumber, $versionNumber, '>=')) {
        return true;
    }

    return false;
}

/**
 * Checks whether QuoteUp version is lesser than specified version.
 *
 * NOT TO BE USED DIRECTLY
 *
 * This is used during installation procedure before updating new version number
 * in the database. That means this function returns true when last installed plugin's
 * version number is lesser than specified $versionNumber variable
 *
 * @param string $versionNumber
 *
 * @return bool
 */
function isQuoteupLesserThanVersion($versionNumber)
{
    $installedVersionNumber = get_option('wdm_quoteup_version');

    if ($installedVersionNumber == false) {
        return true;
    }

    if (quoteupVersionCompare($installedVersionNumber, $versionNumber, '<')) {
        return true;
    }

    return false;
}

/**
 * Compares version number which are php standardized as well non-php standardized.
 *
 * @param string $ver1
 * @param string $ver2
 * @param string $operator
 *
 * @return bool
 */
function quoteupVersionCompare($ver1, $ver2, $operator = null)
{
    $pattern = '#(\.0+)+($|-)#';
    $ver1 = preg_replace($pattern, '', $ver1);
    $ver2 = preg_replace($pattern, '', $ver2);

    return isset($operator) ?
    version_compare($ver1, $ver2, $operator) :
    version_compare($ver1, $ver2);
}

function quoteupSetDefaultSettings()
{
    $defaultSettings = array(
        'enable_disable_quote' => '1',
        'only_if_out_of_stock' => '0',
        'show_enquiry_on_shop' => '1',
        'enable_disable_mpe' => '0',
        'custom_label' => 'Request a Quote',
        'cart_custom_label' => 'View Enquiry Cart',
        'pos_radio' => 'show_after_summary',
        'show_button_as_link' => '0',
        'enable_send_mail_copy' => '1',
        'enable_telephone_no_txtbox' => '1',
        'make_phone_mandatory' => '0',
        'user_email' => get_option('admin_email'),
        'default_sub' => '['.get_bloginfo('name').'] Enquiry/Quote Request for a product from '.get_bloginfo('name'),
        'button_CSS' => 'theme_css',
        'company_name' => get_bloginfo('name'),
        'company_email' => get_option('admin_email'),
        'send_mail_to_admin' => '1',
        'send_mail_to_author' => '1',
    );

    if (isFreshInstalledQuoteup()) {
        update_option('wdm_form_data', $defaultSettings);
    } else {
        $currentSettings = quoteupSettings();
        //If upgraded from older version, check which all settings are not there in the settings
        // and add those settings
        $settingsToBeSet = array_diff_assoc($defaultSettings, $currentSettings);

        if (empty($settingsToBeSet)) {
            return;
        }

        $newSettings = $currentSettings + $settingsToBeSet;

        update_option('wdm_form_data', $newSettings);
    }
}

function quoteupCreateCronJobs()
{
    wp_clear_scheduled_hook('quoteupDeletePdfs');
    wp_clear_scheduled_hook('quoteupExpireQuotes');
    // Make sure this event hasn't been scheduled
    if (!wp_next_scheduled('quoteupDeletePdfs')) {
        // Schedule the event
        wp_schedule_event(time(), 'hourly', 'quoteupDeletePdfs');
    }

    $timestamp = strtotime('tomorrow') - (get_option('gmt_offset') * 3600);
    // Make sure this event hasn't been scheduled
    if (!wp_next_scheduled('quoteupExpireQuotes')) {
        // Schedule the event
        wp_schedule_event($timestamp, 'daily', 'quoteupExpireQuotes');
    }
}
