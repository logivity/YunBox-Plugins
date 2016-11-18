jQuery(window).load( function () {

    var enable_price_meta_on_load = jQuery( "input[name='_enable_price']" );
    if ( enable_price_meta_on_load.length && !enable_price_meta_on_load.is( ":checked" ) ) {
	jQuery( "#wdm_enable_add_to_cart" ).hide();
	jQuery( "input[name='_enable_add_to_cart']" ).prop( 'disabled', true );
    }
    
    var enable_add_to_cart_value = null;
    jQuery( "input[name='_enable_price']" ).change( function () {
	if ( !jQuery( this ).is( ":checked" ) ) {
	    jQuery( "#wdm_enable_add_to_cart" ).hide();
	    jQuery( "input[name='_enable_add_to_cart']" ).prop( 'disabled', true );

	} else {
	    jQuery( "#wdm_enable_add_to_cart" ).show();
	    jQuery( "input[name='_enable_add_to_cart']" ).prop( 'disabled', false );

	}
    } );
} );

