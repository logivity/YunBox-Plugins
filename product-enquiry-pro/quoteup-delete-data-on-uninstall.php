<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// If uninstall is not called from WordPress, exit
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

global $wpdb;

$args        = array(
    'post_type' => 'product',
);
$wp_query    = new WP_Query($args);

if ($wp_query->have_posts()) :
    while ($wp_query->have_posts()) :
        $wp_query->the_post();
        @delete_post_meta($post->ID, '_enable_add_to_cart');
        @delete_post_meta($post->ID, '_disable_quoteup');
        @delete_post_meta($post->ID, '_enable_pep');
        @delete_post_meta($post->ID, '_enable_price');
    endwhile;
endif;

delete_option('edd_quoteup_license_key');
delete_option('edd_pep_license_status');
delete_option('wdm_quoteup_version');
delete_option('wdm_quoteup_product_site');
delete_option('wdm_quoteup_license_key_sites');
delete_option('wdm_quoteup_license_max_site');
delete_transient('wdm_quoteup_license_trans');
delete_option('wdm_form_data');

//find out pdfs older than an hour and delete them
$uploadDir   = wp_upload_dir();
$pdfDir      = $uploadDir[ 'basedir' ] . '/QuoteUp_PDF/';

if (! file_exists($pdfDir)) {
    return;
}
/* * * cycle through all files in the directory ** */
foreach (glob($pdfDir . "*") as $file) {
    unlink($file);
}

/**
 * Delete all tables
 */
global $wpdb;
$tables_to_be_deleted = array(
    'enquiry_detail_new',
    'enquiry_history',
    'enquiry_meta',
    'enquiry_quotation',
    'enquiry_thread',
);

foreach ($tables_to_be_deleted as $single_table) {
    quoteupDeleteTable($wpdb->prefix . $single_table);
}

function quoteupDeleteTable($table_name)
{
    global $wpdb;
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        $sql = "DROP TABLE IF EXISTS $table_name;";
        $wpdb->query($sql);
    }
}
