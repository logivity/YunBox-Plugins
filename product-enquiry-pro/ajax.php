<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
if (!defined('DOING_AJAX')) {
    return; // Exit if accessed directly
}
//Ajax for CSV Generation
add_action('wp_ajax_wdm_return_rows', 'wdm_return_rows');
// add_action('wp_ajax_nopriv_wdm_return_rows', 'wdm_return_rows');
//Ajax to add products in enquiry cart
add_action('wp_ajax_wdm_add_product_in_enq_cart', 'quoteupAddProductInEnqCart');
add_action('wp_ajax_nopriv_wdm_add_product_in_enq_cart', 'quoteupAddProductInEnqCart');

//Ajax to update cart
add_action('wp_ajax_wdm_update_enq_cart_session', 'quoteupUpdateEnqCartSession');
add_action('wp_ajax_nopriv_wdm_update_enq_cart_session', 'quoteupUpdateEnqCartSession');

//Ajax to Migrate Scripts
add_action('wp_ajax_migrateScript', 'migrateScript');

//Ajax for Nounce Validation
add_action('wp_ajax_quoteupValidateNonce', 'quoteupValidateNonce');
add_action('wp_ajax_nopriv_quoteupValidateNonce', 'quoteupValidateNonce');

//Ajax for submitting enquiry form
add_action('wp_ajax_quoteupSubmitWooEnquiryForm', 'quoteupSubmitWooEnquiryForm');
add_action('wp_ajax_nopriv_quoteupSubmitWooEnquiryForm', 'quoteupSubmitWooEnquiryForm');

//Ajax to send reply to customer
add_action('wp_ajax_wdmSendReply', 'wdmSendReply');
add_action('wp_ajax_nopriv_wdmSendReply', 'wdmSendReply');

//Ajax to update customer data
add_action('wp_ajax_modify_user_data', 'quoteupModifyUserQuoteData');

/*
 * Ajax To set the global setting option of add_to_cart to individual product.
 * Ajax To set the global setting option of quoteup_enquiry to individual product.
 */
add_action('wp_ajax_wdm_set_add_to_cart_value', 'quoteupSetAddToCartValue');

/**
 * Ajax to add product in Enquiry/Quote Cart
 */
add_action('wp_ajax_wdm_trigger_add_to_enq_cart', 'wdmTriggerAddToEnqCart');
add_action('wp_ajax_nopriv_wdm_trigger_add_to_enq_cart', 'wdmTriggerAddToEnqCart');

// add_action('wp_ajax_clearsession', 'unsetApprovalSession');
// add_action('wp_ajax_nopriv_clearsession', 'unsetApprovalSession');

function unsetApprovalSession()
{
    global $quoteup;
    $quoteup->wcCart->unsetSession();
}

function wdmTriggerAddToEnqCart()
{
    quoteupAddProductInEnqCart();
}

function displaymsg($message)
{
    return $message;
}

/*
 * Callback for CSV generation ajax
 */
if (!function_exists('wdm_return_rows')) {
    function wdm_return_rows()
    {
        if (!wp_verify_nonce($_POST[ 'security' ], 'quoteup-nonce')) {
            die('SECURITY_ISSUE');
        }

        if (!current_user_can('manage_options')) {
            die('SECURITY_ISSUE');
        }
        global $wpdb;
        $ids = array();

        $arr = getarr($ids);

        $tel = '';
        $dateFiels = '';

        $form = quoteupSettings();

        if (isset($form[ 'enable_telephone_no_txtbox' ])) {
            $tel = $form[ 'enable_telephone_no_txtbox' ];
        }

        if (isset($form[ 'enable_date_field' ])) {
            $dateFiels = $form[ 'enable_date_field' ];
            // $date_label = $form[ 'date_field_label' ];
        }

        $table = $wpdb->prefix.'enquiry_detail_new';
        $table2 = $wpdb->prefix.'enquiry_meta';
        $qry = array();

        $name = apply_filters('pep_export_csv_customer_name_column', 'name');
        $name = apply_filters('quoteup_export_csv_customer_name_column', $name);

        $email = apply_filters('pep_export_csv_customer_email_column', 'email');
        $email = apply_filters('quoteup_export_csv_customer_email_column', $email);

        $phone_number = apply_filters('pep_export_csv_customer_telephone_column', 'phone_number');
        $phone_number = apply_filters('quoteup_export_csv_customer_telephone_column', $phone_number);

        $date_label = apply_filters('pep_export_csv_customer_telephone_column', 'date_field');
        $date_label = apply_filters('quoteup_export_csv_customer_telephone_column', $date_label);

        $subject = apply_filters('pep_export_csv_subject_column', 'subject');
        $subject = apply_filters('quoteup_export_csv_subject_column', $subject);

        $message = apply_filters('pep_export_csv_message_column', 'message');
        $message = apply_filters('quoteup_export_csv_message_column', $message);

        $product_details = apply_filters('pep_export_csv_product_details_column', 'product_details');
        $product_details = apply_filters('quoteup_export_csv_product_details_column', $product_details);

        $qry[] = 'SELECT';
        $columns[] = 'enquiry_id';
        $columns[] = 'enquiry_date';
        $columns[] = 'enquiry_ip';
        $columns[] = $product_details;
        $columns[] = $name;
        $columns[] = $email;

        if ($tel == 1) {
            $columns[] = $phone_number;
        }

        if ($dateFiels == 1) {
            $columns[] = $date_label;
        }

        $columns[] = $subject;
        $columns[] = $message;
        $meta_key = array();
        $sql = 'SELECT distinct meta_key FROM '.$table2;
        $results = $wpdb->get_results($sql);

        foreach ($results as $k => $v) {
            $meta_key[] = $v->meta_key;
            unset($k);
        }

        $all_columns = implode(', ', $columns);
        $qry[] = $all_columns;

        unset($columns);
        $columns = explode(',', $all_columns);

        array_walk($columns, 'quoteupTrimNamesOfAllColumns');
        $array_of_default_columns = array('name', 'email', 'subject', 'message', 'product_details', 'date_field');
        foreach ($array_of_default_columns as $single_default_name) {
            $key_to_be_removed = array_search($single_default_name, $columns);
            unset($columns[ $key_to_be_removed ]);
        }
        $columns = array_filter($columns); //Remove Default Columns from Columns list, So that Dynamic filters can be genrated.

        $qry[] = "FROM $table ";

        $qry = appendConditionInQUery($qry, $table, $arr);

        $result = $wpdb->get_results(implode(' ', $qry));

        $single_result = forEachEnquiry($result, $columns);
        $result = forEachMetaValue($result, $meta_key, $table2);

        $data = array();
        $data = forEachProduct($result, $data);


        echo json_encode($data);
        unset($single_result);
        die();
    }
}

