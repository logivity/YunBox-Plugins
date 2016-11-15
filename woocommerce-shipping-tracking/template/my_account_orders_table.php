<script>
var orders_id_to_url = new Array(); 
<?php foreach($track_urls as $oder_id => $url_array) 
{
	if($url_array === 'false')
		echo "orders_id_to_url['{$oder_id}'] = \"{$url_array}\"; ";
	else
	{
		?>
		if(typeof orders_id_to_url['<?php echo $oder_id?>'] === 'undefined')
			orders_id_to_url['<?php echo $oder_id?>'] = new Array();
		<?php foreach($url_array as $url): ?>
			orders_id_to_url['<?php echo $oder_id?>'].push('<?php echo $url?>');
	<?php   endforeach;
	}
}
?>
jQuery(document).ready(function()
{
	jQuery('table.shop_table.my_account_orders tbody tr.order').each(function(index)
	{
		var order_num = jQuery(this).find('td.order-number a').html();
		var main_element = jQuery(this).find('td.order-actions');
		order_num = order_num.replace('#',' ');
		order_num = order_num.replace(/\s/g, '');
		if(orders_id_to_url[order_num] !== 'false')
		{
			//jQuery(this).find('td.order-actions').prepend('<a href="'+orders_id_to_url[order_num]+'" class="button wcst-myaccount-tracking-button" target="_blank"><?php _e('Track shipment', 'woocommerce-shipping-tracking'); ;?></a>');
			var last_element = null;
			//jQuery.each(orders_id_to_url[order_num], function(index, value)
			for(var i=0; i< orders_id_to_url[order_num].length; i++)
			{
				var value = orders_id_to_url[order_num][i];
				var button_element = jQuery('<a href="'+value+'" class="button wcst-myaccount-tracking-button" target="_blank"><?php _e('Track shipment', 'woocommerce-shipping-tracking');?></a>');
				//if(last_element == null)
					main_element.prepend(button_element);
				/* else
					button_element.after(last_element);
				
				last_element = button_element; */
			}
			//);
		}
	});
});
</script>