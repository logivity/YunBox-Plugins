<?php

class WCML_Custom_Prices{

    private $woocommerce_wpml;

    public function __construct( &$woocommerce_wpml ){
        add_filter( 'init', array( $this, 'custom_prices_init' ) );
        $this->woocommerce_wpml = $woocommerce_wpml;
    }

    public function custom_prices_init(){
        if ( is_admin() ) {
            add_action( 'woocommerce_variation_options', array($this, 'add_individual_variation_nonce'), 10, 3 );

            //custom prices for different currencies for products/variations [BACKEND]
            add_action( 'woocommerce_product_options_pricing', array($this, 'woocommerce_product_options_custom_pricing') );
            add_action( 'woocommerce_product_after_variable_attributes', array($this, 'woocommerce_product_after_variable_attributes_custom_pricing'), 10, 3 );

        }

        add_action( 'woocommerce_get_children', array( $this, 'filter_product_variations_with_custom_prices' ), 10 );
        add_filter( 'loop_shop_post_in', array( $this, 'filter_products_with_custom_prices' ), 100 );

    }

    public function add_individual_variation_nonce($loop, $variation_data, $variation){

        wp_nonce_field('wcml_save_custom_prices_variation_' . $variation->ID, '_wcml_custom_prices_variation_' . $variation->ID . '_nonce');

    }

