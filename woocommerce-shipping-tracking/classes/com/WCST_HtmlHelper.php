<?php 
class WCST_HtmlHelper
{
	public function __construct()
	{
	}
	function shipping_dropdown_options($data, $options, $already_shifted = false, $part = '')
		{ 
		 
			if ($part == '0' || $part == '' ) {
				$part = '';
			}
			
			$no_company_selected = 0;
			foreach($data as $key => $value)
				if(strpos('_wcst_order_trackurl', $key) !== false)
					$no_company_selected++;
			$no_company_selected = $no_company_selected > 0 ? false:true;
			
			if(!$already_shifted)
			{
				if(isset($data['_wcst_order_trackurl'.$part][0]))
					$data['_wcst_order_trackurl'.$part] = $data['_wcst_order_trackurl'.$part][0];
				else
					$data['_wcst_order_trackurl'.$part] = null;
			}
			$favorite = isset($options['favorite']) ? $options['favorite'] : "-1";
			$shipping_companies = WCST_AdminMenu::get_shipping_companies_list();
			$custom_companies = get_option( 'wcst_user_defined_companies');
			
			foreach( $shipping_companies as $k => $v )
			{
				if (isset($options[$k]) == '1') 
				{
					echo '<option value="'.$k.'" ';
					if ( ($no_company_selected && $favorite === $k) || (isset($data['_wcst_order_trackurl'.$part]) && $data['_wcst_order_trackurl'.$part] == $k)) {
						echo 'selected="selected"';
					}
					echo '>'.$v.'</option>';  
				}
				
			}
			//Custom companies
			foreach( $custom_companies as $index => $custom_company )
			{
				if (isset($options[$index]) == '1') 
				{
					echo '<option value="'.$index.'" ';
					if ( ($no_company_selected && $favorite===(string)$index) || (isset($data['_wcst_order_trackurl'.$part]) && $data['_wcst_order_trackurl'.$part] == $index.''))
					{
						echo 'selected="selected"';
					}
					echo '>'.$custom_company['name'].'</option>';  
				}
			}
			
		}
		function render_shipping_companies_tracking_info_configurator_widget($post = null) 
		{
			$option_model = new WCST_Option();
			$general_options = $option_model->get_general_options();
			$date_format = isset($general_options['date_format']) ? $general_options['date_format'] : "dd/mm/yyyy";
		
			$data = isset($post) ? get_post_custom( $post->ID ) : array();
			$options = $option_model->get_option();//get_option( 'wcst_options' );
			$style1 = 'style="display: none"';
			$btn1 = '';
			//wccm_var_dump($data);
			if( isset( $data['_wcst_order_trackno1'][0]) && $data['_wcst_order_trackno1'][0] != '' ){
				$style1 = '';
				$btn1 = 'style="display: none"';
			}
			$index_additiona_companies = 0;
			if(isset($data['_wcst_additional_companies']))
			{
				$additiona_companies = unserialize(array_shift($data['_wcst_additional_companies']));
				//var_dump($additiona_companies); //additiona_companies
			}
			
			wp_enqueue_style('wcst-datepicker-classic', WCST_PLUGIN_PATH.'/css/datepicker/classic.css');   
			wp_enqueue_style('wcst-datepicker-date-classic', WCST_PLUGIN_PATH.'/css/datepicker/classic.date.css');   
			wp_enqueue_style('wcst-datepicker-time-classic', WCST_PLUGIN_PATH.'/css/datepicker/classic.time.css');  
			wp_enqueue_style('wcst-order-detail', WCST_PLUGIN_PATH.'/css/datepicker/classic.time.css'); 
			wp_enqueue_style('wcst-shipping-companies-info-widget',  WCST_PLUGIN_PATH.'/css/wcst_shipping_companies_tracking_info_configurator_widget.css');
			
			wp_enqueue_script('wcst-ui-picker', WCST_PLUGIN_PATH.'/js/datepicker/picker.js', array( 'jquery' ));
			wp_enqueue_script('wcst-ui-datepicker', WCST_PLUGIN_PATH.'/js/datepicker/picker.date.js', array( 'jquery' ));
			wp_enqueue_script('wcst-ui-datepicker', WCST_PLUGIN_PATH.'/js/datepicker/picker.date.js', array( 'jquery' ));
			wp_enqueue_script('wcst-add-additional-company', WCST_PLUGIN_PATH. '/js/wcst-additional-companies.js' ,	array( 'jquery' ));
			wp_enqueue_script('wcst-order-details', WCST_PLUGIN_PATH.'/js/wcst-order-details.js',	array( 'jquery' ));
			?>
			<div id="sdetails">
				<ul class="totals">
					<li>
						<label  style="display:block; clear:both;"><?php _e('Shipping Company:', 'woocommerce-shipping-tracking'); ?></label>
						<select style="margin-bottom:15px;" id="_wcst_order_trackurl" name="_wcst_order_trackurl" >
							<option value="NOTRACK" <?php if ( isset($data['_wcst_order_trackurl'][0]) && $data['_wcst_order_trackurl'][0] == 'NOTRACK') {
								echo 'selected="selected"';
							} ?>><?php _e('No Tracking', 'woocommerce-shipping-tracking'); ?></option>
							<?php $this->shipping_dropdown_options( $data, $options ); ?>
						</select>
					</li>
					<li>
						<label style="display:block; clear:both;"><?php _e('Tracking Number:', 'woocommerce-shipping-tracking'); ?></label>
						<input style="margin-bottom:15px;" type="text" id="_wcst_order_trackno" name="_wcst_order_trackno" placeholder="<?php _e('Enter Tracking No', 'woocommerce-shipping-tracking'); ?>" value="<?php if (isset($data['_wcst_order_trackno'][0])) echo $data['_wcst_order_trackno'][0]; ?>" class="first" />
					</li>
					<li>
						<label  style="display:block; clear:both;"><?php _e('Dispatch date', 'woocommerce-shipping-tracking'); ?></label>
						<input style="margin-bottom:15px;" type="text" class="wcst_dispatch_date" id="_wcst_order_dispatch_date" name="_wcst_order_dispatch_date" placeholder="<?php _e('19/02/15 or 15th December 2015', 'woocommerce-shipping-tracking'); ?>" value="<?php if (isset($data['_wcst_order_dispatch_date'][0])) echo $data['_wcst_order_dispatch_date'][0]; ?>"  />
					</li>
					<li>
						<label style="display:block; clear:both;"><?php _e('Custom text', 'woocommerce-shipping-tracking'); ?></label>
						<textarea style="margin-bottom:15px;" type="text"  name="_wcst_custom_text" placeholder="<?php _e('Info about the shipped item(s) or whatever you want', 'woocommerce-shipping-tracking'); ?>" rows="4"><?php if (isset($data['_wcst_custom_text'][0])) echo $data['_wcst_custom_text'][0]; ?></textarea>
					</li>
				</ul>
			</div>
			<h4><?php _e('Additional tracking codes', 'woocommerce-shipping-tracking'); ?></h4>
			<div id="wcst-additional-shippings">
				<?php if(isset($additiona_companies))
						foreach($additiona_companies as $company):
					//var_dump($company);?>
					<div id="wcst-additiona-shipping-box-<?php echo $index_additiona_companies?>">
						<ul class="totals">
							<li>
								<label style="display:block; clear:both;"><?php _e('Shipping Company:', 'woocommerce-shipping-tracking'); ?></label>								
								<select style="margin-bottom:15px;" name="_wcst_order_additional_shipping[<?php echo $index_additiona_companies?>][trackurl]"  >
									<option value="NOTRACK" <?php if ( isset($company['_wcst_order_trackurl']) && $company['_wcst_order_trackurl'] == 'NOTRACK') {
										echo 'selected="selected"';
									} ?>><?php _e('No Tracking', 'woocommerce-shipping-tracking'); ?></option>
									<?php $this->shipping_dropdown_options( $company, $options, true ); ?>
								</select>
							</li>
							<li>
								<label style="display:block; clear:both;"><?php _e('Tracking Number:', 'woocommerce-shipping-tracking'); ?></label>
								<input style="margin-bottom:15px;"type="text" name="_wcst_order_additional_shipping[<?php echo $index_additiona_companies?>][trackno]" placeholder="<?php _e('Enter Tracking No', 'woocommerce-shipping-tracking'); ?>" value="<?php if (isset($company['_wcst_order_trackno'])) echo $company['_wcst_order_trackno']; ?>" class="first" />
							</li>
							<li>
								<label style="display:block; clear:both;"><?php _e('Dispatch date', 'woocommerce-shipping-tracking'); ?></label>
								<input style="margin-bottom:15px;" type="text" class="wcst_dispatch_date" name="_wcst_order_additional_shipping[<?php echo $index_additiona_companies?>][order_dispatch_date]" placeholder="<?php _e('19/02/16 or 15th Dec 2016', 'woocommerce-shipping-tracking'); ?>" value="<?php if (isset($company['_wcst_order_dispatch_date'])) echo $company['_wcst_order_dispatch_date']; ?>"  />
							</li>
							<li>
								<label style="display:block; clear:both;"><?php _e('Custom text', 'woocommerce-shipping-tracking'); ?></label>
								<textarea style="margin-bottom:15px;" type="text" class="wcst_custom_text" name="_wcst_order_additional_shipping[<?php echo $index_additiona_companies?>][custom_text]" placeholder="<?php _e('Info about the shipped item(s) or whatever you want', 'woocommerce-shipping-tracking'); ?>" rows="4"> <?php if (isset($company['_wcst_custom_text'])) echo $company['_wcst_custom_text']; ?></textarea>
							</li>
						</ul>
						<button class="button wcst-remove-shipping" data-id="<?php echo $index_additiona_companies?>"> <?php _e('Remove', 'woocommerce-shipping-tracking'); ?></button>
					</div>
				<?php $index_additiona_companies++; endforeach; ?>
			</div>
			<div class="clear"></div>
			<button class="button" id="wcst-additional-shipping-button"><?php _e('Add another', 'woocommerce-shipping-tracking'); ?></button>
			<script>
			var wcst_index = <?php echo $index_additiona_companies; ?>;
			var wcst_date_format = "<?php echo $date_format; ?>";
			function wcst_get_template(index)
			{
				var wcst_add_shipping_company_template = '<div id="wcst-additiona-shipping-box-'+index+'">';
					wcst_add_shipping_company_template += '	<ul class="totals">';
					wcst_add_shipping_company_template += '		<li>';
					wcst_add_shipping_company_template += '			<label style="display:block; clear:both;"><?php _e('Shipping Company:', 'woocommerce-shipping-tracking'); ?></label>';
					wcst_add_shipping_company_template += '			<select style="margin-bottom:15px;" name="_wcst_order_additional_shipping['+index+'][trackurl]" >';
					wcst_add_shipping_company_template += '				<option value="NOTRACK"><?php _e('No Tracking', 'woocommerce-shipping-tracking'); ?></option>';
					wcst_add_shipping_company_template += '				<?php $this->shipping_dropdown_options( $data, $options); ?>';
					wcst_add_shipping_company_template += '			</select>';
					wcst_add_shipping_company_template += '		</li>';
					wcst_add_shipping_company_template += '		<li>';
					wcst_add_shipping_company_template += '			<label style="display:block; clear:both;"><?php _e('Tracking Number:', 'woocommerce-shipping-tracking'); ?></label>';
					wcst_add_shipping_company_template += '			<input style="margin-bottom:15px;" type="text" name="_wcst_order_additional_shipping['+index+'][trackno]" placeholder="<?php _e('Enter Tracking No', 'woocommerce-shipping-tracking'); ?>" value="" class="first" />';
					wcst_add_shipping_company_template += '		</li>';
					wcst_add_shipping_company_template += '		<li>';
					wcst_add_shipping_company_template += '			<label style="display:block; clear:both;"><?php _e('Dispatch date', 'woocommerce-shipping-tracking'); ?></label>';
					wcst_add_shipping_company_template += '			<input style="margin-bottom:15px;" class="wcst_dispatch_date" type="text" name="_wcst_order_additional_shipping['+index+'][order_dispatch_date]" placeholder="<?php _e('19/02/15 or 15th December 2015', 'woocommerce-shipping-tracking'); ?>"   />';
					wcst_add_shipping_company_template += '		</li>';
					wcst_add_shipping_company_template += '		<li>';
					wcst_add_shipping_company_template += '			<label style="display:block; clear:both;"><?php _e('Custom text', 'woocommerce-shipping-tracking'); ?></label>';
					wcst_add_shipping_company_template += '			<textarea style="margin-bottom:15px;" type="text" class="wcst_custom_text" name="_wcst_order_additional_shipping['+index+'][custom_text]" placeholder="<?php _e('Info about the shipped item(s) or whatever you want', 'woocommerce-shipping-tracking'); ?>" rows="4" />';
					wcst_add_shipping_company_template += '		</li>';
					wcst_add_shipping_company_template += ' 	</ul>';
					wcst_add_shipping_company_template += ' 	<button class="button wcst-remove-shipping" data-id="'+index+'"> <?php _e('Remove', 'woocommerce-shipping-tracking'); ?></button>';
					wcst_add_shipping_company_template += '	</div>';
				return wcst_add_shipping_company_template;
			}
			</script>
			<?php 
		}	
}
?>