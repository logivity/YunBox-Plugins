<?php 

include_once( WCST_PLUGIN_ABS_PATH . '/classes/acf/acf.php' );

$wcst_hide_menu = true;
if ( ! function_exists( 'is_plugin_active' ) ) 
{
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); 
}
/* Checks to see if the acf pro plugin is activated  */
if ( is_plugin_active('advanced-custom-fields-pro/acf.php') )  {
	$wcst_hide_menu = false;
}

/* Checks to see if the acf plugin is activated  */
if ( is_plugin_active('advanced-custom-fields/acf.php') ) 
{
	add_action('plugins_loaded', 'wcst_load_acf_standard_last', 10, 2 ); //activated_plugin
	add_action('deactivated_plugin', 'wcst_detect_plugin_deactivation', 10, 2 ); //activated_plugin
	$wcst_hide_menu = false;
}
function wcst_detect_plugin_deactivation(  $plugin, $network_activation ) { //after
   // $plugin == 'advanced-custom-fields/acf.php'
	//wcst_var_dump("wcst_detect_plugin_deactivation");
	$acf_standard = 'advanced-custom-fields/acf.php';
	if($plugin == $acf_standard)
	{
		$active_plugins = get_option('active_plugins');
		$this_plugin_key = array_keys($active_plugins, $acf_standard);
		if (!empty($this_plugin_key)) 
		{
			foreach($this_plugin_key as $index)
				unset($active_plugins[$index]);
			update_option('active_plugins', $active_plugins);
			//forcing
			deactivate_plugins( plugin_basename( WP_PLUGIN_DIR.'/advanced-custom-fields/acf.php') );
		}
	}
} 
function wcst_load_acf_standard_last($plugin, $network_activation = null) { //before
	$acf_standard = 'advanced-custom-fields/acf.php';
	$active_plugins = get_option('active_plugins');
	$this_plugin_key = array_keys($active_plugins, $acf_standard);
	if (!empty($this_plugin_key)) 
	{ 
		foreach($this_plugin_key as $index)
			//array_splice($active_plugins, $index, 1);
			unset($active_plugins[$index]);
		//array_unshift($active_plugins, $acf_standard); //first
		array_push($active_plugins, $acf_standard); //last
		update_option('active_plugins', $active_plugins);
	} 
}


add_filter('acf/settings/path', 'wcst_acf_settings_path');
function wcst_acf_settings_path( $path ) 
{
 
    // update path
    $path = WCST_PLUGIN_ABS_PATH. '/classes/acf/';
    
    // return
    return $path;
    
}

add_filter('acf/settings/dir', 'wcst_acf_settings_dir');
function wcst_acf_settings_dir( $dir ) {
 
    // update path
    $dir = WCST_PLUGIN_PATH . '/classes/acf/';
    
    // return
    return $dir;
    
}

function wcst_acf_init() {
    
    include WCST_PLUGIN_ABS_PATH . "/assets/fields.php";
    
}
add_action('acf/init', 'wcst_acf_init');

//hide acf menu
if($wcst_hide_menu)	
	add_filter('acf/settings/show_admin', '__return_false');

?>