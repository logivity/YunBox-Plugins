/*
 *To set the global setting option of add_to_cart to individual product.
 * * To set the global setting option of quoteup_enquiry  to individual product. 
 */
 jQuery(document).ready(function() {

  // //Code to highlight the selected filter
  // var parts = window.location.search.substr(1).split("&");
  // var $_GET = {};
  // for (var i = 0; i < parts.length; i++) {
  //   var temp = parts[i].split("=");
  //   $_GET[decodeURIComponent(temp[0])] = decodeURIComponent(temp[1]);
  // }
  // if (typeof($_GET['status']) == 'undefined') {
  //   $_GET['status'] = 'all';
  // }
  // jQuery('#' + $_GET['status']).addClass('current');
  //End of Code to highlight the selected filter

  jQuery('.button').click(function(e) {
    if (jQuery(this).attr('id') == "doaction") {
      var url = dashboard_quote_listing_bulk_actions.ajax_url;

      if (jQuery('#bulk-action-selector-top').val() == 'bulk-export') {

        var allVals = [];
        jQuery('input[name="bulk-delete[]"]:checked').each(function() {
          allVals.push(jQuery(this).attr('pr-id'));
        });
        //Check if array is empty and show alert if it is.
        if (allVals.length === 0) {
          alert("Select atleast one enquiry to export");
          return false;
        }
        jQuery.ajax({
          type: 'POST',
          url: url,
          data: {
            action: 'wdm_return_rows',
            'ids': allVals,
            'security': dashboard_quote_listing_bulk_actions.export_nonce,
          },
          success: function(response) {
            if (response == 'SECURITY_ISSUE') {
              alert(dashboard_quote_listing_bulk_actions.could_not_create_csv);
              return false;
            }
            jQuery('#data').val(response);
            jQuery('#csv_form').submit();
          },
          error: function(response) {
            // alert("error="+response);
          }
        });

        return false;
      }
    }

    if (jQuery(this).attr('id') == "doaction2") {

      var url = dashboard_quote_listing_bulk_actions.ajax_url;

      if (jQuery('#bulk-action-selector-bottom').val() == 'bulk-export') {

        var allVals = [];
        jQuery('input[name="bulk-delete[]"]:checked').each(function() {
          allVals.push(jQuery(this).attr('pr-id'));
        });
        //Check if array is empty and show alert if it is.
        if (allVals.length === 0) {
          alert(dashboard_quote_listing_bulk_actions.select_one_enquiry);
          return false;
        }
        jQuery.ajax({
          type: 'POST',
          url: url,
          data: {
            action: 'wdm_return_rows',
            'ids': allVals,
            'security': dashboard_quote_listing_bulk_actions.export_nonce,
          },
          success: function(response) {
            if (response == 'SECURITY_ISSUE') {
              alert(dashboard_quote_listing_bulk_actions.could_not_create_csv);
              return false;
            }
            jQuery('#data').val(response);
            jQuery('#csv_form').submit();
          },
          error: function(response) {
            // alert("error="+response);
          }
        });

        return false;
      }
    }

  });

jQuery('.button').click(function(e) {
  var url = dashboard_quote_listing_bulk_actions.ajax_url;
  if (jQuery(this).attr('id') == "doaction") {
    if (jQuery('#bulk-action-selector-top').val() == 'bulk-export-all') {
      jQuery.ajax({
        type: 'POST',
        url: url,
        data: {
          action: 'wdm_return_rows',
          'security': dashboard_quote_listing_bulk_actions.export_nonce,
          'status' : dashboard_quote_listing_bulk_actions.status,

        },
        success: function(response) {
          if (response == 'SECURITY_ISSUE') {
            alert(dashboard_quote_listing_bulk_actions.could_not_create_csv);
            return false;
          }
          jQuery('#data').val(response);
          jQuery('#csv_form').submit();
        },
        error: function(response) {
            // alert("error="+response);
          }
        });

      return false;
    }
  }

  if (jQuery(this).attr('id') == "doaction2") {
    if (jQuery('#bulk-action-selector-bottom').val() == 'bulk-export-all') {
      jQuery.ajax({
        type: 'POST',
        url: url,
        data: {
          action: 'wdm_return_rows',
          'security': dashboard_quote_listing_bulk_actions.export_nonce,
          'status' : dashboard_quote_listing_bulk_actions.status,
        },
        success: function(response) {
          if (response == 'SECURITY_ISSUE') {
            alert(dashboard_quote_listing_bulk_actions.could_not_create_csv);
            return false;
          }
          jQuery('#data').val(response);
          jQuery('#csv_form').submit();
        },
        error: function(response) {
            // alert("error="+response);
          }
        });

      return false;
    }
  }
});
});