    public function get_product_custom_prices($product_id, $currency = false){
        global $wpdb, $sitepress;

        if(empty($currency)){
            $currency = $this->woocommerce_wpml->multi_currency->get_client_currency();
        }

        $original_product_id = $product_id;
        $post_type = get_post_type($product_id);
        $product_translations = $sitepress->get_element_translations($sitepress->get_element_trid($product_id, 'post_'.$post_type), 'post_'.$post_type);
        foreach($product_translations as $translation){
            if( $translation->original ){
                $original_product_id = $translation->element_id;
                break;
            }
        }

        $product_meta = get_post_custom($original_product_id);

        $custom_prices = false;

        if(!empty($product_meta['_wcml_custom_prices_status'][0])){

            $prices_keys = array(
                '_price', '_regular_price', '_sale_price',
                '_min_variation_price', '_max_variation_price',
                '_min_variation_regular_price', '_max_variation_regular_price',
                '_min_variation_sale_price', '_max_variation_sale_price');

            foreach($prices_keys as $key){

                if(!empty($product_meta[$key . '_' . $currency][0])){
                    $custom_prices[$key] = $product_meta[$key . '_' . $currency][0];
                }

            }

        }

        if(!isset($custom_prices['_price'])) return false;

        $current__price_value = $custom_prices['_price'];

        // update sale price
        if(!empty($custom_prices['_sale_price'])){

            if(!empty($product_meta['_wcml_schedule_' . $currency][0])){
                // custom dates
                if(!empty($product_meta['_sale_price_dates_from_' . $currency][0]) && !empty($product_meta['_sale_price_dates_to_' . $currency][0])){
                    if(current_time('timestamp') > $product_meta['_sale_price_dates_from_' . $currency][0] && current_time('timestamp') < $product_meta['_sale_price_dates_to_' . $currency][0]){
                        $custom_prices['_price'] = $custom_prices['_sale_price'];
                    }else{
                        $custom_prices['_price'] = $custom_prices['_regular_price'];
                    }
                }else{
                    $custom_prices['_price'] = $custom_prices['_sale_price'];
                }

            }else{
                // inherit
                if(!empty($product_meta['_sale_price_dates_from'][0]) && !empty($product_meta['_sale_price_dates_to'][0])){
                    if(current_time('timestamp') > $product_meta['_sale_price_dates_from'][0] && current_time('timestamp') < $product_meta['_sale_price_dates_to'][0]){
                        $custom_prices['_price'] = $custom_prices['_sale_price'];
                    }else{
                        $custom_prices['_price'] = $custom_prices['_regular_price'];
                    }
                }else{
                    $custom_prices['_price'] = $custom_prices['_sale_price'];
                }
            }

        }

        if($custom_prices['_price'] != $current__price_value){
            update_post_meta($product_id, '_price_' . $currency, $custom_prices['_price']);
        }

        // detemine min/max variation prices
        if(!empty($product_meta['_min_variation_price'])){

            static $product_min_max_prices = array();

            if(empty($product_min_max_prices[$product_id])){

                // get variation ids
                $variation_ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d", $product_id));

                // variations with custom prices
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key='_wcml_custom_prices_status'",join(',', $variation_ids)));
                foreach($res as $row){
                    $custom_prices_enabled[$row->post_id] = $row->meta_value;
                }

                // REGULAR PRICES
                // get custom prices
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key='_regular_price_" . $currency . "'",join(',', $variation_ids)));
                foreach($res as $row){
                    $regular_prices[$row->post_id] = $row->meta_value;
                }

                // get default prices (default currency)
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key='_regular_price'",join(',', $variation_ids)));
                foreach($res as $row){
                    $default_regular_prices[$row->post_id] = $row->meta_value;
                }

                // include the dynamic prices
                foreach($variation_ids as $vid){
                    if(empty($regular_prices[$vid]) && isset($default_regular_prices[$vid])){
                        $regular_prices[$vid] = apply_filters('wcml_raw_price_amount', $default_regular_prices[$vid] );
                    }
                }

                // SALE PRICES
                // get custom prices
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key=%s",join(',', $variation_ids),'_sale_price_'.$currency));
                foreach($res as $row){
                    $custom_sale_prices[$row->post_id] = $row->meta_value;
                }

                // get default prices (default currency)
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key='_sale_price' AND meta_value <> ''",join(',', $variation_ids)));
                foreach($res as $row){
                    $default_sale_prices[$row->post_id] = $row->meta_value;
                }

                // include the dynamic prices
                foreach($variation_ids as $vid){
                    if(empty($sale_prices[$vid]) && isset($default_sale_prices[$vid])){
                        $sale_prices[$vid] = apply_filters('wcml_raw_price_amount', $default_sale_prices[$vid]);
                    }
                }


                // PRICES
                // get custom prices
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key=%s",join(',', $variation_ids),'_price_'.$currency));
                foreach($res as $row){
                    $custom_prices_prices[$row->post_id] = $row->meta_value;
                }

                // get default prices (default currency)
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key='_price'",join(',', $variation_ids)));
                foreach($res as $row){
                    $default_prices[$row->post_id] = $row->meta_value;
                }

                // include the dynamic prices
                foreach($variation_ids as $vid){
                    if(empty($custom_prices_prices[$vid]) && isset($default_prices[$vid])){
                        $prices[$vid] = apply_filters('wcml_raw_price_amount', $default_prices[$vid]);
                    }
                }

                if(!empty($regular_prices)){
                    $product_min_max_prices[$product_id]['_min_variation_regular_price'] = min($regular_prices);
                    $product_min_max_prices[$product_id]['_max_variation_regular_price'] = max($regular_prices);
                }

                if(!empty($sale_prices)){
                    $product_min_max_prices[$product_id]['_min_variation_sale_price'] = min($sale_prices);
                    $product_min_max_prices[$product_id]['_max_variation_sale_price'] = max($sale_prices);
                }

                if(!empty($prices)){
                    $product_min_max_prices[$product_id]['_min_variation_price'] = min($prices);
                    $product_min_max_prices[$product_id]['_max_variation_price'] = max($prices);
                }


            }

            if(isset($product_min_max_prices[$product_id]['_min_variation_regular_price'])){
                $custom_prices['_min_variation_regular_price'] = $product_min_max_prices[$product_id]['_min_variation_regular_price'];
            }
            if(isset($product_min_max_prices[$product_id]['_max_variation_regular_price'])){
                $custom_prices['_max_variation_regular_price'] = $product_min_max_prices[$product_id]['_max_variation_regular_price'];
            }

            if(isset($product_min_max_prices[$product_id]['_min_variation_sale_price'])){
                $custom_prices['_min_variation_sale_price'] = $product_min_max_prices[$product_id]['_min_variation_sale_price'];
            }
            if(isset($product_min_max_prices[$product_id]['_max_variation_sale_price'])){
                $custom_prices['_max_variation_sale_price'] = $product_min_max_prices[$product_id]['_max_variation_sale_price'];
            }

            if(isset($product_min_max_prices[$product_id]['_min_variation_price'])){
                $custom_prices['_min_variation_price'] = $product_min_max_prices[$product_id]['_min_variation_price'];
            }
            if(isset($product_min_max_prices[$product_id]['_max_variation_price'])){
                $custom_prices['_max_variation_price'] = $product_min_max_prices[$product_id]['_max_variation_price'];
            }

        }

        $custom_prices = apply_filters( 'wcml_product_custom_prices', $custom_prices, $product_id, $currency );

        return $custom_prices;
    }

