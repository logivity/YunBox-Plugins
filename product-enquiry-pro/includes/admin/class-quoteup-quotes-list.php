<?php

namespace Includes\Admin;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (! class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class QuoteupQuotesList extends \WP_List_Table
{
    public $countFilter;
    public function __construct()
    {
        parent::__construct(array(
            'singular'   => __('Enquiry', 'quoteup')/* singular name of the listed records */,
            'plural'     => __('Enquiries', 'quoteup')/* plural name of the listed records */
        ));
    }

    /**
     * This function gives sql query as per status
     * @return [type] [description]
     */
    public static function getSqlStatus($filter)
    {
        global $wpdb;
        $tableName = $wpdb->prefix.'enquiry_history';

        $sql = "SELECT s1.enquiry_id
                FROM $tableName s1
                LEFT JOIN $tableName s2 ON s1.enquiry_id = s2.enquiry_id
                AND s1.id < s2.id
                WHERE s2.enquiry_id IS NULL AND s1.status ='". $filter ."'AND s1.enquiry_id > 0 AND s1.ID > 0";
        $res = $wpdb->get_col($sql);
        
        $resultSet = join(',', $res);
        return $resultSet;
    }

    public static function getStatusImage($status)
    {
        $statusImage = '';

        switch ($status) {
            case 'Requested':
                $statusImage = "<img title = '". __('Requested', 'quoteup') ."' src = ".QUOTEUP_PLUGIN_URL."/images/requested.png >";
                return $statusImage;
                break;

            case 'Saved':
                $statusImage = "<img title = '". __('Saved', 'quoteup') ."' src = ".QUOTEUP_PLUGIN_URL."/images/saved.png >";
                return $statusImage;
                break;

            case 'Sent':
                $statusImage = "<img title = '". __('Sent', 'quoteup') ."' src = ".QUOTEUP_PLUGIN_URL."/images/sent.png >";
                return $statusImage;
                break;

            case 'Approved':
                $statusImage = "<img title = '". __('Approved', 'quoteup') ."' src = ".QUOTEUP_PLUGIN_URL."/images/approved.png >";
                return $statusImage;
                break;

            case 'Rejected':
                $statusImage = "<img title = '". __('Rejected', 'quoteup') ."' src = ".QUOTEUP_PLUGIN_URL."/images/rejected.png >";
                return $statusImage;
                break;

            case 'Order Placed':
                $statusImage = "<img title = '". __('Order Placed', 'quoteup') ."' src = ".QUOTEUP_PLUGIN_URL."/images/completed.png >";

                return $statusImage;
                break;

            case 'Expired':
                $statusImage = "<img title = '". __('Expired', 'quoteup') ."' src = ".QUOTEUP_PLUGIN_URL."/images/expired.png >";
                return $statusImage;
                break;
        }
        
    }

    public static function orderByQuery()
    {
        if (! empty($_REQUEST[ 'orderby' ])) {
            $sql = ' ORDER BY ' . esc_sql($_REQUEST[ 'orderby' ]);
            $sql .= ! empty($_REQUEST[ 'order' ]) ? ' ' . esc_sql($_REQUEST[ 'order' ]) : ' ASC';
        } else {
            $sql = ' ORDER BY enquiry_id DESC';
        }
        return $sql;
    }

    public static function get_enquiries($per_page = 10, $page_number = 1)
    {

        global $wpdb,$quoteupManageHistory;
        $filterByStatus = "";
        if (isset($_GET['status'])) {
            $filter = $_GET['status'];
        }

        if(isset($filter)) {
            $resultSet = self::getSqlStatus($filter);
            if (null == $resultSet) {
                return;
            }
            $filterByStatus = "  WHERE enquiry_id IN ($resultSet)";

        }

        $sql = "SELECT enquiry_id,product_details,name,email,enquiry_date,message,total FROM {$wpdb->prefix}enquiry_detail_new $filterByStatus";

        $sql .= self::orderByQuery();

        $sql .= " LIMIT $per_page";

        $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;

        $result      = $wpdb->get_results($sql, 'ARRAY_A');
        $new_results = array();
        $admin_path  = get_admin_url();
        foreach ($result as $res) {
            $id      = $res[ 'enquiry_id' ];
            $status  = $quoteupManageHistory->getLastAddedHistory($id);
            $orderid  = \Includes\QuoteupOrderQuoteMapping::getOrderIdOfQuote($id);
            if ($orderid == '0' || $orderid==null) {
                $orderid = '-';
            } else {
                $orderid = '<a href="' . admin_url('post.php?post=' . absint($orderid) . '&action=edit') . '" >'.$orderid.'</a>';
            }
            $statusImage = self::getStatusImage($status['status']);
            $details = array();
            if ($res[ 'product_details' ] != '') {
                $details = unserialize($res[ 'product_details' ]);
            }
            $count   = count($details);
            $name    = $res[ 'name' ];

            $email           = $res[ 'email' ];
            $date            = $res[ 'enquiry_date' ];
            $msg             = $res[ 'message' ];
            $tooltip         = ""; //self :: tooltipOnHover($res)
            $current_data    = array( 'enquiry_id'       => "<a href='$admin_path?page=quoteup-details-edit&id=$id'>$id</a>",
                'status'       => $statusImage,
                'product_details'    => "<a class = 'Items-hover' title='$tooltip'  href='$admin_path?page=quoteup-details-edit&id=$id'> {$count} ". __('Items', 'quoteup') ." </a>",
                'name'               => $name,
                'email'              => $email,
                'enquiry_date'       => $date,
                'message'            => $msg,
                'amount'       => ($res['total'] == null || empty($res['total']))  ? '-' : wc_price($res['total']),
                'order_number'       => $orderid,
            );

            $new_results[] = apply_filters('enquiry_list_table_data', $current_data, $res);
        }

        return $new_results;
    }


    public function get_views()
    {
        $countAll = $countRequested = $countSaved = $countSent = $countApproved = $countRejected = $countPlaced = $countExpired = 0;

        global $wpdb;
        $tableName = $wpdb->prefix.'enquiry_history';

        $sql = "SELECT s1.status,COUNT(s1.enquiry_id) AS EnquiryCount FROM $tableName s1 LEFT JOIN $tableName s2 ON s1.enquiry_id = s2.enquiry_id AND s1.id < s2.id WHERE s2.enquiry_id IS NULL AND s1.status IN ('requested','saved','sent','approved','rejected','Order Placed','expired') AND s1.enquiry_id > 0 AND s1.ID > 0 GROUP BY s1.status";
        $res = $wpdb->get_results($sql, ARRAY_A);
        $this->countFilter = $res;
        self::getCount($res, $countAll, $countRequested, $countSaved, $countSent, $countApproved, $countRejected, $countPlaced, $countExpired);
        
        $requestedURL = get_admin_url('', 'admin.php?page=quoteup-details-new');
        $currentAll = $currentRequested = $currentSaved = $currentSent = $currentApproved = $currentRejected = $currentPlaced = $currentExpired = '';
        if (isset($_GET['status'])) {
            switch ($_GET['status']) {
                case 'requested':
                    $currentRequested = 'current';
                    break;

                case 'saved':
                    $currentSaved = 'current';
                    break;

                case 'sent':
                    $currentSent = 'current';
                    break;

                case 'approved':
                    $currentApproved = 'current';
                    break;

                case 'rejected':
                    $currentRejected = 'current';
                    break;

                case 'Order Placed':
                    $currentPlaced = 'current';
                    break;

                case 'expired':
                    $currentExpired = 'current';
                    break;
            }
        } else {
            $currentAll = 'current';
        }
        $status_links = array(
            "all"       => "<a class=$currentAll id='all' href='".$requestedURL."'>".__('All', 'quoteup') ." <span class='count'>(".$countAll.")</span></a>",
            "requested"       => "<a class='".$currentRequested."'  id='requested' href='".$requestedURL."&status=requested'>". __('Requested', 'quoteup') ."<span class='count'>(".$countRequested.")</span></a>",
            "saved" => "<a class='".$currentSaved."'  id='saved' href='".$requestedURL."&status=saved'>". __('Saved', 'quoteup') ." <span class='count'>(".$countSaved.")</span></a>",
            "sent"   => "<a class='".$currentSent."'  id='sent' href='".$requestedURL."&status=sent'>". __('Sent', 'quoteup') ." <span class='count'>(".$countSent.")</span></a>",
            "approved" => "<a class='".$currentApproved."'  id='approved' href='".$requestedURL."&status=approved'>". __('Approved', 'quoteup') ." <span class='count'>(".$countApproved.")</span></a>",
            "rejected"   => "<a class='".$currentRejected."'  id='rejected' href='".$requestedURL."&status=rejected'>". __('Rejected', 'quoteup') ." <span class='count'>(".$countRejected.")</span></a>",
            "Order Placed" => "<a class='".$currentPlaced."'  id='completed' href='".$requestedURL."&status=Order Placed'>". __('Order Placed', 'quoteup') ." <span class='count'>(".$countPlaced.")</span></a>",
            "expired" => "<a class='".$currentExpired."' id='expired' href='".$requestedURL."&status=expired'>". __('Expired', 'quoteup') ." <span class='count'>(".$countExpired.")</span></a>",
        );
        return $status_links;
    }

    public static function tooltipOnHover($res)
    {
        $tooltip = "<table>";
        $tooltip.="<thead>";
        $tooltip.="<th>". __('Items', 'quoteup') ."</th>";
        $tooltip.="<th>". __('Quantity', 'quoteup') ."</th>";
        $tooltip.="</thead>";
        $details = maybe_unserialize($res[ 'product_details' ]);

        foreach ((array) $details as $row) {
            foreach ((array) $row as $attribute) {
                if (!empty($attribute['variation_id'])) {
                    $productAvailable                 = isProductAvailable($attribute[ 'variation_id' ]);
                    $variationString = "";
                    if (isset($attribute['variation'])) {
                        foreach ($attribute['variation'] as $attributeName => $attributeValue) {
                            if (!empty($variationString)) {
                                $variationString .= ",";
                            }
                            $variationString .= "<b>".wc_attribute_label($attributeName)." </b>:".$attributeValue;
                        }
                    }
                    if ($productAvailable) {
                        $tooltip.="<tr>";
                        $tooltip.="<td>" . $attribute[ 'title' ] . "(".$variationString.")</td>";
                        $tooltip.="<td>" . $attribute[ 'quant' ] . "</td>";
                        $tooltip.="</tr>";
                    } else {
                        $tooltip.="<tr>";
                        $tooltip.="<td> <del>" . $attribute[ 'title' ] . "(".$variationString.")</del></td>";
                        $tooltip.="<td> <del>" . $attribute[ 'quant' ] . "</del></td>";
                        $tooltip.="</tr>";
                    }
                } else {
                    $productAvailable                 = isProductAvailable($attribute[ 'id' ]);
                    if ($productAvailable) {
                        $tooltip.="<tr>";
                        $tooltip.="<td>" . $attribute[ 'title' ] . "</td>";
                        $tooltip.="<td>" . $attribute[ 'quant' ] . "</td>";
                        $tooltip.="</tr>";
                    } else {
                        $tooltip.="<tr>";
                        $tooltip.="<td> <del>" . $attribute[ 'title' ] . "</del></td>";
                        $tooltip.="<td> <del>" . $attribute[ 'quant' ] . "</del></td>";
                        $tooltip.="</tr>";
                    }
                }
            }
        }
        $tooltip.="</table>";
        return $tooltip;
    }

    public static function delete_enquiry($enquiry_id)
    {
        global $wpdb;

        $wpdb->delete("{$wpdb->prefix}enquiry_detail_new", array( 'enquiry_id' => $enquiry_id ), array( '%d' ));
        $wpdb->delete("{$wpdb->prefix}enquiry_history", array( 'enquiry_id' => $enquiry_id ), array( '%d' ));
        $wpdb->delete("{$wpdb->prefix}enquiry_meta", array( 'enquiry_id' => $enquiry_id ), array( '%d' ));
        $wpdb->delete("{$wpdb->prefix}enquiry_quotation", array( 'enquiry_id' => $enquiry_id ), array( '%d' ));
        $wpdb->delete("{$wpdb->prefix}enquiry_thread", array( 'enquiry_id' => $enquiry_id ), array( '%d' ));
    }

    public static function getCount($res, &$countAll, &$countRequested, &$countSaved, &$countSent, &$countApproved, &$countRejected, &$countPlaced, &$countExpired)
    {
        foreach ($res as $key) {
            switch ($key['status']) {
                case 'Requested':
                    $countRequested = $key['EnquiryCount'];
                    $countAll = $countAll + $countRequested;
                    break;

                case 'Saved':
                    $countSaved = $key['EnquiryCount'];
                    $countAll = $countAll + $countSaved;
                    break;

                case 'Sent':
                    $countSent = $key['EnquiryCount'];
                    $countAll = $countAll + $countSent;
                    break;

                case 'Approved':
                    $countApproved = $key['EnquiryCount'];
                    $countAll = $countAll + $countApproved;
                    break;

                case 'Rejected':
                    $countRejected = $key['EnquiryCount'];
                    $countAll = $countAll + $countRejected;
                    break;

                case 'Order Placed':
                    $countPlaced = $key['EnquiryCount'];
                    $countAll = $countAll + $countPlaced;
                    break;

                case 'Expired':
                    $countExpired = $key['EnquiryCount'];
                    $countAll = $countAll + $countExpired;
                    break;
            }
        }
    }

    public static function record_count($res)
    {

        $countAll = $countRequested = $countSaved = $countSent = $countApproved = $countRejected = $countPlaced = $countExpired = 0;

        self::getCount($res, $countAll, $countRequested, $countSaved, $countSent, $countApproved, $countRejected, $countPlaced, $countExpired);

        // foreach ($res as $key) {
        //     if ($key['status'] == 'Requested') {
        //         $countRequested = $key['EnquiryCount'];
        //         $countAll = $countAll + $countRequested;
        //     }

        //     if ($key['status'] == 'Saved') {
        //         $countSaved = $key['EnquiryCount'];
        //         $countAll = $countAll + $countSaved;
        //     }

        //     if ($key['status'] == 'Sent') {
        //         $countSent = $key['EnquiryCount'];
        //         $countAll = $countAll + $countSent;
                
        //     }

        //     if ($key['status'] == 'Approved') {
        //         $countApproved = $key['EnquiryCount'];
        //         $countAll = $countAll + $countApproved;
        //     }

        //     if ($key['status'] == 'Rejected') {
        //         $countRejected = $key['EnquiryCount'];
        //         $countAll = $countAll + $countRejected;
        //     }

        //     if ($key['status'] == 'Completed') {
        //         $countPlaced = $key['EnquiryCount'];
        //         $countAll = $countAll + $countPlaced;
        //     }
        //     if ($key['status'] == 'Expired') {
        //         $countExpired = $key['EnquiryCount'];
        //         $countAll = $countAll + $countExpired;
        //     }
        // }

        if (isset($_GET['status'])) {
            switch ($_GET['status']) {
                case 'requested':
                    return $countRequested;
                    break;

                case 'saved':
                    return  $countSaved;
                    break;

                case 'sent':
                    return $countSent;
                    break;

                case 'approved':
                    return $countApproved;
                    break;

                case 'rejected':
                    return $countRejected;
                    break;

                case 'Order Placed':
                    return $countPlaced;
                    break;

                case 'expired':
                    return $countExpired;
                    break;
            }
        } else {
            return $countAll;
        }
    }

    public function no_items()
    {
        _e('No enquiry & quote details available.', 'quoteup');
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
            'cb'                 => '<input type="checkbox" />',
            'enquiry_id'         => __('ID', 'quoteup'),
            'status'             => __('Status', 'quoteup'),
            'product_details'    => __('Items', 'quoteup'),
            'name'               => __('Customer Name', 'quoteup'),
            'email'              => __('Customer Email', 'quoteup'),
            'enquiry_date'       => __('Enquiry Date', 'quoteup'),
            'amount'             => __('Total', 'quoteup'),
            'order_number'       => __('Order #', 'quoteup'),
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
            // 'status'   => array( 'status', true ),
            // 'amount'   => array( 'amount', true ),
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
        $total_items     = self::record_count($this->countFilter);

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