/**
 * This function is used to apped the where clause in query depending upon condition
 * @param  [type] $qry [description]
 * @return [type]      [description]
 */
function appendConditionInQUery($qry, $table, $arr)
{
    if (!empty($arr) && $arr != '') {
        $qry[] = "WHERE $table.enquiry_id in ($arr)";
    } elseif (isset($_POST[ 'status' ]) && 'all' != $_POST[ 'status' ]) {
        $resultSet = getSqlStatus($_POST[ 'status' ]);
        if (isset($resultSet)) {
            $qry[] = "WHERE $table.enquiry_id in ($resultSet)";
        }
    }

    return $qry;
}

/**
 * This function gives sql query as per status.
 * @return [type] [description]
 */
function getSqlStatus($filter)
{
    global $wpdb;
    $tableName = $wpdb->prefix.'enquiry_history';

    $sql = "SELECT s1.enquiry_id
                FROM $tableName s1
                LEFT JOIN $tableName s2 ON s1.enquiry_id = s2.enquiry_id
                AND s1.id < s2.id
                WHERE s2.enquiry_id IS NULL AND s1.status ='".$filter."'AND s1.enquiry_id > 0 AND s1.ID > 0";
    $res = $wpdb->get_col($sql);
    if (isset($res)) {
        $resultSet = join(',', $res);
    }

    return $resultSet;
}

function getarr($ids)
{
    if (isset($_POST[ 'ids' ])) {
        $ids = $_POST[ 'ids' ];
    }
    if ($ids == '') {
        $arr = '';
    } else {
        $arr = implode(',', $ids);
    }

    return $arr;
}

function quoteupTrimNamesOfAllColumns(&$array_item)
{
    $array_item = trim($array_item);
}

function forEachEnquiry($result, $columns)
{
    foreach ($result as &$single_result) {
        $single_result->name = apply_filters('pep_export_csv_customer_name_data', $single_result->name);
        $single_result->name = apply_filters('quoteup_export_csv_customer_name_data', $single_result->name);

        $single_result->email = apply_filters('pep_export_csv_customer_email_data', $single_result->email);
        $single_result->email = apply_filters('quoteup_export_csv_customer_email_data', $single_result->email);

        $single_result->subject = apply_filters('pep_export_csv_subject_data', $single_result->subject);
        $single_result->subject = apply_filters('quoteup_export_csv_subject_data', $single_result->subject);

        $single_result->message = apply_filters('pep_export_csv_message_data', $single_result->message);
        $single_result->message = apply_filters('quoteup_export_csv_message_data', $single_result->message);

        $single_result->product_details = apply_filters('pep_export_csv_product_details_data', $single_result->product_details);
        $single_result->product_details = apply_filters('quoteup_export_csv_product_details_data', $single_result->product_details);

        foreach ($columns as $single_custom_column) {
            $single_result->{$single_custom_column} = apply_filters('pep_export_csv_'.$single_custom_column.'_data', $single_result->{$single_custom_column});
            $single_result->{$single_custom_column} = apply_filters('quoteup_export_csv_'.$single_custom_column.'_data', $single_result->{$single_custom_column});
        }
    }

    // return $single_result;
}

function forEachMetaValue($result, $meta_key, $table2)
{
    global $wpdb;
    foreach ($result as $k => $v) {
        $id = $v->enquiry_id;
        foreach ($meta_key as $key => $value) {
            $sql = $wpdb->prepare("SELECT meta_value FROM $table2 WHERE meta_key like %s AND enquiry_id = %d", $value, $id);
            // $sql = "SELECT meta_value FROM $table2 WHERE meta_key like '$value' AND enquiry_id = $id";
            $res = $wpdb->get_results($sql);

            if (count($res) == 1) {
                $result[ $k ]->$value = $res[ 0 ]->meta_value;
            } else {
                $result[ $k ]->$value = '';
            }
            unset($key);
        }
    }

    return $result;
}

function forEachProduct($result, $data)
{
    foreach ($result as $k => $v) {
        $dm = array();
        foreach ($v as $key => $val) {
            if ($key == 'product_details') {
                $dt = maybe_unserialize($val);
                $val = '';
                if ($dt == null) {
                    continue;
                }
                foreach ($dt as $dv) {
                    $price = html_entity_decode(strip_tags($dv[ 0 ][ 'price' ]));
                    $price = getSalePrice($price);
                    $str = '';
                    if ($dv[ 0 ][ 'remark' ] != '') {
                        $str = "Remark: {$dv[ 0 ][ 'remark' ]}";
                    }
                    $val .= "{Name: {$dv[ 0 ][ 'title' ]};SKU: {$dv[ 0 ][ 'sku' ]};Quantity: {$dv[ 0 ][ 'quant' ]};Price: {$price};{$str}}\n";
                }
            }
            if ($key == 'date_field') {
                $dateField = "";
                if(!empty($val) && $val != '0000-00-00 00:00:00'){
                    $dateField = date('M d, Y', strtotime($val));
                }


                if(empty($dateField)){
                    $dateField = "-";
                }

                // $createDate = new DateTime($val);
                // $val = $createDate->format('Y-m-d');
                $val = $dateField;
            }
            $dm[ $key ] = $val;
        }
        array_push($data, $dm);
        unset($k);
    }

    return $data;
}

function getNewVariation($variation_detail)
{
    foreach ($variation_detail as $individualVariation) {
        $keyValue = explode(':', $individualVariation);
        $newVariation[trim($keyValue[0])] = trim($keyValue[1]);
    }
    return $newVariation;
}

/**
 * Callback for Add products to enquiry cart ajax.
 */
