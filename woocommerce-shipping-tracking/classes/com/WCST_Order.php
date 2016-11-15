<?php 
class WCST_Order
{
	public function __construct()
	{
		add_action('wp_ajax_wcst_get_order_list', array(&$this, 'ajax_get_order_partial_list'));
	}
	public function get_delivery_and_times($order_id)
	{
		$result =  get_post_meta($order_id, '_wcst_order_delivery_datetimes' , true);
		return isset($result) && $result != "" ? $result : array();
	}
	public function save_delivery_date_and_time($order_id, $date_and_time)
	{
		return update_post_meta($order_id, '_wcst_order_delivery_datetimes',$date_and_time);
	}
	 
	public function ajax_get_order_partial_list()
	{
		$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;
		$orders = $this->get_order_list($order_id );
		 echo json_encode( $orders);
		 wp_die();
	}
	private function get_order_list($search_string = null)
	{
		global $wpdb;
		 $query_string = "SELECT orders.ID as order_id, orders.post_date as order_date, orders.post_status as order_status
							 FROM {$wpdb->posts} AS orders
							 WHERE orders.post_type = 'shop_order' ";
		if($search_string)
				$query_string .=  " AND ( orders.ID LIKE '%{$search_string}%' OR  orders.post_date LIKE '%{$search_string}%' OR orders.post_status LIKE '%{$search_string}%')";
		
		$query_string .=  " GROUP BY orders.ID ORDER BY orders.post_date DESC";
		return $wpdb->get_results($query_string );
	}
	public function save_shippings_info_metas($post_id, $data_to_save)
	{
		global $wcst_shipping_company_model;
		/* $post_code = stripslashes( $data_to_save['_billing_postcode']);
		if(isset( $data_to_save['_shipping_postcode']) && !empty( $data_to_save['_shipping_postcode']))
			$post_code = stripslashes( $data_to_save['_shipping_postcode']); */
		$post_code = '';
		
		$info = WCST_shipping_companies_url::get_company_url(stripslashes( $data_to_save['_wcst_order_trackurl'] ), stripslashes( $data_to_save['_wcst_order_trackno'] ), $post_code );
		add_post_meta( $post_id, '_order_key', uniqid('order_') );
		update_post_meta( $post_id, '_wcst_order_trackno', stripslashes( $data_to_save['_wcst_order_trackno'] ));
		update_post_meta( $post_id, '_wcst_order_dispatch_date', stripslashes( $data_to_save['_wcst_order_dispatch_date'] ));
		update_post_meta( $post_id, '_wcst_custom_text', stripslashes( $data_to_save['_wcst_custom_text'] ));
		update_post_meta( $post_id, '_wcst_order_trackname', stripslashes( $wcst_shipping_company_model->get_company_name_by_id($data_to_save['_wcst_order_trackurl']) ));
		update_post_meta( $post_id, '_wcst_order_trackurl', stripslashes( $data_to_save['_wcst_order_trackurl'] ));
		update_post_meta( $post_id, '_wcst_order_track_http_url', stripslashes( $info['urltrack'] ));
		
		//additional
		if(!isset($data_to_save['_wcst_order_additional_shipping']))
		{
			delete_post_meta( $post_id, '_wcst_additional_companies' );
			return;
		}
		
		$addtional_companies_counter = 0;
		$additiona_companies = array();
		foreach($data_to_save['_wcst_order_additional_shipping'] as $additional_company)
		{
			$temp = array();
			$info = WCST_shipping_companies_url::get_company_url(stripslashes( $additional_company['trackurl'] ), stripslashes( $additional_company['trackno'] ), $post_code );
			$temp['_wcst_order_trackno'] = $additional_company['trackno'] ;
			$temp['_wcst_custom_text'] = $additional_company['custom_text'] ;
			$temp['_wcst_order_dispatch_date'] = $additional_company['order_dispatch_date'] ;
			$temp['_wcst_order_trackname'] = stripslashes( $wcst_shipping_company_model->get_company_name_by_id($additional_company['trackurl']) );
			$temp['_wcst_order_trackurl'] = stripslashes( $additional_company['trackurl']);
			$temp['_wcst_order_track_http_url'] = stripslashes( $info['urltrack']);
			array_push($additiona_companies, $temp);
		}
		update_post_meta( $post_id, '_wcst_additional_companies', $additiona_companies );
	}
}
?>