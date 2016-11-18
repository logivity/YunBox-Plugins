jQuery(document).ready(function(){
  jQuery(".msg-wrapper:last").find(".msg-border").hide();
  var atLeastOneIsChecked;
  jQuery('.wdm-checkbox-quote').each(function () {
    if (jQuery(this).is(':checked')) {
      atLeastOneIsChecked = true;
      rowNumber=jQuery(this).attr("data-row-num");
      jQuery('#content-qty-'+rowNumber).removeClass("unchecked");
      jQuery('#content-new-'+rowNumber).removeClass("unchecked");
      jQuery('#variation-'+rowNumber).show();
      jQuery('#variation-unchecked-'+rowNumber).hide();
            // jQuery(this).removeClass("unchecked");
          }else{
            rowNumber=jQuery(this).attr("data-row-num");
            jQuery('#content-qty-'+rowNumber).addClass("unchecked");
            jQuery('#content-new-'+rowNumber).addClass("unchecked");
            jQuery('#variation-'+rowNumber).hide();
            jQuery('#variation-unchecked-'+rowNumber).show();
            // jQuery(this).addClass("unchecked");
          }
          rowNumber=jQuery(this).attr("data-row-num");
          if(jQuery(this).closest('.wdmpe-detailtbl-content-row').find('.item-content-variations').find('#variation-unchecked-'+rowNumber).hasClass('quotation-disabled')){
            jQuery('#variation-unchecked-'+rowNumber).hide();
          }
        });
if(atLeastOneIsChecked){
  jQuery( '#send' ).prop( 'disabled', false );
  jQuery( '#btnPQuote' ).prop( 'disabled', false );
}else{
  jQuery( '#send' ).prop( 'disabled', true );
  jQuery( '#btnPQuote' ).prop( 'disabled', true );
}

jQuery('.rply-link').click(function (e)
{
  e.preventDefault();

  jQuery(this).next('.reply-div').slideToggle();

});

jQuery('.button-rply-user').click(function (e)
{
  e.preventDefault();
  elem = jQuery(this);
  thread_id = jQuery(this).attr('data_thread_id');

  user_email = jQuery('.wdm-enquiry-usr').val();
  subject = jQuery('.wdm_reply_subject_'+thread_id).val();
  path = jQuery('.admin-url').val();
  message = jQuery('.wdm_reply_msg_'+thread_id).val();
  enq_id =jQuery('.wdm-enq-id').val();
  parent_id=elem.closest('.reply-div').find('.parent-id').val();
  jQuery(this).next('.load-ajax').css('display','inline-block');
  jQuery.ajax(
  {
    method: 'POST',
    url: path,
    data: {action: 'wdmSendReply',
    'email': user_email,
    'subject': subject,
    'msg': message,
    'eid': enq_id,
    'parent_id':parent_id
  },
  success: function (response) {
    jQuery('.wdm-action option:first-child').attr('selected', 'selected');
    elem.closest('.reply-div').slideUp();
    elem.closest('.reply-div').next('.msg-sent').fadeIn();
    jQuery(this).next('.load-ajax').css('display','none');
    setTimeout(function () {
      elem.next('.msg-sent').fadeOut();
    }, 3000);
    location.reload();
  },
  error: function (error) {
    console.log(error);
  }
});


});

jQuery( ".newqty" ).change( function () {
  quantity = jQuery(this).val();
  if(quantity % 1 !== 0){
    jQuery(this).css('border-color','red');
    jQuery( "#btnPQuote" ).attr( "disabled", false );
    jQuery( "#send" ).attr( "disabled", false );
    jQuery( "#downloadPDF" ).attr( "disabled", false );

    jQuery( "#PdfLoad" ).css( "visibility", "hidden" );

    // displayAjaxResponseMessages( quote_data.quantity_invalid );
    return;
  }else{
    jQuery(this).css('border-color','#ddd');
  }
} );

var totalprice=0;
var rowNumber;
var previousSKU = jQuery(this).closest('.wdmpe-detailtbl-content-row').find('.item-content-sku').text();
        //Enable input boxes if checkbox is checked
        jQuery('.wdm-checkbox-quote').click (function(){
          var atLeastOneIsChecked = false;
          jQuery('.wdm-checkbox-quote').each(function () {
            if (jQuery(this).is(':checked')) {
              atLeastOneIsChecked = true;
                  // Stop .each from processing any more items
                  return false;
                }
              });
          if(atLeastOneIsChecked){
            jQuery( '#send' ).prop( 'disabled', false );
            jQuery( '#btnPQuote' ).prop( 'disabled', false );
          }else{
            jQuery( '#send' ).prop( 'disabled', true );
            jQuery( '#btnPQuote' ).prop( 'disabled', true );
          }
          rowNumber=jQuery(this).attr("data-row-num");
          if(jQuery(this).is(":checked")){
            jQuery('#content-qty-'+rowNumber).removeClass("unchecked");
            jQuery('#content-new-'+rowNumber).removeClass("unchecked");
            jQuery('#variation-'+rowNumber).show();
            jQuery('#variation-unchecked-'+rowNumber).hide();
            jQuery(this).closest('.wdmpe-detailtbl-content-row').find('.item-content-sku').text(previousSKU);
            if(!jQuery('#content-qty-'+rowNumber).hasClass('sold-individual-quantity')){
              jQuery('#content-qty-'+rowNumber).prop('disabled', false);
            }
            jQuery('#content-new-'+rowNumber).prop('disabled', false);
            var quantity=jQuery('#content-qty-'+rowNumber).val();
            var newprice=jQuery('#content-new-'+rowNumber).val();
            var finalprice=newprice*quantity;
            jQuery('#content-cost-'+rowNumber).html(quoteupFormatPrice(finalprice));
            jQuery('#content-amount-'+rowNumber).val(finalprice);
            var finaltotal=0
            jQuery('.amount_database').each(function(){
              var current=jQuery(this).val();
              finaltotal=parseFloat(finaltotal)+parseFloat(current);
            })

            jQuery('#amount_total').html(quoteupFormatPrice(finaltotal));
          }else{
            previousSKU = jQuery(this).closest('.wdmpe-detailtbl-content-row').find('.item-content-sku').text();
            jQuery('#content-qty-'+rowNumber).addClass("unchecked");
            jQuery('#content-new-'+rowNumber).addClass("unchecked");
            jQuery('#variation-'+rowNumber).hide();
            jQuery('#variation-unchecked-'+rowNumber).show();
            jQuery(this).closest('.wdmpe-detailtbl-content-row').find('.item-content-sku').text(jQuery('#variationUnchecked-'+rowNumber).val());
            jQuery('#content-qty-'+rowNumber).prop('disabled', true);
            jQuery('#content-new-'+rowNumber).prop('disabled', true);
            jQuery('#content-cost-'+rowNumber).html("-");
            jQuery('#content-amount-'+rowNumber).val(0);
            var finaltotal=0
            jQuery('.amount_database').each(function(){
              var current=jQuery(this).val();
              finaltotal=parseInt(finaltotal)+parseInt(current);
            })

            jQuery('#amount_total').html(quoteupFormatPrice(finaltotal));
          }
        });

        //Update Amout and total amount on change of new price or quantity
        jQuery('.newprice, .newqty').on('input',function(e){
          rowNumber=jQuery(this).attr("data-row-num");
          var quantity=jQuery('#content-qty-'+rowNumber).val();
          var newprice=jQuery('#content-new-'+rowNumber).val();
          var finalprice=newprice*quantity;
          jQuery('#content-cost-'+rowNumber).html(quoteupFormatPrice(finalprice));
          jQuery('#content-amount-'+rowNumber).val(finalprice);
          var finaltotal=0
          jQuery('.amount_database').each(function(){
            var current=jQuery(this).val();
            finaltotal=parseFloat(finaltotal)+parseFloat(current);
          })

          jQuery('#amount_total').html(quoteupFormatPrice(finaltotal));
        })

        //Change Quantity to 1 if it is blank or invalid
        jQuery('.newqty, .newprice').focusout(function(){
          makeDataValid(jQuery(this));

        });

        function makeDataValid(selector){
          var isDataValid = true;
          if(selector.hasClass('newqty')){
            if(!jQuery.trim(selector.val()) || selector.val() <= 0){
              isDataValid = false;
              selector.val("1");
            }
          } else {
            if(!jQuery.trim(selector.val()) || selector.val() < 0){
              isDataValid = false;
              selector.val("0");
            }
          }

          if(isDataValid == false){
           rowNumber=selector.attr("data-row-num");
           var quantity=jQuery('#content-qty-'+rowNumber).val();
           var newprice=jQuery('#content-new-'+rowNumber).val();
           var finalprice=newprice*quantity;
           jQuery('#content-cost-'+rowNumber).html(quoteupFormatPrice(finalprice));
           jQuery('#content-amount-'+rowNumber).val(finalprice);
           var finaltotal=0;
           jQuery('.amount_database').each(function(){
            var current=selector.val();
            finaltotal=parseFloat(finaltotal)+parseFloat(current);
          })

           jQuery('#amount_total').html(quoteupFormatPrice(finaltotal));
         }
       }


       jQuery(".wdm-input-expiration-date").datepicker({
        altFormat: "yy-mm-dd 00:00:00",
        altField: ".expiration_date_hidden",
        minDate: 0,
        showButtonPanel: true,
        closeText: dateData.closeText,
        currentText: dateData.currentText,
        monthNames: dateData.monthNames,
        monthNamesShort: dateData.monthNamesShort,
        dayNames: dateData.dayNames,
        dayNamesShort: dateData.dayNamesShort,
        dayNamesMin: dateData.dayNamesMin,
        dateFormat: dateData.dateFormat,
        firstDay: dateData.firstDay,
        isRTL: dateData.isRTL,
      });

    // When WooCommerce changes image on changing variation, copy new image's src value in the image column of Product Details Table 
    jQuery('.variation_image').observe({ attributes: true, attributeFilter: ['src'] }, function(){
     if(empty(this.getAttribute('src'))) {
       jQuery(this).closest('.wdmpe-detailtbl-content-row').find('.item-content-img img').attr('src', this.getAttribute('data-o_src'));
     } else {
       jQuery(this).closest('.wdmpe-detailtbl-content-row').find('.item-content-img img').attr('src', this.getAttribute('src')); 
     }
   });

    // When WooCommerce changes SKU, copy new SKU value in the SKU column of Product Details Table 
    jQuery('.sku').observe('childlist subtree', function(){
      jQuery(this).closest('.wdmpe-detailtbl-content-row').find('.item-content-sku').text(jQuery(this).text());
    });

	//If no date is filled, set hidden value to 0000-00-00 00:00:00 
	jQuery(".wdm-input-expiration-date").change(function(){
   if(empty(jQuery(this).val())){
    jQuery('.expiration_date_hidden').val('0000-00-00 00:00:00');
  }
});

  jQuery('#wpfooter').css("position", "relative");

  //
  jQuery('.variations_form').on( 'change', '.variations select', function() {
      var productLink = jQuery(this).closest('.item-content-variations').data('product-link');
      wc_cart_fragments_params.wc_ajax_url = add_query_arg('wc-ajax', '%%endpoint%%', productLink);
  });

  /**
     * When variation id is changed, then show actual price of selected variation as old price
     */
    jQuery('.variations_form').on('change', '[name="variation_id"]', function() {
        /**
         * Check if selected variation was already present in the enquiry and if present, set old price
         * as price available during enquiry
         */
        var $shouldOriginalPriceBeRetrieved = true;
        var $rowOfCurrentVariation = jQuery(this).closest('.wdmpe-detailtbl-content-row');
        var $oldPriceCell = $rowOfCurrentVariation.find('.item-content-old-cost');
        var $oldPriceData = $oldPriceCell.data('old_price');

        if (!empty($oldPriceData.variation)) {
            /**
             * If value of all variation attributes match with the ones selected during enquiry, then 
             * show price available at enquiry
             */
            for (var variation_attribute in $oldPriceData.variation) {
                if ($oldPriceData.variation.hasOwnProperty(variation_attribute)) {
                    if ($rowOfCurrentVariation.find(".variations select[name='attribute_" + variation_attribute + "']").val() != $oldPriceData.variation[variation_attribute]) {
                        $shouldOriginalPriceBeRetrieved = false;
                        break;
                    }
                }
            }
        } else {
            $shouldOriginalPriceBeRetrieved = false;
        }

        if ($shouldOriginalPriceBeRetrieved) {
            $oldPriceCell.find('.amount').html(quoteupFormatPrice($oldPriceData.price));
            $oldPriceCell.find('input').val($oldPriceData.price);
        } else {
            /**
             * Finds out price of a selected variation saved on Product Edit page and set it as old
             * price
             */
            var $productData = jQuery(this).closest('.product');
            var $variationId = jQuery(this).val();
            var $allVariations = $productData.find('.variations_form.cart').data('product_variations');
            var $variationsFetchedByAjax = false;
            if ($allVariations === false) {
                $variationsFetchedByAjax = true;
            }

            if (!$variationsFetchedByAjax) {
                /**
                 * Find out data of current selected variation from $allVariations object and set that data
                 * in 'Price' (i.e Old Price) column
                 */
                for ($i = 0; $i < $allVariations.length; $i++) {
                    if ($allVariations[$i].variation_id == $variationId) {
                        $oldPriceCell.find('.amount').html(quoteupFormatPrice($allVariations[$i].display_regular_price));
                        $oldPriceCell.find('input').val($allVariations[$i].display_regular_price);
                        if (!empty($allVariations[$i].display_price)) {
                            $oldPriceCell.find('.amount').html(quoteupFormatPrice($allVariations[$i].display_price));
                            $oldPriceCell.find('input').val($allVariations[$i].display_price);
                        }

                        break;
                    }
                }
            } else {
                var newRegularPrice = $productData.find('.quoteup-regular-price').text();
                var newPrice = $productData.find('.quoteup-price').text();
                $oldPriceCell.find('.amount').html(quoteupFormatPrice(newRegularPrice));
                $oldPriceCell.find('input').val(newRegularPrice);
                if (!empty(newPrice)) {
                    $oldPriceCell.find('.amount').html(quoteupFormatPrice(newPrice));
                    $oldPriceCell.find('input').val(newPrice);
                }
            }
        }

    });

});