function quoteupAddProductInEnqCart()
{
    if (!isset($_SESSION)) {
        @session_start();
    }
    $data = $_POST;

    $product_id = $_POST[ 'product_id' ];
    $prod_quant = $_POST[ 'product_quant' ];
    $title = get_the_title($product_id);
    $remark = isset($_POST[ 'remark' ]) ? $_POST[ 'remark' ] : '';
    $id_flag = 0;
    $counter = 0;
    $authorEmail = $_POST['author_email'];
    $variation_id = $_POST['variation'];
    $variation_detail = '';

    //Variable Product
    if ($variation_id != '') {
        $product = new WC_Product_Variation($variation_id);
        $var_product = new WC_Product($variation_id);
        $sku = $var_product->get_sku();
        $variation_detail = $_POST['variation_detail'];
        $variation_detail = getNewVariation($variation_detail);
        // $variation_detail = $newVariation;
        $price = $var_product->get_price();
        $img = wp_get_attachment_url(get_post_thumbnail_id($variation_id));
        if ($img != '') {
            $img_url = $img;
        } else {
            $img_url = wp_get_attachment_url(get_post_thumbnail_id($product_id));
        }
    } else {
        $product = new WC_Product($product_id);
        // $price = $product->get_price_html();
        $price = $product->get_price();

        $sku = $product->get_sku();
        $img_url = wp_get_attachment_url(get_post_thumbnail_id($product_id));
    }
    //End of Variable Product

    $flag_counter = setFlag($product_id, $id_flag, $counter, $variation_detail, $variation_id);
    $id_flag = $flag_counter[ 'id_flag' ];
    $counter = $flag_counter[ 'counter' ];

    if ($id_flag == 0) {
        $product_array = array();
        $prod = array('id' => $product_id,
            'title' => $title,
            'price' => $price,
            'quant' => $prod_quant,
            'img' => $img_url,
            'remark' => $remark,
            'sku' => $sku,
            'variation_id' => $variation_id,
            'variation' => $variation_detail,
            'author_email' => $authorEmail);
        $product_array[] = apply_filters('wdm_filter_product_data', $prod, $data);
        if (isset($_SESSION[ 'wdm_product_count' ])) {
            if ($_SESSION[ 'wdm_product_count' ] != '') {
                $counter = $_SESSION[ 'wdm_product_count' ];
            }
        }
        $_SESSION[ 'wdm_product_info' ][ $counter ] = $product_array;
        setProductCount();
    } else {
        setProductInfo($counter, $remark, $prod_quant, $price);
    }
    echo $_SESSION[ 'wdm_product_count' ];
}

/**
 * This function is used to update total count of products in cart
 * @param [int] $productCount [total number of products in cart]
 */
function setProductCount()
{
    if (isset($_SESSION[ 'wdm_product_count' ]) && !empty($_SESSION[ 'wdm_product_count' ])) {
        $_SESSION[ 'wdm_product_count' ] = $_SESSION[ 'wdm_product_count' ] + 1;
    } else {
        $_SESSION[ 'wdm_product_count' ] = 1;
    }
}

function setProductInfo($counter, $remark, $prod_quant, $price)
{
    if ($remark != '') {
        $_SESSION[ 'wdm_product_info' ][ $counter ][ 0 ][ 'remark' ] = $remark;
    }
    $_SESSION[ 'wdm_product_info' ][ $counter ][ 0 ][ 'quant' ] += $prod_quant;
    $_SESSION[ 'wdm_product_info' ][ $counter ][ 0 ][ 'price' ] = $price;
}

/**
 * Checks whether product has already been added to Enquiry/Quote cart.
 *
 * If product is already there in the Enquiry cart, returns id_flag as 1, else returns id_flag as 0
 */
function setFlag($product_id, $id_flag, $counter, $variation_detail, $variation_id)
{
    if (isset($_SESSION[ 'wdm_product_info' ]) && !empty($_SESSION[ 'wdm_product_info' ])) {
        for ($search = 0; $search < count($_SESSION[ 'wdm_product_info' ]); ++$search) {
            if ($product_id == $_SESSION[ 'wdm_product_info' ][ $search ][ 0 ][ 'id' ]) {
                if ($variation_detail != '' && $variation_id != '') {
                    if ($_SESSION['wdm_product_info'][$search][0]['variation'] == $variation_detail && $_SESSION['wdm_product_info'][$search][0]['variation_id'] == $variation_id) {
                        $id_flag = 1;
                        $counter = $search;
                    }
                } else {
                    $id_flag = 1;
                    $counter = $search;
                }
            }
        }
    }

    return array(
        'id_flag' => $id_flag,
        'counter' => $counter,
    );
}

/**
 * Callback for Update cart ajax.
 */
function quoteupUpdateEnqCartSession()
{
    @session_start();
    $status = false;
    $pid = $_POST[ 'product_id' ];
    $vid = $_POST['product_var_id'];
    $variation_detail = $_POST['variation'];
    $status = isSoldIndividually($pid);
    if ($status == true) {
        if (isset($_POST[ 'clickcheck' ]) && $_POST[ 'clickcheck' ] == 'remove') {
            $quant = 0;
        } else {
            $quant = 1;
        }
    } else {
        $quant = $_POST[ 'quantity' ];
    }
    if (isset($_POST[ 'remark' ])) {
        $remark = $_POST[ 'remark' ];
    }
    $product = new WC_Product($pid);
    $pri = $product->get_price();
    $price = $product->get_price_html();
    $priceStatus = get_post_meta($pid, '_enable_price', true);
    for ($search = 0; $search < count($_SESSION[ 'wdm_product_info' ]); ++$search) {
        if ($pid == $_SESSION[ 'wdm_product_info' ][ $search ][ 0 ][ 'id' ]) {
            if ($vid != '') {
                if ($_SESSION['wdm_product_info'][$search][0]['variation_id'] == $vid && $_SESSION['wdm_product_info'][$search][0]['variation'] == $variation_detail) {
                    if ($quant == 0) {
                        array_splice($_SESSION['wdm_product_info'], $search, 1);
                        $_SESSION['wdm_product_count'] = $_SESSION['wdm_product_count'] - 1;
                    } else {
                        $product = new WC_Product($vid);
                        $pri = $product->get_price();
                        $price = $product->get_price_html();
                        $price = wc_price($pri * $quant);
                        if ($priceStatus == 'yes') {
                            echo json_encode(array('product_id' => $pid, 'variation_id' => $vid,  'variation_detail' => $variation_detail,  'price' => $price));
                        } else {
                            echo json_encode(array('product_id' => $pid, 'variation_id' => $vid, 'variation_detail' => $variation_detail, 'price' => "-"));
                        }
                        $_SESSION['wdm_product_info'][$search][0]['quant'] = $quant;
                        $_SESSION[ 'wdm_product_info' ][ $search ][ 0 ][ 'price' ] = $pri;
                        $_SESSION['wdm_product_info'][$search][0]['remark'] = $remark;
                    }
                }
            } else {
                if ($quant == 0) {
                    array_splice($_SESSION['wdm_product_info'], $search, 1);
                    $_SESSION['wdm_product_count'] = $_SESSION['wdm_product_count'] - 1;
                } else {
                    $price = wc_price($pri * $quant);
                    if ($priceStatus == 'yes') {
                        echo json_encode(array('product_id' => $pid, 'price' => $price));
                    } else {
                        echo json_encode(array('product_id' => $pid, 'price' => '-'));
                    }
                    $_SESSION['wdm_product_info'][$search][0]['quant'] = $quant;
                    $_SESSION[ 'wdm_product_info' ][ $search ][ 0 ][ 'price' ] = $pri;
                    $_SESSION['wdm_product_info'][$search][0]['remark'] = $remark;
                }
            }
        }
    }
    die();
}

/*
 * Callback for script migration ajax
 */
