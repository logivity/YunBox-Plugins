<?php 

class WCST_AdminMenu
{
	var $aftership_url = "http://track.aftership.com/%s";
	var $trackingmore_url = "http://track.trackingmore.com/choose-en-%s.html";
	function __construct() {
	}

	public static function get_shipping_companies_list()
	{
		include WCST_PLUGIN_ABS_PATH.'included_companies/WCST_shipping_companies_list.php';
				
		ksort($shipping_companies);
		
		return $shipping_companies;
	}

	public function render_page() 
	{
		/* $tab_to_render = isset($_REQUEST['tab']) ? $_REQUEST['tab']:'general';
		
		if($tab_to_render == 'add-custom-companies')
			$this->render_add_custom_companies_tab();
		else if($tab_to_render == 'edit-messages')
			$this->render_edit_messages_tab();
		else if($tab_to_render == 'additional-checkout-fields')
			$this->render_delivery_date_time_tab();
		else if($tab_to_render == 'general-options')
			$this->render_general_options_tab();
		else
			$this->render_general_tab(); */
		
		$tab_to_render = isset($_REQUEST['page']) ? $_REQUEST['page']:'wcst-shipping-companies';
		
		if($tab_to_render == 'wcst-add-custom-shipping-company')
			$this->render_add_custom_companies_tab();
		else if($tab_to_render == 'wcst-edit-messages')
			$this->render_edit_messages_tab();
		else if($tab_to_render == 'wcst-delivery-extra-fields')
			$this->render_delivery_date_time_tab();
		else if($tab_to_render == 'wcst-general-options')
			$this->render_general_options_tab();
		else
			$this->render_general_tab();
	}
	public static function get_default_message()
	{
		$default_message =  '<h3>Your Order has been shipped via [shipping_company_name].</h3>';
		$default_message .=	'<strong>Tracking #</strong>[tracking_number]';
		$default_message .=	'<br/>';
		$default_message .=	'<a href="[url_track]" target="_blank" ><strong>CLICK HERE</strong></a> to track your shipment.</a><br/>';
		$default_message .=	'[dispatch_date] <br/>';
		$default_message .=	'[custom_text] <br/>';
		return $default_message;
	}
	public static function get_default_message_additional_shippings()
	{
		$default_message =  '<br/>';
		$default_message .=  '<br/>';
		$default_message .=  'Company name: [additional_shipping_company_name]<br/>';
		$default_message .=  '<strong>Tracking #</strong>[additional_shipping_tracking_number]';
		$default_message .=	'<br/>';
		$default_message .=	'<a href="[additional_shipping_url_track]" target="_blank" ><strong>CLICK HERE</strong></a> to track your shipment.</a><br/>';
		$default_message .=	'[additional_dispatch_date] <br/>';
		$default_message .=	'[additional_custom_text] <br/>';
		return $default_message;
	}
	private function update_custom_companies()
	{
		$companies = array();
		if(isset($_REQUEST['wcst_custom_shipping_company']))
		{
			
			foreach($_REQUEST['wcst_custom_shipping_company'] as $company)
			{
				if(isset($company['name']) && (isset($company['url']) || isset($company['enable_aftership']) || isset($company['enable_trackingmore'])) )
				{
					if(!isset($company['enable_aftership']) && !isset($company['enable_trackingmore']))
						array_push($companies, array("name"=> $company['name'], "url"=>$company['url'], "enable_aftership"=>false));
					else if(isset($company['enable_trackingmore']))
						array_push($companies, array("name"=> $company['name'], "url"=>$this->trackingmore_url, "enable_aftership"=>false,"enable_trackingmore"=>true));
					else
						array_push($companies, array("name"=> $company['name'], "url"=>$this->aftership_url, "enable_aftership"=>true,"enable_trackingmore"=>false));
				}
			}
			update_option( 'wcst_user_defined_companies', $companies );
		}
		else
		{
			delete_option( 'wcst_user_defined_companies');
		}
	}
	private function render_edit_messages_tab()
	{
		$options = new WCST_Option();
		$wpml = new WCST_Wpml();
		
		if(isset($_POST['wcst_template_messages']))
			$options->save_messages($_POST['wcst_template_messages']);
		//$messages = get_option( 'wcst_template_messages');
		$messages = $options->get_messages();
		
		$default_message = WCST_AdminMenu::get_default_message();
		$default_message_additional_shippings = WCST_AdminMenu::get_default_message_additional_shippings();
		
		
		$mail_message = (!isset($messages['wcst_mail_message']) || $messages['wcst_mail_message'] == "") ? $default_message:$messages['wcst_mail_message'];
		$mail_additional_snippet = (!isset($messages['wcst_mail_message_additional_shippings']) || $messages['wcst_mail_message_additional_shippings'] == "") ? $default_message_additional_shippings:$messages['wcst_mail_message_additional_shippings'];
	
	
		$order_details_page_message =  (!isset($messages['wcst_order_details_page_message']) || $messages['wcst_order_details_page_message'] == "" )? $default_message:$messages['wcst_order_details_page_message'];
		$order_additional_snippet = (!isset($messages['wcst_order_details_page_additional_shippings']) || $messages['wcst_order_details_page_additional_shippings'] == "") ? $default_message_additional_shippings:$messages['wcst_order_details_page_additional_shippings'];
	
		wp_enqueue_style( 'wcst-common', WCST_PLUGIN_PATH.'/css/wcst_common.css');
		wp_enqueue_style( 'wcst-admin', WCST_PLUGIN_PATH.'/css/wcst_options.css');
		ob_start();
		?>
		<!-- <div id="icon-themes" class="icon32"><br></div>
		<h2 class="nav-tab-wrapper">
		<a class='nav-tab ' href='?page=woocommerce-shipping-tracking&tab=general'>Shipping Companies</a>
		<a class='nav-tab' href='?page=woocommerce-shipping-tracking&tab=add-custom-companies'>Add Custom Companies</a>
		<a class='nav-tab nav-tab-active' href='?page=woocommerce-shipping-tracking&tab=edit-messages'>Edit Messages</a>
		<a class='nav-tab' href='?page=woocommerce-shipping-tracking&tab=additional-checkout-fields'>Delivery date e time fields</a>
		<a class='nav-tab' href='?page=woocommerce-shipping-tracking&tab=general-options'>General</a>
		</h2> -->
		<div class="wrap white-box">
		<?php if($wpml->is_wpml_active()):?>
			<small class="wcst_notice"><strong><?php _e('NOTE:', 'woocommerce-customers-manager');?></strong> <?php _e('WPML Detected! to translate following texts simply select the language you desire from the upper WPML language selector, edit texts and save!', 'woocommerce-customers-manager');?> </small>
		<?php endif; ?>
		<h2><?php _e('Edit email and "Order details" page messages', 'woocommerce-shipping-tracking');?></h2>
		<!--<form action="options.php" method="post" >-->
		<form action="" method="post" >
		<?php //settings_fields('wcst_template_messages_group'); ?> 
				<div class="input_fields_wrap">
				<p>
				<strong><?php _e('Note:', 'woocommerce-shipping-tracking');?></strong> <?php _e('For main shipping company use the following shortocodes <strong>[shipping_company_name]</strong>, <strong>[tracking_number]</strong>, <strong>[custom_text]</strong>, <strong>[dispatch_date]</strong> and <strong>[url_track]</strong> to display directly into the messages the Shipping company name, the tracking number and the tracking url.', 'woocommerce-shipping-tracking');?>
				<br/>
				<?php _e('For additional shippings, use following shorcodes <strong>[additional_shipping_company_name]</strong>, <strong>[additional_shipping_tracking_number]</strong>, <strong>[additional_custom_text]</strong>, <strong>[additional_dispatch_date]</strong> and <strong>[additional_shipping_url_track]</strong>', 'woocommerce-shipping-tracking');?>
				</p>
				<h2 style="margin-bottom:15px;"><?php _e('Email', 'woocommerce-shipping-tracking');?></h2>
					<label><?php _e('Email message', 'woocommerce-shipping-tracking');?></label>
					<textarea rows="8" cols="100" name="wcst_template_messages[wcst_mail_message]"><?php echo $mail_message;?></textarea>
					<label><?php _e('Additional email message snippet messagge (In case of one or more additional shippings. Will be rendered one per additional shippings).', 'woocommerce-shipping-tracking');?></label>
					<textarea rows="8" cols="100" name="wcst_template_messages[wcst_mail_message_additional_shippings]"><?php echo $mail_additional_snippet;?></textarea>
					<label><?php _e('Email message preview', 'woocommerce-shipping-tracking');?></label>
					<div class="preview_box"><?php echo $mail_message.$mail_additional_snippet;?></div>
				
				<h2 style="margin-bottom:15px;"><?php _e('Order details page', 'woocommerce-shipping-tracking');?></h2>				
					<label><?php _e('Order detail page message', 'woocommerce-shipping-tracking');?></label>
					<textarea rows="8" cols="100"  name="wcst_template_messages[wcst_order_details_page_message]"><?php echo $order_details_page_message;?></textarea>
					<label><?php _e('Additional order detail page snippet messagge (In case of one or more additional shippings. Will be rendered one per additional shippings)', 'woocommerce-shipping-tracking');?></label>
					<textarea rows="8" cols="100" name="wcst_template_messages[wcst_order_details_page_additional_shippings]"><?php echo $order_additional_snippet;?></textarea>
					<label><?php _e('Order detail page message preview', 'woocommerce-shipping-tracking');?></label>
					<div class="preview_box"><?php echo $order_details_page_message.$order_additional_snippet;?></div>
				</div>
				<p class="submit">
					<input type="submit" value="Save Changes" class="button-primary" name="Submit">
				</p>
		</form>
		</div>
		<?php
		echo ob_get_clean();
	}
	private function render_add_custom_companies_tab()
	{
		if ($_SERVER['REQUEST_METHOD'] == 'POST')
			$this->update_custom_companies();
		
		$custom_companies = get_option( 'wcst_user_defined_companies');
		$counter  = 0;
		wp_enqueue_style( 'wcst-common', WCST_PLUGIN_PATH.'/css/wcst_common.css');
		wp_enqueue_style( 'wcst-admin', WCST_PLUGIN_PATH.'/css/wcst_options.css');
		ob_start();
		?>
		<!--<div id="icon-themes" class="icon32"><br></div>
		<h2 class="nav-tab-wrapper">
		<a class='nav-tab ' href='?page=woocommerce-shipping-tracking&tab=general'>Shipping Companies</a>
		<a class='nav-tab nav-tab-active' href='?page=woocommerce-shipping-tracking&tab=add-custom-companies'>Add Custom Companies</a>
		<a class='nav-tab' href='?page=woocommerce-shipping-tracking&tab=edit-messages'>Edit Messages</a>
		<a class='nav-tab' href='?page=woocommerce-shipping-tracking&tab=additional-checkout-fields'>Delivery date e time fields</a>
		<a class='nav-tab' href='?page=woocommerce-shipping-tracking&tab=general-options'>General</a>
		</h2> -->
		<?php if ($_SERVER['REQUEST_METHOD'] == 'POST') echo '<div id="message" class="updated"><p>' . __('Saved successfully.', 'woocommerce-shipping-tracking') . '</p></div>'; ?>
		<div class="wrap white-box">
		
			<h2><?php _e('Custom defined shipping companies', 'woocommerce-shipping-tracking');?></h2>
			<h3><b><?php _e('Add or remove custom shipping companies:', 'woocommerce-shipping-tracking');?></b></h3>
			<p><b><?php _e('NOTE:', 'woocommerce-shipping-tracking');?></b>
			<?php _e('You can create a special URL using the <b>%s</b> string in the address. For example: <i>"http://www.shipping-company.com/?tacking-code:%s"</i><br/>In this way the WCST plugin will include the tracking code directly in the url.', 'woocommerce-shipping-tracking');?>
			<p>
			<br>
			<form method="post" >
				<div class="input_fields_wrap">
				<button class="add_field_button button-primary"><?php _e('Add one more Company', 'woocommerce-shipping-tracking');?></button>
				<?php if($custom_companies):
						foreach($custom_companies as $company): 
						$company['enable_aftership'] = isset($company['enable_aftership']) ? $company['enable_aftership'] : false;
						$company['enable_trackingmore'] = isset($company['enable_trackingmore']) ? $company['enable_trackingmore'] : false;
						?>
						<div class="input_box">
							<label><?php _e('Shipping Company Name:', 'woocommerce-shipping-tracking'); ?> </label>
							<input type="text" value="<?php echo $company['name']; ?>" name="wcst_custom_shipping_company[<?php echo $counter ?>][name]" placeholder="ex. DHL, UPS, ..." required></input>
							<br/>
							<label class="wcst_label"><?php _e('Shipping Company Tracking URL:', 'woocommerce-shipping-tracking'); ?> </label>
							<input  class="wcst_tracking_url_input" value="<?php echo $company['url']; ?>"  type="text" size="80" name="wcst_custom_shipping_company[<?php echo $counter ?>][url]" placeholder="http://trkcnfrm1.smi.usps.com/PTSInternetWeb/InterLabelInquiry.do?origTrackNum=" required></input>
							<button class="remove_field button-secondary"><?php _e('Remove company', 'woocommerce-shipping-tracking');?></button>
							<label class="wcst_label"><?php _e('3rd party tracking services', 'woocommerce-shipping-tracking'); ?> </label>
							<input class="wcst_aftership_checkbox" type="checkbox" value="true" name="wcst_custom_shipping_company[<?php echo $counter ?>][enable_aftership]" <?php if($company['enable_aftership']) echo 'checked="checked"';?>><?php _e('Use Aftership service to track order', 'woocommerce-shipping-tracking'); ?></input><br/>
							<input class="wcst_trackingmore_checkbox" type="checkbox" value="true" name="wcst_custom_shipping_company[<?php echo $counter ?>][enable_trackingmore]" <?php if($company['enable_trackingmore']) echo 'checked="checked"';?>><?php _e('Use TrackingMore service to track order', 'woocommerce-shipping-tracking'); ?></input>
						</div>
				<?php $counter++; endforeach; else: ?>
					<div class="input_box">
						<label><?php _e('Shipping Company Name:', 'woocommerce-shipping-tracking'); ?> </label>
						<input type="text" name="wcst_custom_shipping_company[0][name]" placeholder="ex. DHL, UPS, ..." required></input>
						<br/>
						<label class="wcst_label"><?php _e('Shipping Company Tracking URL:', 'woocommerce-shipping-tracking'); ?> </label>
						<input class="wcst_tracking_url_input" type="text" size="80" name="wcst_custom_shipping_company[0][url]" placeholder="http://trkcnfrm1.smi.usps.com/PTSInternetWeb/InterLabelInquiry.do?origTrackNum=" required></input>
						<button class="remove_field button-secondary"><?php _e('Remove field', 'woocommerce-shipping-tracking');?></button>
						<label class="wcst_label"><?php _e('3rd party tracking services', 'woocommerce-shipping-tracking'); ?> </label>
						<input class="wcst_aftership_checkbox" type="checkbox" value="true" name="wcst_custom_shipping_company[0][enable_aftership]"><?php _e('Use Aftership service to track order', 'woocommerce-shipping-tracking'); ?></input><br/>
						<input class="wcst_trackingmore_checkbox" type="checkbox" value="true" name="wcst_custom_shipping_company[0][enable_trackingmore]"><?php _e('Use TrackingMore service to track order', 'woocommerce-shipping-tracking'); ?></input>
					</div>
				<?php endif ?>
				</div>
				<script>
				jQuery(document).ready(function() 
				{
						var max_fields      = 50; //maximum input boxes allowed
						var wrapper         = jQuery(".input_fields_wrap"); //Fields wrapper
						var add_button      = jQuery(".add_field_button"); //Add button ID
						var x = <?php echo $counter; ?>; //initlal text box count
						
						//init
						wcst_setup_click_managment();
						wcst_tracking_checkbox_click(null);
						
						jQuery(add_button).click(function(e)
						{ //on add input button click
							e.preventDefault();
							if(x < max_fields){ //max input box allowed
								x++; //text box increment
								
								jQuery(wrapper).append(getHtmlTemplate(x)); //add input box
								wcst_setup_click_managment();
							}
						});
					   
						jQuery(wrapper).on("click",".remove_field", function(e){ //user click on remove text
							e.preventDefault(); jQuery(this).parent('div').remove(); x--;
						})
				});
				function wcst_setup_click_managment()
				{
					jQuery('.wcst_aftership_checkbox').on('click',wcst_tracking_checkbox_click);						
					jQuery('.wcst_trackingmore_checkbox').on('click',wcst_tracking_checkbox_click);						
					jQuery('.wcst_aftership_checkbox, .wcst_trackingmore_checkbox').click(wcst_tracking_services_mutually_exclusive_checkbox_managment);
				}
				 function wcst_tracking_services_mutually_exclusive_checkbox_managment(event)
				{
					if(jQuery(event.currentTarget).attr("class") == 'wcst_aftership_checkbox' && jQuery(event.currentTarget).prop('checked'))
						jQuery(event.currentTarget).parent().find('.wcst_trackingmore_checkbox').removeAttr('checked');
					else if(jQuery(event.currentTarget).attr("class") == 'wcst_trackingmore_checkbox' && jQuery(event.currentTarget).prop('checked'))
						jQuery(event.currentTarget).parent().find('.wcst_aftership_checkbox').removeAttr('checked');
						
				}
				function wcst_tracking_checkbox_click(event)
				{
					jQuery('.wcst_aftership_checkbox').each(function(index, elem)
					{
						var elem_to_disable = jQuery(elem).parent().find('.wcst_tracking_url_input');
						var trackingmore_checkbox = jQuery(elem).parent().find('.wcst_trackingmore_checkbox');
						elem_to_disable.prop('disabled', jQuery(elem).prop('checked') || jQuery(trackingmore_checkbox).prop('checked'));
					});
				}
				function getHtmlTemplate(index)
				{
					var template = '<div class="input_box">';
									template += '<label><?php _e('Shipping Company Name:', 'woocommerce-shipping-tracking');?> </label>';
									template += '<input type="text" name="wcst_custom_shipping_company['+index+'][name]" placeholder="ex. DHL, UPS, ..." required></input>';
									template += '<br/>';
									template += '<label class="wcst_label"><?php _e('Shipping Company URL:', 'woocommerce-shipping-tracking');?> </label>';
									template += '<input class="wcst_tracking_url_input" type="text" size="80" name="wcst_custom_shipping_company['+index+'][url]" placeholder="http://trkcnfrm1.smi.usps.com/PTSInternetWeb/InterLabelInquiry.do?origTrackNum=" required></input>';
									template += '<button class="remove_field button-secondary"><?php _e('Remove field', 'woocommerce-shipping-tracking');?></button>';
									template += '<label class="wcst_label"><?php _e('3rd party tracking services', 'woocommerce-shipping-tracking'); ?> </label>';
									template += '<input class="wcst_aftership_checkbox" type="checkbox" value="true" name="wcst_custom_shipping_company['+index+'][enable_aftership]"><?php _e('Use Aftership service to track order', 'woocommerce-shipping-tracking'); ?></input><br/>';
									template += '<input class="wcst_trackingmore_checkbox" type="checkbox" value="true" name="wcst_custom_shipping_company['+index+'][enable_trackingmore]"><?php _e('Use TrackingMore service to track order', 'woocommerce-shipping-tracking'); ?></input>';
								template += '</div>';
								
					return template;
				}
				</script>
				<p class="submit">
					<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'woocommerce-shipping-tracking'); ?>" />
				</p>
			</form>
		</div>
		<?php
		echo ob_get_clean();
	}
	private function render_general_tab()
	{
		$options = get_option( 'wcst_options' );
		$custom_companies = get_option( 'wcst_user_defined_companies');
		$shipping_companies = WCST_AdminMenu::get_shipping_companies_list();
		$favorite = isset($options['favorite']) ? $options['favorite'] : -1;
		//wcst_var_dump($favorite);
		
		wp_enqueue_style( 'wcst-common', WCST_PLUGIN_PATH.'/css/wcst_common.css');
		wp_enqueue_style( 'wcst-admin', WCST_PLUGIN_PATH.'/css/wcst_options.css');
		ob_start();
		?>
		<!--<div id="icon-themes" class="icon32"><br></div>
		<h2 class="nav-tab-wrapper">
		<a class='nav-tab nav-tab-active' href='?page=woocommerce-shipping-tracking&tab=general'>Shipping Companies</a>
		<a class='nav-tab' href='?page=woocommerce-shipping-tracking&tab=add-custom-companies'>Add Custom Companies</a>
		<a class='nav-tab' href='?page=woocommerce-shipping-tracking&tab=edit-messages'>Edit Messages</a>
		<a class='nav-tab' href='?page=woocommerce-shipping-tracking&tab=additional-checkout-fields'>Delivery date e time fields</a>
		<a class='nav-tab' href='?page=woocommerce-shipping-tracking&tab=general-options'>General</a>
		</h2>-->
		<div class="wrap white-box">
			<?php screen_icon("options-general"); ?>
			<h2><?php _e('Select Shipping Companies used to ship products', 'woocommerce-shipping-tracking');?></h2>
			<form action="options.php" method="post"  style="padding-left:20px">
			<?php settings_fields('wcst_shipping_companies_group'); ?> 
			<table cellpadding="10px">
			<?php if( ($favorite > -1 && count($options) > 1) || ( $favorite < 0 && !empty($options) && count($options) > 0)): ?>
				<h3><?php _e('Select default company', 'woocommerce-shipping-tracking');?></h3>
				<select name="wcst_options[favorite]">
				<option value="NOTRACK" <?php if($favorite === 'NOTRACK') echo 'selected="selected"'; ?>> <?php _e('No Tracking', 'woocommerce-shipping-tracking'); ?></option>
				<?php 
					
					foreach( $shipping_companies as $k => $v )
					{
						
						if (isset($options[$k]) == '1') 
						{
							echo '<option value="'.$k.'" ';
							if ($favorite === $k) {
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
							if ($favorite === (string)$index) 
							{
								echo ' selected="selected"';
							}
							echo '>'.$custom_company['name'].'</option>';  
						}
					}
				?>
				</select>
			<?php endif; ?>
			
			
			<h3><b><?php _e('User defined Companies list:', 'woocommerce-shipping-tracking');?></b></h3>
			<p><?php _e('You can add new ones clicking on the "Add custom company" menu link.', 'woocommerce-shipping-tracking');?></p>
			<?php
					//Custom companies
					$i = 0;
					if($custom_companies)
						foreach( $custom_companies as $index => $custom_company )
						{
							if($i%5==0){
								echo '<tr>';
							}
								
							$checked = '';
								
							if(1 == isset($options[$index])){
								$checked = "checked='checked'";
							}
										
							echo "<td><td class='forminp'>
									<input type='checkbox' name='wcst_options[$index]' id='$index' value='1' $checked />
								</td>
								<td scope='row'><label for='$index' >".$custom_company['name']."<br/>(ID: ".$index.")</label></td>
								</td>";
							$i++;
							if($i%5==0){
								echo '</tr>';
							}
						}
					if($i%5!=0){
						echo '</tr>';
					}						
			?>
			</table>
			<h3><b><?php _e('Already defined Companies list:', 'woocommerce-shipping-tracking');?></b></h3>
			
			
				<table cellpadding="10px">
				<?php
										
					$i = 0;
					foreach( $shipping_companies as $k => $v )
					{
						
						if($i%5==0){
							echo '<tr>';
						}
							
						$checked = '';
							
						if(1 == isset($options[$k])){
							$checked = "checked='checked'";
						}
									
						echo "<td><td class='forminp'>
								<input type='checkbox' name='wcst_options[$k]' id='$k' value='1' $checked />
							</td>
							<td scope='row'><label for='$k' >$v<br/>(ID: $k)</label></td>
							</td>";
								
						$i++;
						if($i%5==0){
							echo '</tr>';
						}
					}
					if($i%5!=0){
						echo '</tr>';
					}
						
				?>
				</table>
				<p class="submit">
					<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'woocommerce-shipping-tracking'); ?>" />
				</p>
			</form>
		</div>
		<?php
		echo ob_get_clean();
	}	
	public function render_delivery_date_time_tab()
	{
		$options = new WCST_Option();
		$wpml = new WCST_Wpml();
		if(isset($_POST['wcst_checkout_options']))
			$options->save_checkout_options($_POST['wcst_checkout_options']);
		
		$messages_and_options = $options->get_checkout_options();
		//wccm_var_dump($_POST['wcst_checkout_options']);
		//wccm_var_dump($messages_and_options);
		wp_enqueue_style( 'wcst-common', WCST_PLUGIN_PATH.'/css/wcst_common.css');
		wp_enqueue_style( 'wcst-admin', WCST_PLUGIN_PATH.'/css/wcst_options.css');	
		?>
		<!--<div id="icon-themes" class="icon32"><br></div>
		<h2 class="nav-tab-wrapper">
		<a class='nav-tab ' href='?page=woocommerce-shipping-tracking&tab=general'>Shipping Companies</a>
		<a class='nav-tab' href='?page=woocommerce-shipping-tracking&tab=add-custom-companies'>Add Custom Companies</a>
		<a class='nav-tab' href='?page=woocommerce-shipping-tracking&tab=edit-messages'>Edit Messages</a>
		<a class='nav-tab nav-tab-active' href='?page=woocommerce-shipping-tracking&tab=additional-checkout-fields'>Delivery date e time fields</a>
		<a class='nav-tab' href='?page=woocommerce-shipping-tracking&tab=general-options'>General</a>
		</h2>-->
		<div class="wrap white-box">
		<?php if($wpml->is_wpml_active()):?>
			<small class="wcst_notice"><strong><?php _e('NOTE:', 'woocommerce-customers-manager');?></strong> <?php _e('WPML Detected! to translate following texts simply select the language you desire from the upper WPML language selector, edit texts and save!', 'woocommerce-customers-manager');?> </small>
		<?php endif; ?>
				
			<?php screen_icon("options-general"); ?>
			<form method="post" >
				<h2><?php _e('Visibility', 'woocommerce-shipping-tracking');?></h2>
				<input type="checkbox" name="wcst_checkout_options[options][show_on_checkout_page]" <?php if(isset($messages_and_options['options']['show_on_checkout_page'])) echo 'checked="checked"'; ?>><?php _e('Show on Checkout page', 'woocommerce-shipping-tracking'); ?></input><br/>
				<input type="checkbox" name="wcst_checkout_options[options][show_on_order_details_page]" <?php if(isset($messages_and_options['options']['show_on_order_details_page'])) echo 'checked="checked"'; ?>><?php _e('Show on Order details page', 'woocommerce-shipping-tracking'); ?></input><br/><br/>
					
			
				<h2><?php _e('Delivery date and time', 'woocommerce-shipping-tracking');?></h2>
				<p><?php _e('If enabled, be displayed a date and time selector to let the user to decide an optional date and/or time when receive items', 'woocommerce-shipping-tracking');?></p>
			
				<input type="checkbox" name="wcst_checkout_options[options][date_range]" <?php if(isset($messages_and_options['options']['date_range'])) echo 'checked="checked"'; ?>><?php _e('Display date range', 'woocommerce-shipping-tracking'); ?></input><br/>
				
				<h3><?php _e('Time ranges', 'woocommerce-shipping-tracking');?></h3>
				<input type="checkbox" name="wcst_checkout_options[options][time_range]" <?php if(isset($messages_and_options['options']['time_range'])) echo 'checked="checked"'; ?>><?php _e('Display time range (you can restrict selection using the following starting and ending hours and minutes boxes)', 'woocommerce-shipping-tracking'); ?></input><br/><br/>
				<label><?php _e('Start hour & minute', 'woocommerce-shipping-tracking');?></label>
				<input type="number" name="wcst_checkout_options[options][time_range_start_hour]" min="0" step="1" max="24" value="<?php if(isset($messages_and_options['options']['time_range_start_hour'])) echo $messages_and_options['options']['time_range_start_hour']; else echo 0;?>"></input>
				<input type="number" name="wcst_checkout_options[options][time_range_start_minute]" min="0" step="1" max="59" value="<?php if(isset($messages_and_options['options']['time_range_start_minute'])) echo $messages_and_options['options']['time_range_start_minute'];  else echo 0;?>"></input><br/>
				<label><?php _e('End hour & minute', 'woocommerce-shipping-tracking');?></label>
				<input type="number" name="wcst_checkout_options[options][time_range_end_hour]" min="0" step="1" max="24" value="<?php if(isset($messages_and_options['options']['time_range_end_hour'])) echo $messages_and_options['options']['time_range_end_hour']; else echo 23;?>"></input>
				<input type="number" name="wcst_checkout_options[options][time_range_end_minute]" min="0" step="1" max="59" value="<?php if(isset($messages_and_options['options']['time_range_end_minute'])) echo $messages_and_options['options']['time_range_end_minute']; else echo 59;?>" ></input><br/><br/><br/>
				
				
				<input type="checkbox" name="wcst_checkout_options[options][time_secondary_range]" <?php if(isset($messages_and_options['options']['time_secondary_range'])) echo 'checked="checked"'; ?>><?php _e('Display secondary time range (will be displayed only in previous option has been checked. You can restrict selection using the following starting and ending hours and minutes boxes)', 'woocommerce-shipping-tracking'); ?></input><br/><br/>
				<label><?php _e('Start hour & minute', 'woocommerce-shipping-tracking');?></label>
				<input type="number" name="wcst_checkout_options[options][time_secondary_range_start_hour]" min="0" step="1" max="24" value="<?php if(isset($messages_and_options['options']['time_secondary_range_start_hour'])) echo $messages_and_options['options']['time_secondary_range_start_hour']; else echo 0;?>"></input>
				<input type="number" name="wcst_checkout_options[options][time_secondary_range_start_minute]" min="0" step="1" max="59" value="<?php if(isset($messages_and_options['options']['time_secondary_range_start_minute'])) echo $messages_and_options['options']['time_secondary_range_start_minute']; else echo 0;?>"></input><br/>
				<label><?php _e('End hour & minute', 'woocommerce-shipping-tracking');?></label>
				<input type="number" name="wcst_checkout_options[options][time_secondary_range_end_hour]" min="0" step="1" max="24" value="<?php if(isset($messages_and_options['options']['time_secondary_range_end_hour'])) echo $messages_and_options['options']['time_secondary_range_end_hour']; else echo 23;?>"></input>
				<input type="number" name="wcst_checkout_options[options][time_secondary_range_end_minute]" min="0" step="1" max="59" value="<?php if(isset($messages_and_options['options']['time_secondary_range_end_minute'])) echo $messages_and_options['options']['time_secondary_range_end_minute']; else echo 59;?>"></input><br/><br/>
				
				
				<h2><?php _e('Title, labels and description', 'woocommerce-shipping-tracking');?></h2>
				<p>
					<label><?php _e('Title', 'woocommerce-shipping-tracking'); ?></label>
					<input class="wcst_checkout_tab_input" type="text" name="wcst_checkout_options[messages][title]" value="<?php if(isset($messages_and_options['messages']['title'])) echo $messages_and_options['messages']['title']; ?>" placeholder="<?php _e('Ex.: Additional shipping delivery info', 'woocommerce-shipping-tracking'); ?>"></input>
				<p/>
				
				<p><?php _e('Will be visible only if one of the previews option has been selected.', 'woocommerce-shipping-tracking');?></p>
				<p>
					<label><?php _e('Date range label', 'woocommerce-shipping-tracking'); ?></label>
					<input class="wcst_checkout_tab_input" type="text" name="wcst_checkout_options[messages][date_range]" value="<?php if(isset($messages_and_options['messages']['date_range'])) echo $messages_and_options['messages']['date_range']; ?>" placeholder="<?php _e('Ex.: Select a start and end period.', 'woocommerce-shipping-tracking'); ?>"></input>
				</p>
				<p>
					<label><?php _e('Time range label', 'woocommerce-shipping-tracking'); ?></label>
					<input class="wcst_checkout_tab_input" type="text" name="wcst_checkout_options[messages][time_range]" value="<?php if(isset($messages_and_options['messages']['time_range'])) echo $messages_and_options['messages']['time_range']; ?>" placeholder="<?php _e('Ex.: Select a start and end time period.', 'woocommerce-shipping-tracking'); ?>"></input>
				</p>
				<p>
					<label><?php _e('Seconday time range', 'woocommerce-shipping-tracking'); ?></label>
					<input class="wcst_checkout_tab_input" type="text" name="wcst_checkout_options[messages][time_secondary_range]" value="<?php if(isset($messages_and_options['messages']['time_secondary_range'])) echo $messages_and_options['messages']['time_secondary_range']; ?>" placeholder="<?php _e('Ex.: Select a secondary start and end time period.', 'woocommerce-shipping-tracking'); ?>"></input>
				</p>
				<p>
					<label><?php _e('Description', 'woocommerce-shipping-tracking'); ?></label>
					<textarea class="wcst_checkout_tab_textarea" name="wcst_checkout_options[messages][note]" placeholder="<?php _e('Ex.: Type a preferead Date and time to receive you items, we will do the best to respect your needings.', 'woocommerce-shipping-tracking'); ?>"><?php if(!empty($messages_and_options['messages']['note'])) echo $messages_and_options['messages']['note']; ?></textarea>
				</p>
				
				<p class="submit">
					<input  name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'woocommerce-shipping-tracking'); ?>" />
				</p>
			</fom>
		</div>
		<?php
	}
	public function render_general_options_tab()
	{
		/* $options = new WCST_Option();
		$wpml = new WCST_Wpml(); */
		if(isset($_POST['wcst_general_options']))
			update_option('wcst_general_options', $_POST['wcst_general_options']);
		
		$options = get_option('wcst_general_options');
		$date_format = isset($options['date_format']) ? $options['date_format'] : "dd/mm/yyyy";
		$order_details_page_positioning = isset($options['order_details_page_positioning']) ? $options['order_details_page_positioning'] : "woocommerce_order_details_after_order_table";
		$redirect_method = isset($options['tracking_form_redirect_method']) ? $options['tracking_form_redirect_method'] : "same_page";
		wp_enqueue_style( 'wcst-common', WCST_PLUGIN_PATH.'/css/wcst_common.css');
		wp_enqueue_style( 'wcst-admin', WCST_PLUGIN_PATH.'/css/wcst_options.css');		
		?>
		<!--<div id="icon-themes" class="icon32"><br></div>
		<h2 class="nav-tab-wrapper">
		<a class='nav-tab ' href='?page=woocommerce-shipping-tracking&tab=general'>Shipping Companies</a>
		<a class='nav-tab' href='?page=woocommerce-shipping-tracking&tab=add-custom-companies'>Add Custom Companies</a>
		<a class='nav-tab' href='?page=woocommerce-shipping-tracking&tab=edit-messages'>Edit Messages</a>
		<a class='nav-tab' href='?page=woocommerce-shipping-tracking&tab=additional-checkout-fields'>Delivery date e time fields</a>
		<a class='nav-tab nav-tab-active' href='?page=woocommerce-shipping-tracking&tab=general-options'>General</a>
		</h2>-->
		<div class="wrap white-box">
				
			<?php screen_icon("options-general"); ?>
			<h2><?php _e('General options', 'woocommerce-shipping-tracking');?></h2>
			<form action="" method="post" > <!--options.php -->
			<?php settings_fields('wcst_general_options_group'); ?> 
				
				<h3><?php _e('Email options', 'woocommerce-shipping-tracking');?></h3>
				<p>
					<label><?php _e('Include tracking info only if an order is marked as completed?', 'woocommerce-shipping-tracking'); ?></label>
					<input class="" type="checkbox" name="wcst_general_options[email_options][include_only_if_order_completed]" value="true" <?php  if(isset($options['email_options']['include_only_if_order_completed'])) echo 'checked="checked"';?> ></input>
				<p/>
				<h3><?php _e('Date format', 'woocommerce-shipping-tracking');?></h3>
				<label><?php _e('Select the date format used for dispatch and delivery dates', 'woocommerce-shipping-tracking'); ?></label>
				<select name="wcst_general_options[date_format]">
					<option value="dd/mm/yyyy" <?php if($date_format == "dd/mm/yyyy") echo 'selected="selected"';?>><?php _e('dd/mm/yyyy', 'woocommerce-shipping-tracking');?></option>
					<option value="mm/dd/yyyy" <?php if($date_format == "mm/dd/yyyy") echo 'selected="selected"';?>><?php _e('mm/dd/yyyy', 'woocommerce-shipping-tracking');?></option>
				</select>
				
				<h3><?php _e('Tracking form redirection', 'woocommerce-shipping-tracking');?></h3>
				<label><?php _e('Select redirection method. Using the "Open new tab" method could cause the browse to detect the new tab as a popup.', 'woocommerce-shipping-tracking'); ?></label>
				<select name="wcst_general_options[tracking_form_redirect_method]">
					<option value="same_page" <?php if($redirect_method == "same_page") echo 'selected="selected"';?>><?php _e('Same page', 'woocommerce-shipping-tracking');?></option>
					<option value="new_tab" <?php if($redirect_method == "new_tab") echo 'selected="selected"';?>><?php _e('Open a new tab', 'woocommerce-shipping-tracking');?></option>
				</select>
				
				<h3><?php _e('Tacking info positioning', 'woocommerce-shipping-tracking');?></h3>
				<label><?php _e('Order details page: select where to display tracking info', 'woocommerce-shipping-tracking'); ?></label>
				<select name="wcst_general_options[order_details_page_positioning]">
					<option value="woocommerce_order_details_after_order_table" <?php if($order_details_page_positioning == "woocommerce_order_details_after_order_table") echo 'selected="selected"';?>><?php _e('After order table', 'woocommerce-shipping-tracking');?></option>
					<option value="woocommerce_view_order" <?php if($order_details_page_positioning == "woocommerce_view_order") echo 'selected="selected"';?>><?php _e('Before order table', 'woocommerce-shipping-tracking');?></option>
				</select>
				
				
				<p class="submit">
					<input  name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'woocommerce-shipping-tracking'); ?>" />
				</p>
			</fom>
		</div>
		<?php
	}
}
?>