    public function woocommerce_product_options_custom_pricing(){
        global $pagenow;

        $this->load_custom_prices_js_css();

        if( ( isset($_GET['post'] ) && ( get_post_type($_GET['post']) != 'product' || !$this->woocommerce_wpml->products->is_original_product( $_GET['post'] ) ) ) ||
            ( isset($_GET['post_type'] ) && $_GET['post_type'] == 'product' && isset( $_GET['source_lang'] ) ) ){
            return;
        }

        $product_id = 'new';

        if($pagenow == 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) == 'product'){
            $product_id = $_GET['post'];
        }

        $this->custom_pricing_output($product_id);

        do_action( 'wcml_after_custom_prices_block', $product_id );

        wp_nonce_field('wcml_save_custom_prices','_wcml_custom_prices_nonce');

    }

    public function woocommerce_product_after_variable_attributes_custom_pricing($loop, $variation_data, $variation){

        if( $this->woocommerce_wpml->products->is_original_product( $variation->post_parent ) ) {

            echo '<tr><td>';
            $this->custom_pricing_output( $variation->ID );
            echo '</td></tr>';

        }

    }

    private function load_custom_prices_js_css(){
        wp_register_style( 'wpml-wcml-prices', WCML_PLUGIN_URL . '/res/css/wcml-prices.css', null, WCML_VERSION );
        wp_register_script( 'wcml-tm-scripts-prices', WCML_PLUGIN_URL . '/res/js/prices' . WCML_JS_MIN . '.js', array( 'jquery' ), WCML_VERSION );

        wp_enqueue_style('wpml-wcml-prices');
        wp_enqueue_script('wcml-tm-scripts-prices');
    }

    private function custom_pricing_output( $post_id = false){

        $custom_prices_ui = new WCML_Custom_Prices_UI( $this->woocommerce_wpml, $post_id );
        $custom_prices_ui->show();

    }

    //display variations with custom prices when "Show only products with custom prices in secondary currencies" enabled
    public function filter_product_variations_with_custom_prices( $children ){

        if( is_product() && $this->woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT &&
            isset($this->woocommerce_wpml->settings['display_custom_prices']) &&
            $this->woocommerce_wpml->settings['display_custom_prices'] ){

            foreach( $children as $key => $child ){
                $orig_lang = $this->woocommerce_wpml->products->get_original_product_language( $child );
                $orig_child_id = apply_filters( 'translate_object_id', $child, get_post_type( $child ), true, $orig_lang );

                if( !get_post_meta( $orig_child_id, '_wcml_custom_prices_status', true ) ){
                    unset( $children[ $key ] );
                }
            }
        }

        return $children;

    }

    // display products with custom prices only if enabled "Show only products with custom prices in secondary currencies" option on settings page
    public function filter_products_with_custom_prices( $filtered_posts ) {
        global $wpdb;

        if( $this->woocommerce_wpml->settings[ 'enable_multi_currency' ] == WCML_MULTI_CURRENCIES_INDEPENDENT &&
            isset( $this->woocommerce_wpml->settings[ 'display_custom_prices' ]  ) &&
            $this->woocommerce_wpml->settings[ 'display_custom_prices' ] ){

            $client_currency = $this->woocommerce_wpml->multi_currency->get_client_currency();
            $woocommerce_currency = get_option( 'woocommerce_currency' );

            if( $client_currency == $woocommerce_currency ){
                return $filtered_posts;
            }
            $matched_products = array();
            $matched_products_query = $wpdb->get_results( "
	        	SELECT DISTINCT ID, post_parent, post_type FROM {$wpdb->posts}
				INNER JOIN {$wpdb->postmeta} ON ID = post_id
				WHERE post_type IN ( 'product', 'product_variation' ) AND post_status = 'publish' AND meta_key = '_wcml_custom_prices_status' AND meta_value = 1
			", OBJECT_K );

            if ( $matched_products_query ) {
                remove_filter( 'get_post_metadata', array( $this->woocommerce_wpml->multi_currency->prices, 'product_price_filter' ), 10, 4);
                foreach ( $matched_products_query as $product ) {
                    if( !get_post_meta( $product->ID,'_price_'.$client_currency, true ) ) continue;
                    if ( $product->post_type == 'product' )
                        $matched_products[] = apply_filters( 'translate_object_id', $product->ID, 'product', true );
                    if ( $product->post_parent > 0 && ! in_array( $product->post_parent, $matched_products ) )
                        $matched_products[] = apply_filters( 'translate_object_id', $product->post_parent, get_post_type( $product->post_parent ), true );
                }
                add_filter('get_post_metadata', array( $this->woocommerce_wpml->multi_currency->prices, 'product_price_filter' ), 10, 4);
            }

            // Filter the id's
            if ( sizeof( $filtered_posts ) == 0) {
                $filtered_posts = $matched_products;
                $filtered_posts[] = 0;
            } else {
                $filtered_posts = array_intersect( $filtered_posts, $matched_products );
                $filtered_posts[] = 0;
            }
        }

        return $filtered_posts;
    }

    public function save_custom_prices( $post_id ){
        $nonce = filter_input( INPUT_POST, '_wcml_custom_prices_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        if( isset( $_POST[ '_wcml_custom_prices' ] ) && isset( $nonce ) && wp_verify_nonce( $nonce, 'wcml_save_custom_prices' ) && !$this->woocommerce_wpml->products->is_variable_product( $post_id ) ){
            if( isset( $_POST[ '_wcml_custom_prices' ][ $post_id ] ) || isset( $_POST[ '_wcml_custom_prices' ][ 'new' ] ) ) {
                $wcml_custom_prices_option = isset( $_POST[ '_wcml_custom_prices' ][ $post_id ] ) ? $_POST[ '_wcml_custom_prices' ][ $post_id ] : $_POST[ '_wcml_custom_prices' ][ 'new' ];
            }else{
                $current_option = get_post_meta( $post_id, '_wcml_custom_prices_status', true );
                $wcml_custom_prices_option = $current_option ? $current_option : 0;
            }
            update_post_meta( $post_id, '_wcml_custom_prices_status', $wcml_custom_prices_option );

            if( $wcml_custom_prices_option == 1){
                $currencies = $this->woocommerce_wpml->multi_currency->get_currencies();
                foreach( $currencies as $code => $currency ){
                    $sale_price = wc_format_decimal( $_POST[ '_custom_sale_price' ][ $code ] );
                    $regular_price = wc_format_decimal( $_POST[ '_custom_regular_price' ][ $code ] );
                    $date_from = isset( $_POST[ '_custom_sale_price_dates_from' ][ $code ] ) ? strtotime( $_POST[ '_custom_sale_price_dates_from' ][ $code ] ) : '';
                    $date_to = isset( $_POST[ '_custom_sale_price_dates_to' ][ $code ] ) ? strtotime( $_POST[ '_custom_sale_price_dates_to' ][ $code ] ) : '';
                    $schedule = $_POST[ '_wcml_schedule' ][ $code ];

                    $custom_prices = apply_filters( 'wcml_update_custom_prices_values',
                        array( '_regular_price' => $regular_price,
                            '_sale_price' => $sale_price,
                            '_wcml_schedule' => $schedule,
                            '_sale_price_dates_from' => $date_from,
                            '_sale_price_dates_to' => $date_to ),
                        $code
                    );
                    $this->update_custom_prices( $post_id, $custom_prices , $code );

                    do_action( 'wcml_after_save_custom_prices', $post_id );
                }
            }
        }
    }

    public function update_custom_prices( $post_id, $custom_prices, $code ){
        $price = '';

        // initialization
        $keys = array(
            '_sale_price_dates_to', '_sale_price_dates_from',
            '_sale_price', '_sale_price_dates_to', '_sale_price_dates_from',

        );
        foreach( $keys as $key ){
            if( !isset( $custom_prices[$key] ) ){ $custom_prices[$key] = ''; }
        }

        foreach( $custom_prices as $custom_price_key => $custom_price_value ){
            update_post_meta( $post_id, $custom_price_key.'_'.$code, $custom_price_value );
        }
        if ( $custom_prices[ '_sale_price_dates_to' ]  && ! $custom_prices[ '_sale_price_dates_from' ] ) {
            update_post_meta($post_id, '_sale_price_dates_from_' . $code, strtotime( 'NOW', current_time( 'timestamp' ) ) );
        }
        // Update price if on sale
        if ( $custom_prices[ '_sale_price' ] != '' && $custom_prices[ '_sale_price_dates_to' ] == '' && $custom_prices[ '_sale_price_dates_from' ] == '' ){
            $price = stripslashes( $custom_prices[ '_sale_price' ] );
            update_post_meta( $post_id, '_price_'.$code, stripslashes( $custom_prices[ '_sale_price' ] ) );
        }else{
            $price = stripslashes( $custom_prices[ '_regular_price' ] );
            update_post_meta( $post_id, '_price_'.$code, stripslashes( $custom_prices[ '_regular_price' ] ) );
        }

        if ( $custom_prices[ '_sale_price' ] != '' && $custom_prices[ '_sale_price_dates_from' ] < strtotime( 'NOW', current_time( 'timestamp' ) ) ){
            update_post_meta( $post_id, '_price_'.$code, stripslashes( $custom_prices[ '_sale_price' ] ) );
            $price = stripslashes( $custom_prices[ '_sale_price' ] );
        }

        if ( $custom_prices[ '_sale_price_dates_to' ] && $custom_prices[ '_sale_price_dates_to' ] < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
            update_post_meta( $post_id, '_price_'.$code, stripslashes( $custom_prices[ '_regular_price' ] ) );
            $price = stripslashes( $custom_prices[ '_regular_price' ] );
            update_post_meta( $post_id, '_sale_price_dates_from_'.$code, '' );
            update_post_meta( $post_id, '_sale_price_dates_to_'.$code, '' );
        }

        return $price;
    }

    public function sync_product_variations_custom_prices( $product_id ){
        global $wpdb;

        $is_variable_product = $this->woocommerce_wpml->products->is_variable_product( $product_id );
        if( $is_variable_product ){
            $get_all_post_variations = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->posts}
                                                WHERE post_status IN ('publish','private')
                                                  AND post_type = 'product_variation'
                                                  AND post_parent = %d
                                                ORDER BY ID"
                    ,$product_id )
            );
            $duplicated_post_variation_ids = array();
            $min_max_prices = array();

            foreach( $get_all_post_variations as $k => $post_data ){
                $duplicated_post_variation_ids[] = $post_data->ID;
                //save files option
                $this->woocommerce_wpml->downloadable->save_files_option( $post_data->ID );

                if( !isset( $_POST[ '_wcml_custom_prices' ][ $post_data->ID ] ) ){
                    continue; // save changes for individual variation
                }
                //save custom prices for variation
                $nonce = filter_input( INPUT_POST, '_wcml_custom_prices_variation_' . $post_data->ID . '_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                if( isset( $_POST['_wcml_custom_prices'][$post_data->ID]) && isset( $nonce ) && wp_verify_nonce( $nonce, 'wcml_save_custom_prices_variation_' . $post_data->ID ) ){
                    update_post_meta( $post_data->ID, '_wcml_custom_prices_status', $_POST[ '_wcml_custom_prices' ][ $post_data->ID ] );
                    $currencies = $this->woocommerce_wpml->multi_currency->get_currencies();

                    if( $_POST[ '_wcml_custom_prices' ][ $post_data->ID ] == 1 ){
                        foreach( $currencies as $code => $currency ){
                            $sale_price = $_POST[ '_custom_variation_sale_price' ][ $code ][ $post_data->ID ];
                            $regular_price = $_POST[ '_custom_variation_regular_price' ][ $code ][ $post_data->ID ];
                            $date_from = strtotime( $_POST[ '_custom_variation_sale_price_dates_from' ][ $code ][ $post_data->ID ] );
                            $date_to = strtotime( $_POST[ '_custom_variation_sale_price_dates_to' ][ $code ][ $post_data->ID ] );
                            $schedule = $_POST[ '_wcml_schedule' ][ $code ][ $post_data->ID ];
                            $custom_prices = apply_filters( 'wcml_update_custom_prices_values',
                                array( '_regular_price' => $regular_price,
                                    '_sale_price' => $sale_price,
                                    '_wcml_schedule_' => $schedule,
                                    '_sale_price_dates_from' => $date_from,
                                    '_sale_price_dates_to' => $date_to ),
                                $code,
                                $post_data->ID
                            );
                            $price = $this->update_custom_prices( $post_data->ID, $custom_prices, $code );

                            if( !isset( $min_max_prices[ '_min_variation_price_'.$code ] ) || ( $price && $price < $min_max_prices[ '_min_variation_price_'.$code ] ) ){
                                $min_max_prices[ '_min_variation_price_'.$code ] = $price;
                                $min_max_prices[ '_min_price_variation_id_'.$code ] = $post_data->ID;
                            }

                            if( !isset( $min_max_prices[ '_max_variation_price_'.$code ] ) || ( $price && $price > $min_max_prices[ '_max_variation_price_'.$code ] ) ){
                                $min_max_prices[ '_max_variation_price_'.$code ] = $price;
                                $min_max_prices[ '_max_price_variation_id_'.$code ] = $post_data->ID;
                            }

                            if( !isset( $min_max_prices[ '_min_variation_regular_price_'.$code ] ) || ( $regular_price && $regular_price < $min_max_prices[ '_min_variation_regular_price_'.$code ] ) ){
                                $min_max_prices[ '_min_variation_regular_price_'.$code ] = $regular_price;
                            }

                            if( !isset( $min_max_prices[ '_max_variation_regular_price_'.$code ] ) || ( $regular_price && $regular_price > $min_max_prices[ '_max_variation_regular_price_'.$code ] ) ){
                                $min_max_prices[ '_max_variation_regular_price_'.$code ] = $regular_price;
                            }

                            if( !isset( $min_max_prices[ '_min_variation_sale_price_'.$code ] ) || ( $sale_price && $sale_price < $min_max_prices[ '_min_variation_sale_price_'.$code ] ) ){
                                $min_max_prices[ '_min_variation_sale_price_'.$code ] = $sale_price;
                            }

                            if( !isset( $min_max_prices[ '_max_variation_sale_price_'.$code ] ) || ( $sale_price && $sale_price > $min_max_prices[ '_max_variation_sale_price_'.$code ] ) ){
                                $min_max_prices[ '_max_variation_sale_price_'.$code ] = $sale_price;
                            }
                        }
                    }
                }
            }
        }
    }

}