<script>
	var wcst_date_error_message = "<?php _e('Check start and end date. Start date cannot be greater than end.','woocommerce-shipping-tracking'); ?>";
	var wcst_time_error_message = "<?php _e('Check start and end time. Start time cannot be greater than end.','woocommerce-shipping-tracking'); ?>";
	var wcst_secondary_error_message = "<?php _e('Check secondary start and end time. Start time cannot be greater than end.','woocommerce-shipping-tracking'); ?>";
	var wcst_date_format = "<?php echo $date_format; ?>";
	var wcst_time_range_start_hour = "<?php if(isset($messages_and_options['options']['time_range_start_hour'])) echo $messages_and_options['options']['time_range_start_hour']; else echo 0;?>";
	var wcst_time_range_start_minute = "<?php if(isset($messages_and_options['options']['time_range_start_minute'])) echo $messages_and_options['options']['time_range_start_minute'];  else echo 0;?>";
	var wcst_time_range_end_hour = "<?php if(isset($messages_and_options['options']['time_range_end_hour'])) echo $messages_and_options['options']['time_range_end_hour']; else echo 23;?>";
	var wcst_time_range_end_minute = "<?php if(isset($messages_and_options['options']['time_range_end_minute'])) echo $messages_and_options['options']['time_range_end_minute']; else echo 59;?>";
	var wcst_time_secondary_range_start_hour = "<?php if(isset($messages_and_options['options']['time_secondary_range_start_hour'])) echo $messages_and_options['options']['time_secondary_range_start_hour']; else echo 0;?>";
	var wcst_time_secondary_range_start_minute = "<?php if(isset($messages_and_options['options']['time_secondary_range_start_minute'])) echo $messages_and_options['options']['time_secondary_range_start_minute']; else echo 0;?>";
	var wcst_time_secondary_range_end_hour = "<?php if(isset($messages_and_options['options']['time_secondary_range_end_hour'])) echo $messages_and_options['options']['time_secondary_range_end_hour']; else echo 23;?>";
	var wcst_time_secondary_range_end_minute = "<?php if(isset($messages_and_options['options']['time_secondary_range_end_minute'])) echo $messages_and_options['options']['time_secondary_range_end_minute']; else echo 59;?>";
</script>
<form method="post" action="" id="wcst_order_details_form">
<?php 
	$render_save_button = (isset($delivery_info['date_start_range']) && $delivery_info['date_start_range'] != "") && 
						  (isset($delivery_info['date_end_range']) && $delivery_info['date_end_range'] != "") &&
						  (isset($delivery_info['time_start_range']) && $delivery_info['time_start_range'] != "") &&
						 (isset($delivery_info['time_end_range']) && $delivery_info['time_end_range'] != "") &&
						 (isset($delivery_info['time_secondary_start_range']) && $delivery_info['time_secondary_start_range'] != "") &&
						 (isset($delivery_info['time_secondary_end_range']) && $delivery_info['time_secondary_end_range'] != "") ? false  : true;
						  ;
	if(isset($messages_and_options['options']['date_range']) || isset($messages_and_options['options']['time_range']))
	{
		?><h4 class="" style="margin-bottom:5px;  margin-top:15px;"><?php echo $messages_and_options['messages']['title']; ?></h4> <?php
		if(isset($messages_and_options['messages']['note']))
		{
			?>
				<p  class="form-row form-row form-row-wide">
					<?php echo $messages_and_options['messages']['note']; ?>
				</p>					
			<?php
		}
	}
	
	if(isset($messages_and_options['options']['date_range']))
	{
		?>
			<p  class="form-row form-row form-row-wide">
				<label><?php echo $messages_and_options['messages']['date_range']; ?></label>
				<input type="text" id="wcst_start_date_range" class="wcst_input_date" name="wcst_delivery[date_start_range]" value="<?php if(isset($delivery_info['date_start_range'])) echo $delivery_info['date_start_range']; ?>" <?php if(isset($delivery_info['date_start_range']) && $delivery_info['date_start_range'] != "") echo 'disabled ="disabled"';?>></input>
				<input type="text" id="wcst_end_date_range" class="wcst_input_date" name="wcst_delivery[date_end_range]" value="<?php if(isset($delivery_info['date_end_range'])) echo $delivery_info['date_end_range']; ?>" <?php if(isset($delivery_info['date_end_range']) && $delivery_info['date_end_range'] != "") echo 'disabled ="disabled"';?>></input>
			</p>					
		<?php
	}
	if(isset($messages_and_options['options']['time_range']))
	{
		?>
		<p  class="form-row form-row form-row-wide">
			<label><?php echo $messages_and_options['messages']['time_range']; ?></label>
			<input type="text" id="wcst_start_time_range" class="wcst_input_time" name="wcst_delivery[time_start_range]" value="<?php if(isset($delivery_info['time_start_range'])) echo $delivery_info['time_start_range']; ?>" <?php if(isset($delivery_info['time_start_range']) && $delivery_info['time_start_range'] != "") echo 'disabled ="disabled"';?>></input>
			<input type="text" id="wcst_end_time_range" class="wcst_input_time" name="wcst_delivery[time_end_range]" value="<?php if(isset($delivery_info['time_end_range'])) echo $delivery_info['time_end_range']; ?>" <?php if(isset($delivery_info['time_end_range']) && $delivery_info['time_end_range'] != "") echo 'disabled ="disabled"';?>></input>
		</p>
		<?php
	}
	if(isset($messages_and_options['options']['time_range']) && isset($messages_and_options['options']['time_secondary_range']))
	{
		?>
		<p  class="form-row form-row form-row-wide">
			<label><?php  echo $messages_and_options['messages']['time_secondary_range']; ?></label>
			<input type="text" id="wcst_start_time_secondary_range" class="wcst_input_time" name="wcst_delivery[time_secondary_start_range]" value="<?php if(isset($delivery_info['time_secondary_start_range'])) echo $delivery_info['time_secondary_start_range']; ?>" <?php if(isset($delivery_info['time_secondary_start_range']) && $delivery_info['time_secondary_start_range'] != "") echo 'disabled ="disabled"';?>></input>
			<input type="text" id="wcst_end_time_secondary_range" class="wcst_input_time" name="wcst_delivery[time_secondary_end_range]" value="<?php if(isset($delivery_info['time_secondary_end_range'])) echo $delivery_info['time_secondary_end_range']; ?>" <?php if(isset($delivery_info['time_secondary_end_range']) && $delivery_info['time_secondary_end_range'] != "") echo 'disabled ="disabled"';?>></input>
		</p>
		<?php
	} 
			
?>
<?php if($render_save_button): ?><input type="submit" class="button" value="<?php _e('Save', 'woocommerce-files-upload'); ?>"></input><?php endif; ?>
</form> 