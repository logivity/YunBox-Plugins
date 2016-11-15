<?php
/*
Plugin Name: 快递跟踪
Description: 快递跟踪.
Author: woocommerce中文网
Version: 5.2
*/


/* 
Copyright: WooCommerce Shipping Tracking uses the ACF PRO plugin. ACF PRO files are not to be used or distributed outside of the WooCommerce Shipping Tracking plugin.
*/

//define('WCST_PLUGIN_PATH', WP_PLUGIN_URL."/".dirname( plugin_basename( __FILE__ ) ) );
define('WCST_PLUGIN_PATH', rtrim(plugin_dir_url(__FILE__), "/") );
define('WCST_PLUGIN_ABS_PATH', plugin_dir_path( __FILE__ ) );

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) 
{
	include_once( "classes/com/WCST_Acf.php");
	
	load_plugin_textdomain('woocommerce-shipping-tracking', false, basename( dirname( __FILE__ ) ) . '/languages' );
	if(!class_exists('WCST_AdminMenu'))
		require_once('classes/admin/WCST_AdminMenu.php');
	if(!class_exists('WCST_Option'))
		require_once('classes/com/WCST_Option.php');
	if(!class_exists('WCST_Wpml'))
		require_once('classes/com/WCST_Wpml.php');
	if(!class_exists('WCST_shipping_companies_url'))
		require_once('included_companies/WCST_shipping_companies_url.php');
	if(!class_exists('WCST_Order'))
		require_once('classes/com/WCST_Order.php');
	$wcst_order_model = new WCST_Order();
	
	if(!class_exists('WCST_Email'))
		require_once('classes/com/WCST_Email.php');
	$wcst_email_model = new WCST_Email();
	
	if(!class_exists('WCST_ShippingCompany'))
	{	require_once('classes/com/WCST_ShippingCompany.php');
		$wcst_shipping_company_model = new WCST_ShippingCompany();
	}
	if(!class_exists('WCST_HtmlHelper'))
	{	require_once('classes/com/WCST_HtmlHelper.php');
		$wcst_html_helper = new WCST_HtmlHelper();
	}
	if(!class_exists('WCST_Shortcodes'))
	{	require_once('classes/com/WCST_Shortcodes.php');
		$wcst_shortcodes = new WCST_Shortcodes();
	}
	if(!class_exists('WCST_Time'))
	{	require_once('classes/com/WCST_Time.php');
		$wcst_time_model = new WCST_Time();
	}
	if(!class_exists('WCST_Product'))
	{	require_once('classes/com/WCST_Product.php');
		$wcst_product_model = new WCST_Product();
	}
	if(!class_exists('WCST_Tracking_info_displayer'))
	{
		require_once('classes/com/WCST_Tracking_info_displayer.php');
		$wcst_tracking_info_displayer = new WCST_Tracking_info_displayer();
	}
	if(!class_exists('WCST_WooCommerceAddon'))
	{
		require_once('classes/admin/WCST_WooCommerceAddon.php');
		$wcst_woocommerce_addon = new WCST_WooCommerceAddon(); 
	}
	if(!class_exists('WCST_Dashboard'))
	{
		require_once('classes/admin/WCST_Dashboard.php');
		$wcst_wdashboard = new WCST_Dashboard(); 
	}
	if(!class_exists('WCST_ExtraDelivery'))
	{
		require_once('classes/admin/WCST_ExtraDelivery.php');
		$wcst_extra_delivery = new WCST_ExtraDelivery();
	}
	if(!class_exists('WCST_EstimatorConfigurator'))
	{
		require_once('classes/admin/WCST_EstimatorConfigurator.php');
		/* $wcst_estimator_configurator = new WCST_EstimatorConfigurator(); */
	}
	if(!class_exists('WCST_QuickAssignPage'))
	{
		require_once('classes/admin/WCST_QuickAssignPage.php');
	}
	
	add_action('admin_menu', 'init_wcst_admin_panel');
	add_action( 'admin_init', 'register_settings');
	add_action( 'init', 'load_styles');
	
}


