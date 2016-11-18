/*
 * Jquery File for client side validations and ajax call
 */

 jQuery( document ).ready( function ( $ ) {

    /**
     * Set to true when Saving Quotation process is completed. It could be successful or failed.
     * @type {Boolean}
     */
     var isSavingQuoteCompleted = false;

    /**
     * Holds the reason why Quote Saving functionalty failed.
     * @type {String}
     */
     var quoteSaveFailureReason = ''

    /**
     * isAllowedToShowPdf is used to determine whether to show PDF popup or not
     * @type {Boolean}
     */
     var isAllowedToShowPdf = true;

    /**
     * Set to true if error occurs during saving the Quote.
     * @type {Boolean}
     */
     var hasErrorOccurredSavingQuote = false;

    /**
     * Set to true when PDF generation is completed.
     * @type {Boolean}
     */
     var isPdfGenerationCompleted = false;

    /**
     * Set to true whenever is data changed after last pdf generation. 
     * If this is set to true, PDF is Generated again.
     */
     var isDataChangedAfterLastPDFGeneration = false;

    //Set isDataChangedAfterLastPDFGeneration when user comes to the page. Since PDF is deleted after 
    //every two hours, if user comes after two hours, program should generate it again.
    if ( quote_data.lastGeneratedPDFExists == false ) {
     isDataChangedAfterLastPDFGeneration = true;
   }

    //Hide div which displays the success message on updating Name and Email on focussing the fields
    jQuery( '#input-name, #input-email' ).focus( function () {
     jQuery( "#update-text" ).css( "visibility", "hidden" );
     jQuery( "#update-customerdata-load" ).css( "visibility", "hidden" );
   } );

    // Update customer data on username and email change
    jQuery( '#input-name, #input-email' ).change( function () {
     jQuery( "#update-customerdata-load" ).css( "visibility", "visible" );
     var enquiry_id = jQuery( '#enquiry_id' ).val();
     var cname = jQuery( 'input[name="cust_name"]' ).val();
     var cemail = jQuery( 'input[name="cust_email"]' ).val();
     if ( isValidName( cname ) ) {
       if ( isValidEmailAddress( cemail ) ) {
        var data = {
		    'action': 'modify_user_data', //Action to store quotation in database
		    'enquiry_id': enquiry_id,
		    'cname': cname,
		    'email': cemail,
		    'security': jQuery( '#nonce' ).val(),
      };

      jQuery.post( quote_data.ajax_url, data, function ( response ) {

        if ( response == 'SECURITY_ISSUE' || response != 'Saved Successfully.' ) {
         $failureMessage = ( response == 'SECURITY_ISSUE' ) ? quote_data.data_update_aborted : response;
         console.log( $failureMessage );
         return;
       }

       if ( response == 'Saved Successfully.' ) {
         response = quote_data.data_updated;
         jQuery( '.wdm-enquiry-usr' ).val( cemail );
         jQuery( "#update-text" ).css( "visibility", "visible" );
         jQuery( "#update-text" ).removeClass( 'error' );
         jQuery( "#update-text" ).addClass( 'updated' );
         document.getElementById( "update-text" ).innerHTML = response;

       }
     } );
    } else {
      $failMessage = quote_data.data_not_updated_email;
      jQuery( "#update-text" ).css( "visibility", "visible" );
      jQuery( "#update-text" ).removeClass( 'updated' );
      jQuery( "#update-text" ).addClass( 'error' );
      document.getElementById( "update-text" ).innerHTML = $failMessage;
		// jQuery( 'input[name="cust_email"]' ).val("");
 }
} else {
 $failMessage = quote_data.data_not_updated_name;
 jQuery( "#update-text" ).css( "visibility", "visible" );
 jQuery( "#update-text" ).removeClass( 'updated' );
 jQuery( "#update-text" ).addClass( 'error' );
 document.getElementById( "update-text" ).innerHTML = $failMessage;
	    // jQuery( 'input[name="input-name"]' ).val("");
   }
   jQuery( "#update-customerdata-load" ).css( "visibility", "hidden" );

 } );

    /**
     * Checks whether email address provided as parameter is valid or not
     * @param {string} emailAddress
     * @returns {Boolean} returns true if valid email address
     */
     function isValidEmailAddress( emailAddress ) {
       var pattern = /^[-a-z0-9~!$%^&*_=+}{\'?]+(\.[-a-z0-9~!$%^&*_=+}{\'?]+)*@([a-z0-9_][-a-z0-9_]*(\.[-a-z0-9_]+)*\.(aero|arpa|biz|com|coop|edu|gov|info|int|mil|museum|name|net|org|pro|travel|mobi|[a-z][a-z])|([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}))(:[0-9]{1,5})?$/i;
       return pattern.test( emailAddress );
     };

    /**
     * Checks whether string provided as parameter is valid as a name or not
     * @param {string} name
     * @returns {Boolean} returns true if valid name
     */
     function isValidName( name ) {
       var pattern = /^[a-zA-Z\u00C0-\u00ff ]+$/;
       return pattern.test( name );
     }

     function enableSendQuotationButton( enablePreviewQuotation ) {
       var human_readable_expiration_date = jQuery( '.wdm-input-expiration-date' ).val();
       if ( !empty( human_readable_expiration_date ) ) {
         var expiration_date_object = new Date( human_readable_expiration_date );
         var todays_date_object = new Date( quote_data.todays_date );
         if ( todays_date_object.getTime() <= expiration_date_object.getTime() ) {
          jQuery( '#send' ).prop( 'disabled', false );
          if ( enablePreviewQuotation ) {
            jQuery( '#btnPQuote' ).prop( 'disabled', false );
          }
          jQuery( ".send-quotation-button-disabled-note" ).hide();
        }
      } else {
       jQuery( '#send' ).prop( 'disabled', false );
       if ( enablePreviewQuotation ) {
        jQuery( '#btnPQuote' ).prop( 'disabled', false );
      }
    }
  }

  jQuery( '.wdm-input-expiration-date' ).change( function () {
   enableSendQuotationButton( true );
 } );
    /**
     * If any input field is changed, disable Download PDF button and regenerate PDF.
     */
     jQuery( "#show_price, .newprice, .newqty, input[name='cust_name'], input[name='cust_email'], .wdm-input-expiration-date, .wdm-checkbox-quote" ).change( function () {
       disableSendDownloadBtn();

     } );

     /**
      * When Variation Dropdown is changed, the following actions should be performed
      */
      jQuery( document ).on( 'change', '.variations select', function () {
        disableSendDownloadBtn();
	/**
	 * Check if selected variation was already present in the enquiry and if present, set old price
	 * as price available during enquiry
	 */
   var $shouldOriginalPriceBeRetrieved = true;
   var $rowOfCurrentVariation = jQuery( this ).closest( '.wdmpe-detailtbl-content-row' );
   var $oldPriceCell = $rowOfCurrentVariation.find( '.item-content-old-cost' );
   var $oldPriceData = $oldPriceCell.data( 'old_price' );

	/**
	 * If value of all variation attributes match with the ones selected during enquiry, then 
	 * show old price
	 */
   for ( var variation_attribute in $oldPriceData.variation ) {
     if ( $oldPriceData.variation.hasOwnProperty( variation_attribute ) ) {
      if ( $rowOfCurrentVariation.find( ".variations select[name='attribute_" + variation_attribute + "']" ).val() != $oldPriceData.variation[variation_attribute] ) {
        $shouldOriginalPriceBeRetrieved = false;
        break;
      }
    }
  }

  if ( $shouldOriginalPriceBeRetrieved ) {
   $oldPriceCell.find( '.amount' ).html( quoteupFormatPrice( $oldPriceData.price ) );
   $oldPriceCell.find( 'input' ).val( $oldPriceData.price );
 } else {
	    /**
	     * Finds out price of a selected variation saved on Product Edit page and set it as old
	     * price
	     */
       var $productData = jQuery( this ).closest( '.product' );
       var $variationId = $productData.find( '.variation_id' ).val();
       var $allVariations = $productData.find( '.variations_form.cart' ).data( 'product_variations' );
	    /**
	     * Find out data of current selected variation from $allVariations object and set that data
	     * in 'Price' (i.e Old Price) column
	     */
       for ( $i = 0; $i < $allVariations.length; $i++ ) {
        if ( $allVariations[$i].variation_id == $variationId ) {
          $oldPriceCell.find( '.amount' ).html( quoteupFormatPrice( $allVariations[$i].display_regular_price ) );
          $oldPriceCell.find( 'input' ).val( $allVariations[$i].display_regular_price );
          if ( !empty( $allVariations[$i].display_price ) ) {
           $oldPriceCell.find( '.amount' ).html( quoteupFormatPrice( $allVariations[$i].display_price ) );
           $oldPriceCell.find( 'input' ).val( $allVariations[$i].display_price );
         }

         break;
       }
     }
   }
 } );

jQuery( "#show_price, .newprice, .newqty, input[name='cust_name'], input[name='cust_email'], .wdm-input-expiration-date, .wdm-checkbox-quote" ).keyup( function () {
	disableSendDownloadBtn();
} )


function disableSendDownloadBtn() {
	// return;
	isDataChangedAfterLastPDFGeneration = true;
	jQuery( "#btnPQuote" ).val( quote_data.save_and_preview_quotation );
	jQuery( "#send" ).css( 'display', 'none' );
	jQuery( "#send" ).val( quote_data.save_and_send_quotation );
	if ( jQuery( '#send' ).is( ":disabled" ) ) {
   jQuery( "#btnPQuote" ).prop( 'disabled', true );
 }
 jQuery( "#downloadPDF" ).attr( "disabled", true );
 jQuery( "#downloadPDF" ).css( "display", 'none' );
}
    /**
     * This is for Saving Quote. 
     */
     function saveQuotation() {

       if ( isDataChangedAfterLastPDFGeneration == false ) {
         jQuery( "#PdfLoad" ).css( "visibility", "hidden" );
         jQuery( "#btnPQuote" ).attr( "disabled", false );
         enableSendQuotationButton( false );
	    // jQuery("#text").css("visibility", "visible");
	    isSavingQuoteCompleted = true;
	    hasErrorOccurredSavingQuote = false;
	    return;
   }
	isSavingQuoteCompleted = false; //Set isSavingQuoteCompleted to false so that program waits till that tasks get completed.
	hasErrorOccurredSavingQuote = false;
	jQuery( "#btnPQuote" ).attr( "disabled", true );
	jQuery( "#send" ).attr( "disabled", true );
	jQuery( "#downloadPDF" ).attr( "disabled", true );

	jQuery( "#PdfLoad" ).css( "visibility", "visible" );
	jQuery( "#text" ).css( "visibility", "visible" );
	displayAjaxResponseMessages( quote_data.save );
	var total_rows = jQuery( '#Quotation tr' ).length;
	var quantity = [ ];
	var variation_id = [ ];
  var variation_index_in_enquiry = [ ];
  var variationDetails = [ ];
  var totalQuantity = parseInt( "0" );
  var newprice = [ ];
  var id = [ ];
  var old_price = [ ];
  var enquiry_id = jQuery( '#enquiry_id' ).val();
  for ( i = 0; i < total_rows - 2; i++ ) {
   var rowNumber = i + 1;
   if ( jQuery( '#add-to-quote-' + rowNumber ).is( ':checked' ) ) {
    old_price[i] = jQuery( '#old-price-' + rowNumber ).val();
    variation_index_in_enquiry[i] = rowNumber - 1;
    id[i] = jQuery( '#content-ID-' + rowNumber ).val();
    quantity[i] = jQuery( '#content-qty-' + rowNumber ).val();
		//Get all variations
		if ( "undefined" != typeof jQuery( '#variation-id-' + rowNumber ).val() ) {
      if ( jQuery( '#variation-id-' + rowNumber ).val() == "" ) {
        var message = quote_data.invalid_variation + " " + jQuery( '#product-title-' + rowNumber ).text();
        displayAjaxResponseMessages( message );
        jQuery( "#PdfLoad" ).css( "visibility", "hidden" );
        jQuery( "#btnPQuote" ).attr( "disabled", false );
        jQuery( "#send" ).attr( "disabled", false );
        return;
      }
      var variationArray = [ ];
      variation_id[i] = jQuery( '#variation-id-' + rowNumber ).val();
      jQuery( '#variation-' + rowNumber + '  select[name^=attribute_]' ).each( function ( ind, obj ) {
       name = jQuery( this ).attr( 'name' );
       name = name.substring( 10 );
       variation = name + " : " + jQuery( this ).val();
       variationArray.push( variation );

       variationDetails[i] = variationArray;

     } );
    } else {
      variation_id[i] = 0;
      variationDetails[i] = "";
    }

    if ( quantity[i] % 1 !== 0 ) {
      jQuery( '#content-qty-' + rowNumber ).css( 'border-color', 'red' );
      jQuery( "#btnPQuote" ).attr( "disabled", false );
      jQuery( "#send" ).attr( "disabled", false );
      jQuery( "#downloadPDF" ).attr( "disabled", false );

      jQuery( "#PdfLoad" ).css( "visibility", "hidden" );

      displayAjaxResponseMessages( quote_data.quantity_invalid );
      return;
    }
    newprice[i] = jQuery( '#content-new-' + rowNumber ).val();
    totalQuantity = parseInt( totalQuantity + quantity[i] );
  }
}

var variationLength = variationDetails.length;
for ( i = 0; i < variationLength; i++ ) {
 for ( j = i + 1; j < variationLength; j++ ) {
  if ( variation_id[i] != 0 && parseInt( variation_id[i] ) == parseInt( variation_id[j] ) ) {
    if ( variationDetails[i].compare( variationDetails[j] ) ) {
     jQuery( "#btnPQuote" ).attr( "disabled", false );
     jQuery( "#send" ).attr( "disabled", false );
     jQuery( "#downloadPDF" ).attr( "disabled", false );

     jQuery( "#PdfLoad" ).css( "visibility", "hidden" );

     displayAjaxResponseMessages( quote_data.same_variation );
     return;
   }
 }
}
}

var show_price = jQuery( 'input[name="show_price"]:checked' ).val();
var cname = jQuery( 'input[name="cust_name"]' ).val();
var cemail = jQuery( 'input[name="cust_email"]' ).val();
var expiration_date = jQuery( '.expiration_date_hidden' ).val();
var human_readable_expiration_date = jQuery( '.wdm-input-expiration-date' ).val();
if ( show_price == "1" ) {
 show_price = 'yes'
} else {
 show_price = 'no'
}

var data = {
	    'action': 'save_quotation', //Action to store quotation in database
	    'enquiry_id': enquiry_id,
	    'cname': cname,
	    'email': cemail,
	    'id': id,
	    'newprice': newprice,
	    'quantity': quantity,
	    'old-price': old_price,
	    'variations_id': variation_id,
	    'variations': variationDetails,
	    'show-price': show_price,
	    'expiration-date': expiration_date,
      'variation_index_in_enquiry' : variation_index_in_enquiry,
      'security': jQuery( '#nonce' ).val(),
    };
    if ( totalQuantity <= 0 ) {
     processQutationSaveFailure( quote_data.quantity_less_than_0 );
     return;
   }

   if ( !empty( human_readable_expiration_date ) ) {
     var expiration_date_object = new Date( human_readable_expiration_date );
     var todays_date_object = new Date( quote_data.todays_date );
     if ( todays_date_object.getTime() > expiration_date_object.getTime() ) {
      processQutationSaveFailure( quote_data.quote_expired );
      return;
    }
  }

  jQuery.post( quote_data.ajax_url, data, function ( response ) {

   if ( response == 'SECURITY_ISSUE' || response != 'Saved Successfully.' ) {
    processQutationSaveFailure( ( response == 'SECURITY_ISSUE' ) ? quote_data.pdf_generation_aborted : response );

    return;
  }

  if ( response == 'Saved Successfully.' ) {
    response = quote_data.saved_successfully;
  }

  jQuery( "#PdfLoad" ).css( "visibility", "hidden" );

  jQuery( "#text" ).css( "visibility", "visible" );
  displayAjaxResponseMessages( response );
  updateEnquiryHistoryTable( enquiry_id );
  isSavingQuoteCompleted = true;
  hasErrorOccurredSavingQuote = false;
} );
}


Array.prototype.compare = function ( testArr ) {
	if ( this.length != testArr.length )
   return false;
 for ( var i = 0; i < testArr.length; i++ ) {
   if ( this[i].compare ) {
    if ( !this[i].compare( testArr[i] ) )
      return false;
  }
  if ( this[i] !== testArr[i] )
    return false;
}
return true;
}

function highlightNewRow( selector, color ) {
	var $el = selector;
	originalColor = $el.css( "background" );
	$el.animate( { backgroundColor: color }, {
   duration: 100,
   progress: function ( animation, progress, remainingMs ) {
    if ( progress == 1 && remainingMs == 0 ) {
    }
  }
} );
}

function updateEnquiryHistoryTable( enquiry_id ) {
	jQuery.ajax( {
   url: quote_data.ajax_url,
   method: 'post',
   dataType: "JSON",
   data: {
		'action': 'get_last_history_data', //Action to store quotation in database
		'enquiry_id': enquiry_id,
 },
 success: function ( response )
 {
  if ( response.status != 'NO_NEW_HISTORY' ) {
    var row = jQuery( response.table_row );
    row.hide();
    jQuery( '.enquiry-history-table tbody tr:first' ).before( row );
    row.fadeIn( 500 );
    jQuery( '.quote-status-span' ).text( response.status );
  }
},
} );
}

    /**
     * Handles things to be done when Saving Quotation in datbase fails.
     * @param  {String} failureMessage Message to be displayed on the frontend
     */
     function processQutationSaveFailure( failureMessage ) {
       jQuery( "#text" ).css( "visibility", "visible" );
       displayAjaxResponseMessages( failureMessage );
       jQuery( "#PdfLoad" ).css( "visibility", "hidden" );
       jQuery( "#btnPQuote" ).attr( "disabled", false );
       enableSendQuotationButton( false );
       isAllowedToShowPdf = false;
       hasErrorOccurredSavingQuote = true;
       isSavingQuoteCompleted = true;
     }

     function displayAjaxResponseMessages( message ) {
       document.getElementById( "text" ).innerHTML = message;
       document.getElementById( "txt" ).innerHTML = message;
     }

     /* This is for Preview Quote.*/
     /* It saves the quote again and then shows PDF in popup*/
     jQuery( "#btnPQuote" ).click( function ( e ) {
	isPdfGenerationCompleted = false; //Wait till PDF is generated.
	//
	//Save and Preview Button click Triggered by real person
	if ( e.hasOwnProperty( 'originalEvent' ) ) {
   isAllowedToShowPdf = true;
 }

 saveQuotation();
 var interval = setInterval( function () {

   if ( isSavingQuoteCompleted == true ) {
		//Once inside this function, clear the interval so that it does not get called twice
		clearInterval( interval );
		//isDataChangedAfterLastPDFGeneration = false; //setting this to false so that if saveQuotation is called again without making any changes in the content, then PDF is not produced again.
		if ( hasErrorOccurredSavingQuote == true ) {
      isPdfGenerationCompleted = true;
      return false;
    }
    var enquiry_id = jQuery( '#enquiry_id' ).val();
		//No need to generate PDF again as no data is changed.
		if ( isDataChangedAfterLastPDFGeneration == false ) {
      handleResponseAfterPDFGeneration( enquiry_id );
      return;
    }

    jQuery( "#PdfLoad" ).css( "visibility", "visible" );
    jQuery( "#text" ).css( "visibility", "visible" );
    displayAjaxResponseMessages( quote_data.generatePDF )

    var show_price = jQuery( 'input[name="show_price"]:checked' ).val();

    var data = {
		    'action': 'action_pdf', //Action which generates pdf
		    'enquiry_id': enquiry_id,
		    'show-price': show_price,
      };

		// jQuery("#DownloadPDF").css({"width":"111px","margin":"0px"});
		jQuery.post( quote_data.ajax_url, data, function ( response ) {
      if ( response == "ERROR" ) {
       console.log( quote_data.errorPDF );
       displayAjaxResponseMessages( quote_data.errorPDF );
       jQuery( "#PdfLoad" ).css( "visibility", "hidden" );
       jQuery( "#btnPQuote" ).attr( "disabled", false );
			// jQuery( "#send" ).attr( "disabled", true );
			return;
    } else {
     jQuery( "#DownloadPDF" ).css( "display", "inline" );
     handleResponseAfterPDFGeneration( enquiry_id );

   }
 } );
 }
}, 1000 );
return false;
} );

    /**
     * Handle the response obtained from PDF Generation Ajax request and show the PDF Preview
     */
     function handleResponseAfterPDFGeneration( enquiry_id ) {
       jQuery( "#PdfLoad" ).css( "visibility", "hidden" );
       displayAjaxResponseMessages( quote_data.generatedPDF );
       if ( isAllowedToShowPdf == true ) {
        var file = quote_data.path + enquiry_id + ".pdf?reload=" + Math.random();
        jQuery( '.wdm-pdf-iframe' ).attr( 'src', file );
        jQuery( ".wdm-pdf-preview-modal" ).modal().on( "hidden.bs.modal", function () {
          jQuery( this ).remove()
        } );
      }
      jQuery( "#btnPQuote" ).attr( "disabled", false );
      jQuery( "#send" ).css( 'display', 'inline-block' );
      jQuery( "#downloadPDF" ).css( 'display', 'inline-block' );
      jQuery( "#downloadPDF" ).attr( "disabled", false );
      enableSendQuotationButton( false );
	//Change button text to Preview Quotation and Send Quotation
	jQuery( "#btnPQuote" ).val( quote_data.preview_quotation );
	jQuery( "#send" ).val( quote_data.send_quotation );
	isAllowedToShowPdf = true;
	isPdfGenerationCompleted = true;
	isDataChangedAfterLastPDFGeneration = false;
}

/* This is for Sending Quote.*/
/* It saves the quote again and regenrates PDF when 'Send Quote' button is clicked*/
jQuery( "#btnSendQuote" ).click( function () {
	jQuery( "#btnPQuote" ).attr( "disabled", true );
	jQuery( "#send" ).attr( "disabled", true );
	jQuery( "#downloadPDF" ).attr( "disabled", true );
	var enquiry_id = jQuery( '#enquiry_id' ).val();
	var cemail = jQuery( 'input[name="cust_email"]' ).val();
	var subject = jQuery( '#subject' ).val();
	var message = jQuery( '#wdm_message' ).val();
	jQuery( "#txt" ).css( "visibility", "visible" );
	jQuery( "#Load" ).css( "visibility", "visible" );
	document.getElementById( "txt" ).innerHTML = "";
	isAllowedToShowPdf = false; //setting it here 1 so that below click trigger does not show pop up of PDF preview
	isPdfGenerationCompleted = false; //Set isSavingQuoteCompleted to false so that program waits till that tasks get completed.
	jQuery( "#btnPQuote" ).trigger( 'click' );
	jQuery( "#PdfLoad" ).css( "visibility", "visible" );
	jQuery( "#text" ).css( "visibility", "visible" );

	var interval = setInterval( function () {

	    //document.getElementById("text").innerHTML = "" + quote_data.sendmail;
	    // Wait till PDF gets generated.
	    if ( isPdfGenerationCompleted == false ) {
        return false;
      }

      if ( hasErrorOccurredSavingQuote == true ) {
        clearInterval( interval );
        jQuery( "#text" ).css( "visibility", "visible" );
        jQuery( "#txt" ).css( "visibility", "visible" );
        jQuery( "#PdfLoad" ).css( "visibility", "hidden" );
        jQuery( "#btnPQuote" ).attr( "disabled", false );
        jQuery( "#send" ).attr( "disabled", false );
        jQuery( "#Load" ).css( "visibility", "hidden" );
        return;
      } else {
        clearInterval( interval );
        displayAjaxResponseMessages( quote_data.sendmail );
        var data = {
          'action': 'action_send',
          'enquiry_id': enquiry_id,
          'email': cemail,
          'subject': subject,
          'message': message,
        };

        jQuery.post( quote_data.ajax_url, data, function ( response ) {

          jQuery( "#PdfLoad" ).css( "visibility", "hidden" );
          jQuery( "#Load" ).css( "visibility", "hidden" );
          isAllowedToShowPdf = true;
          jQuery( "#txt" ).css( "visibility", "visible" );
          displayAjaxResponseMessages( response );
          jQuery( "#btnPQuote" ).attr( "disabled", false );
          jQuery( "#send" ).attr( "disabled", false );
          jQuery( "#downloadPDF" ).attr( "disabled", false );
          updateEnquiryHistoryTable( enquiry_id );
          return;
        } );
      }
    }, 1000 );
} )

jQuery( "#send" ).click( function () {
	document.getElementById( "txt" ).innerHTML = '';
	jQuery( "#txt" ).css( "visibility", "hidden" );
	jQuery( '.wdm-quote-modal' ).modal();
} )

} );