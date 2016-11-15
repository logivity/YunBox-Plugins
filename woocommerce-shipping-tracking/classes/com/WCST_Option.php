<?php 
class WCST_Option
{
	public function __construct(){}
	
	public function get_messages($default = null , $lang_code = null )
	{
		//WPML
		global $sitepress;
		
		$options = get_option( 'wcst_template_messages');
		$result = isset($options) ? $options: $default;
		
		if(isset($result))
		{
			if(class_exists('SitePress') && ( (isset($lang_code) && $lang_code != $sitepress->get_default_language()) || ICL_LANGUAGE_CODE != $sitepress->get_default_language()))
			{
				$current_lang = isset($lang_code) ? $lang_code : ICL_LANGUAGE_CODE;
				//$current_lang = ICL_LANGUAGE_CODE;
				
				$result[$current_lang]['wcst_mail_message'] = (!isset($result[$current_lang]['wcst_mail_message']) || $result[$current_lang]['wcst_mail_message'] == "") ? "":stripslashes($result[$current_lang]['wcst_mail_message']);
				$result[$current_lang]['wcst_mail_message_additional_shippings'] = (!isset($result[$current_lang]['wcst_mail_message_additional_shippings']) || $result[$current_lang]['wcst_mail_message_additional_shippings'] == "") ? "":stripslashes($result[$current_lang]['wcst_mail_message_additional_shippings']);
					
				$result[$current_lang]['wcst_order_details_page_message'] =  (!isset($result[$current_lang]['wcst_order_details_page_message']) || $result[$current_lang]['wcst_order_details_page_message'] == "" )? "":stripslashes($result[$current_lang]['wcst_order_details_page_message']);
				$result[$current_lang]['wcst_order_details_page_additional_shippings'] = (!isset($result[$current_lang]['wcst_order_details_page_additional_shippings']) || $result[$current_lang]['wcst_order_details_page_additional_shippings'] == "") ? "":stripslashes($result[$current_lang]['wcst_order_details_page_additional_shippings']);
				
				return isset($result[$current_lang]) ? $result[$current_lang] : $default ;
			}
			else
			{
				  $result['wcst_mail_message'] = (!isset($result['wcst_mail_message']) || $result['wcst_mail_message'] == "") ? "":stripslashes($result['wcst_mail_message']);
				  $result['wcst_mail_message_additional_shippings'] = (!isset($result['wcst_mail_message_additional_shippings']) || $result['wcst_mail_message_additional_shippings'] == "") ? "":stripslashes($result['wcst_mail_message_additional_shippings']);
					
				  $result['wcst_order_details_page_message'] =  (!isset($result['wcst_order_details_page_message']) || $result['wcst_order_details_page_message'] == "" )? "":stripslashes($result['wcst_order_details_page_message']);
				  $result['wcst_order_details_page_additional_shippings'] = (!isset($result['wcst_order_details_page_additional_shippings']) || $result['wcst_order_details_page_additional_shippings'] == "") ? "":stripslashes($result['wcst_order_details_page_additional_shippings']);
		
			}
		}
		return $result;
	}
	public function save_messages($value)
	{
		global $sitepress;
		$options = get_option('wcst_template_messages');
		if(class_exists('SitePress') && ICL_LANGUAGE_CODE != $sitepress->get_default_language())
		{
			if(isset($options) && is_array($options))
				$options[ICL_LANGUAGE_CODE] = $value;
			else
			{
				$options = array();
				$options[ICL_LANGUAGE_CODE] = $value;
			}
		}
		else			
		{
			if(isset($options) && is_array($options))
				$options = array_merge($options, $value);
			else
				$options = $value;
		}
		update_option( 'wcst_template_messages', $options);
	}
	public function save_checkout_options($new_values)
	{
		global $sitepress;
		$options = get_option('wcst_checkout_options');
		if(!isset($options) || !is_array($options))
			$options = array();
		
		
		$temp_value = array();
		if(class_exists('SitePress') && ICL_LANGUAGE_CODE != $sitepress->get_default_language())
		{
			$temp_value = array();
			$temp_value[ICL_LANGUAGE_CODE] = array();
			$temp_value[ICL_LANGUAGE_CODE]['messages'] = $new_values['messages'];
			$temp_value['options'] = $new_values['options'];
		}
		else $temp_value = $new_values;
		
		$options = array_merge($options, $temp_value);
		update_option( 'wcst_checkout_options', $options);
	}
	public function get_checkout_options($default = null , $lang_code = null)
	{
		//WPML
		global $sitepress;
		$options = get_option( 'wcst_checkout_options');
		$result = isset($options) ? $options: $default;
		if(isset($result))
		{
			if(class_exists('SitePress') && ( (isset($lang_code) && $lang_code != $sitepress->get_default_language()) || ICL_LANGUAGE_CODE != $sitepress->get_default_language()))
			{
				$current_lang = isset($lang_code) ? $lang_code : ICL_LANGUAGE_CODE;
				//$current_lang = ICL_LANGUAGE_CODE;
				$result['messages'] = !empty($result[$current_lang]['messages']) ? $result[$current_lang]['messages'] : array();
			}
			$result['messages']['date_range'] = (!isset($result['messages']['date_range']) || $result['messages']['date_range'] == "") ? __('Select a date range', 'woocommerce-shipping-tracking'):stripslashes($result['messages']['date_range']);
			$result['messages']['time_range'] = (!isset($result['messages']['time_range']) || $result['messages']['time_range'] == "") ? __('Select a time range', 'woocommerce-shipping-tracking'):stripslashes($result['messages']['time_range']);
				
			$result['messages']['time_secondary_range'] =  (!isset($result['messages']['time_secondary_range']) || $result['messages']['time_secondary_range'] == "" )? __('Select a secondary time range', 'woocommerce-shipping-tracking'):stripslashes($result['messages']['time_secondary_range']);
			$result['messages']['title'] = (!isset($result['messages']['title']) || $result['messages']['title'] == "") ? __('Select a preferred delivery date and time', 'woocommerce-shipping-tracking'):stripslashes($result['messages']['title']);
			$result['messages']['note'] = (!isset($result['messages']['note']) || $result['messages']['note'] == "") ? "":stripslashes($result['messages']['note']);	
		}
		return $result;
	}
	public function get_option($option_name = 'wcst_options', $option_key = null, $default_value = null)
	{
		$result =  get_option( $option_name );
		if($option_key && isset($result))
			return isset($result[$option_key]) ? $result[$option_key] : $default_value;
		
		return isset($result) ? $result : $default_value;
	}
	public function get_general_options()
	{
		return get_option('wcst_general_options');
	}
	function cl_acf_set_language() 
	{
	  return acf_get_setting('default_language');
	}
	public function get_estimations_options($option_name = null, $default_value = null)
	{
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		
		$all_data = array();
		$all_data['hour_offset'] = get_field('wcst_hour_offset', 'option'); 
		$all_data['estimated_shipping'] = array(); 
	
		if( have_rows('wcst_shippings', 'option') )
			while ( have_rows('wcst_shippings', 'option') ) 
			{
				the_row();
				$estimated_shipping = array();
				$estimated_shipping['non_working_days'] = array();
				$estimated_shipping['name_id'] = get_sub_field('wcst_name_id', 'option'); //Check if value exists: if( $value )
				$estimated_shipping['products'] = get_sub_field('wcst_products', 'option'); 
				$estimated_shipping['categories'] = get_sub_field('wcst_categories', 'option'); 
				$estimated_shipping['children_categories'] = get_sub_field('wcst_children_categories', 'option'); 
				$estimated_shipping['working_days'] = get_sub_field('wcst_working_days', 'option'); 
				$estimated_shipping['day_cut_off_hour'] = get_sub_field('wcst_day_cut_off_hour', 'option'); 
				$estimated_shipping['days_delay'] = get_sub_field('wcst_days_delay', 'option'); 
			
				
				if( have_rows('wcst_non_working_days', 'option') )
					while ( have_rows('wcst_non_working_days', 'option') ) 
					{
						the_row();
						$non_working_day = array();
						$non_working_day['day'] = get_sub_field('wcst_day', 'option'); 
						$non_working_day['month'] = get_sub_field('wcst_month', 'option'); 
						
						$estimated_shipping['non_working_days'][] = $non_working_day;
					}
					
				$all_data['estimated_shipping'][] = $estimated_shipping;
			}
			
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		if(isset($option_name))
		{
			return isset($all_data[$option_name]) ? $all_data[$option_name] : $default_value;
		}
		
		/* Format
		  ["non_working_days"]=>
			  array(2) {
				[0]=>
				array(2) {
				  ["day"]=>
				  string(1) "1"
				  ["month"]=>
				  string(1) "3"
				}
				[1]=>
				array(2) {
				  ["day"]=>
				  string(1) "1"
				  ["month"]=>
				  string(2) "11"
				}
			  }
			  ["name_id"]=>
			  string(4) "Test"
			  ["products"]=>
			  array(1) {
				[0]=>
				int(12)
			  }
			  ["categories"]=>
			  array(1) {
				[0]=>
				int(11)
			  },
			  ["children_categories"]=>
				  string(13) "selected_only"
			  ["working_days"]=>
			  array(2) {
				[0]=>
				string(1) "2"
				[1]=>
				string(1) "5"
			  }
			  ["day_cut_off_hour"]=>
			  string(1) "0"
			  ["days_delay"]=>
			  string(1) "0"
			}
			*/
		
		return $all_data;
	}
}
?>