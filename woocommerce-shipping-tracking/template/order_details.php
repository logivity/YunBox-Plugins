<?php 
$shipping_traking_num = $order_meta['_wcst_order_trackno'][0];
$dispatch_date = isset($order_meta['_wcst_order_dispatch_date'][0]) ? $order_meta['_wcst_order_dispatch_date'][0] : "" ;
$custom_text = isset($order_meta['_wcst_custom_text'][0]) ? $order_meta['_wcst_custom_text'][0] : "";
$shipping_company_name;
//$messages = get_option( 'wcst_template_messages');
$messages = $options_model->get_messages();
$order_detail_message = (!isset($messages['wcst_order_details_page_message']) || $messages['wcst_order_details_page_message'] == "") ? $default_message:$messages['wcst_order_details_page_message'];
$order_detail_message_additional = "";

$order_detail_message = str_replace("[shipping_company_name]", $shipping_company_name, $order_detail_message);
$order_detail_message = str_replace("[url_track]", $urltrack, $order_detail_message);
$order_detail_message = str_replace("[tracking_number]", $shipping_traking_num, $order_detail_message);
$order_detail_message = str_replace("[dispatch_date]", $dispatch_date, $order_detail_message);
$order_detail_message = str_replace("[custom_text]", $custom_text, $order_detail_message);

if($order_meta_additional_shippings)
{
	foreach($order_meta_additional_shippings as $additiona_shipping)
	{
		$order_detail_message_additional .= (!isset($messages['wcst_order_details_page_additional_shippings']) || $messages['wcst_order_details_page_additional_shippings'] == "") ? $default_message_additional:$messages['wcst_order_details_page_additional_shippings'];
		
		$urltrack = $additiona_shipping['_wcst_order_trackno'];
		$dispatch_date = isset($additiona_shipping['_wcst_order_dispatch_date']) ? $additiona_shipping['_wcst_order_dispatch_date'] : "" ;
		$shipping_company_name =  $additiona_shipping['_wcst_order_trackname'];
		$shipping_traking_num = $additiona_shipping['_wcst_order_track_http_url'];
		$custom_text = isset($additiona_shipping['_wcst_custom_text']) ? $additiona_shipping['_wcst_custom_text'] : "";
		
		$order_detail_message_additional = str_replace("[additional_shipping_company_name]", $shipping_company_name, $order_detail_message_additional);
		$order_detail_message_additional = str_replace("[additional_shipping_tracking_number]", $urltrack, $order_detail_message_additional);
		$order_detail_message_additional = str_replace("[additional_shipping_url_track]", $shipping_traking_num, $order_detail_message_additional);
		$order_detail_message_additional = str_replace("[additional_dispatch_date]", $dispatch_date, $order_detail_message_additional);
		$order_detail_message_additional = str_replace("[additional_custom_text]", $custom_text, $order_detail_message_additional);
	}
}
echo '<div class="tracking-box">';
echo $order_detail_message.$order_detail_message_additional;
echo '</div>';
?>
	
				