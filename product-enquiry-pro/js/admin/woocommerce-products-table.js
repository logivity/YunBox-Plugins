/**
 * This JavaScript file is loaded on WooCommerce's Product's listing page.
 * 
 * @glboal quoteup_products_listing_localization
 */

/**
 * This variable decides whether Actions and Filter form on the products listing table should be 
 * submiited or not. When set to true, form is submittted.
 * 
 * @type Boolean|Boolean
 */
var shouldFormBeSubmitted = true;

jQuery( document ).ready( function () {
    
    //On clicking the 'Action' button set the value of shouldFormBeSubmitted variable
    jQuery( '.button.action' ).click( function () {
	//Check if selected Dropdown value in bulk action is 'Hide Price'.
	if ( ( jQuery( "select[name='action2']" ).val() == 'hide-price' ) || ( jQuery( "select[name='action']" ).val() == 'hide-price' ) ) {
	    var userResponse = confirm( quoteup_products_listing_localization.confirm_message );
	    if(userResponse != true){
		shouldFormBeSubmitted = false;
	    } else {
		shouldFormBeSubmitted = true;
	    }
	}

    } );
    
    //When shouldFormBeSubmitted is set to false, do not submit the form
    jQuery('#posts-filter').submit(function(){
	if(!shouldFormBeSubmitted) {
	    return false;
	}
    });
} );