if (!function_exists('migrateScript')) {
    function migrateScript()
    {
        if (!wp_verify_nonce($_POST[ 'security' ], 'migratenonce')) {
            die('SECURITY_ISSUE');
        }

        if (!current_user_can('manage_options')) {
            die('SECURITY_ISSUE');
        }

        $migrated = get_option('wdm_enquiries_migrated');
        if ($migrated != 1) {
            global $wpdb;
            $enquiry_tbl = $wpdb->prefix.'enquiry_details';
            $enquiry_tbl_new = $wpdb->prefix.'enquiry_detail_new';
            $enquiry_meta_tbl = $wpdb->prefix.'enquiry_meta';
            $enquiries = $wpdb->get_results("SELECT * FROM {$enquiry_tbl}");
            foreach ($enquiries as $enquiry) {
                $pid = $enquiry->product_id;
                $pname = $enquiry->product_name;
                $psku = $enquiry->product_sku;
                $price = get_post_meta($pid, '_regular_price', true);
                $id = $enquiry->enquiry_id;
                $sql = $wpdb->prepare("select meta_key,meta_value FROM {$enquiry_meta_tbl} WHERE enquiry_id=%d", $id);
                $meta = $wpdb->get_results($sql);

                $cust_name = $enquiry->name;
                $cust_email = $enquiry->email;
                $ip = $enquiry->enquiry_ip;
                $dt = $enquiry->enquiry_date;
                $sub = $enquiry->subject;
                $number = $enquiry->phone_number;
                $msg = $enquiry->message;
                $img_url = wp_get_attachment_url(get_post_thumbnail_id($pid));

                $products_arr = array();
                $products_arr[][ 0 ] = array('id' => $pid, 'title' => $pname, 'quant' => 1, 'sku' => $psku, 'img' => $img_url, 'price' => $price, 'remark' => '');
                $record = serialize($products_arr);
                $wpdb->insert(
                    $enquiry_tbl_new,
                    array('name' => $cust_name,
                    'email' => $cust_email,
                    'message' => $msg,
                    'phone_number' => $number,
                    'subject' => $sub,
                    'enquiry_ip' => $ip,
                    'product_details' => $record,
                    'enquiry_date' => $dt,
                    ),
                    array('%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    )
                );
                echo $insert_id = $wpdb->insert_id;

                foreach ($meta as $pair) {
                    $key = $pair->meta_key;
                    $value = $pair->meta_value;
                    $wpdb->insert(
                        $enquiry_meta_tbl,
                        array(
                        'enquiry_id' => $insert_id,
                        'meta_key' => $key,
                        'meta_value' => $value,
                        ),
                        array('%d',
                        '%s',
                        '%s',
                        )
                    );
                }
            }
            update_option('wdm_enquiries_migrated', 1);

            $table_name = $wpdb->prefix . "enquiry_history";
            $enquiryDetailTable = $wpdb->prefix . "enquiry_detail_new";
            $sql = "SELECT enquiry_id,enquiry_date FROM  $enquiryDetailTable WHERE  enquiry_id NOT IN (SELECT enquiry_id FROM $table_name)";
            $oldEnquiryIDs = $wpdb->get_results($sql, ARRAY_A);
            foreach ($oldEnquiryIDs as $enquiryID) {
                $enquiry = $enquiryID[ 'enquiry_id' ];
                $date = $enquiryID[ 'enquiry_date' ];
                $table_name = $wpdb->prefix . "enquiry_history";
                $performedBy = null;
                $wpdb->insert(
                    $table_name,
                    array(
                    'enquiry_id' => $enquiry,
                    'date' => $date,
                    'message' => '-',
                        'status' => "Requested",
                    'performed_by' => $performedBy,
                    )
                );
            }
        }

        die();
    }
}

/**
 * Callback for nonce ajax.
 */
function quoteupValidateNonce()
{
    echo check_ajax_referer('nonce_for_enquiry', 'security', false);
    die();
}

/**
 * Callback for submitting enquiry form ajax.
 */
function quoteupSubmitWooEnquiryForm()
{
    @session_start();

    if (isset($_POST[ 'security' ]) && wp_verify_nonce($_POST[ 'security' ], 'nonce_for_enquiry')
    ) {
        global $wpdb;
        $data_obtained_from_form = $_POST;
        $form_data_for_mail = json_encode($data_obtained_from_form);
        $name = wp_kses($_POST[ 'custname' ], array());
        $email = $_POST[ 'txtemail' ];
        $phone = phoneNumber();
        $dateField = dateField();
        $subject = '';
        $authorEmail = '';
        if (isset($_POST[ 'txtsubject' ])) {
            $subject = wp_kses($_POST[ 'txtsubject' ], array());
            $subject = stripcslashes($subject);
        }

        $product_table = '';
        $msg = wp_kses($_POST[ 'txtmsg' ], array());
        $msg = stripcslashes($msg);
        $form_data = quoteupSettings();

        $product_table_and_details = emailAndDbDataOfProducts($form_data, $product_table);
        $product_details = setProductDetails($product_table_and_details);
        if (isset($product_table_and_details[ 'product_table' ])) {
            $product_table = $product_table_and_details[ 'product_table' ];
        } else {
            $product_table = '';
        }

        if (isset($product_table_and_details[ 'customer_product_table' ])) {
            $customer_product_table = $product_table_and_details[ 'customer_product_table' ];
        } else {
            $customer_product_table = '';
        }

        $authorEmail = setAuthorEmail($product_table_and_details);

        $address = getEnquiryIP();

        $type = 'Y-m-d H:i:s';
        $date = current_time($type);
        if (!empty($dateField)) {
            $dateField = date('Y-m-d', strtotime($dateField));
        }
        $tbl = $wpdb->prefix.'enquiry_detail_new';

        if ($wpdb->insert(
            $tbl,
            array(
            'name' => $name,
            'email' => $email,
            'phone_number' => $phone,
            'subject' => $subject,
            'enquiry_ip' => $address,
            'product_details' => $product_details,
            'message' => $msg,
            'enquiry_date' => $date,
            'date_field' => $dateField,
            ),
            array(
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            )
        )
        ) {
            do_action('mpe_form_entry_added_in_db', $wpdb->insert_id);
            do_action('quoteup_form_entry_added_in_db', $wpdb->insert_id);
            do_action('pep_form_entry_added_in_db', $wpdb->insert_id);
            do_action('quoteup_create_custom_field');
            do_action('pep_create_custom_field');
            do_action('quoteup_add_custom_field_in_db', $wpdb->insert_id);
            do_action('pep_add_custom_field_in_db', $wpdb->insert_id);

            $blg_name = get_option('blogname');
            $admin_emails = array();
            $admin_subject = '';

            $email_data = quoteupSettings();
            if ($email_data[ 'user_email' ] != '') {
                $admin_emails = explode(',', $email_data[ 'user_email' ]);
            }
            $admin_emails = array_map('trim', $admin_emails);

            //Send email to admin only if 'Send mail to Admin' settings is checked
            $admin_emails = addAdminMail($email_data, $admin_emails);

            //Send email to author only if 'Send mail to Author' settings is checked
            $admin_emails = addAuthorMail($email_data, $authorEmail, $admin_emails);

            $wdm_sitename = '['.trim(get_bloginfo('name')).'] ';
            $admin_subject = adminSubject($subject, $email_data, $wdm_sitename);
            $admin_emails = array_unique($admin_emails);

            if (empty($admin_emails)) {
                return;
            }

            foreach ($admin_emails as $admin_email) {
                forEachAdminEmails($admin_email, $blg_name, $form_data_for_mail, $product_table, $email, $name, $admin_subject);
            }
            do_action('wdm_after_send_admin_email');

            sendCopyIfChecked($name, $blg_name, $form_data_for_mail, $admin_subject, $email, $customer_product_table);

            $_SESSION[ 'wdm_product_info' ] = '';
            $_SESSION[ 'wdm_product_count' ] = 0;
            unset($_SESSION[ 'wdm_product_info' ]);
        }
    }
    //Sending output to screen so that browsers other than Chrome wait till response received from the server.
    echo 'COMPLETED';
    die();
}

/**
 * This function is used to add author mail in admin_emails
 * @param [array] $email_data   [Settings stored in database]
 * @param [string] $authorEmail  [Author email id]
 * @param [array] $admin_emails [Total mail ids on which mail needs to be sent]
 */
function addAuthorMail($email_data, $authorEmail, $admin_emails)
{
    if (isset($email_data[ 'send_mail_to_author' ]) && $email_data[ 'send_mail_to_author' ] == 1) {
        if (!empty($authorEmail)) {
            $admin_emails = array_merge($admin_emails, $authorEmail);
        }
    }
    return $admin_emails;
}

/**
 * This function is used to add author mail in admin_emails
 * @param [array] $email_data   [Settings stored in database]
 * @param [array] $admin_emails [Total mail ids on which mail needs to be sent]
 */
function addAdminMail($email_data, $admin_emails)
{
    if (isset($email_data[ 'send_mail_to_admin' ]) && $email_data[ 'send_mail_to_admin' ] == 1) {
        $admin = get_option('admin_email');
        if (!in_array($admin, $admin_emails)) {
            $admin_emails[] = $admin;
        }
    }

    return $admin_emails;
}

function setAuthorEmail($product_table_and_details)
{
    if (isset($product_table_and_details[ 'authorEmail' ])) {
        $authorEmail = $product_table_and_details[ 'authorEmail' ];
    } else {
        $authorEmail = '';
    }

    return $authorEmail;
}

/**
 * set phone number
 * sets phone number if entered by customer or keeps it blank.
 *
 * @return [type] [description]
 */
function phoneNumber()
{
    if (isset($_POST[ 'txtphone' ])) {
        $phone = $_POST[ 'txtphone' ];
    } else {
        $phone = '';
    }

    return $phone;
}

/**
 * set Date Field Value
 * sets Date field value if entered by customer or keeps it blank.
 *
 * @return [type] [description]
 */
function dateField()
{
    if (isset($_POST[ 'txtdate' ])) {
        $dateField = $_POST[ 'txtdate' ];
    } else {
        $dateField = null;
    }

    return $dateField;
}

/**
 * This function is used to get img url of product
 * @param  [type] $img [description]
 * @return [type]      [description]
 */
function getImgUrl($img, $product_id)
{
    if ($img != '') {
        $img_url = $img;
    } else {
        $img_url = wp_get_attachment_url(get_post_thumbnail_id($product_id));
    }
    return $img_url;
}

/**
 * This function is used to get variation details of the product
 * @param  [type] $variation_detail [description]
 * @return [type]                   [description]
 */
function getVariationDetails($variation_detail)
{
    foreach ($variation_detail as $individualVariation) {
        $keyValue = explode(':', $individualVariation);
        $newVariation[trim($keyValue[0])] = trim($keyValue[1]);
    }

    return $newVariation;
}

/**
 * THis function is used to get variation string
 * @param  [array] $variation_detail [Variation details]
 * @return [type]                   [description]
 */
function getVatiationString($variation_detail)
{
    $variationString = '';
    foreach ($variation_detail as $attributeName => $attributeValue) {
        if (!empty($variationString)) {
            $variationString .= ',';
        }
        $variationString .= '<b> '.wc_attribute_label($attributeName).'</b> : '.$attributeValue;
    }

    return $variationString;
}

/**
 * Returns email content to be sent to customer and admin. It also returns content
 * to be saved in the database. Checks whether multi product enquiry mode is enabled
 * or not and returns data accordingly.
 *
 * @param [array] $form_data     [settings stored by admin]
 * @param [type]  $product_table [description]
 */
function emailAndDbDataOfProducts($form_data, $product_table)
{
    @session_start();
    $product_details = '';
    $product_table = '';
    $customer_product_table = '';
    $authorEmail = array();
    if (isset($form_data[ 'enable_disable_mpe' ]) && $form_data[ 'enable_disable_mpe' ] == 1) {
        $product_details = serialize($_SESSION[ 'wdm_product_info' ]);

        $product_table .= "<tr>
                                    <th colspan='2'><h3>".__('Products', 'quoteup')."</h3></th>
                                    </tr>
                                    <tr>
                                    <td colspan='2'>
                                    <table style='width: 100%;' cellspacing='0' cellpadding='0'>
                                    <tbody><tr>
                                    <td style='background:none;border: 1px solid #999999;border-width:1px 0 0 0;height:1px;width:100%;margin:0px 0px 0px 0px;padding-top: 0;padding-bottom: 0;'>&nbsp;</td>
                                    </tr>
                                    </tbody>
                                    </table>
                                    </td>
                                    </tr>";
        $customer_product_table = $product_table;
        foreach ($_SESSION[ 'wdm_product_info' ] as $arr) {
            $combined_table = forEachProductInfo($arr, $product_table, $customer_product_table);
            array_push($authorEmail, $arr[0]['author_email']);
            $product_table = $combined_table[ 'admin_product_table' ];
            $customer_product_table = $combined_table[ 'customer_product_table' ];
        }
    } else {
        $product_id = $_POST[ 'product_id' ];
        $prod_quant = $_POST[ 'product_quant' ];
        $title = get_the_title($product_id);
        $prod_permalink = $_POST[ 'product_url' ];
        // $remark = isset($_POST[ 'remark' ]) ? $_POST[ 'remark' ] : '';
        // $id_flag = 0;
        // $counter = 0;
        $variation_id = $_POST['variation_id'];
        $variation_detail = '';
        $authorEmail = array();
        array_push($authorEmail, isset($_POST[ 'uemail' ]) ? $_POST[ 'uemail' ] : '');

    //Variable Product
        if ($variation_id != '') {
            $product = new WC_Product_Variation($variation_id);
            $var_product = new WC_Product($variation_id);
            $sku = $var_product->get_sku();
            $variation_detail = $_POST['variation_detail'];
            $price = $var_product->get_price();
            $img = wp_get_attachment_url(get_post_thumbnail_id($variation_id));
            $img_url = getImgUrl($img, $product_id);

            $variation_detail = getVariationDetails($variation_detail);
            $variationString  = getVatiationString($variation_detail);
            // $variation_detail = $newVariation;
        } else {
            $product = new WC_Product($product_id);
            // $price = $product->get_price_html();
            $enable_price = get_post_meta($product_id, '_enable_price', true);
            $price = $product->get_price();

            $sku = $product->get_sku();
            $img_url = wp_get_attachment_url(get_post_thumbnail_id($product_id));
        }
    //End of Variable Product
        $enable_price = get_post_meta($product_id, '_enable_price', true);
        $prod[][ 0 ] = array('id' => $product_id,
            'title' => $title,
            'price' => $price,
            'quant' => $prod_quant,
            'img' => $img_url,
            'remark' => '',
            'sku' => $sku,
            'variation_id' => $variation_id,
            'variation' => $variation_detail);
        if ($price == '') {
            $price = 0;
        }

        $product_table = "<tr>
                            <th style='width:25%;text-align:left'>".__('Product Name', 'quoteup')."</th>
                            <td style='width:75%'>:<a href='{$prod_permalink}'>{$title}</a></td>
                            </tr>";
        if (!empty($variationString)) {
            $product_table .=    "<tr>
                            <th style='width:25%;text-align:left'>".__('Variation', 'quoteup')."</th>
                            <td style='width:75%'>:{$variationString}</td>
                            </tr>";
        }

        $product_table .=    "<tr>
                            <th style='width:25%;text-align:left'>".__('Product Price', 'quoteup')."</th>
                            <td style='width:75%'>:{$price}</td>
                            </tr>
                            ";
        $customer_product_table = "<tr>
                            <th style='width:25%;text-align:left'>".__('Product Name', 'quoteup')."</th>
                            <td style='width:75%'>:<a href='{$prod_permalink}'>{$title}</a></td>
                            </tr>";

        if (!empty($variationString)) {
            $customer_product_table .=    "<tr>
                            <th style='width:25%;text-align:left'>".__('Variation', 'quoteup')."</th>
                            <td style='width:75%'>:{$variationString}</td>
                            </tr>";
        }

        if ($enable_price == 'yes') {
            $customer_product_table .= "<tr>
                            <th style='width:25%;text-align:left'>".__('Product Price', 'quoteup')."</th>
							<td style='width:75%'>:{$price}</td>
                            </tr>
                            ";
        }

        $product_details = serialize($prod);
    }

    return array(
        'product_details' => $product_details,
        'product_table' => $product_table,
        'customer_product_table' => $customer_product_table,
        'authorEmail' => $authorEmail,
    );
}

function forEachProductInfo($arr, $product_table, $customer_product_table)
{
    foreach ($arr as $element) {
        $id = $element[ 'id' ];
        $url = get_permalink($id);
        $product = new WC_Product($id);
        $sku = $product->get_sku();
        if ($element['variation_id'] != '') {
            $product = new WC_Product($element['variation_id']);
            $variation_sku = $product->get_sku();
            if (!empty($variation_sku)) {
                $sku = $variation_sku;
            }
        }
        $enable_price = get_post_meta($id, '_enable_price', true);
        $customer_prod_price = ($enable_price == 'yes') ?  wc_price($element[ 'price' ]) :'-';
        $product_table .= "<tr>
                                    <th colspan='2' style='text-align: justify;'><a href='{$url}'>{$element[ 'title' ]}</a>:</th>
                                    </tr>
                                    <tr>
                                    <td colspan='2'>
                                    <table border='1' cellspacing='0' cellpadding='10' style='text-align: center; border-color: #ddd; width: 60%;'>
                                    <tr>
                                    <th>" . __('Price', 'quoteup') . "</th>
                                    <th>" . __('Quantity', 'quoteup') . "</th>
                                    <th>" . __('Expected Price', 'quoteup') . "</th>
                                    <th>" . __('SKU', 'quoteup') . "</th>";
        $product_table .= "<th>" . __('Variation', 'quoteup') . "</th>";

        $product_table .= "</tr>
                                    <tr>
                                    <td>" . wc_price($element['price']) . "</td>
                                    <td>{$element[ 'quant' ]}</td>
                                    <td>{$element[ 'remark' ]}</td>
                                    <td>{$sku}</td>";
        if ($element['variation'] != '') {
            $variationString = "";
            foreach ($element['variation'] as $attributeName => $attributeValue) {
                $variationString .= "<br>" . wc_attribute_label($attributeName) . ":" . $attributeValue;
            }
            $product_table .= "<td>{$variationString}</td>";
        } else {
            $product_table .= "<td>-</td>";
        }

        $product_table .= "</tr>
                                    </table>
                                    </td>
                                    </tr>";
        $customer_product_table .= "<tr>
                                    <th colspan='2' style='text-align: justify;'><a href='{$url}'>{$element[ 'title' ]}</a>:</th>
                                    </tr>
                                    <tr>
                                    <td colspan='2'>
                                    <table border='1' cellspacing='0' cellpadding='10' style='text-align: center; border-color: #ddd; width: 60%;'>
                                    <tr>
                                    <th>" . __('Price', 'quoteup') . "</th>
                                    <th>" . __('Quantity', 'quoteup') . "</th>
                                    <th>" . __('Expected Price', 'quoteup') . "</th>
                                    <th>" . __('SKU', 'quoteup') . "</th>";
        $customer_product_table .= "<th>" . __('Variation', 'quoteup') . "</th>";

        $customer_product_table .= "</tr>
                                    <tr>
									<td>{$customer_prod_price}</td>
                                    <td>{$element[ 'quant' ]}</td>
                                    <td>{$element[ 'remark' ]}</td>
                                    <td>{$sku}</td>";
        if ($element['variation'] != '') {
            $variationString = "";
            foreach ($element['variation'] as $attributeName => $attributeValue) {
                $variationString .= "<br>" . wc_attribute_label($attributeName) . ":" . $attributeValue;
            }
            $customer_product_table .= "<td>{$variationString}</td>";
        } else {
            $customer_product_table .= "<td>-</td>";
        }

        $customer_product_table .= "</tr>
                                    </table>
                                    </td>
                                    </tr>";
    }

    return array(
        'admin_product_table' => $product_table,
        'customer_product_table' => $customer_product_table,
    );
}

/**
 * Set product details from an array.
 *
 * @param [type] $product_table_and_details [description]
 */
function setProductDetails($product_table_and_details)
{
    if (isset($product_table_and_details[ 'product_details' ])) {
        $product_details = $product_table_and_details[ 'product_details' ];
    } else {
        $product_details = '';
    }

    return $product_details;
}

/**
 * Get IP of client.
 *
 * @return [type] [description]
 */
function getEnquiryIP()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        //check ip from share internet
        $address = $_SERVER[ 'HTTP_CLIENT_IP' ];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        //to check ip is pass from proxy
        $address = $_SERVER[ 'HTTP_X_FORWARDED_FOR' ];
    } else {
        $address = $_SERVER[ 'REMOTE_ADDR' ];
    }

    return $address;
}

