jQuery(".js-data-orders-ajax").select2(
{
  ajax: {
    url: ajaxurl,
    dataType: 'json',
    delay: 250,
	multiple: false,
    data: function (params) {
      return {
        order_id: params.term, // search term
        page: params.page,
		action: 'wcst_get_order_list'
      };
    },
    processResults: function (data, page) 
	{
   
       return {
        results: jQuery.map(data, function(obj) {
            return { id: obj.order_id, text: "<b>#"+obj.order_id+"</b> on "+obj.order_date+" (<i>"+obj.order_status+"</i>)" }; 
        })
		};
    },
    cache: true
  },
  escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
  minimumInputLength: 0,
  templateResult: wcoei_formatRepo, 
  templateSelection: wcoei_formatRepoSelection  
}
);

function wcoei_formatRepo (repo) 
{
	if (repo.loading) return repo.text;
	
	var markup = '<div class="clearfix">' +
			'<div class="col-sm-12">' + repo.text + '</div>';
    markup += '</div>'; 
	
    return markup;
  }

  function wcoei_formatRepoSelection (repo) 
  {
	  return repo.full_name || repo.text;
  }