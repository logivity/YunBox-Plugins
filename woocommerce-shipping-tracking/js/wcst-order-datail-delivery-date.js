jQuery(document).ready(function()
{
	jQuery( ".wcst_input_date" ).pickadate({formatSubmit: wcst_date_format, format: wcst_date_format, selectYears:true, selectMonths:true});
	jQuery( ".wcst_input_time" ).pickatime({formatSubmit: 'HH:i', format: 'HH:i'});
	
});

