jQuery( document ).ready( function () {

  jQuery('.date-field').datepicker({
    minDate: 0,
    altField: ".current-selected",
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
    autoclose: true,
  });

  jQuery(document).on("click",'.wdm_enquiry',function(event){
    var id=jQuery(this).attr('id');
    var number = id.match("wdm-quoteup-trigger-(.*)");
    if(number){
      var newdate= jQuery("#wdm-quoteup-modal-"+number[1]).find("#txtdate").addClass('current-selected');
    }
  });





  jQuery('.error').each(function(){
    jQuery(this).after('<div id="wdm-error-email" class="error"></div>');
  });

});