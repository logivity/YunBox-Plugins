jQuery(document).ready(function()
{
	jQuery('#wcst-additional-shipping-button').click(wcst_additional_shipping_company);
	jQuery('.wcst-remove-shipping').live('click', wcst_remove_additional_shipping_company);
});
function wcst_additional_shipping_company(event)
{
	event.preventDefault();
	event.stopImmediatePropagation();
	jQuery('#wcst-additional-shippings').append(wcst_get_template(wcst_index));
	wcst_set_date_pickers();
	wcst_index++;
	return false;
}
function wcst_remove_additional_shipping_company(event)
{
	event.preventDefault();
	event.stopImmediatePropagation();
	var id = jQuery(event.currentTarget).data('id');
	jQuery("#wcst-additiona-shipping-box-"+id).remove();
	//wcst_index--;
	return false;
}