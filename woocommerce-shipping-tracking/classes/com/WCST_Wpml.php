<?php 
class WCST_Wpml
{
	public function __construct()
	{
	}
	public function is_wpml_active()
	{
		return class_exists('SitePress');
	}
	public function switch_language($language_code)
	{
		if(class_exists('SitePress'))
		{
			global $sitepress;
			$sitepress->switch_lang($language_code, true);
			return true;
		}
		return false;
	}
	public function get_default_lang()
	{
		if(!class_exists('SitePress'))
			return "none" ;
		
		global $sitepress;
		return $sitepress->get_default_language();
	}
	public function get_all_translation_ids($post_id, $post_type = "product")
	{
		if(!class_exists('SitePress'))
			return false;
		
		global $sitepress, $wpdb;
		$translations = array();
		$translations_result = array();
		
		//if($post_type == "product")
		{
			$trid = $sitepress->get_element_trid($post_id, 'post_'.$post_type);
			$translations = $sitepress->get_element_translations($trid, $post_type);
			//wpps_var_dump($translations);
			foreach($translations as $language_code => $item)
			{
				if($language_code != $sitepress->get_default_language())
					$translations_result[] = $item->element_id;
			}
			//wpps_var_dump($translations_result);
		}
		/* else
		{
			$query = "SELECT post_meta.post_id as product_id 
					  FROM {$wpdb->postmeta} AS post_meta 
					  WHERE post_meta.meta_value = '{$post_id}' 
					  AND post_meta.meta_key = '_wcml_duplicate_of_variation' ";
			$result = $wpdb->get_results($query);
			$result = isset($result) ? $result : array();
			//wpps_var_dump($result);
			foreach($result as $item)
			{
				$translations_result[] = $item->product_id;
			}
		} */
		
		/*Format: 
		array(2) {
		  ["en"]=>
		  object(stdClass)#227 (5) {
			["translation_id"]=>
			string(3) "174"
			["language_code"]=>
			string(2) "en"
			["element_id"]=>
			string(3) "943"
			["source_language_code"]=>
			NULL
			["original"]=>
			string(1) "1"
		  }
		  ["it"]=>
		  object(stdClass)#228 (5) {
			["translation_id"]=>
			string(3) "175"
			["language_code"]=>
			string(2) "it"
			["element_id"]=>
			string(3) "944"
			["source_language_code"]=>
			string(2) "en"
			["original"]=>
			string(1) "0"
		  }
		}*/
		return !empty($translations_result) ? $translations_result:false;
	}

	public function get_original_ids($items_array, $post_type = "product")
	{
		if(!class_exists('SitePress'))
			return false;
		
		global $sitepress;
		$original_ids = array();
		foreach($items_array as $item)	
		{
			if(function_exists('icl_object_id'))
				$item_translated_id = icl_object_id($item->id, $post_type, true, $sitepress->get_default_language());
			else
				$item_translated_id = apply_filters( 'wpml_object_id', $item->id, $post_type, true, $sitepress->get_default_language() );
			
			if(!in_array($item_translated_id, $original_ids))
				array_push($original_ids, $item_translated_id);
		}
			
		return $original_ids;
	}
	public function get_original_id($item_id, $post_type = "product", $return_original = true)
	{
		if(!class_exists('SitePress'))
			return false;
		
		global $sitepress;
		if(function_exists('icl_object_id'))
			$item_translated_id = icl_object_id($item_id, $post_type, $return_original, $sitepress->get_default_language());
		else
			$item_translated_id = apply_filters( 'wpml_object_id', $item_id, $post_type, $return_original, $sitepress->get_default_language() );
		
		return $item_translated_id;
	}
	public function is_item_a_translation($item_id, $post_type = "product")
	{
		if(!$this->is_wpml_active())
			return false;
		
		$result = $this->get_original_id($item_id, $post_type);
		if($item_id != $result)
			return true;
		
		if($post_type == "product_variation")
			$_icl_lang_duplicate_of = get_post_meta( $item_id, '_wcml_duplicate_of_variation', true ); 
		else
			$_icl_lang_duplicate_of = get_post_meta( $item_id, '_icl_lang_duplicate_of', true );
		
		return $_icl_lang_duplicate_of != false ? true : false;
	}
	public function switch_to_default_language()
	{
		if(!$this->is_wpml_active())
			return;
		global $sitepress;
		$this->curr_lang = ICL_LANGUAGE_CODE ;
		$sitepress->switch_lang($sitepress->get_default_language());
	
	}
	public function switch_to_current_language()
	{
		if(!$this->is_wpml_active())
			return;
		
		global $sitepress;
		$sitepress->switch_lang($this->curr_lang);
	}
}
?>