/**
 * Send mail to admin on successful enquiry.
 *
 * @param [type] $admin_email        [email id of admin]
 * @param [type] $blg_name           [name of website]
 * @param [type] $form_data_for_mail [description]
 * @param [type] $product_table      [Table of products in enquiry]
 * @param [type] $email              [Site email]
 * @param [type] $name               [admin name]
 * @param [type] $admin_subject      [Subject of mail]
 *
 * @return [type] [description]
 */
function forEachAdminEmails($admin_email, $blg_name, $form_data_for_mail, $product_table, $email, $name, $admin_subject)
{
    global $quoteupEmail;
    $optionData = quoteupSettings();
    do_action('wdm_before_send_admin_email', trim($admin_email));

    if (isset($optionData[ 'enable_disable_quote' ]) && $optionData[ 'enable_disable_quote' ] == 1) {
        $enquiry_email = "<div style=background-color:#ddd>
            <h2 style='text-align:center;margin-bottom:0px !important;padding:10px;border-bottom: 1px solid #ddd;border-top:1px solid #ddd;' >
           <b>".__('Enquiry from', 'quoteup')."  $blg_name </b></h2>
           
           <table style='width: 100%;
                    background: #F7F7F7;
                    border-bottom: 1px solid #ddd;
                    margin-bottom: 0px;
                    'cellspacing='10px'>";
    } else {
        $enquiry_email = "<div style=background-color:#ddd>
            <h2 style='text-align:center;margin-bottom:0px !important;padding:10px;border-bottom: 1px solid #ddd;border-top:1px solid #ddd;' >
           <b>".__('Enquiry And Quote Request From', 'quoteup')."  $blg_name </b></h2>
           
           <table style='width: 100%;
                    background: #F7F7F7;
                    border-bottom: 1px solid #ddd;
                    margin-bottom: 0px;
                    'cellspacing='10px'>";
    }

    //$enquiry_email .= apply_filters('pep_add_custom_field_admin_email', $enquiry_email);
    $enquiry_email = apply_filters('pep_add_custom_field_admin_email', $enquiry_email);
    $enquiry_email = apply_filters('quoteup_add_custom_field_admin_email', $enquiry_email);
    $enquiry_email = apply_filters('pep_before_product_name_in_admin_email', $enquiry_email, $form_data_for_mail);
    $enquiry_email = apply_filters('quoteup_before_product_name_in_admin_email', $enquiry_email, $form_data_for_mail);
    $enquiry_email = apply_filters('pep_before_price_in_admin_email', $enquiry_email, $form_data_for_mail);
    $enquiry_email = apply_filters('quoteup_before_price_in_admin_email', $enquiry_email, $form_data_for_mail);
    $enquiry_email .= $product_table;
    $enquiry_email = apply_filters('pep_after_price_in_admin_email', $enquiry_email, $form_data_for_mail);
    $enquiry_email = apply_filters('quoteup_after_price_in_admin_email', $enquiry_email, $form_data_for_mail);
    $enquiry_email .= '</table>';

    $enquiry_email .= '<div>';
    $admin_headers = array();
    //echo "admin<br>".$enquiry_mail;
    $admin_headers[] = 'Content-Type: text/html; charset=UTF-8';
    $admin_headers[] = 'MIME-Version: 1.0';
    $admin_headers[] = "Reply-to: {$email}";
    //Customer name in From field of email
    $admin_headers[] = 'From:'.$name.' <'.$email.'>'."\r\n";
    $admin_subject = html_entity_decode($admin_subject, ENT_QUOTES, 'UTF-8');
    $enquiry_email = html_entity_decode($enquiry_email, ENT_QUOTES, 'UTF-8');
    $admin_subject = apply_filters('pep_admin_email_subject', $admin_subject);
    $enquiry_email = apply_filters('pep_admin_email_content', $enquiry_email);
    $admin_subject = stripcslashes($admin_subject);
    $enquiry_email = stripcslashes($enquiry_email);

    $quoteupEmail->send($admin_email, apply_filters('quoteup_admin_email_subject', $admin_subject), apply_filters('quoteup_admin_email_content', $enquiry_email), $admin_headers);
}

