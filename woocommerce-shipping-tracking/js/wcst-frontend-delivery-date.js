jQuery(document).ready(function()
{
	var wcst_start_date_range = jQuery( "#wcst_start_date_range" ).pickadate({formatSubmit: wcst_date_format, format: wcst_date_format});
	var wcst_end_date_range = jQuery( "#wcst_end_date_range" ).pickadate({formatSubmit: wcst_date_format, format: wcst_date_format});
	var wcst_start_time_range = jQuery( "#wcst_start_time_range" ).pickatime({formatSubmit: 'HH:i', format: 'HH:i', min:[wcst_time_range_start_hour,wcst_time_range_start_minute], max:[wcst_time_range_end_hour,wcst_time_range_end_minute]});
	var wcst_end_time_range = jQuery( "#wcst_end_time_range" ).pickatime({formatSubmit: 'HH:i', format: 'HH:i',min:[wcst_time_range_start_hour,wcst_time_range_start_minute], max:[wcst_time_range_end_hour,wcst_time_range_end_minute]});
	var wcst_start_time_secondary_range = jQuery( "#wcst_start_time_secondary_range" ).pickatime({formatSubmit: 'HH:i', format: 'HH:i', min:[wcst_time_secondary_range_start_hour,wcst_time_secondary_range_start_minute], max:[wcst_time_secondary_range_end_hour,wcst_time_secondary_range_end_minute]});
	var wcst_end_time_secondary_range = jQuery( "#wcst_end_time_secondary_range" ).pickatime({formatSubmit: 'HH:i', format: 'HH:i',min:[wcst_time_secondary_range_start_hour,wcst_time_secondary_range_start_minute], max:[wcst_time_secondary_range_end_hour,wcst_time_secondary_range_end_minute]});
	
	
	jQuery('#place_order').live('click', function(event) 
	{
		var start_date_range, end_date_range, start_time_range, end_time_range, start_time_secondary_range, end_time_secondary_range;
			
		start_date_range = wcst_start_date_range.pickadate('picker');
		if(typeof start_date_range != 'undefined')
			start_date_range = start_date_range.get('select', wcst_date_format); 
		
		end_date_range = wcst_end_date_range.pickadate('picker');
		if(typeof end_date_range != 'undefined')
			end_date_range = end_date_range.get('select', wcst_date_format); 
		
		start_time_range = wcst_start_time_range.pickatime('picker');
		if(typeof start_time_range != 'undefined')
			start_time_range = start_time_range.get('select','HH:i'); 
		
		end_time_range = wcst_end_time_range.pickatime('picker');
		if(typeof end_time_range != 'undefined')
			end_time_range = end_time_range.get('select','HH:i'); 
		
		start_time_secondary_range = wcst_start_time_secondary_range.pickatime('picker');
		if(typeof start_time_secondary_range != 'undefined')
			start_time_secondary_range = start_time_secondary_range.get('select','HH:i'); 
		
		end_time_secondary_range = wcst_end_time_secondary_range.pickatime('picker');
		if(typeof end_time_secondary_range != 'undefined')
			end_time_secondary_range = end_time_secondary_range.get('select','HH:i');  
		
		if((typeof start_date_range != 'undefined' && typeof end_date_range != 'undefined') && ( (start_date_range != null && end_date_range == null) || (start_date_range == null && end_date_range != null) || start_date_range > end_date_range) )
		{
			alert(wcst_date_error_message);
			event.preventDefault();
			event.stopImmediatePropagation();
			return false;
		}
		
		if((typeof start_time_range != 'undefined' && typeof end_time_range != 'undefined') && ( (start_time_range != null && end_time_range == null) || (start_time_range == null && end_time_range != null) || start_time_range > end_time_range) )
		{
			alert(wcst_time_error_message);
			event.preventDefault();
			event.stopImmediatePropagation();
			return false;
		}
		if((typeof start_time_secondary_range != 'undefined' && typeof end_time_secondary_range != 'undefined') && ((start_time_secondary_range != null && end_time_secondary_range == null) || (start_time_secondary_range != null && end_time_secondary_range == null) || start_time_secondary_range > end_time_secondary_range) )
		{
			alert(wcst_secondary_error_message);
			event.preventDefault();
			event.stopImmediatePropagation();
			return false;
		}
	
	});
});