function register_settings()
{
	register_setting('wcst_shipping_companies_group','wcst_options');
	register_setting('wcst_general_options_group','wcst_general_options');
	register_setting('wcst_template_messages_group','wcst_template_messages');
	//wp_enqueue_script('wcst-js', WCST_PLUGIN_PATH.'/js/wcst.js', array('jquery')); 
}

function load_styles()
{
	wp_enqueue_style('wcst-style',WCST_PLUGIN_PATH.'/css/wcst_style.css');  
}
function wcst_get_free_menu_position($start, $increment = 0.1)
{
	foreach ($GLOBALS['menu'] as $key => $menu) {
		$menus_positions[] = $key;
	}
	
	if (!in_array($start, $menus_positions)) return $start;

	/* the position is already reserved find the closet one */
	while (in_array($start, $menus_positions)) 
	{
		$start += $increment;
	}
	return $start;
}
function init_wcst_admin_panel()
{
	$place = wcst_get_free_menu_position(54, 0.5);
	
	add_menu_page( __( 'Shipping tracking', 'woocommerce' ), __( '航运跟踪', 'woocommerce' ), 'manage_woocommerce', 'wcst-shipping-tracking', null, WCST_PLUGIN_PATH."/img/menu-icon.png", (string)$place );
	add_submenu_page('wcst-shipping-tracking', __('Shipping companies','woocommerce-shipping-tracking'), __('Shipping companies','woocommerce-shipping-tracking'), 'manage_woocommerce', 'wcst-shipping-companies', 'wcst_render_option_page');
	//add_submenu_page('woocommerce', __('Shipping tracking options','woocommerce-shipping-tracking'), __('Shipping tracking options','woocommerce-shipping-tracking'), 'edit_shop_orders', 'woocommerce-shipping-tracking', 'wcst_render_option_page');
	add_submenu_page('wcst-shipping-tracking', __('Add custom company','woocommerce-shipping-tracking'), __('Add custom company','woocommerce-shipping-tracking'), 'manage_woocommerce', 'wcst-add-custom-shipping-company', 'wcst_render_option_page');
	add_submenu_page('wcst-shipping-tracking', __('Edit emails/order page messages','woocommerce-shipping-tracking'), __('Edit emails/order page messages','woocommerce-shipping-tracking'), 'manage_woocommerce', 'wcst-edit-messages', 'wcst_render_option_page');
	add_submenu_page('wcst-shipping-tracking', __('Delivery date and time input fields','woocommerce-shipping-tracking'), __('Delivery date and time input fields','woocommerce-shipping-tracking'), 'manage_woocommerce', 'wcst-delivery-extra-fields', 'wcst_render_option_page');
	add_submenu_page('wcst-shipping-tracking', __('General options','woocommerce-shipping-tracking'), __('General options','woocommerce-shipping-tracking'), 'manage_woocommerce', 'wcst-general-options', 'wcst_render_option_page');
	add_submenu_page('wcst-shipping-tracking', __('Quick assign','woocommerce-shipping-tracking'), __('Quick assign','woocommerce-shipping-tracking'), 'manage_woocommerce', 'wcst-quick-assign', 'wcst_render_wcst_quick_assign_page');
	
	remove_submenu_page( 'wcst-shipping-tracking', 'wcst-shipping-tracking');
	
	$wcst_estimator_configurator = new WCST_EstimatorConfigurator();
}
function wcst_render_wcst_quick_assign_page()
{
	$page = new WCST_QuickAssignPage();
	$page->render_page();
}
function wcst_render_option_page()
{
	
	$page = new WCST_AdminMenu();
	$page->render_page();
}
function wcst_var_dump($var)
{
	echo "<pre>";
	var_dump($var);
	echo "</pre>";
}
?>