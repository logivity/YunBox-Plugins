<?php 
class WCST_ShippingCompany
{
	public function __construct()
	{
		//ajax
		add_action('wp_ajax_nopriv_wcst_get_tracking_url', array(&$this, 'ajax_get_tracking_url'));
		add_action('wp_ajax_wcst_get_tracking_url', array(&$this, 'ajax_get_tracking_url'));
	}
	public function get_company_name_by_id($id)
	{
		$shipping_companies = WCST_AdminMenu::get_shipping_companies_list();
		$custom_companies = get_option( 'wcst_user_defined_companies');
		foreach( $shipping_companies as $k => $v )
		{
			if ($k == $id) 
				return $v;
		}
		//Custom companies
		if(isset($custom_companies) && is_array($custom_companies))
			foreach( $custom_companies as $index => $custom_company )
			{
				if ($index == $id) 
					return $custom_company['name'];
			}
		
		return null;
	}
	public function get_all_selected_comanies()
	{
		$option_model = new WCST_Option();
		$company_id = isset($atts['company_id']) && $atts['company_id'] != "" ? $atts['company_id'] : false;
		$shipping_companies = WCST_AdminMenu::get_shipping_companies_list();
		$custom_companies = get_option( 'wcst_user_defined_companies');
		$options = $option_model->get_option();
		
		$result = array('custom_companies' => array(), 'default_companies' => array());
		//Default companies
		foreach( (array)$shipping_companies as $k => $v )
		{
			if (isset($options[$k]) == '1')  //$options[$k] means that the company can be showed. $shipping_companies contains the complete companies list
			{
				$result['default_companies'][$k]['name'] = $v;
			}
			
		}
		//Custom companies
		foreach( (array)$custom_companies as $index => $custom_company )
		{
			if (isset($options[$index]) == '1' && isset($custom_company['name']) && $custom_company['name'] != "") //$options[$index] means that the company can be showed.
			{
				$result['custom_companies'][$index]['name'] = $custom_company['name']; 
			}
		}
		
		return $result;
	}
	public function ajax_get_tracking_url()
	{
		$tracking_code = isset($_POST['tracking_code']) ? $_POST['tracking_code'] : 'none';
		$tracking_company_id = isset($_POST['tracking_company_id']) ? $_POST['tracking_company_id'] : 'none';
		if($tracking_code != 'none')
		{
			$info = WCST_shipping_companies_url::get_company_url(stripslashes( $tracking_company_id ), stripslashes($tracking_code ) );
			echo $info['urltrack'];
		}
		wp_die();
	}
}
?>