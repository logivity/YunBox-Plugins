<?php 
class WCST_QuickAssignPage
{
	public function __construct(){}
	
	private function save_data()
	{
		global $wcst_order_model, $wcst_email_model;
		$wcst_order_model->save_shippings_info_metas($_POST['order_id'], $_POST);
		$order = new WC_Order($_POST['order_id']);
		
		if($_POST['order_status'] != 'no')
		{
			$order->update_status($_POST['order_status']);
		}
		if($_POST['order_action'] != 'do_not_resend_emails')
		{
			 $wcst_email_model->force_status_email_sending($_POST['order_action'], $order);
		}
	}
	public function render_page()
	{
		if(isset($_POST) && isset($_POST['order_id']))
		{
			$this->save_data();
			echo '<div id="message" class="updated">'.__('Shipping info added', 'woocommerce-shipping-tracking').'</div>';
		}
		
		global $wcst_html_helper;
		wp_enqueue_style( 'wcst-common', WCST_PLUGIN_PATH.'/css/wcst_common.css');
		wp_enqueue_style( 'wcst-admin', WCST_PLUGIN_PATH.'/css/wcst_options.css');
		wp_enqueue_style( 'wcst-select2-style',  WCST_PLUGIN_PATH.'/css/select2/select2.css' ); 

		wp_enqueue_script('wcst-quick-assing-load-orders', WCST_PLUGIN_PATH.'/js/wcst_quick_assign_load_orders.js', array('jquery'));		
		?>
		<script>
			jQuery.fn.select2=null;
			function ignoreerror()
			{
			   return true
			}
			window.onerror=ignoreerror();
		</script>
		<script type='text/javascript' src='<?php echo WCST_PLUGIN_PATH.'/js/select2/select2.min.js'; ?>'></script>
		
		
		<div class="wrap white-box">
		<form action="" method="post" >
			<h1 ><?php _e('How it works?', 'woocommerce-shipping-tracking');?></h1>
			<p><?php _e('In this section in just 3 step you can easily assign one or more shipping tracking info to an order. NOTE: older order info will be overwritten. Leave empty to clear an order shipping info data.', 'woocommerce-shipping-tracking');?></p>
			<h2 class="wcst_title_with_border"><?php _e('1. Select order', 'woocommerce-shipping-tracking');?></h2>
			<select class="js-data-orders-ajax" id="wcst_select2_order_id" name="order_id" required="required"> </select>
			
			<h2 class="wcst_title_with_border"><?php _e('2. Assign a shipping', 'woocommerce-shipping-tracking');?></h2>
			<?php $wcst_html_helper->render_shipping_companies_tracking_info_configurator_widget(); ?>
		
			<h2 class="wcst_title_with_border"><?php _e('3. Change order status?', 'woocommerce-shipping-tracking');?></h2>
			<p><?php _e('If the order hits for the first time a status, a notification email will be send automatically to the user', 'woocommerce-shipping-tracking');?></p>
			<select id="wcst_select_order_status" name="order_status" required="required"> 
			<option value="no"><?php _e('Leave unchanged', 'woocommerce-shipping-tracking');?></option>
			<?php foreach(wc_get_order_statuses() as $code => $status): ?>
					<option value="<?php echo $code; ?>"><?php echo $status; ?></option>
			<?php endforeach; ?>
			</select>
			<p>
			<label><?php _e('Resend order emails? (useful to force email sending if an order already hitted a status)', 'woocommerce-shipping-tracking');?></label>
			<select name="order_action">
					<option value="do_not_resend_emails"><?php _e('No', 'woocommerce-shipping-tracking');?></option>
					<option value="send_email_new_order"><?php _e('New order', 'woocommerce-shipping-tracking');?></option>
					<option value="send_email_cancelled_order"><?php _e('Cancelled order', 'woocommerce-shipping-tracking');?></option>
					<option value="send_email_customer_processing_order"><?php _e('Processing order', 'woocommerce-shipping-tracking');?></option>
					<option value="send_email_customer_completed_order"><?php _e('Completed order', 'woocommerce-shipping-tracking');?></option>
					<option value="send_email_customer_refunded_order"><?php _e('Refunded order', 'woocommerce-shipping-tracking');?></option>
					<option value="send_email_customer_invoice"><?php _e('Customer invoice', 'woocommerce-shipping-tracking');?></option>					
				
			</select>
			</p>
									
			<p class="submit">
						<input type="submit" value="Save Changes" class="button-primary" name="Submit">
					</p>
			</form>
		</div>
		<?php
	}
}
?>