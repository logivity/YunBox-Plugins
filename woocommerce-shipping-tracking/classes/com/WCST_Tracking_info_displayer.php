<?php 
class WCST_Tracking_info_displayer
{
	public function __construct()
	{
		$option = new WCST_Option();
		//add_action( 'woocommerce_order_details_after_order_table', array( &$this, 'track_page_shipping_details' ) );
		//add_action( 'woocommerce_view_order', array( &$this, 'track_page_shipping_details' ) );
		add_action( $option->get_option('wcst_general_options', 'order_details_page_positioning', 'woocommerce_order_details_after_order_table'), array( &$this, 'track_page_shipping_details' ) );
		add_action( 'woocommerce_email_before_order_table', array( &$this, 'email_shipping_details' ) );
	}
	function track_page_shipping_details( $order )
	{
			$order_id = !is_numeric($order) ? $order->id : $order;
			$order = !is_numeric($order) ? $order : new WC_Order($order_id);
			$order_meta = get_post_custom( $order_id );
					
			if(isset($order_meta['_wcst_order_trackno']) && isset($order_meta['_wcst_order_trackurl']) && isset($order_meta['_wcst_order_trackname']))
				$this->shipping_details( $order_meta, $order);
					
	} 
	function email_shipping_details( $order ) 
	{
				
		$order_meta = get_post_custom( $order->id );
					
		
		if(isset($order_meta['_wcst_order_trackno']) && isset($order_meta['_wcst_order_trackurl']) && isset($order_meta['_wcst_order_trackname']))
			$this->shipping_details($order_meta, $order, true);
				
	}
	function shipping_details($order_meta, $order,$is_email = false){
			
			
			$options_model = new WCST_Option();
			//$options = get_option( 'wcst_options' );
			//$options = $options_model->get_option( 'wcst_options' );
			$options =  $options_model->get_option( 'wcst_general_options', 'email_options');
			$urltrack = $order_meta['_wcst_order_track_http_url'][0];
			$shipping_company_name =  $order_meta['_wcst_order_trackname'][0];
			$default_message = WCST_AdminMenu::get_default_message();
			$default_message_additional = WCST_AdminMenu::get_default_message_additional_shippings();
			$order_meta_additional_shippings = isset($order_meta['_wcst_additional_companies']) ? unserialize(array_shift($order_meta['_wcst_additional_companies'])):null;
			
			if ($order_meta['_wcst_order_trackno'][0] != null &&  $order_meta['_wcst_order_trackurl'][0] != null &&  $order_meta['_wcst_order_trackurl'][0] != 'NOTRACK' ) 
			{
				if($is_email )
				{
					/* wcst_var_dump($order->get_status( ));
					wcst_var_dump($order->get_status( ) == "completed"); 
					wcst_var_dump($options);*/
					if(!isset($options['include_only_if_order_completed']) || $order->get_status( ) == "completed") //$order->has_status( 'complete' )
					{
						$lang = isset($order_meta['wpml_language']) ? $order_meta['wpml_language'][0] : null; 
						include WCST_PLUGIN_ABS_PATH.'/template/mail.php';
					}
				}
				else
					include WCST_PLUGIN_ABS_PATH.'/template/order_details.php';
			}
		}
}
?>