/**
 * This function is used to set default subject for mail if subject is blank
 * or set the subject entered by customer.
 *
 * @param [type] $subject      [description]
 * @param [type] $email_data   [description]
 * @param [type] $wdm_sitename [description]
 *
 * @return [type] [description]
 */
function adminSubject($subject, $email_data, $wdm_sitename)
{
    if ($subject == '') {
        $admin_subject = $wdm_sitename.$email_data[ 'default_sub' ];
        if ($admin_subject == '') {
            $admin_subject = $wdm_sitename.__('Enquiry or Quote Request for Products from  ', 'quoteup').get_bloginfo('name');
        }
    } else {
        $admin_subject = $wdm_sitename.$subject;
    }

    return $admin_subject;
}

/**
 * Send enquiry copy to customer if he has checked 'send me a copy'.
 *
 * @param [type] $name               [description]
 * @param [type] $blg_name           [name of website]
 * @param [type] $form_data_for_mail [description]
 * @param [type] $admin_subject      [subject for mail]
 * @param [type] $email              [customer email]
 * @param [type] $product_table      [Table of products in enquiry]
 *
 * @return [type] [description]
 */
function sendCopyIfChecked($name, $blg_name, $form_data_for_mail, $admin_subject, $email, $product_table)
{
    global $quoteupEmail;
    $optionData = quoteupSettings();
    if ($_POST[ 'cc' ] == 'checked') {
        $cust_email_name = $name;
        $admin_email_address = get_option('admin_email');
        $client_headers[] = 'Content-Type: text/html; charset=UTF-8';
        $client_headers[] = 'MIME-Version: 1.0';
        $client_headers[] = "Reply-to: {$admin_email_address}";
        //Customer name in From field of email
        //$client_headers[] = 'From:'.$cust_email_name.' <'.$admin_email_address.' >'."\r\n";

        if (isset($optionData[ 'enable_disable_quote' ]) && $optionData[ 'enable_disable_quote' ] == 1) {
            $cust_email_heading = "<div style='background-color:#ddd'><h2 style='text-align:center;margin:0px !important;padding:10px;border-bottom: 1px solid #ddd;border-top:2px solid #ddd;' >
              ".__('Your enquiry at', 'quoteup')." $blg_name </h2>";

            $cust_email = '<b>'.__('Thank you for your enquiry. We will get back to you soon.', 'quoteup').'</b><br><br>';
        } else {
            $cust_email_heading = "<div style='background-color:#ddd'><h2 style='text-align:center;margin:0px !important;padding:10px;border-bottom: 1px solid #ddd;border-top:2px solid #ddd;' >
              ".__('Your enquiry/quote request at', 'quoteup')." $blg_name </h2>";

            $cust_email = '<b>'.__('Thank you for your enquiry/quote request. We will get back to you soon.', 'quoteup').'</b><br><br>';
        }
        $cust_email .= $cust_email_heading;
        $cust_email .= "<table style='width: 100%;
                          background: #f7f7f7;
                          border-bottom: 1px solid #ddd; margin-bottom:0px;' cellspacing='10px'>";

        $cust_email = apply_filters('pep_before_product_name_in_customer_email', $cust_email, $form_data_for_mail);
        $cust_email = apply_filters('quoteup_before_product_name_in_customer_email', $cust_email, $form_data_for_mail);
        $cust_email = apply_filters('pep_add_custom_field_customer_email', $cust_email);
        $cust_email = apply_filters('quoteup_add_custom_field_customer_email', $cust_email);
        $cust_email = $cust_email.$product_table;

        $cust_email .= '</table>';

        $cust_email .= '</div>';

        $admin_subject = html_entity_decode($admin_subject, ENT_QUOTES, 'UTF-8');
        $cust_email = html_entity_decode($cust_email, ENT_QUOTES, 'UTF-8');
        $admin_subject = apply_filters('pep_customer_email_subject', $admin_subject);
        $cust_email = apply_filters('quoteup_customer_email_content', $cust_email);
        $admin_subject = stripcslashes($admin_subject);
        $cust_email = stripcslashes($cust_email);
        $quoteupEmail->send($email, apply_filters('quoteup_customer_email_subject', $admin_subject), apply_filters('quoteup_customer_email_content', $cust_email), $client_headers);  /* 'Product Enquiry' */
    }
}

