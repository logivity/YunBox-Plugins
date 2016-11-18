jQuery( document ).ready( function () {

	var triggerUpdateCartAutomatically = false;
	var validated = true;
	var error_val = 0;
	var err_string = '';
	jQuery( "body" ).on( 'click', '#btnMPESend', function ( e ) {
		err_string = '';
		jQuery( ".load-send-quote-ajax" ).addClass( 'loading' );
		triggerUpdateCartAutomatically = true;
		sendRequestToUpdateCart( false, false );
		var $this = jQuery( this );

		e.preventDefault();
		var path = jQuery( '#site_url' ).val();
		var cust_name = jQuery( '#frm_mpe_enquiry' ).find( '#custname' ).val();
		var email = jQuery( '#frm_mpe_enquiry' ).find( '#txtemail' ).val();
		var subject = jQuery( '#frm_mpe_enquiry' ).find( '#txtsubject' ).val();
		var message = jQuery( '#frm_mpe_enquiry' ).find( '#txtmsg' ).val();
		var phone = jQuery( '#frm_mpe_enquiry' ).find( '#txtphone' ).val();
		var uemail = jQuery( '#author_email' ).val();
		var fields = wdm_data.fields;

		nm_regex = /^[a-zA-Z ]+$/;
		var enquiry_field;

		if ( fields.length > 0 ) {
			jQuery( '#wdm-quoteupform-error' ).css( 'display', 'none' );
			jQuery( '.error-list-item' ).remove();
			jQuery( '.wdm-error' ).removeClass( 'wdm-error' );
			for ( i = 0; i < fields.length; i++ )
			{

				var temp = jQuery( '#frm_mpe_enquiry' ).find( '#' + fields[i].id ).val();

				var required = fields[i].required;
				if ( fields[i].validation != "" ) {
					var validation = new RegExp( fields[i].validation );
				}

				var message = fields[i].validation_message;
				var flag = 0;
				if ( required == 'yes' ) {
					if ( fields[i].type == "text" || fields[i].type == "textarea" )
					{
						enquiry_field = jQuery( '#frm_mpe_enquiry' ).find( '#' + fields[i].id );
						if ( temp == "" ) {

							enquiry_field.addClass( 'wdm-error' );
							flag = 1;
							error_val = 1;
							err_string += '<li class="error-list-item">' + fields[i].required_message + '</li>';

						} else {
							flag = 0;
							enquiry_field.removeClass( 'wdm-error' );
						}
					}

					else if ( fields[i].type == "radio" )
					{
						jQuery( '#frm_mpe_enquiry' ).find( "[name=" + fields[i].id + "]" ).each( function () {

							var temp1 = jQuery( this );
							if ( temp1.is( ":checked" ) )
							{
								flag = 1;
							}
						} );

						if ( flag == 0 )
						{

							error_val = 1;
							jQuery( '#frm_mpe_enquiry' ).find( '#' + fields[i].id ).parent().css( "cssText", "background:#FCC !important;" );
							err_string += '<li class="wdmquoteup-err-display">' + fields[i].required_message + '</li>';
						} else
						{
							jQuery( '#frm_mpe_enquiry' ).find( '#' + fields[i].id ).parent().css( "cssText", "background:white !important;" );
						}

		    }//radio

		    else if ( fields[i].type == "checkbox" )
		    {
		    	jQuery( '#frm_mpe_enquiry' ).find( "input[name=" + fields[i].id + "\\[\\]]" ).each( function () {

		    		var temp1 = jQuery( this );

		    		if ( temp1.is( ":checked" ) ) {
		    			flag = 1;

		    		}
		    	} );
		    	if ( flag == 0 ) {

		    		error_val = 1;
		    		jQuery( '#frm_mpe_enquiry' ).find( '#' + fields[i].id ).parent().css( "cssText", "background:#FCC !important;" );
		    		err_string += '<li class="error-list-item">' + fields[i].required_message + '</li>';
		    	} else
		    	{
		    		jQuery( '#frm_mpe_enquiry' ).find( '#' + fields[i].id ).parent().css( "cssText", "background:white !important;" );
		    	}

		    }//checkbox
		    else if ( fields[i].type == "select" )
		    {
		    	jQuery( '#frm_mpe_enquiry' ).find( "[name=" + fields[i].id + "]" ).each( function () {
		    		var temp1 = jQuery( this );
		    		if ( temp1.val() != "#" ) {
		    			flag = 1;

		    		}
		    	} );
		    	if ( flag == 0 )
		    	{
		    		error_val = 1;
		    		jQuery( '#frm_mpe_enquiry' ).find( '#' + fields[i].id ).parent().css( "cssText", "background:#FCC !important;" );
		    		err_string += '<li class="wdmquoteup-err-display">' + fields[i].required_message + '</li>';
		    	} else
		    	{
		    		jQuery( '#frm_mpe_enquiry' ).find( '#' + fields[i].id ).parent().css( "cssText", "background:white !important;" );
		    	}
		    }
		}//required

		if ( flag == 0 )
			if ( fields[i].validation != "" && temp != "" )
			{
				if ( fields[i].id == "txtphone" && wdm_data.country != '' )
				{
					if ( !isValidNumber( phone, wdm_data.country ) )
					{
						enquiry_field = jQuery( '#frm_mpe_enquiry' ).find( '#' + fields[i].id );
						enquiry_field.addClass( 'wdm-error' );
						err_string += '<li class="error-list-item">' + message + '</li>';
						error_val = 1;
					}
					else
					{
						country = wdm_data.country;
						jQuery( '#frm_mpe_enquiry' ).find( '#' + fields[i].id ).val( formatInternational( country, phone ) );
					}
			}//txtphone


			else if ( !validation.test( temp ) )
			{
				enquiry_field = jQuery( '#frm_mpe_enquiry' ).find( '#' + fields[i].id );
				enquiry_field.addClass( 'wdm-error' );
				err_string += '<li class="error-list-item">' + message + '</li>';
				error_val = 1;
			}

		}
	    }//for feilds loop
	}//if


	if ( error_val == 0 )
	{
		jQuery( '#btnMPESend' ).attr( 'disabled', 'disabled' );
		jQuery( '.wdmquoteup-loader' ).css( 'display', 'inline-block' );
	    // jQuery('#frm_mpe_enquiry').find('#error' ).html( '' );
	    jQuery( '#submit_value' ).val( 1 );
	    fun_set_cookie();

	    if ( jQuery( "#contact-cc" ).is( ":checked" ) )
	    {
	    	var wdm_checkbox_val = 'checked';

	    }
	    else
	    {
	    	var wdm_checkbox_val = 0;

	    }

	    validate_enq = {
	    	action: 'quoteupValidateNonce',
	    	security: jQuery( '#mpe_ajax_nonce' ).val(),
	    }
	    nonce_error = 0;

	    jQuery.post( wdm_data.ajax_admin_url, validate_enq, function ( response )
	    {
	    	if ( response == '' )
	    	{
	    		jQuery( ".load-send-quote-ajax" ).removeClass( 'loading' );
	    		jQuery( '.wdmquoteup-loader' ).css( 'display', 'none' );
	    		jQuery( '#frm_mpe_enquiry' ).find( '#nonce_error' ).css( 'display', 'block' );
	    		nonce_error = 1;

	    	}
	    	else
	    	{
	    		jQuery( '.wdmquoteup-loader' ).css( 'display', 'none' );
	    		mydatavar = {
	    			action: 'quoteupSubmitWooEnquiryForm',
	    			security: jQuery( '#mpe_ajax_nonce' ).val(),
	    			cc: wdm_checkbox_val,
	    		};

	    		jQuery( ".quoteup_registered_parameter" ).each( function () {
	    			mydatavar[jQuery( this ).attr( 'id' )] = jQuery( this ).val();
	    		} );
	    		if ( fields.length > 0 ) {

	    			for ( i = 0; i < fields.length; i++ ) {

	    				if ( fields[i].type == 'text' || fields[i].type == 'textarea' || fields[i].type == 'select' ) {

	    					mydatavar[fields[i].id] = jQuery( '#frm_mpe_enquiry' ).find( "#" + fields[i].id ).val();


	    				}
	    				else if ( fields[i].type == 'radio' )
	    				{

	    					mydatavar[fields[i].id] = jQuery( '#frm_mpe_enquiry' ).find( "[name='" + fields[i].id + "']:checked" ).val();
	    				}
	    				else if ( fields[i].type == 'checkbox' )
	    				{

	    					var selected = "";
	    					jQuery( '#frm_mpe_enquiry' ).find( "[name='" + fields[i].id + "[]']:checked" ).each( function () {
	    						if ( selected == "" ) {

	    							selected = jQuery( this ).val();
	    						} else
	    						selected += "," + jQuery( this ).val();
	    					} );

	    					mydatavar[fields[i].id] = selected;
	    				}
	    				else if ( fields[i].type == 'multiple' )
	    				{
	    					var selected = "";
	    					selected = jQuery( '#frm_mpe_enquiry' ).find( "#" + fields[i].id ).multipleSelect( 'getSelects' ).join( ',' );

	    					mydatavar[fields[i].id] = selected;
	    				}

	    			}
	    		}
	    		jQuery( '#wdm-cart-count' ).hide();
		    // $this.parent().parent('form').siblings('#success_'+id_array[1]).show();
		    jQuery.post( wdm_data.ajax_admin_url, mydatavar, function ( response ) {
		    	if ( response == 'COMPLETED' ) {
		    		jQuery( '.success' ).slideDown();
		    		jQuery('.quoteup-quote-cart').slideUp();
		    		if ( wdm_data.redirect != 'n' ) {
		    			window.location = wdm_data.redirect;
		    		}
		    	}
		    } );

		}


	} );

}
else
{
	jQuery( ".load-send-quote-ajax" ).removeClass( 'loading' );
	jQuery( '#wdm-quoteupform-error' ).css( 'display', 'block' );
	jQuery( '#wdm-quoteupform-error > .form-errors > ul.error-list' ).html( err_string );
	return false;
}

return false;
} );


jQuery( '.remove' ).on( 'click', function ( e )
{
	e.preventDefault();
	product_id = jQuery( this ).attr( 'data-product_id' );
	product_var_id = jQuery( this ).attr( 'data-variation_id' );
	product_variation_details = jQuery( this ).attr( 'data-variation' );
	quant = 0;
	remark = "";
	//remove the row.
	jQuery( this ).parents( 'tr.cart_item' ).remove();

	if ( jQuery( '.cart_item' ).length == 1 ) {
		jQuery( '.wdm-quote-cart-table' ).append( "<tr> <td colspan='6 class='no-product'> Your cart is currently empty. </td></tr>" );
		jQuery( '.td-btn-update' ).remove();
		jQuery( '.wdm-enquiry-form' ).remove();
	}

	jQuery.ajax( {
		url: wdm_data.ajax_admin_url,
		method: 'post',
		data:
		{
			action: 'wdm_update_enq_cart_session',
			'product_id': product_id,
			'product_var_id': product_var_id,
			'quantity': quant,
			'variation' : JSON.parse(product_variation_details),
			'clickcheck': 'remove'
			// 'remark' : remark
		},
		success: function ( response )
		{
			count = jQuery( '.wdm-quoteupicon-count' ).text();
			count = parseInt( count ) - 1;
			jQuery( '.wdm-quoteupicon-count' ).text( count );
		},
		error: function ( error )
		{
			console.log( error );
		}

	} );
} );

jQuery( '.wdm-update' ).click( function () {


	sendRequestToUpdateCart( true, true );

} );

function sendRequestToUpdateCart( showUpdateCartImage, showAlertAfterUpdateCart ) {
	document.getElementById( "error-quote-cart" ).innerHTML = "";
	validated = true;
	error_val = 0;
	jQuery( '.td-btn-update' ).find( '.load-ajax' ).removeClass( 'updated' );
	jQuery( '.cart_product' ).each( function () {
		thiss = jQuery( this );
		thiss.find( '.wdm-prod-quant' ).css( 'border-color', '#515151' );
	} )
	jQuery( '.cart_product' ).each( function () {
		thiss = jQuery( this );
		quant = thiss.find( '.wdm-prod-quant' ).val();
		if ( quant < 1 ) {
			thiss.find( '.wdm-prod-quant' ).css( 'border-color', 'red' );
			validated = false;
			err_string = '<li class="error-list-item">' + wdm_data.cart_not_updated + '</li>';
			error_val = 1;
		}

		if ( Number( quant ) % 1 !== 0 ) {
			thiss.find( '.wdm-prod-quant' ).css( 'border-color', 'red' );
			validated = false;
			err_string = '<li class="error-list-item">' + wdm_data.cart_not_updated + '</li>';
			error_val = 1;
		}

	} )

	if ( validated ) {
		if ( jQuery( '.cart_item' ).length > 1 ) {
			if ( showUpdateCartImage ) {

				jQuery( '.td-btn-update' ).find( '.load-ajax' ).removeClass( 'updated' ).addClass( 'loading' );
			}

			jQuery( '.cart_item' ).each( function () {
				thiss = jQuery( this );
				prod_id = thiss.find( '.wdm-prod-quant' ).attr( 'data-product_id' );
				product_var_id = thiss.find( '.wdm-prod-quant' ).attr( 'data-variation_id' );
				product_variation_details = thiss.find( '.wdm-prod-quant' ).attr( 'data-variation' );
				if ( !isNaN( prod_id ) )
				{
					quant = thiss.find( '.wdm-prod-quant' ).val();
					remark = thiss.find( '.wdm-remark' ).val();
					jQuery.ajax( {
						url: wdm_data.ajax_admin_url,
						method: 'post',
						dataType: "JSON",
						data:
						{
							action: 'wdm_update_enq_cart_session',
							'product_id': prod_id,
							'product_var_id': product_var_id,
							'quantity': quant,
							'remark': remark,
							'variation' : JSON.parse(product_variation_details),

						},
						success: function ( response )
						{
							if ( showUpdateCartImage ) {
								jQuery( '.td-btn-update' ).find( '.load-ajax' ).removeClass( 'loading' ).addClass( 'updated' );
							}

							if ( response.variation_id == undefined ) {
								jQuery( 'input[data-product_id="' + response.product_id + '"]' ).closest( 'tr' ).find( '.product-price' ).html( response.price );
							} else {
								jQuery( 'input[data-variation_id="' + response.variation_id + '"][data-variation=\'' + JSON.stringify(response.variation_detail) + '\']' ).closest( 'tr' ).find( '.product-price' ).html( response.price );
							}

							jQuery( '.sold_individually' ).val( 1 );

						},
						error: function ( error )
						{
							console.log( error );
						}
					} );
}
} );
} else {
	jQuery( '.wdm-quote-cart-table' ).append( "<tr> <td colspan='6> No Products available in Quote Cart </td></tr>" );
}

} else {
	document.getElementById( "error-quote-cart" ).innerHTML = wdm_data.cart_not_updated;
}
}

} );


function fun_set_cookie()
{
	var cname = document.getElementById( 'custname' ).value;
	var cemail = document.getElementById( 'txtemail' ).value;
	if ( cname != '' && cemail != '' )
	{
		var d = new Date();
		d.setTime( d.getTime() + ( 90 * 24 * 60 * 60 * 1000 ) );
		var expires = "expires=" + d.toGMTString();
		document.cookie = "wdmusername=" + cname + "; expires=" + expires + "; path=/";
		document.cookie = "wdmuseremail=" + cemail + "; expires=" + expires + ";path=/";
	}

}