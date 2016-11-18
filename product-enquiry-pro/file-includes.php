<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
require_once(QUOTEUP_PLUGIN_DIR . '/init-quote-cart-session.php');

require_once(QUOTEUP_PLUGIN_DIR . '/includes/class-quoteup-add-sku-field.php');

require_once(QUOTEUP_PLUGIN_DIR . '/includes/class-quoteup-add-custom-field.php');
require_once(QUOTEUP_PLUGIN_DIR . '/shortcodes.php');

require_once(QUOTEUP_PLUGIN_DIR . '/includes/class-quoteup-manage-history.php');

require_once(QUOTEUP_PLUGIN_DIR . '/includes/class-quoteup-manage-expiration.php');

require_once(QUOTEUP_PLUGIN_DIR . '/includes/class-quoteup-add-data-in-db.php');

require_once(QUOTEUP_PLUGIN_DIR . '/includes/class-quoteup-get-data.php');

require_once(QUOTEUP_PLUGIN_DIR . '/includes/class-quoteup-plugin-updater.php');

//Include file which handles Database Operations related to mapping between Order Id and Quote/Enquiry Id
require_once(QUOTEUP_PLUGIN_DIR . '/includes/class-quoteup-order-quote-mapping.php');

//Create and Manage Session
require_once(QUOTEUP_PLUGIN_DIR . '/includes/public/class-quoteup-manage-session.php');

//Include file which handles approval and rejecton of quote
require_once(QUOTEUP_PLUGIN_DIR . '/includes/public/class-quoteup-handle-cart.php');

/*
 * Frontend Files
 */
if ( ! is_admin() && ! defined( 'DOING_CRON' ) ) {


//Include file which handles view of approval and rejecton of quote
require_once(QUOTEUP_PLUGIN_DIR . '/templates/public/class-quoteup-handle-quote-approval-rejection-view.php');

//Include file which handles view of enquiry cart
require_once(QUOTEUP_PLUGIN_DIR . '/templates/public/class-quoteup-handle-enquiry-cart-view.php');


//Include file which displays add to quote button
require_once(QUOTEUP_PLUGIN_DIR . '/includes/public/class-quoteup-display-quote-button.php');

//Include file which displays bubble on the frontend after adding product into the cart
require_once(QUOTEUP_PLUGIN_DIR . '/includes/public/class-quoteup-display-enquiry-cart-bubble.php');

//Include file which handles approval and rejecton of quote
	require_once(QUOTEUP_PLUGIN_DIR . '/includes/public/class-quoteup-handle-quote-approval-rejection.php');
//Include file which handles enquiry cart
require_once(QUOTEUP_PLUGIN_DIR . '/includes/public/class-quoteup-handle-enquiry-cart.php');


}



if ( is_admin() ) {
	//Display meta box on single product page to Enable/Disable Enquiry/Quote button
	require_once(QUOTEUP_PLUGIN_DIR . '/includes/admin/class-quoteup-enable-disable-quoteup-button.php');

	//Display meta box on single product page to Show/hide price on frontend
	require_once(QUOTEUP_PLUGIN_DIR . '/includes/admin/class-quoteup-enable-disable-price.php');

	//Display meta box on single product page to Enable/Disable Add to cart
	require_once(QUOTEUP_PLUGIN_DIR . '/includes/admin/class-quoteup-enable-disable-add-to-cart-button.php');

	//Products Table with extended bulk actions
	require_once(QUOTEUP_PLUGIN_DIR . '/includes/admin/class-quoteup-products-table.php');

	// List Quote Details
	require_once(QUOTEUP_PLUGIN_DIR . '/includes/admin/class-quoteup-quote-details.php');

	// Load Settings Page
	require_once(QUOTEUP_PLUGIN_DIR . '/includes/settings/class-quoteup-settings.php');

	// Load Dashboard Menu
	require_once(QUOTEUP_PLUGIN_DIR . '/includes/admin/class-quoteup-dashboard-menu.php');

	//include file for edit enquiry details feature
	require_once(QUOTEUP_PLUGIN_DIR . '/includes/admin/class-quoteup-quote-details-edit.php');

	//file for pdf generation
	require_once(QUOTEUP_PLUGIN_DIR . '/includes/admin/class-quoteup-generate-pdf.php');

	// file for sending mail with quotation
	require_once(QUOTEUP_PLUGIN_DIR . '/includes/admin/class-quoteup-send-quote-mail.php');

	//file to add history
	require_once(QUOTEUP_PLUGIN_DIR . '/includes/admin/class-quoteup-display-history.php');

	//include file for hover feature
	require_once(QUOTEUP_PLUGIN_DIR . '/includes/admin/class-quoteup-tooltip-on-hover.php');

	//Include file which handles approval and rejecton of quote
	require_once(QUOTEUP_PLUGIN_DIR . '/includes/admin/class-quoteup-handle-quote-approval-rejection.php');

	//Include file which handles Admin Notices for MPE Cart Page
	require_once(QUOTEUP_PLUGIN_DIR . '/includes/admin/class-quoteup-admin-notices.php');
}
