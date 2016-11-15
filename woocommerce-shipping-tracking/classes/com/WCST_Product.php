<?php 
class WCST_Product
{
	public function __construct()
	{
	}
	public function get_estimation_shippment_rule($id = null)
	{
		$wcst_options = new WCST_Option();
		$estimated_shipping_rules = $wcst_options->get_estimations_options();
		$id = !isset($id) ? get_the_ID() : $id;
		$not_translated_id = -1;
		if(!isset($id))
			return null;
		$wpps_wpml_model = new WCST_Wpml();
		if($wpps_wpml_model->is_wpml_active())
		{
			$not_translated_id = $id;
			$id = $wpps_wpml_model->get_original_id($id);
		}
		
		$result = null;
		foreach($estimated_shipping_rules['estimated_shipping'] as $rule)
		{
			if(is_array($rule['products']) && in_array($id , $rule['products']))
			{
				$result = $rule;
				break;
			}
			elseif(is_array($rule['categories']))
			{
				$ids = $this->get_products_ids_using_categories($rule['categories'], $rule['children_categories']);
				
				if(is_array($ids) && (in_array($id , $ids) || in_array($not_translated_id , $ids)) )
				{
					$result = $rule;
					break;
				}
			}
		}
		return $result;
	}
	private function get_products_ids_using_categories($selected_categories, $get_post_belonging_to_children_categories = "selected_only" , $strategy = "all")
	{
		//$get_post_belonging_to_children_categories : "selected_only" || "all_children"
		
		global $wpdb;
		$not_suffix = $strategy == "all" ? "  " : " NOT ";
		$results = $additional_categories_ids = array();
		
		//Retrieve children categories id
		if($get_post_belonging_to_children_categories == 'all_children')
		{
			foreach($selected_categories as $current_category)
			{
				$args = array(
						'type'                     => 'post',
						'child_of'                 => $current_category,
						'parent'                   => '',
						'orderby'                  => 'name',
						'order'                    => 'ASC',
						'hide_empty'               => 1,
						'hierarchical'             => 1,
						'exclude'                  => '',
						'include'                  => '',
						'number'                   => '',
						'taxonomy'                 => 'product_cat',
						'pad_counts'               => false

					); 

					$categories = get_categories( $args );
					//wcosm_var_dump($categories);
					foreach($categories as $result)
						$additional_categories_ids[] = $result->term_id;
			}
		}
		if(!empty($additional_categories_ids))
			$selected_categories = array_merge($selected_categories, $additional_categories_ids);
		
		//GROUP_CONCAT(posts.ID)
		$query = "SELECT DISTINCT posts.ID
				 FROM {$wpdb->posts} AS posts 
				 INNER JOIN {$wpdb->term_relationships} AS term_rel ON term_rel.object_id = posts.ID
				 INNER JOIN {$wpdb->term_taxonomy} AS term_tax ON term_tax.term_taxonomy_id = term_rel.term_taxonomy_id 
				 INNER JOIN {$wpdb->terms} AS terms ON terms.term_id = term_tax.term_id
				 WHERE  terms.term_id {$not_suffix} IN ('" . implode( "','", $selected_categories). "')  
				 AND term_tax.taxonomy = 'product_cat' "; 
		$ids = $wpdb->get_results($query, ARRAY_A);
	
		foreach($ids as $id)
			$results[] = $id['ID'];
			
		/* $temp_array = array();
		//WPML: get translated posts
		$wpps_wpml_model = new WCST_Wpml();
		if($wpps_wpml_model->is_wpml_active())
			foreach($results as $post_id)
			{
				$additional_ids = $wpps_wpml_model->get_all_translation_ids($post_id);
				if(!empty($additional_ids))
					$temp_array = array_merge($temp_array, $additional_ids);
			}
		if(!empty($temp_array))
			$results = array_merge($results, $temp_array); */
		
		return $results;
	}
}
?>