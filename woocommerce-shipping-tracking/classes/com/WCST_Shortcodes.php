<?php 
class WCST_Shortcodes
{
	public function __construct()
	{
		add_shortcode( 'wcst_show_estimated_date', array(&$this, 'display_estimated_date' ));
		add_shortcode( 'wcst_tracking_form', array(&$this, 'display_tracking_form' ));
	}
	public function display_estimated_date($atts)
	{
		$parameters = shortcode_atts( array(
        'product_id' => get_the_ID(),
			), $atts );
			
		if(!isset($parameters['product_id']))
			return "";
		
		global $wcst_product_model,$wcst_time_model;
		
		$estimated_date = $wcst_time_model->get_available_date($wcst_product_model->get_estimation_shippment_rule());	
		
		ob_start();
		echo $estimated_date;
		return ob_get_clean();
	}
	public function display_tracking_form($atts)
	{
		global $wcst_shipping_company_model;
		$options = new WCST_Option();
		$redirection_method = $options->get_option('wcst_general_options','tracking_form_redirect_method', 'same_page');
		$shipping_companies = $wcst_shipping_company_model->get_all_selected_comanies();
		$company_id = isset($atts['company_id']) && $atts['company_id'] != "" ? $atts['company_id'] : 'none';
		
		wp_enqueue_script('wcst-shortcode-tracking-input', WCST_PLUGIN_PATH.'/js/wcst-shortcode-tracking-input.js', array( 'jquery' ));
		wp_enqueue_style('wcst-shortcode-style', WCST_PLUGIN_PATH.'/css/wcst-shortcode.css');
		ob_start();
		include WCST_PLUGIN_ABS_PATH."template/shortcode_tracking_input.php";
		return ob_get_clean();
	}
}
?>