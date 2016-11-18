<?php

namespace Admin\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * This class is used to create meta box for history on enquiry details page and display history in that box
 */
class QuoteupDisplayHistory {

    protected static $instance = null;
    public $enquiry_details = null;

    /**
     * Function to create a singleton instance of class and return the same
     * @return [Object] [
     * description]
     */
    public static function getInstance() {
	if ( ! self::$instance ) {
	    self::$instance = new self();
	}
	return self::$instance;
    }

    /**
     * Constructor is used to add action for meta box
     */
    public function __construct() {
	// Display the admin notification
	add_action( 'quoteup_edit_details', array( $this, 'historyMeta' ) );
    }

    /**
     * Create Meta box with heading "Quote Status History"
     * @return [type] [description]
     */
    public function historyMeta( $enquiry_details ) {
	$this->enquiry_details	 = $enquiry_details;
	global $quoteup_admin_menu;
	$form_data		 = get_option( 'wdm_form_data' );
	$showQuoteStatusHistory	 = 1;
	if ( isset( $form_data[ 'enable_disable_quote' ] ) && $form_data[ 'enable_disable_quote' ] == 1 ) {
	    $showQuoteStatusHistory = 0;
	}

	if ( $showQuoteStatusHistory == 1 ) {
	    add_meta_box( 'editPEDetailHistory', __( 'Quote Status History', 'quoteup' ), array(
		$this, 'editHistoryFn' ), $quoteup_admin_menu, 'normal' );
	}
    }

    /**
     * This function displays all the history available in enquiry history table for that particular enquiry
     * @return [type] [description]
     */
    public function editHistoryFn() {
	global $wpdb;
	$table_name	 = $wpdb->prefix . "enquiry_history";
	$enquiryID	 = $_GET[ 'id' ];
	$sql		 = $wpdb->prepare( "SELECT enquiry_id, date, message, status, performed_by FROM $table_name WHERE enquiry_id=%s  ORDER BY date DESC", $enquiryID );
	$result		 = $wpdb->get_results( $sql, ARRAY_A );
	?>
	<div class="enquiry-history-table-parent">
	    <table class="enquiry-history-table">
	        <thead class="enquiry-history-table-thead">
	            <tr>
			<th><?php _e( 'Date and Time', 'quoteup' ); ?></th>
	                <th><?php _e( 'Action', 'quoteup' ); ?></th>
	                <th><?php _e( 'Performed by', 'quoteup' ); ?></th>
	                <th><?php _e( 'Message', 'quoteup' ); ?></th>
	            </tr>
	        </thead>
	        <tbody>
	<?php
	foreach ( $result as $History ) {
	    $this->printSingleRow( $History, $this->enquiry_details );
	}
	?>
	        </tbody>
	    </table>
	</div>
		    <?php
		}

		public function printSingleRow( $History, $enquiry_details ) {
		    $this->historyAccept( $History, $enquiry_details );
		    $this->historyReject( $History, $enquiry_details );
		    $this->historySent( $History, $enquiry_details );
		    $this->historyRequested( $History, $enquiry_details );
		    $this->historyQuoteSaved( $History, $enquiry_details );
		    $this->historyQuoteExpired( $History, $enquiry_details );
		    $this->historyPlaced( $History, $enquiry_details );
		}

		private function performedByUser( $History, $enquiry_details, $default = 'visitor' ) {
		    if ( $History[ 'performed_by' ] == null || $History[ 'performed_by' ] == '' || $History[ 'performed_by' ] == 0 ) {
			switch ( $default ) {
			    case 'visitor':
				return $enquiry_details->name;
				break;
			    case 'user':
				return $this->getUserName( get_current_user_id() );
				break;
			}
		    }
		    return $this->getUserName( $History[ 'performed_by' ] );
		}

		private function getUserName( $userId ) {
		    if ( is_numeric( $userId ) ) {
			$user = get_userdata( $userId );
			if ( $user === false ) {
			    return '';
			} else {
			    return $user->display_name;
			}
		    }
		}

		/**
		 * Display current element if history is for acception
		 * @param  [array] $History         [history data]
		 * @param  [array] $enquiry_details [enquiry details of customer]
		 * @return [type]                  [description]
		 */
		private function historyAccept( $History, $enquiry_details ) {
		    if ( $History[ 'status' ] == "Approved" ) {
			?>
	    <tr>
	        <td><?php echo $History[ 'date' ]; ?> </td>
	        <td><?php _e( 'Quote Approved', 'quoteup' ); ?></td>
	        <td><?php echo $this->performedByUser( $History, $enquiry_details, 'visitor' ); ?></td>
	        <td class="history-message"><?php echo $History[ 'message' ] == 'Approved' ? __( 'Approved', 'quoteup' ) : $History[ 'message' ]; ?> </td> 
	    </tr>
	    <?php
	}
    }