/*
 * Function to check input currency and return only sale price
 * @param  [string] $original_price Original string containing price.
 * @return [int]                    Sale price
 */
if (!function_exists('getSalePrice')) {
    function getSalePrice($original_price)
    {
        // Trim spaces
        $original_price = trim($original_price);
        // Extract Sale Price
        $price = extractSalePrice($original_price);
        $sanitized_price = filter_var($price, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        if (false == $sanitized_price) {
            return $original_price;
        }

        return $sanitized_price;
    }
}
if (!function_exists('extractSalePrice')) {
    function extractSalePrice($price)
    {
        //Check if more than 1 value is present
        $prices = explode(' ', $price);
        if (count($prices) > 1) {
            return $prices[ 1 ];   // If yes return sale price.
        }

        return $prices[ 0 ]; //  Else return same string.
    }
}

/*
 * To set the global setting option of add_to_cart to individual product. 
 * To set the global setting option of quoteup_enquiry to individual product.
 * To set the global setting option of show price to individual product.
 */

function quoteupSetAddToCartValue()
{
    if (!current_user_can('manage_options')) {
        die('SECURITY_ISSUE');
    }
    $add_to_cart_option = $_POST[ 'option_add_to_cart' ];
    $global_quoteup_enquiry_option = $_POST[ 'option_quoteup_enquiry' ];
    $quoteup_price = $_POST[ 'option_quoteup_price' ];
    if ($global_quoteup_enquiry_option == 'yes') {
        $individual_quoteup_enquiry_option = 'yes';
    }
    if ($global_quoteup_enquiry_option == 'no') {
        $individual_quoteup_enquiry_option = '';
    }

    global $post;

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => '-1',
    );
    $wp_query = new WP_Query($args);

    if ($wp_query->have_posts()) :
        while ($wp_query->have_posts()) :
            $wp_query->the_post();

            $product = get_product($post->ID);
            update_post_meta($post->ID, '_enable_add_to_cart', $add_to_cart_option);
            update_post_meta($post->ID, '_enable_pep', $individual_quoteup_enquiry_option);
            update_post_meta($post->ID, '_enable_price', $quoteup_price);
        endwhile;
    endif;

    die();
    unset($product);
}

