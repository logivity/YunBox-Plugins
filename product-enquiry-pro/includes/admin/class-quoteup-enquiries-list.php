<?php

namespace Includes\Admin;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (! class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class QuoteupEnquiriesList extends \WP_List_Table
{
    public $countFilter;
    public function __construct()
    {
        parent::__construct(array(
            'singular'   => __('Enquiry', 'quoteup')/* singular name of the listed records */,
            'plural'     => __('Enquiries', 'quoteup')/* plural name of the listed records */
        ));
    }


    public static function get_enquiries($per_page = 10, $page_number = 1)
    {
        global $wpdb;

        $sql = "SELECT enquiry_id,product_details,name,email,enquiry_date,message FROM {$wpdb->prefix}enquiry_detail_new";

        if (!empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY '.esc_sql($_REQUEST['orderby']);
            $sql .= !empty($_REQUEST['order']) ? ' '.esc_sql($_REQUEST['order']) : ' ASC';
        } else {
            $sql .= ' ORDER BY enquiry_id DESC';
        }

        $sql .= " LIMIT $per_page";

        $sql .= ' OFFSET '.($page_number - 1) * $per_page;

        $result = $wpdb->get_results($sql, 'ARRAY_A');
        $new_results = array();
        $admin_path = get_admin_url();
        foreach ($result as $res) {
            $id = $res['enquiry_id'];
            $details = array();
            if ($res['product_details'] != '') {
                $details = unserialize($res['product_details']);
            }
            $count = count($details);
            $name = $res['name'];

            $email = $res['email'];
            $date = $res['enquiry_date'];
            $msg = $res['message'];
            $tooltip= "";
            $current_data=array('enquiry_id' => "<a href='$admin_path?page=quoteup-details-edit&id=$id'>$id</a>",
                'product_details' => "<a class = 'Items-hover' title='$tooltip'  href='$admin_path?page=quoteup-details-edit&id=$id'> {$count} Items </a>",
                'name' => $name,
                'email' => $email,
                'enquiry_date' => $date,
                'message' => $msg,
            );

            $new_results[] = apply_filters('enquiry_list_table_data', $current_data, $res);
        }

        return $new_results;
    }

    // public static function tooltipOnHover($res)
    // {
    //     $tooltip = "<table>";
    //     $tooltip.="<thead>";
    //     $tooltip.="<th> Items </th>";
    //     $tooltip.="<th> Quantity </th>";
    //     $tooltip.="</thead>";
    //     $details = maybe_unserialize($res[ 'product_details' ]);

    //     foreach ((array) $details as $row) {
    //         foreach ((array) $row as $attribute) {
    //             $productAvailable                 = isProductAvailable($attribute[ 'id' ]);
    //             if ($productAvailable) {
    //                 $tooltip.="<tr>";
    //                 $tooltip.="<td>" . $attribute[ 'title' ] . "</td>";
    //                 $tooltip.="<td>" . $attribute[ 'quant' ] . "</td>";
    //                 $tooltip.="</tr>";
    //             } else {
    //                 $tooltip.="<tr>";
    //                 $tooltip.="<td> <del>" . $attribute[ 'title' ] . "</del></td>";
    //                 $tooltip.="<td> <del>" . $attribute[ 'quant' ] . "</del></td>";
    //                 $tooltip.="</tr>";
    //             }
    //         }
    //     }
    //     $tooltip.="</table>";
    //     return $tooltip;
    // }

    public static function delete_enquiry($enquiry_id)
    {
        global $wpdb;

        $wpdb->delete("{$wpdb->prefix}enquiry_detail_new", array( 'enquiry_id' => $enquiry_id ), array( '%d' ));
        $wpdb->delete("{$wpdb->prefix}enquiry_history", array( 'enquiry_id' => $enquiry_id ), array( '%d' ));
        $wpdb->delete("{$wpdb->prefix}enquiry_meta", array( 'enquiry_id' => $enquiry_id ), array( '%d' ));
        $wpdb->delete("{$wpdb->prefix}enquiry_quotation", array( 'enquiry_id' => $enquiry_id ), array( '%d' ));
        $wpdb->delete("{$wpdb->prefix}enquiry_thread", array( 'enquiry_id' => $enquiry_id ), array( '%d' ));
    }

    public static function record_count()
    {
        global $wpdb;

        $sql = "SELECT COUNT(enquiry_id) FROM {$wpdb->prefix}enquiry_detail_new";

        return $wpdb->get_var($sql);
    }

    public function no_items()
    {
        _e('No enquiries avaliable.', 'quoteup');
    }

    public function column_enquiry_id($item)
    {
        $enquiry_id  = strip_tags($item[ 'enquiry_id' ]);
        $nonce       = wp_create_nonce('wdm_enquiry_actions');
        $admin_path  = get_admin_url();

        $actions = array(
            'edit' => sprintf('<a href="%s?page=%s&id=%s">%s</a>', $admin_path, 'quoteup-details-edit', $enquiry_id, __('Edit', 'quoteup')),

            'delete' => sprintf('<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">%s</a>', esc_attr($_REQUEST[ 'page' ]), 'delete', $enquiry_id, $nonce, __('Delete', 'quoteup'))
        );

        return sprintf('%s %s', $item[ 'enquiry_id' ], $this->row_actions($actions));
    }

    public function column_default($item, $column_name)
    {
        return $item[ $column_name ];
    }

    public function column_cb($item)
    {
        $enquiry_id = strip_tags($item[ 'enquiry_id' ]);

        return sprintf('<input type="checkbox" name="bulk-delete[]" pr-id="%d" value="%s" />', $enquiry_id, $item[ 'enquiry_id' ]);
    }

    public function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'enquiry_id' => __('ID', 'quoteup'),
            'product_details' => __('Items', 'quoteup'),
            'name' => __('Customer Name', 'quoteup'),
            'email' => __('Customer Email', 'quoteup'),
            'enquiry_date' => __('Enquiry Date', 'quoteup'),
            'message' => __('Message', 'quoteup'),
        );

        return $columns;
    }

    public function get_hidden_columns()
    {
        $hidden_columns = get_user_option('managetoplevel_page_quoteup-details-newcolumnshidden');
        if (!$hidden_columns) {
            $hidden_columns=array();
        }

        return $hidden_columns;
    }

    public function get_sortable_columns()
    {
        $sortable_columns = array(
            'enquiry_id'     => array( 'enquiry_id', true ),
            'name'           => array( 'name', true ),
            'email'          => array( 'email', true ),
            'enquiry_date'   => array( 'enquiry_date', true ),
        );

        return $sortable_columns;
    }

    public function get_bulk_actions()
    {
        $actions = array(
            'bulk-export'        => __('Export', 'quoteup'),
            'bulk-export-all'    => __('Export all enquiries', 'quoteup'),
            'bulk-delete'        => __('Delete', 'quoteup'),
        );

        return $actions;
    }


    public function prepare_items()
    {
        $columns     = $this->get_columns();
        $hidden      = $this->get_hidden_columns();
        $sortable    = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        /* Process bulk action */
        $this->process_bulk_action();
        $this->views();

        // $user = get_current_user_id();
        // $screen = get_current_screen();
        // $option = $screen->get_option('per_page', 'option');

        $per_page        = $this->get_items_per_page('enquiries_per_page', 10);
        $current_page    = $this->get_pagenum();
        $total_items     = self::record_count();

        $this->set_pagination_args(array(
            'total_items'    => $total_items, //WE have to calculate the total number of items
            'per_page'       => $per_page, //WE have to determine how many items to show on a page
        ));

        $this->items = self::get_enquiries($per_page, $current_page);
    }

    public function process_bulk_action()
    {
        // global $wpdb;
        //Detect when a single delete is being triggered...
        if ('delete' === $this->current_action()) {
            $nonce = esc_attr($_REQUEST[ '_wpnonce' ]);

            if (! wp_verify_nonce($nonce, 'wdm_enquiry_actions')) {
                die('Go get a life script kiddies');
            } else {
                self::delete_enquiry(absint($_GET[ 'id' ]));
                echo $div = "<div class='updated'><p>Enquiry is deleted successfully</p></div>";
                //wp_redirect(esc_url($_SERVER['REQUEST_URI']));
            }
        }

        // If the delete bulk action is triggered
        if ((isset($_POST[ 'action' ]) && $_POST[ 'action' ] == 'bulk-delete') || (isset($_POST[ 'action2' ]) && $_POST[ 'action2' ] == 'bulk-delete')
        ) {
            if (isset($_POST[ 'bulk-delete' ])) {
                $delete_ids = esc_sql($_POST[ 'bulk-delete' ]);

                // loop over the array of record IDs and delete them
                foreach ($delete_ids as $id) {
                    $id = strip_tags($id);
                    self::delete_enquiry($id);
                }
                $count = count($delete_ids);

                echo $div = "<div class='updated'><p> $count ". __('enquiries are deleted', 'quoteup') ."</p></div>";
                //wp_redirect(esc_url($_SERVER['REQUEST_URI']));
                //exit;
            } else {
                echo $div = "<div class='error'><p>". __('Select Enquiries to delete', 'quoteup')  ."</p></div>";
            }
        }
    }
}
