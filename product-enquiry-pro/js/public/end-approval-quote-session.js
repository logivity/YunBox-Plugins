/**
 * Ends a session created after Approving the Quote,
 */
jQuery( document ).ready( function () {
    jQuery( "#endsession" ).click( function () {
	var data = {
	    'action': 'clearsession',
	};
	jQuery.post( quote_data.ajax_url, data, function ( response ) {
	    window.location = quote_data.URL;
	} );
    } );
} );