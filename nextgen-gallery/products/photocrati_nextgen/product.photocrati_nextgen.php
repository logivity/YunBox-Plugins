<?php

/***
{
Product: photocrati-nextgen
}
 ***/

define('NGG_CHANGE_OPTIONS_CAP', 'NextGEN Manage gallery');

class P_Photocrati_NextGen extends C_Base_Product
{
	static $modules_provided = array(
		'photocrati-fs'                         =>  'always',
		'photocrati-i18n'                       =>  'always',
		'photocrati-validation'                 =>  'always',
		'photocrati-router'                     =>  'always',
		'photocrati-wordpress_routing'          =>  'always',
		'photocrati-security'                   =>  'always',
		'photocrati-nextgen_settings'           =>  'always',
		'photocrati-mvc'                        =>  'always',
		'photocrati-ajax'                       =>  'always',
		'photocrati-datamapper'                 =>  'always',
		'photocrati-nextgen-legacy'             =>  'always',
		'photocrati-nextgen-data'               =>  'always',

		// We should look at how to make the modules below only
		// require loading in wp-admin
		'photocrati-dynamic_thumbnails'         =>  'always',
		'photocrati-nextgen_admin'              =>  'always',
		'photocrati-nextgen_gallery_display'    =>  'always',
		'photocrati-frame_communication'        =>  'always',
		'photocrati-attach_to_post'             =>  'always',
		'photocrati-nextgen_addgallery_page'    =>  'always',
		'photocrati-nextgen_other_options'      =>  'always',
		'photocrati-nextgen_pagination'         =>  'always',

		// Front-end only
		'photocrati-dynamic_stylesheet'         =>  'frontend',

		// Backend-only
		'photocrati-nextgen_pro_upgrade'        =>  '',

		'photocrati-cache'                      =>  'always',
		'photocrati-lightbox'                   =>  'always',
		'photocrati-nextgen_basic_templates'    =>  'always',
		'photocrati-nextgen_basic_gallery'      =>  'always',
		'photocrati-nextgen_basic_imagebrowser' =>  'always',
		'photocrati-nextgen_basic_singlepic'    =>  'always',
		'photocrati-nextgen_basic_tagcloud'     =>  'always',
		'photocrati-nextgen_basic_album'        =>  'always',
		'photocrati-widget'                     =>  'always',
		'photocrati-third_party_compat'         =>  'always',
		'photocrati-nextgen_xmlrpc'             =>  'always',
		'photocrati-wpcli'                      =>  'always'
	);

	function get_modules_provided()
	{
		return array_keys(self::$modules_provided);
	}

	function get_modules_to_load()
	{
		$retval = array();

		foreach (self::$modules_provided as $module_name => $condition) {
			switch ($condition) {
				case 'always':
					$retval[] = $module_name;
					break;
				case 'backend':
					if (is_admin())
						$retval[] = $module_name;
					break;
				case 'frontend':
					if (!is_admin())
						$retval[] = $module_name;
					break;
			}
		}

		$retval = apply_filters('ngg_get_modules_to_load', $retval, self::$modules_provided);

		return $retval;
	}

	function define()
	{
		parent::define(
			'photocrati-nextgen',
			'NextGen Gallery',
			'NextGen Gallery',
            NGG_PLUGIN_VERSION,
			'https://www.imagely.com/wordpress-gallery-plugin/nextgen-gallery/',
			'Photocrati Media',
			'https://www.imagely.com'
		);

		$module_path = implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'modules'));
		$this->get_registry()->set_product_module_path($this->module_id, $module_path);
		foreach ($this->get_modules_to_load() as $module_name) $this->_get_registry()->load_module($module_name);

		include_once('class.nextgen_product_installer.php');
		C_Photocrati_Installer::add_handler($this->module_id, 'C_NextGen_Product_Installer');
	}
}

new P_Photocrati_NextGen();
