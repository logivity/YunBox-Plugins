jQuery( document ).ready( function () {

    jQuery( '.wdm-button-color-field' ).wpColorPicker();
    jQuery( '#button_text_color' ).wpColorPicker();
    jQuery( '#button_border_color' ).wpColorPicker();
    jQuery( '#dialog_color' ).wpColorPicker();
    jQuery( '#dialog_text_color' ).wpColorPicker();
    jQuery( '#dialog_product_color' ).wpColorPicker();

    // Trigger WooCommerce Tooltips. This is used to trigger tooltips added by function \wc_help_tip
    var tiptip_args = {
	'attribute': 'data-tip',
	'fadeIn': 50,
	'fadeOut': 50,
	'delay': 200
    };
    jQuery( '.tips, .help_tip, .woocommerce-help-tip' ).tipTip( tiptip_args );

    jQuery( '#manual_css:checked' ).live( "click", function () {
	jQuery( '#Other_Settings' ).css( 'display', 'block' );
    } );

    jQuery( '#theme_css:checked' ).live( "click", function () {
	jQuery( '#Other_Settings' ).css( 'display', 'none' );
    } );

    var cust_email_template = jQuery( '#custom_email_template' ).is( ":checked" );

    if ( cust_email_template ) {
	jQuery( '#wdm_custom_email_template' ).css( 'display', 'block' );
    }

    jQuery( document ).on( "click", '#custom_email_template:checked', function () {
	jQuery( '#wdm_custom_email_template' ).css( 'display', 'block' );
    } );

    jQuery( document ).on( "click", '#default_email_template:checked', function () {
	jQuery( '#wdm_custom_email_template' ).css( 'display', 'none' );
    } );

    jQuery( '#btnMigrate' ).click( function () {
	jQuery( this ).attr( 'disabled', 'disabled' );
	jQuery( '.wdm-migrate-txt' ).animate( {
	    opacity: 0
	}, 400, function () {
	    jQuery( this ).css( 'display', 'none' );
	} );
	jQuery( '.wdm-migrate-loader-wrap' ).animate( {
	    opacity: 0
	}, 400, function () {
	    jQuery( this ).css( 'display', 'inline-block' );
	    req_migration = {
		action: 'migrateScript',
		'security': jQuery( '#migratenonce' ).val(),
	    };

	    jQuery.post( data.ajax_admin_url, req_migration, function ( response ) {
		if ( response == 'SECURITY_ISSUE' ) {
		    alert( data.could_not_migrate_enquiries );
		    jQuery( '.wdm-migrate-txt' ).animate( {
			opacity: 1
		    }, 400, function () {
			jQuery( this ).css( 'display', 'initial' );
			jQuery( '#btnMigrate' ).removeAttr( 'disabled' );
		    } );
		    jQuery( '.wdm-migrate-loader-wrap' ).animate( {
			opacity: 1
		    }, 400, function () {
			jQuery( this ).css( 'display', 'none' );
		    } );
		    return false;
		}
		jQuery( '.wdm-migrate-txt' ).animate( {
		    opacity: 1
		}, 400, function () {
		    jQuery( this ).css( 'display', 'initial' );
		    jQuery( '#btnMigrate' ).removeAttr( 'disabled' );
		} );
		jQuery( '.wdm-migrate-loader-wrap' ).animate( {
		    opacity: 1
		}, 400, function () {
		    jQuery( this ).css( 'display', 'none' );
		} );
	    } );
	} );
    } );

    jQuery( '#tab-container' ).easytabs();

    // When any checkbox on the settinga page is changed, find out next hidden field and set it to 1 or 0
    jQuery('.wdm_wpi_checkbox').change(function(){
	var nextHiddenField = jQuery( this ).next("input[type='hidden']");
	if ( jQuery( this ).is( ':checked' ) ) {
	    nextHiddenField.val('1');
	} else {
	    nextHiddenField.val('0');
	}
    });

    //Show or hide telephone number related fields on page load
    var phNumber = jQuery( '#enable_telephone_no_txtbox' ).is( ':checked' );
    if ( phNumber ) {
	    jQuery( '.toggle' ).show();
	} else {
	    jQuery( '.toggle' ).hide()
	}
    
    //Show or hide telephone number related fields on click
    jQuery( '#enable_telephone_no_txtbox' ).click( function () {
	if ( jQuery( this ).is( ':checked' ) ) {
	    jQuery( '.toggle' ).show();
	} else {
	    jQuery( '.toggle' ).hide()
	}
    } );

    //Show or hide date related fields on page load
    var phNumber = jQuery( '#enable_date_field' ).is( ':checked' );
    if ( phNumber ) {
	    jQuery( '.toggle-date' ).show();
	} else {
	    jQuery( '.toggle-date' ).hide()
	}

    //Show or hide date related fields on click
    jQuery( '#enable_date_field' ).click( function () {
	if ( jQuery( this ).is( ':checked' ) ) {
	    jQuery( '.toggle-date' ).show();
	} else {
	    jQuery( '.toggle-date' ).hide()
	}
    } );


    
    var quoteVisibilityOnFirstLoad = jQuery( '#quote-enable-disable' ).is( ':checked' );
    
    var previousStatus = quoteVisibilityOnFirstLoad;
    //If default value is not set, set it to 'yes'
    if ( quoteVisibilityOnFirstLoad ) {
	quoteVisibilityOnFirstLoad = 'yes';
	jQuery( '#quote-settings' ).hide();
    } else {
	jQuery( '#quote-settings' ).show();
    }
    

    jQuery( "#quote-enable-disable" ).change( function () {
	var newval = jQuery( "#quote-enable-disable" ).is( ':checked' );
	if ( !newval ) {
	    jQuery( '#quote-settings' ).show();
	} else {
	    jQuery( '#quote-settings' ).hide();
	}
    } );

    var val = jQuery( "#enable-multiproduct" ).is( ':checked' );
    if ( val ) {
	jQuery( '.quote_cart' ).show();
    } else {
	jQuery( '.quote_cart' ).hide();
    }


    jQuery( "#enable-multiproduct" ).change( function () {
	var newval = jQuery( "#enable-multiproduct" ).is( ':checked' );
	if ( newval ) {
	    jQuery( '.quote_cart' ).show();
	} else {
	    jQuery( '.quote_cart' ).hide();
	}
    } );


    jQuery( "#ask_product_form" ).submit( function ( e ) {

	error = 0;
	em_regex = /^(\s)*(([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+)((\s)*,(\s)*(([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+)(\s)*(,)*)*(\s)*$/;
	email = jQuery( '#wdm_user_email' ).val();
	if ( email == '' ) {
	    jQuery( '.email_error' ).text( data.name_req );
	    error = 1;
	} else if ( !em_regex.test( email ) ) {

	    jQuery( '.email_error' ).text( data.valid_name );
	    error = 1;
	} else {
	    jQuery( '.email_error' ).text( '' );
	}
	if ( error == 1 )
	    return false;

    } );
} );

