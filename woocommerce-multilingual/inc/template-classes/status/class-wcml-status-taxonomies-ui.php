<?php

class WCML_Status_Taxonomies_UI extends WPML_Templates_Factory {

    private $woocommerce_wpml;

    function __construct( &$woocommerce_wpml ){
        parent::__construct();

        $this->woocommerce_wpml = $woocommerce_wpml;
    }

    public function get_model() {

        $model = array(
            'taxonomies' => $this->get_taxonomies_data(),
            'strings' => array(
                'tax_missing'       => __( 'Taxonomies Missing Translations', 'woocommerce-multilingual' ),
                'run_site'          => __( 'To run a fully translated site, you should translate all taxonomy terms. Some store elements, such as variations, depend on taxonomy translation.', 'woocommerce-multilingual' ),
                'not_req_trnsl'     => __( '%s do not require translation.', 'woocommerce-multilingual' ),
                'req_trnsl'         => __( 'This taxonomy requires translation.', 'woocommerce-multilingual' ),
                'incl_trnsl'        => __( 'Include in translation', 'woocommerce-multilingual' ),
                'miss_trnsl_one'    => __( '%d %s is missing translations.', 'woocommerce-multilingual' ),
                'miss_trnsl_more'   => __( '%d %s are missing translations.', 'woocommerce-multilingual' ),
                'trnsl'             => __( 'Translate %s', 'woocommerce-multilingual' ),
                'doesnot_req_trnsl' => __( 'This taxonomy does not require translation.', 'woocommerce-multilingual' ),
                'exclude'           => __( 'Exclude from translation', 'woocommerce-multilingual' ),
                'all_trnsl'         => __( 'All %s are translated.', 'woocommerce-multilingual' ),
                'not_to_trnsl'      => __( 'Right now, there are no taxonomy terms needing translation.', 'woocommerce-multilingual' )
            ),
            'nonces' => array(
                'ignore_tax' => wp_create_nonce( 'wcml_ingore_taxonomy_translation_nonce' )
            )
        );

        return $model;

    }

    private function get_taxonomies_data(){
        $taxonomies = $this->woocommerce_wpml->terms->get_wc_taxonomies();
        $taxonomies_data = array();

        foreach ( $taxonomies as $key => $taxonomy ) {
            $taxonomies_data[$key]['tax'] = $taxonomy;
            $taxonomies_data[$key]['untranslated'] = $this->woocommerce_wpml->terms->get_untranslated_terms_number($taxonomy);
            $taxonomies_data[$key]['fully_trans'] = $this->woocommerce_wpml->terms->is_fully_translated($taxonomy);
	        $taxonomy_object = get_taxonomy($taxonomy);
            $taxonomies_data[$key]['name'] = $taxonomy_object->labels->name;
	        $taxonomies_data[$key]['name_singular'] = $taxonomy_object->labels->singular_name;

            if( substr( $taxonomy, 0, 3 ) == 'pa_' ){
                $taxonomies_data[$key]['url'] = admin_url( 'admin.php?page=wpml-wcml&tab=product-attributes&taxonomy=' . $taxonomy );
            }else{
                $taxonomies_data[$key]['url'] = admin_url( 'admin.php?page=wpml-wcml&tab=' . $taxonomy );
            }

        }

        return $taxonomies_data;
    }


    public function init_template_base_dir() {
        $this->template_paths = array(
            WCML_PLUGIN_PATH . '/templates/status/',
        );
    }

    public function get_template() {
        return 'taxonomies.twig';
    }

}