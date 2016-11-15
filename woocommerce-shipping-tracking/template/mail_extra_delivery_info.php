<?php 

	if(isset($delivery_info['date_start_range']) && $delivery_info['date_start_range']!="")
	{
		?>
			<strong><?php  _e('Delivery date range', 'woocommerce-shipping-tracking');  ?></strong><br/>
			<span>
				<?php if(isset($delivery_info['date_start_range'])) echo __('From: ','woocommerce-shipping-tracking').$delivery_info['date_start_range']; ?><br/>
				<?php if(isset($delivery_info['date_end_range'])) echo __('To: ','woocommerce-shipping-tracking').$delivery_info['date_end_range']; ?><br/>
			</span>			
		<?php
	}
	if(isset($delivery_info['time_start_range']) && $delivery_info['time_start_range']!="")
	{
		?>
			<strong><?php _e('Delivery time range: ', 'woocommerce-shipping-tracking'); ?></strong><br/>
			<span>
					<?php if(isset($delivery_info['time_start_range'])) echo __('From: ','woocommerce-shipping-tracking').$delivery_info['time_start_range']; ?><br/>
					<?php if(isset($delivery_info['time_end_range'])) echo __('To: ','woocommerce-shipping-tracking').$delivery_info['time_end_range']; ?><br/>
			</span>
		<?php
	}
	if(isset($delivery_info['time_secondary_start_range']) && $delivery_info['time_secondary_start_range']!="")
	{
		?>
			<strong><?php _e('Delivery secondary time range: ', 'woocommerce-shipping-tracking'); ?></strong><br/>
			<span>
				<?php if(isset($delivery_info['time_secondary_start_range'])) echo __('From: ','woocommerce-shipping-tracking').$delivery_info['time_secondary_start_range']; ?><br/>
				<?php if(isset($delivery_info['time_secondary_end_range'])) echo __('To: ','woocommerce-shipping-tracking').$delivery_info['time_secondary_end_range']; ?><br/>
			</span>
		<?php
	}
?>