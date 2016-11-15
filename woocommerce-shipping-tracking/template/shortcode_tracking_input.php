<?php 
$unique_id = rand ( 25684598 , 25684598698547 );
?>
<script>
var wcst_tracking_code_empty_error = "<?php _e('Please fill the tracking code input field.','woocommerce-shipping-tracking');?>";
var wcst_ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
var wcst_redirection_method = '<?php echo $redirection_method; ?>';
</script>
<div id="wcst_tracking_form_<?php echo $unique_id; ?>" class="wcst_tracking_form">
	<?php if($company_id != 'none'): ?>
	<input type="hidden" value="<?php echo $company_id; ?>" id="wcst_shipping_company_<?php echo $unique_id; ?>"></input>
	<?php else: ?>
	<label class="wcst_shipping_company_select_label"><?php _e('Select a company:','woocommerce-shipping-tracking');?></label>
	<select class="wcst_shipping_company_select" id="wcst_shipping_company_<?php echo $unique_id; ?>" class="" >
		<?php 
		//Default companies
		foreach( (array)$shipping_companies['default_companies'] as $index => $default_company )
		{
			echo '<option value="'.$index.'" ';
			echo '>'.$default_company['name'].'</option>'; 
		}
		//Custom companies
		foreach( (array)$shipping_companies['custom_companies'] as $index => $custom_company )
		{
			echo '<option value="'.$index.'" ';
			echo '>'.$custom_company['name'].'</option>';  
		}
		?>
	</select>
	<?php endif; ?>

	<label class="wcst_tracking_code_input_label"><?php _e('Type the tracking code:','woocommerce-shipping-tracking');?></label>
	<input type="text" class="wcst_tracking_code_input" id="wcst_tracking_code_input_<?php echo $unique_id; ?>"></input>
	<button class="wcst_tracking_code_button button" id="wcst_tracking_code_button_<?php echo $unique_id; ?>" data-id="<?php echo $unique_id; ?>"><?php _e('Track','woocommerce-shipping-tracking');?></button>
</div>
<div id="wcst_loading_<?php echo $unique_id; ?>" class="wcst_loading" style="background-image: url('<?php echo WCST_PLUGIN_PATH;?>/img/loader.gif');"></div>