    /**
     * Display current element if history is for rejection
     * @param  [array] $History         [history data]
     * @param  [array] $enquiry_details [enquiry details of customer]
     * @return [type]                  [description]
     */
    private function historyReject( $History, $enquiry_details ) {
	if ( $History[ 'status' ] == "Rejected" ) {
	    ?>
	    <tr>
	        <td><?php echo $History[ 'date' ]; ?> </td>
	        <td><?php _e( 'Quote Rejected', 'quoteup' ); ?> </td>
	        <td><?php echo $this->performedByUser( $History, $enquiry_details, 'visitor' ); ?></td>
	        <td class="history-message"><?php echo $History[ 'message' ] == 'Reject' ? __( 'Reject', 'quoteup' ) : $History[ 'message' ]; ?> </td>
	    </tr>
	    <?php
	}
    }

    /**
     * Display current element if history is for Quotation Sent
     * @param  [array] $History         [history data]
     * @param  [array] $enquiry_details [enquiry details of customer]
     * @return [type]                  [description]
     */
    private function historySent( $History, $enquiry_details ) {
	$currentUser = wp_get_current_user();
	if ( $History[ 'status' ] == "Sent" ) {
	    ?>
	    <tr>
	        <td><?php echo $History[ 'date' ]; ?> </td>
	        <td><?php _e( 'Quote Sent', 'quoteup' ); ?></td>
	        <td><?php echo $this->performedByUser( $History, $enquiry_details, 'user' ); ?></td>
	        <td class="history-message"><?php echo $History[ 'message' ] == 'Sent' ? __( 'Sent', 'quoteup' ) : $History[ 'message' ]; ?> </td>
	    </tr>
	    <?php
	}
    }

    /**
     * Display current element if history is for Quotation is requested
     * @param  [array] $History         [history data]
     * @param  [array] $enquiry_details [enquiry details of customer]
     * @return [type]                  [description]
     */
    private function historyRequested( $History, $enquiry_details ) {
	if ( $History[ 'status' ] == "Requested" ) {
	    ?>
	    <tr>
	        <td><?php echo $History[ 'date' ]; ?> </td>
	        <td><?php _e( 'Quote Requested', 'quoteup' ); ?></td>
	        <td><?php echo $enquiry_details->name; ?></td>
	        <!-- <td><?php echo $History[ 'message' ] == 'Requested' ? __( 'Requested', 'quoteup' ) : $History[ 'message' ]; ?> </td> -->
	        <td class="history-message"><?php echo $enquiry_details->message ?></td>
	    </tr>
	    <?php
	}
    }

    /**
     * Display current element if history is Saving Quote
     * @param  [array] $History         [history data]
     * @param  [array] $enquiry_details [enquiry details of customer]
     * @return [type]                  [description]
     */
    private function historyQuoteSaved( $History, $enquiry_details ) {
	if ( $History[ 'status' ] == "Saved" ) {
	    ?>
	    <tr>
	        <td><?php echo $History[ 'date' ]; ?> </td>
	        <td><?php _e( 'Quote Saved', 'quoteup' ); ?></td>
	        <td><?php echo $this->performedByUser( $History, $enquiry_details, 'user' ); ?></td>
	        <td class="history-message"><?php echo $History[ 'message' ] == 'Saved' ? __( 'Saved', 'quoteup' ) : $History[ 'message' ]; ?> </td>  
	    </tr>
	    <?php
	}
    }

    private function historyQuoteExpired( $History, $enquiry_details ) {
	if ( $History[ 'status' ] == "Expired" ) {
	    ?>
	    <tr>
	        <td><?php echo $History[ 'date' ]; ?> </td>
	        <td><?php _e( 'Quote Expired', 'quoteup' ); ?></td>
	        <td><?php _e( 'System' ); ?></td>
	        <td class="history-message"><?php echo $History[ 'message' ] == 'Expired' ? __( 'Expired', 'quoteup' ) : $History[ 'message' ]; ?> </td>  
	    </tr>
	    <?php
        unset($enquiry_details);
	}
    }

    private function historyPlaced( $History, $enquiry_details ) {
	if ( $History[ 'status' ] == "Order Placed" ) {
	    ?>
	    <tr>
	        <td><?php echo $History[ 'date' ]; ?> </td>
	        <td><?php _e( 'Order Placed', 'quoteup' ); ?></td>
	        <td><?php echo $this->performedByUser( $History, $enquiry_details, 'visitor' ); ?></td>
	        <td class="history-message"><?php echo "-"; ?> </td>  
	    </tr>
	    <?php
	}
    }

}

$this->displayHistory = QuoteupDisplayHistory::getInstance();
