jQuery(document).ready(function(){

	jQuery(".reject-quote-button").click(function(event){
		quoteRejectionElement = jQuery(".quote-rejection-reason-div");
		if(! quoteRejectionElement.is(":visible") ) {
			quoteRejectionElement.show('slow'); 
		} else {
			quoteRejectionElement.hide('slow');
		}
		event.preventDefault();
	});

})