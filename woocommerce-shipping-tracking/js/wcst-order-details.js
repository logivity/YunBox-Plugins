jQuery(document).ready(function()
{
	jQuery('#_wcst_order_trackno').focus();
	wcst_set_date_pickers();
});

function wcst_set_date_pickers()
{
	try {
			jQuery( ".wcst_dispatch_date" ).pickadate({formatSubmit: wcst_date_format, format: wcst_date_format, selectYears:true, selectMonths:true});
		}
	catch(err) {}
}