if (!function_exists('quoteupModifyUserQuoteData')) {
    function quoteupModifyUserQuoteData()
    {
        if (!wp_verify_nonce($_POST[ 'security' ], 'quoteup')) {
            die('SECURITY_ISSUE');
        }

        if (!current_user_can('manage_options')) {
            die('SECURITY_ISSUE');
        }

        global $wpdb;
        $enq_tbl = $wpdb->prefix.'enquiry_detail_new';
        $name = $_POST[ 'cname' ];
        $email = $_POST[ 'email' ];
        $enquiry_id = $_POST[ 'enquiry_id' ];
        $wpdb->update(
            $enq_tbl,
            array(
            'name' => $name, // string
            'email' => $email, // integer (number)
            ),
            array('enquiry_id' => $enquiry_id),
            array(
            '%s',
            '%s',
            ),
            array('%d')
        );
        echo "Saved Successfully.";
        die;
    }
}

if (!function_exists("wdmSendReply")) {
    function wdmSendReply()
    {
        global $wpdb;

        $wdm_reply_message = $wpdb->prefix.'enquiry_thread';
        $uemail = $_POST[ 'email' ];
        $subject = $_POST[ 'subject' ];
        $message = $_POST[ 'msg' ];
        $enquiryID = $_POST[ 'eid' ];
        $type = 'Y-m-d H:i:s';
        $date = current_time($type);
        $parent = $_POST[ 'parent_id' ];
        $email_data = quoteupSettings();
        $admin_emails = array();
        if ($email_data[ 'user_email' ] != '') {
            $admin_emails = explode(',', $email_data[ 'user_email' ]);
        }
        $admin_emails = array_map('trim', $admin_emails);
        $admin = get_option('admin_email');
        if (!in_array($admin, $admin_emails)) {
            $admin_emails[] = $admin;
        }
        $emails = implode(',', $admin_emails);
        $client_headers[] = 'Content-Type: text/html; charset=UTF-8';
        $client_headers[] = 'MIME-Version: 1.0';
        $client_headers[] = "Reply-to: {$emails}";

        $wpdb->insert(
            $wdm_reply_message,
            array(
            'enquiry_id' => $enquiryID,
            'subject' => $subject,
            'message' => $message,
            'parent_thread' => $parent,
            'date' => $date,
            ),
            array(
            '%d',
            '%s',
            '%s',
            '%d',
            '%s',
            )
        );
        wp_mail($uemail, $subject, $message, $client_headers);
        die();
    }
}
