<?php

namespace Admin\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

add_action( 'load-edit.php', 'Admin\Includes\cloneWPPostsListTable' );

function cloneWPPostsListTable() {
    // Target the products edit screen
    if ( 'edit-product' !== get_current_screen()->id ) {
        return;
    }

    // Include the WP_Posts_List_Table class
    require_once(ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php');

    // Extend the WP_Posts_List_Table class and add extra actions
    class QuoteupProductsTable extends \WP_Posts_List_Table {

        var $quoteupBulkActions      = array();
        var $visibilityIconsTooltip  = array();
        var $settings                = array();

        public function __construct( $args = array() ) {
            $this->set_bulk_actions_array();
            $this->set_visibility_icon_messages();
            $this->retrieveSettings();
            add_action( 'admin_notices', array( $this, 'bulk_action_admin_notices' ) );
            add_filter( 'manage_product_posts_columns', array( $this, 'register_visibility_column' ) );
            add_action( 'manage_product_posts_custom_column', array( $this, 'render_visibility_column_data' ), 10, 2 );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );
            //Call Parent class's constructor
            parent::__construct( $args );
        }

        protected function retrieveSettings() {

            $this->settings = get_option( 'wdm_form_data' );
        }

        protected function set_bulk_actions_array() {
            $this->quoteupBulkActions = array(
                'enable-enquiry'         => __( 'Enable Enquiry', 'quoteup' ),
                'disable-enquiry'        => __( 'Disable Enquiry', 'quoteup' ),
                'enable-add-to-cart'     => __( 'Enable Add to Cart', 'quoteup' ),
                'disable-add-to-cart'    => __( 'Disable Add to Cart', 'quoteup' ),
                'show-price'             => __( 'Show Price', 'quoteup' ),
                'hide-price'             => __( 'Hide Price', 'quoteup' ),
            );
        }

        protected function set_visibility_icon_messages() {
            $this->visibilityIconsTooltip = array(
                'add-to-cart-enabled'                => __( 'Add to Cart Enabled', 'quoteup' ),
                'add-to-cart-disabled'               => __( 'Add to Cart Disabled', 'quoteup' ),
                'price-visible'                      => __( 'Price Visible', 'quoteup' ),
                'price-hidden'                       => __( 'Price Hidden', 'quoteup' ),
                'enquiry-enabled'                    => __( 'Enquiry Enabled', 'quoteup' ),
                'enquiry-enabled-if-out-of-stock'    => __( 'Enquiry is enabled but Enquiry button will be shown when product goes out of stock', 'quoteup' ),
                'enquiry-disabled'                   => __( 'Enquiry Disabled', 'quoteup' ),
            );
        }

        public function enqueue_scripts_and_styles() {
            wp_enqueue_style( 'quoteup_products_listing', QUOTEUP_PLUGIN_URL . '/css/admin/dashboard-products-listing.css', false );
            wp_enqueue_script( 'quoteup_products_listing_js', QUOTEUP_PLUGIN_URL . '/js/admin/woocommerce-products-table.js', array(
                'jquery' ) );
            wp_localize_script( 'quoteup_products_listing_js', 'quoteup_products_listing_localization', array(
                'confirm_message' => __( 'Hiding Price for products will also disable \'Add To Cart\' for those products. Do you want to continue?', 'quoteup' ) ) );
        }

        /**
         * Add new options to Bulk Options Dropdown
         */
        protected function get_bulk_actions() {
            $actions         = array();
            $post_type_obj   = get_post_type_object( $this->screen->post_type );
            $is_trash = isset( $_REQUEST['post_status'] ) && $_REQUEST['post_status'] === 'trash';

            if ( current_user_can( $post_type_obj->cap->edit_posts ) ) {
                if ( $is_trash ) {
                    $actions[ 'untrash' ] = __( 'Restore' );
                } else {
                    $actions[ 'edit' ]   = __( 'Edit' );
                    $actions             = $actions + $this->quoteupBulkActions;
                }
            }

            if ( current_user_can( $post_type_obj->cap->delete_posts ) ) {
                if ( $is_trash || ! EMPTY_TRASH_DAYS ) {
                    $actions[ 'delete' ] = __( 'Delete Permanently' );
                } else {
                    $actions[ 'trash' ] = __( 'Move to Trash' );
                }
            }


            return $actions;
        }

         /**
         * This function is used to perform enable enquiry action
         * @return [type] [description]
         */
        public function actionEnableEnquiry($post_ids, $post_type_obj, $sendback)
        {
            $enabledEnquiries            = $locked                       = $enabledEnquiriesForFuture    = $invalidProducts          = 0;

            foreach ((array) $post_ids as $post_id) {
                $product_object = wc_get_product($post_id);

                //echo '<pre>' . print_r($product_object) . '</pre>'; exit;
                if ($product_object->product_type != 'simple' && $product_object->product_type != 'variable') {
                    $invalidProducts ++;
                    continue;
                }

                if (! current_user_can($post_type_obj->cap->edit_posts, $post_id)) {
                    wp_die(sprintf(__('You are not allowed to edit %s. Therefore you can not enable enquiry for this product', 'quoteup'), get_the_title($post_id)));
                }

                if (wp_check_post_lock($post_id)) {
                    $locked ++;
                    continue;
                }
                update_post_meta($post_id, '_enable_pep', 'yes');
                if ($this->settings == false || (isset($this->settings[ 'only_if_out_of_stock' ]) && $this->settings[ 'only_if_out_of_stock' ] == 1)) {
                    //Check if product is in stock or not
                    if (\quoteupIsProductInStock($post_id)) {
                        //Enquiry enabled for instock product
                        $enabledEnquiriesForFuture ++;
                    } else {
                        //Enquiry enabled for out of stock product
                        $enabledEnquiries ++;
                    }
                } else {
                    $enabledEnquiries ++;
                }
            }

            // build the redirect url
            $sendback = add_query_arg(array( 'enabled-enquiries' => $enabledEnquiries, 'enabled-enquiries-if-out-of-stock' => $enabledEnquiriesForFuture, 'invalid-products' => $invalidProducts, 'locked' => $locked, 'ids' => join(',', $post_ids) ), $sendback);

            return $sendback;

        }
        /**
         * This function is used to perform disable enquiry action
         * @return [type] [description]
         */
        public function actionDisableEnquiry($post_ids, $post_type_obj, $sendback)
        {
            $disabledEnquiries   = $locked               = $invalidProducts  = 0;

                    foreach ((array) $post_ids as $post_id) {

                        $product_object = wc_get_product($post_id);

                        if ($product_object->product_type != 'simple' && $product_object->product_type != 'variable') {
                            $invalidProducts ++;
                            continue;
                        }

                        if (! current_user_can($post_type_obj->cap->edit_posts, $post_id)) {
                            wp_die(sprintf(__('You are not allowed to edit %s. Therefore you can not disable enquiry for this product', 'quoteup'), get_the_title($post_id)));
                        }

                        if (wp_check_post_lock($post_id)) {
                            $locked ++;
                            continue;
                        }
                        update_post_meta($post_id, '_enable_pep', '');
                        $disabledEnquiries ++;
                    }

                    // build the redirect url
                    $sendback = add_query_arg(array( 'disabled-enquiries' => $disabledEnquiries, 'invalid-products' => $invalidProducts, 'locked' => $locked, 'ids' => join(',', $post_ids) ), $sendback);
                    return $sendback;
        }

        /**
         * This function is used to perform Show price action
         * @return [type] [description]
         */
        public function actionShowPrice($post_ids, $post_type_obj, $sendback)
        {
            $showingPrice    = $locked           = $invalidProducts = 0;

            foreach ((array) $post_ids as $post_id) {

                $product_object = wc_get_product($post_id);

                if ($product_object->product_type != 'simple' && $product_object->product_type != 'variable') {
                    $invalidProducts ++;
                    continue;
                }

                if (! current_user_can($post_type_obj->cap->edit_posts, $post_id)) {
                    wp_die(sprintf(__('You are not allowed to edit %s. Therefore you can not make price visible for this product', 'quoteup'), get_the_title($post_id)));
                }

                if (wp_check_post_lock($post_id)) {
                    $locked ++;
                    continue;
                }
                update_post_meta($post_id, '_enable_price', 'yes');
                $showingPrice ++;
            }

            // build the redirect url
            $sendback = add_query_arg(array( 'showing-price' => $showingPrice, 'invalid-products' => $invalidProducts, 'locked' => $locked, 'ids' => join(',', $post_ids) ), $sendback);

            return $sendback;
        }


        /**
         * This function is used to perform Hide Price action
         * @return [type] [description]
         */
        public function actionHidePrice($post_ids, $post_type_obj, $sendback)
        {
            $hidePrice       = $locked           = $invalidProducts = 0;
            foreach ((array) $post_ids as $post_id) {

                $product_object = wc_get_product($post_id);

                if ($product_object->product_type != 'simple' && $product_object->product_type != 'variable') {
                    $invalidProducts ++;
                    continue;
                }

                if (! current_user_can($post_type_obj->cap->edit_posts, $post_id)) {
                    wp_die(sprintf(__('You are not allowed to edit %s. Therefore you can not hide price for this product', 'quoteup'), get_the_title($post_id)));
                }

                if (wp_check_post_lock($post_id)) {
                    $locked ++;
                    continue;
                }
                update_post_meta($post_id, '_enable_price', '');
                //Also hide Add to Cart for product when Price is hidden
                update_post_meta($post_id, '_enable_add_to_cart', '');
                $hidePrice ++;
            }

            // build the redirect url
            $sendback = add_query_arg(array( 'hiding-price' => $hidePrice, 'invalid-products' => $invalidProducts, 'locked' => $locked, 'ids' => join(',', $post_ids) ), $sendback);
            return $sendback;
        }

        /**
         * This function is used to perform Enable add to cart action
         * @return [type] [description]
         */
        public function actionEnableAddToCart($post_ids, $post_type_obj, $sendback)
        {
            $enabledAddToCart            = $locked                       = $skippedEnablingAddToCart     = $outOfStockProducts           = $invalidProducts          = 0;

            foreach ((array) $post_ids as $post_id) {

                $product_object = wc_get_product($post_id);

                if ($product_object->product_type != 'simple' && $product_object->product_type != 'variable') {
                    $invalidProducts ++;
                    continue;
                }

                if (! current_user_can($post_type_obj->cap->edit_posts, $post_id)) {
                    wp_die(sprintf(__('You are not allowed to edit %s. Therefore you can not enable Add to Cart for this product', 'quoteup'), get_the_title($post_id)));
                }

                if (wp_check_post_lock($post_id)) {
                    $locked ++;
                    continue;
                }
                //Enable add to cart only if price is visible on the Frontend
                if ($this->is_price_showing($post_id)) {
                    update_post_meta($post_id, '_enable_add_to_cart', 'yes');
                    if (\quoteupIsProductInStock($post_id)) {
                        $enabledAddToCart ++;
                    } else {
                        $outOfStockProducts ++;
                    }
                } else {
                    $skippedEnablingAddToCart ++;
                }
            }

            // build the redirect url
            $sendback = add_query_arg(array( 'enabled-add-to-cart' => $enabledAddToCart, 'invalid-products' => $invalidProducts, 'skipped-enabling-add-to-cart' => $skippedEnablingAddToCart, 'skipped-out-of-stock-products' => $outOfStockProducts, 'locked' => $locked, 'ids' => join(',', $post_ids) ), $sendback);
            return $sendback;
        }

        /**
         * This function is used to perform Disable add to cart action
         * @return [type] [description]
         */
        public function actionDisableAddToCart($post_ids, $post_type_obj, $sendback)
        {
            $disabledAddToCart   = $locked               = $invalidProducts  = 0;

            foreach ((array) $post_ids as $post_id) {

                $product_object = wc_get_product($post_id);

                if ($product_object->product_type != 'simple' && $product_object->product_type != 'variable') {
                    $invalidProducts ++;
                    continue;
                }

                if (! current_user_can($post_type_obj->cap->edit_posts, $post_id)) {
                    wp_die(sprintf(__('You are not allowed to edit %s. Therefore you can not disable Add to Cart for this product', 'quoteup'), get_the_title($post_id)));
                }

                if (wp_check_post_lock($post_id)) {
                    $locked ++;
                    continue;
                }
                update_post_meta($post_id, '_enable_add_to_cart', '');
                $disabledAddToCart ++;
            }

            // build the redirect url
            $sendback = add_query_arg(array( 'disabled-add-to-cart' => $disabledAddToCart, 'invalid-products' => $invalidProducts, 'locked' => $locked, 'ids' => join(',', $post_ids) ), $sendback);
            return $sendback;
        }



        /**
         * Process Bulk actions
         */
        public function process_bulk_action() {
            // 1. get the action
            $action = $this->current_action();

            $all_actions = array_keys( $this->quoteupBulkActions );

            //If current action is not in the custom bulk actions, return
            if ( ! in_array( $action, $all_actions ) ) {
                return;
            }

            //security check
            check_admin_referer( 'bulk-posts' );

            // this is based on wp-admin/edit.php
            $sendback = remove_query_arg( array( 'enabled-enquiries', 'invalid-products',
                'enabled-enquiries-if-out-of-stock', 'disabled-enquiries', 'showing-price', 'hiding-price',
                'enabled-add-to-cart', 'disabled-add-to-cart', 'skipped-enabling-add-to-cart',
                'untrashed', 'deleted', 'ids' ), wp_get_referer() );

            if ( ! $sendback ) {
                $sendback = admin_url( "edit.php?post_type=product" );
            }

            $pagenum     = $this->get_pagenum();
            $sendback    = add_query_arg( 'paged', $pagenum, $sendback );

            //list of posts selected by admin
            if ( isset( $_REQUEST[ 'post' ] ) ) {
                $post_ids = array_map( 'intval', $_REQUEST[ 'post' ] );
            }

            if ( ! isset( $post_ids ) ) {
                wp_redirect( $sendback );
                exit;
            }

            $post_type_obj = get_post_type_object( $this->screen->post_type );


            switch ( $action ) {
                case 'enable-enquiry':
                    $sendback = $this->actionEnableEnquiry($post_ids, $post_type_obj, $sendback);
                    break;

                case 'disable-enquiry':
                    $sendback = $this->actionDisableEnquiry($post_ids, $post_type_obj, $sendback);
                    break;

                case 'show-price':
                    $sendback = $this->actionShowPrice($post_ids, $post_type_obj, $sendback);
                    break;

                case 'hide-price':
                    $sendback = $this->actionHidePrice($post_ids, $post_type_obj, $sendback);
                    break;

                case 'enable-add-to-cart':
                    $sendback = $this->actionEnableAddToCart($post_ids, $post_type_obj, $sendback);
                    break;

                case 'disable-add-to-cart':
                    $sendback = $this->actionDisableAddToCart($post_ids, $post_type_obj, $sendback);
                    break;

                default:
                    return;
            }

            $sendback = remove_query_arg( array( 'action', 'action2', 'tags_input', 'post_author',
                'comment_status', 'ping_status', '_status', 'post', 'bulk_edit', 'post_view' ), $sendback );

            //Redirect client
            wp_redirect( $sendback );

            exit();
        }

        public function getIsset(&$value)
        {
            return isset($value) ? absint($value) : 0;
        }

        /**
         * This function is used to return bulk counts.
         *
         * @return [type] [description]
         */
        public function getBulkCounts()
        {
            $bulk_counts = array(
                'enabled-enquiries'                  => $this->getIsset($_REQUEST[ 'enabled-enquiries' ]),
                'enabled-enquiries-if-out-of-stock'  => $this->getIsset($_REQUEST[ 'enabled-enquiries-if-out-of-stock' ]),
                'disabled-enquiries'                 => $this->getIsset($_REQUEST[ 'disabled-enquiries' ]),
                'showing-price'                      => $this->getIsset($_REQUEST[ 'showing-price' ]),
                'hiding-price'                       => $this->getIsset($_REQUEST[ 'hiding-price' ]),
                'enabled-add-to-cart'                => $this->getIsset($_REQUEST[ 'enabled-add-to-cart' ]),
                'disabled-add-to-cart'               => $this->getIsset($_REQUEST[ 'disabled-add-to-cart' ]),
                'skipped-enabling-add-to-cart'       => $this->getIsset($_REQUEST[ 'skipped-enabling-add-to-cart' ]),
                'skipped-out-of-stock-products'      => $this->getIsset($_REQUEST[ 'skipped-out-of-stock-products' ]),
                'invalid-products'                   => $this->getIsset($_REQUEST[ 'invalid-products' ])
            );
            return $bulk_counts;

        }

        /**
         * This function is used to return bulk messages
         * @return [type] [description]
         */
        public function getBulkMessages($enquiry_enabled_message, $enquiry_enabled_if_out_of_stock, $bulk_counts)
        {
            $bulk_messages = array(
                'enabled-enquiries'                  => $enquiry_enabled_message,
                'enabled-enquiries-if-out-of-stock'  => $enquiry_enabled_if_out_of_stock,
                'disabled-enquiries'                 => _n('Enquiry disabled for %s product.', 'Enquiries disabled for %s products.', $bulk_counts[ 'disabled-enquiries' ], 'quoteup'),
                'showing-price'                      => _n('Price visible for %s product.', 'Price visible for %s products.', $bulk_counts[ 'showing-price' ], 'quoteup'),
                'hiding-price'                       => _n('\'Add to Cart\' & Price hidden for %s products.', '\'Add to Cart\' & Price hidden for %s products.', $bulk_counts[ 'hiding-price' ], 'quoteup'),
                'enabled-add-to-cart'                => _n('Add to Cart enabled for %s in stock product.', 'Add to Cart enabled for %s in stock products.', $bulk_counts[ 'enabled-add-to-cart' ], 'quoteup'),
                'disabled-add-to-cart'               => _n('Add to Cart disabled for %s product.', 'Add to Cart disabled for %s products.', $bulk_counts[ 'disabled-add-to-cart' ], 'quoteup'),
                'skipped-enabling-add-to-cart'       => _n('Enabling \'Add to Cart\' skipped for %s product because price is hidden for that product.', 'Enabling \'Add to Cart\' skipped for %s products because price is hidden for those products.', $bulk_counts[ 'skipped-enabling-add-to-cart' ], 'quoteup'),
                'skipped-out-of-stock-products'      => _n('Though Add to Cart is enabled for %s out of product, Add to Cart button is shown only for in-stock products.', 'Though Add to Cart is enabled for %s out of products, Add to Cart button is shown only for in-stock products.', $bulk_counts[ 'skipped-out-of-stock-products' ], 'quoteup'),
                'invalid-products'                   => _n('%s product skipped because it\'s product type is not supported by Product Enquiry Pro (A.K.A QuoteUp). It works only with Simple and Variable Products.', '%s products skipped because their product types are not supported by Product Enquiry Pro (A.K.A QuoteUp). It works only with Simple and Variable Products.', $bulk_counts[ 'invalid-products' ], 'quoteup'),
            );
            return $bulk_messages;
        }

        /**
         * This function is used to print all messages
         * @param  [type] $messages [description]
         * @return [type]           [description]
         */
        public function printMessages($messages)
        {
            if ($messages) {
                echo '<div id="message" class="updated notice is-dismissible"><p>' . join(' ', $messages) . '</p></div>';
            }
        }

        /**
         * This function is used to print all error messages
         * @param  [type] $messages [description]
         * @return [type]           [description]
         */
        public function printErrorMessages($error_messages)
        {
            if ($error_messages) {
                echo '<div id="message" class="error notice is-dismissible"><p>' . join(' ', $error_messages) . '</p></div>';
            }
        }


        /**
         * Display Admin notices after performing bulk action
         */
        public function bulk_action_admin_notices() {
           $bulk_counts = $this->getBulkCounts();

            $enquiry_enabled_if_out_of_stock = _n( 'Enquiry enabled for %s in stock product. Enquiry button will be displayed when that product runs out of stock.', 'Enquiries enabled for %s in stock products. Enquiry button will be displayed on those products only when those products run out of stock.', $bulk_counts[ 'enabled-enquiries-if-out-of-stock' ], 'quoteup' );
            if ( $this->settings == false || (isset( $this->settings[ 'only_if_out_of_stock' ] ) && $this->settings[ 'only_if_out_of_stock' ] == 1) ) {
                $enquiry_enabled_message = _n( 'Enquiry enabled for %s out of stock product', 'Enquiries enabled for %s out of stock products', $bulk_counts[ 'enabled-enquiries' ], 'quoteup' );
                if ( $bulk_counts[ 'enabled-enquiries' ] > 0 ) {
                    $enquiry_enabled_if_out_of_stock = _n( 'and %s in stock product. Enquiry button will be displayed only when the product runs out of stock', 'and %s in stock products. Enquiry button will be displayed only when the products run out of stock', $bulk_counts[ 'enabled-enquiries-if-out-of-stock' ], 'quoteup' );
                }
            } else {
                $enquiry_enabled_message = _n( 'Enquiry enabled for %s product.', 'Enquiries enabled for %s products.', $bulk_counts[ 'enabled-enquiries' ], 'quoteup' );
            }

            $bulk_messages = $this->getBulkMessages($enquiry_enabled_message, $enquiry_enabled_if_out_of_stock, $bulk_counts);

            $messages = array();

            $error_messages = array();

            $bulk_counts = array_filter( $bulk_counts );

            $messagesAndErrorMessages = $this->getMessagesAndErrorMessages($bulk_counts, $bulk_messages);
            $messages = isset($messagesAndErrorMessages['messages']) ? $messagesAndErrorMessages['messages'] : array();
            $error_messages = isset($messagesAndErrorMessages['error_messages']) ? $messagesAndErrorMessages['error_messages'] : array();

            $this->printMessages($messages);

            $this->printErrorMessages($error_messages);

            unset( $messages );

            unset( $error_messages );

            $_SERVER[ 'REQUEST_URI' ] = remove_query_arg( array( 'enabled-enquiries', 'enabled-enquiries-if-out-of-stock',
                'disabled-enquiries', 'showing-price', 'hiding-price', 'enabled-add-to-cart',
                'disabled-add-to-cart', 'skipped-enabling-add-to-cart', 'skipped-out-of-stock-products',
                'invalid-products', 'locked', 'skipped', 'updated', 'deleted', 'trashed', 'untrashed' ), $_SERVER[ 'REQUEST_URI' ] );
        }

        public function getMessagesAndErrorMessages($bulk_counts, $bulk_messages)
        {
                $messages = array();
                $error_messages = array();
            foreach ($bulk_counts as $message => $count) {

                if (isset($bulk_messages[ $message ]) && $message != 'skipped-enabling-add-to-cart') {
                    $messages[] = sprintf($bulk_messages[ $message ], number_format_i18n($count));
                }

                if ($message == 'skipped-enabling-add-to-cart') {
                    $error_messages[] = sprintf($bulk_messages[ $message ], number_format_i18n($count));
                }
            }

            return array(
                'messages' => $messages,
                'error_messages' => $error_messages
            );
        }

        /**
         * Checks whether Price is being shown for the product.
         * @param type $product_id
         * @return boolean
         */
        protected function is_price_showing( $product_id ) {
            $price_enabled = get_post_meta( $product_id, '_enable_price', true );
            if ( $price_enabled == 'yes' ) {
                return true;
            }
            return false;
        }

        public function register_visibility_column( $existing_columns ) {
            if ( empty( $existing_columns ) && ! is_array( $existing_columns ) ) {
                $existing_columns = array();
            }
            $column  = array( 'quoteup_visibility' => quoteupHelpTip( __( 'Use Bulk Actions to change visibility', 'quoteup' ), false, '', __( 'Visibility', 'quoteup' ) ) );
            $keys    = array_keys( $existing_columns );
            $index   = array_search( 'product_tag', $keys );
            $pos     = false === $index ? count( $existing_columns ) : $index + 1;
            return array_merge( array_slice( $existing_columns, 0, $pos ), $column, array_slice( $existing_columns, $pos ) );
        }

        public function render_visibility_column_data( $column, $post_id ) {
            if ( 'quoteup_visibility' == $column ) {
                global $post, $the_product;

                //Do not show icons for products which are not simple and variable
                if ( $the_product->product_type != 'simple' && $the_product->product_type != 'variable' ) {
                    echo '-';
                    return;
                }


                //Show Enquiry Icon
                $enable_pep_meta = get_post_meta( $post_id, '_enable_pep', true );
                if ( $enable_pep_meta == 'yes' ) {
                    //Check if 'Display Enquiry/Quote button only when Out of Stock' is checked.
                    if ( $this->settings == false || (isset( $this->settings[ 'only_if_out_of_stock' ] ) && $this->settings[ 'only_if_out_of_stock' ] == 1) ) {
                        $is_product_in_stock = \quoteupIsProductInStock( $the_product );
                        //Check if product is in stock or not
                        if ( $is_product_in_stock ) {
                            $this->visibility_icon_url( 'enquiry-enabled-if-out-of-stock' );
                        } else {
                            $this->visibility_icon_url( 'enquiry-enabled' );
                        }
                    } else {
                        //'Display Enquiry/Quote button only when Out of Stock' is unchecked
                        $this->visibility_icon_url( 'enquiry-enabled' );
                    }
                } else {
                    $this->visibility_icon_url( 'enquiry-disabled' );
                }

                //Show Price icon
                $enable_price_meta = get_post_meta( $post_id, '_enable_price', true );
                if ( $enable_price_meta == 'yes' ) {
                    $this->visibility_icon_url( 'price-visible' );
                } else {
                    $this->visibility_icon_url( 'price-hidden' );
                }


                //Show Add to cart icon
                $enable_add_to_cart = get_post_meta( $post_id, '_enable_add_to_cart', true );
                if ( $enable_price_meta == 'yes' && $enable_add_to_cart == 'yes' ) {
                    $this->visibility_icon_url( 'add-to-cart-enabled' );
                } else {
                    $this->visibility_icon_url( 'add-to-cart-disabled' );
                }
            }
        }

        protected function visibility_icon_url( $icon ) {
            $image_directory = QUOTEUP_PLUGIN_URL . '/images/';
            switch ( $icon ) {
                case 'add-to-cart-enabled':
                    echo quoteupHelpTip( $this->visibilityIconsTooltip[ 'add-to-cart-enabled' ], false, $image_directory . '/cart.png' );
                    break;
                case 'add-to-cart-disabled':
                    echo quoteupHelpTip( $this->visibilityIconsTooltip[ 'add-to-cart-disabled' ], false, $image_directory . '/cart-disabled.png' );
                    break;
                case 'price-visible':
                    echo quoteupHelpTip( $this->visibilityIconsTooltip[ 'price-visible' ], false, $image_directory . '/price.png' );
                    break;
                case 'price-hidden':
                    echo quoteupHelpTip( $this->visibilityIconsTooltip[ 'price-hidden' ], false, $image_directory . '/price-disabled.png' );
                    break;
                case 'enquiry-enabled':
                    echo quoteupHelpTip( $this->visibilityIconsTooltip[ 'enquiry-enabled' ], false, $image_directory . '/enquiry.png' );
                    break;
                case 'enquiry-enabled-if-out-of-stock':
                    echo quoteupHelpTip( $this->visibilityIconsTooltip[ 'enquiry-enabled-if-out-of-stock' ], false, $image_directory . '/enquiry-enabled-if-out-of-stock.png' );
                    break;
                case 'enquiry-disabled':
                    echo quoteupHelpTip( $this->visibilityIconsTooltip[ 'enquiry-disabled' ], false, $image_directory . '/enquiry-disabled.png' );
                    break;
            }
        }

    }

    // end class

    $productsTable = new QuoteupProductsTable;
    $productsTable->prepare_items();

    $productsTable->process_bulk_action();

    // Prepare our table, this method has already run with the global table object

    // Override the global post table object
    add_filter( 'views_edit-product', function ($views) use ($productsTable) {
        global $wp_list_table;
        // Let's clone it to the global object
        $wp_list_table = clone $productsTable;
        return $views;
    } );
}
