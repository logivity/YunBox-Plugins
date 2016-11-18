/*global quoteup_add_to_cart_disabled_variable_products, quoteup_hide_variation_variable_products */
jQuery( document ).ready( function () {
	var $allVariationForms = jQuery( 'form.variations_form.cart' );

    if ( typeof quoteup_add_to_cart_disabled_variable_products != 'undefined' ) {// Any scope
    	for ( $i = 0; $i < quoteup_add_to_cart_disabled_variable_products.length; $i++ ) {
	    //Hide Select Option button on archive page for products having 'Enable add to cart' set to disabled.
	    jQuery( '.product_type_variable[data-product_id="' + quoteup_add_to_cart_disabled_variable_products[$i] + '"]' ).remove();

	    //Hide Add to Cart button on single product page if 'Add to Cart' is disabled
	    if ( $allVariationForms.length ) {
	    	if ( $allVariationForms.length > 1 ) {
	    		$allVariationForms.each( function () {
	    			if ( jQuery( this ).data( 'product_id' ) == quoteup_add_to_cart_disabled_variable_products[$i] ) {
	    				jQuery( this ).find( '.quantity' ).remove();
	    				jQuery( this ).find( '.single_add_to_cart_button' ).remove();
	    			}
	    		} )
	    	} else {
	    		if ( $allVariationForms.data( 'product_id' ) == quoteup_add_to_cart_disabled_variable_products[$i] ) {
	    			$allVariationForms.find( '.quantity' ).remove();
	    			$allVariationForms.find( '.single_add_to_cart_button' ).remove();
	    		}
	    	}

	    }
	}
}
if ( typeof quoteup_hide_variation_variable_products != 'undefined' ) {
	//Hide variation on single product page if Add to Cart and Enquiry/Quote request both are disabled
	for ( $i = 0; $i < quoteup_hide_variation_variable_products.length; $i++ ) {
		jQuery( '.variations_form[data-product_id="' + quoteup_hide_variation_variable_products[$i] + '"]' ).remove();
	}
}

if ( typeof quoteup_price_disabled_variable_products != 'undefined' ) {
	jQuery('body').append("<style> .woocommerce-variation-price { display : none; } </style>")
}

} );