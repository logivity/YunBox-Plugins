/*! http://mths.be/placeholder v2.0.8 by @mathias */
;
(function (window, document, $) {

    var isOperaMini = Object.prototype.toString.call(window.operamini) == '[object OperaMini]';
    var isInputSupported = 'placeholder' in document.createElement('input') && !isOperaMini;
    var isTextareaSupported = 'placeholder' in document.createElement('textarea') && !isOperaMini;
    var prototype = $.fn;
    var valHooks = $.valHooks;
    var propHooks = $.propHooks;
    var hooks;
    var placeholder;

    if (isInputSupported && isTextareaSupported) {

        placeholder = prototype.placeholder = function () {
            return this;
        };

        placeholder.input = placeholder.textarea = true;

    } else {

        placeholder = prototype.placeholder = function () {
            var $this = this;
            $this
            .filter((isInputSupported ? 'textarea' : ':input') + '[placeholder]')
            .not('.placeholder')
            .bind({
                'focus.placeholder': clearPlaceholder,
                'blur.placeholder': setPlaceholder
            })
            .data('placeholder-enabled', true)
            .trigger('blur.placeholder');
            return $this;
        };

        placeholder.input = isInputSupported;
        placeholder.textarea = isTextareaSupported;

        hooks = {
            'get': function (element) {
                var $element = $(element);

                var $passwordInput = $element.data('placeholder-password');
                if ($passwordInput) {
                    return $passwordInput[0].value;
                }

                return $element.data('placeholder-enabled') && $element.hasClass('placeholder') ? '' : element.value;
            },
            'set': function (element, value) {
                var $element = $(element);

                var $passwordInput = $element.data('placeholder-password');
                if ($passwordInput) {
                    return $passwordInput[0].value = value;
                }

                if (!$element.data('placeholder-enabled')) {
                    return element.value = value;
                }
                if (value == '') {
                    element.value = value;
                    // Issue #56: Setting the placeholder causes problems if the element continues to have focus.
                    if (element != safeActiveElement()) {
                        // We can't use `triggerHandler` here because of dummy text/password inputs :(
                            setPlaceholder.call(element);
                        }
                    } else if ($element.hasClass('placeholder')) {
                        clearPlaceholder.call(element, true, value) || (element.value = value);
                    } else {
                        element.value = value;
                    }
                // `set` can not return `undefined`; see http://jsapi.info/jquery/1.7.1/val#L2363
                return $element;
            }
        };

        if (!isInputSupported) {
            valHooks.input = hooks;
            propHooks.value = hooks;
        }
        if (!isTextareaSupported) {
            valHooks.textarea = hooks;
            propHooks.value = hooks;
        }

        $(function () {
            // Look for forms
            $(document).delegate('form', 'submit.placeholder', function () {
                // Clear the placeholder values so they don't get submitted
                var $inputs = $('.placeholder', this).each(clearPlaceholder);
                setTimeout(function () {
                    $inputs.each(setPlaceholder);
                }, 10);
            });
        });

        // Clear placeholder values upon page reload
        $(window).bind('beforeunload.placeholder', function () {
            $('.placeholder').each(function () {
                this.value = '';
            });
        });

    }

    function args(elem) {
        // Return an object of element attributes
        var newAttrs = {};
        var rinlinejQuery = /^jQuery\d+$/;
        $.each(elem.attributes, function (i, attr) {
            if (attr.specified && !rinlinejQuery.test(attr.name)) {
                newAttrs[attr.name] = attr.value;
            }
        });
        return newAttrs;
    }

    function clearPlaceholder(event, value) {
        var input = this;
        var $input = $(input);
        if (input.value == $input.attr('placeholder') && $input.hasClass('placeholder')) {
            if ($input.data('placeholder-password')) {
                $input = $input.hide().next().show().attr('id', $input.removeAttr('id').data('placeholder-id'));
                // If `clearPlaceholder` was called from `$.valHooks.input.set`
                if (event === true) {
                    return $input[0].value = value;
                }
                $input.focus();
            } else {
                input.value = '';
                $input.removeClass('placeholder');
                input == safeActiveElement() && input.select();
            }
        }
    }

    function setPlaceholder() {
        var $replacement;
        var input = this;
        var $input = $(input);
        var id = this.id;
        if (input.value == '') {
            if (input.type == 'password') {
                if (!$input.data('placeholder-textinput')) {
                    try {
                        $replacement = $input.clone().attr({'type': 'text'});
                    } catch (e) {
                        $replacement = $('<input>').attr($.extend(args(this), {'type': 'text'}));
                    }
                    $replacement
                    .removeAttr('name')
                    .data({
                        'placeholder-password': $input,
                        'placeholder-id': id
                    })
                    .bind('focus.placeholder', clearPlaceholder);
                    $input
                    .data({
                        'placeholder-textinput': $replacement,
                        'placeholder-id': id
                    })
                    .before($replacement);
                }
                $input = $input.removeAttr('id').hide().prev().attr('id', id).show();
                // Note: `$input[0] != input` now!
            }
            $input.addClass('placeholder');
            $input[0].value = $input.attr('placeholder');
        } else {
            $input.removeClass('placeholder');
        }
    }

    function safeActiveElement() {
        // Avoid IE9 `document.activeElement` of death
        // https://github.com/mathiasbynens/jquery-placeholder/pull/99
        try {
            return document.activeElement;
        } catch (err) {
        }
    }

}(this, document, jQuery));

jQuery(document).ready(function () {

    jQuery('.btnAddRemark').click(function (e) {
        e.preventDefault();
        product_id = jQuery(this).attr('data-prod-id');

        //This is for variable product
        variation_id = '';
        variation_detail = "";
        if(jQuery('.variation_id').length>0 && jQuery('.variation_id').val() =='')
        {
            alert(wdm_data.select_variation);
            return;
        }
        else if (jQuery('.variation_id').length>0) {
            variation_id = jQuery('.variation_id').val();
            jQuery('select[name^=attribute_]').each(function(ind, obj){
                if(variation_detail = ""){
                    variation_detail = jQuery(this).val();
                }else{
                    variation_detail = variation_detail.concat(','+jQuery(this).val());
                }
            });
        }
        //End of variable product


        txt_remark = '#wdm_remark_' + product_id;
        remark = jQuery(txt_remark).val();
        elem = jQuery(this);
        quantity =1;
        if(jQuery('input[name="quantity"]').length>0)
        {
            quantity =jQuery('input[name="quantity"]').val();
        }
        mydatavar = {
            action: 'wdm_add_product_in_enq_cart',
            'product_id': product_id,
            'remark': remark,
            'product_quant':quantity,
            'variation':variation_id,
            'security' : jQuery('#AddCartNonce').val(),
        };
        jQuery(".quoteup_registered_parameter").each(function () {
            mydatavar[jQuery(this).attr('id')] = jQuery(this).val();
        });
        jQuery('.wdmquoteup-loader').css('display', 'inline-block');
        jQuery('#wdm-cart-count').addClass('animated infinite pulse');

        jQuery.post(wdm_data.ajax_admin_url, mydatavar, function (response) {
            if(response=='SECURITY_ISSUE'){
             jQuery('.wdmquoteup-loader').css('display', 'none');
             jQuery('#nonce_error').css('display', 'block');
             elem.closest('form').css('display', 'none');

             return false;
         }
         jQuery('.wdmquoteup-loader').css('display', 'none');
         jQuery('.wdmquoteup-success-wrap').css('display', 'block');
         elem.closest('form').css('display', 'none');
         jQuery('#wdm-cart-count').removeClass('animated infinite pulse');
         var count_val = parseInt(response);
         var count_txt = '';
         if(count_val > 1) {
            count_txt = response+ ' ' + wdm_data.products_added_in_quote_cart;
        } else {
            count_txt = response+' ' + wdm_data.product_added_in_quote_cart;
        }
        jQuery('#wdm-cart-count').children('a').html('<span class="wdm-quoteupicon wdm-quoteupicon-list"></span><span class="wdm-quoteupicon-count">' + response + '</span>');
        jQuery('#wdm-cart-count').children('a').attr('title',count_txt);
        jQuery('#wdm-cart-count').css('display','block');

    });
return false;

});
jQuery('input[type=text], textarea').click(function (event) {
    event.preventDefault();
});

var error = 0;
jQuery('input, textarea').placeholder();

jQuery("#custname").change(function () {
    jQuery('#custname').attr("placeholder", wdm_data.nm_place);
});
jQuery("#txtemail").change(function () {
    jQuery("#txtemail").attr("placeholder", wdm_data.email_place);
});


jQuery("body").on("click", '[id^="wdm-quoteup-trigger-"]', function (event) {

    if(wdm_data.MPE=='yes'){
     var selector = jQuery(this);
     if(selector.hasClass('added')){
      return;
  }

  //THis is for variable product support
  variation_id = "";
  var variation_detail = [];
  if(jQuery('.variation_id').length>0 && jQuery('.variation_id').val() =='')
  {
    alert(wdm_data.select_variation);
    return;
}
else if (jQuery('.variation_id').length>0) {
    variation_id = jQuery('.variation_id').val();
    jQuery('select[name^=attribute_]').each(function(ind, obj){
        name = jQuery(this).attr('name');
        name = name.substring(10);
        variation = name + " : " + jQuery(this).val();
        variation_detail.push(variation);
    });
}
//ENd of code for variable product support

id = jQuery(this).attr('id');
number = id.match("wdm-quoteup-trigger-(.*)");
product_id = number[1];

txt_remark = '#wdm_remark_' + product_id;
remark = jQuery(txt_remark).val();
quantity =1;
if(jQuery('input[name="quantity"]').length>0)
{
    quantity =jQuery('input[name="quantity"]').val();
}

var data = {
    action: 'wdm_trigger_add_to_enq_cart',
    'product_id': product_id,
    'remark': remark,
    'product_quant':quantity,
    'variation':variation_id,
    'variation_detail' : variation_detail,
    'author_email' : jQuery('#author_email').val(),
    'security' : jQuery('#AddCartNonce').val(),
};

selector.addClass('loading');
if (jQuery(event.target).is('a')){
    selector.after('<img class=\'loading-image\' src="' + wdm_data.spinner_img_url + '"/>');
    selector.prop('disabled', true);
    event.preventDefault();
}
jQuery.post(wdm_data.ajax_admin_url, data, function (response) {
    selector.html(wdm_data.view_quote_cart_link_with_text).addClass('added').removeClass('loading');
        //If loading image is manually added, remove it after adding is succesful
        if(selector.next().hasClass('loading-image')){
         selector.next().remove();
         selector.removeProp('disabled');
     }
		//Adding onclick link because browsers other than Chrome don't support redirecting to link on clicking the button
		selector.attr('onclick', "location.href='" + wdm_data.view_quote_cart_link + "'");
    });
}else{

     //THis is for variable product support
     if(jQuery('.variation_id').length>0 && jQuery('.variation_id').val() =='')
     {
        alert(wdm_data.select_variation);
        return;
    }
//ENd of code for variable product support

event.preventDefault();
id = jQuery(this).attr('id');
header = jQuery('.wdm-modal-header');
form = jQuery('.wdm-modal-body form');
msg = jQuery('.wdm-msg');
var number = id.match("wdm-quoteup-trigger-(.*)");
if (header.parent().is("a")) {
    header.unwrap();
}
if (form.parent().is("a")) {
    form.unwrap();
}
if (msg.parent().is("a")) {
    msg.unwrap();
}
jQuery('.wdm-quoteup-form').css('display', 'block');
modal_id = "#wdm-quoteup-modal-" + number[1];
jQuery(modal_id).appendTo('body').modal('show');
jQuery('.wdm-modal-footer').css('display', 'block');
jQuery('#error').css('display', 'none');
jQuery('#nonce_error').css('display', 'none');
jQuery('#success_' + number[1]).css('display', 'none');
}

});

jQuery('body').on('shown.bs.wdm-modal','.wdm-modal',function(e) {
    jQuery('#wdm-cart-count').addClass('animated infinite pulse');
    jQuery(this).find('textarea').focus();
});

jQuery('body').on('hidden.bs.wdm-modal','.wdm-modal',function(e) {
    jQuery('#wdm-cart-count').removeClass('animated infinite pulse');
});

jQuery("body").on('click', ' [id^="btnSend_"]', function (e) {
    e.preventDefault();
    var $this = jQuery(this);
    id_send = jQuery(this).attr('id');
    var id_array = id_send.match("btnSend_(.*)");

    p_name = jQuery('#product_name_' + id_array[1]).val();

    var message = jQuery(this).closest('.form_input').siblings('.wdm-quoteup-form-inner').find('#txtmsg').val();
    var phone = jQuery(this).closest('.form_input').siblings('.wdm-quoteup-form-inner').find('#txtphone').val();
    var fields = wdm_data.fields;
    var error_field;
//This is for variable product support
variation_id = '';
variation_detail = [];
if (jQuery('.variation_id').length>0) {
    variation_id = jQuery('.variation_id').val();
    jQuery('select[name^=attribute_]').each(function(ind, obj){
        name = jQuery(this).attr('name');
        name = name.substring(10);
        variation = name + " : " + jQuery(this).val();
        variation_detail.push(variation);
    });
}
//End of variable product code
var error_val = 0;
var err_string = '';
nm_regex = /^[a-zA-Z ]+$/;
var enquiry_field;

if (fields.length > 0) {
    error_field = jQuery(this).closest('.form_input').siblings('.form-errors-wrap');
    error_field.css( 'display', 'none' );
    jQuery('.error-list-item').remove();
    jQuery( '.wdm-error' ).removeClass('wdm-error');
    for (i = 0; i < fields.length; i++) {

        enquiry_field = $this.closest('.form_input').siblings('.wdm-quoteup-form-inner').find('#' + fields[i].id);
        var temp = enquiry_field.val();

        var required = fields[i].required;
        if (fields[i].validation !== "") {
            var validation = new RegExp(fields[i].validation);
        }

        var message = fields[i].validation_message;
        var flag = 0;
        if (required == 'yes') {

            if (fields[i].type == "text" || fields[i].type == "textarea")
            {

                if (temp == "") {
                    enquiry_field.addClass('wdm-error');
                    flag = 1;
                    error_val = 1;
                    err_string += '<li class="error-list-item">' + fields[i].required_message + '</li>';


                } else {
                    flag = 0;
                    enquiry_field.removeClass('wdm-error');
                }
            }

            else if (fields[i].type == "radio")
            {
                $this.closest('.form_input').siblings('.wdm-quoteup-form-inner').find("[name=" + fields[i].id + "]").each(function () {

                    var temp1 = jQuery(this);
                    if (temp1.is(":checked"))
                    {
                        flag = 1;
                    }
                });

                if (flag == 0)
                {

                    error_val = 1;
                    $this.closest('.form_input').siblings('.wdm-quoteup-form-inner').find('#' + fields[i].id).parent().css("cssText", "background:#FCC !important;");
                    err_string += '<li class="error-list-item">' + fields[i].required_message + '</li>';
                } else
                {
                    $this.closest('.form_input').siblings('.wdm-quoteup-form-inner').find('#' + fields[i].id).parent().css("cssText", "background:white !important;");
                }

                    }//radio

                    else if (fields[i].type == "checkbox")
                    {
                        $this.closest('.form_input').siblings('.wdm-quoteup-form-inner').find("input[name=" + fields[i].id + "\\[\\]]").each(function () {

                            var temp1 = jQuery(this);

                            if (temp1.is(":checked")) {
                                flag = 1;

                            }
                        });
                        if (flag == 0) {

                            error_val = 1;
                            $this.closest('.form_input').siblings('.wdm-quoteup-form-inner').find('#' + fields[i].id).parent().css("cssText", "background:#FCC !important;");
                            err_string += '<li class="error-list-item">' + fields[i].required_message + '</li>';
                        } else
                        {
                            $this.parent().siblings().find('#' + fields[i].id).parent().css("cssText", "background:white !important;");
                        }

                    }//checkbox
                    else if(fields[i].type == "select")
                    {
                        $this.closest('.form_input').siblings('.wdm-quoteup-form-inner').find("[name=" + fields[i].id + "]").each(function () {
                           var temp1 = jQuery(this);
                           if (temp1.val()!="#") {
                            flag = 1;

                        }
                    });
                        if (flag == 0)
                        {
                            error_val = 1;
                            $this.closest('.form_input').siblings('.wdm-pep-form-inner').find('#' + fields[i].id).parent().css("cssText", "background:#FCC !important;");
                            err_string += '<li class="error-list-item">' + fields[i].required_message + '</li>';
                        } else
                        {
                            $this.closest('.form_input').siblings('.wdm-pep-form-inner').find('#' + fields[i].id).parent().css("cssText", "background:white !important;");
                        }
                    }
                }//required

                if (flag == 0)
                    if (fields[i].validation != "" && temp != "")
                    {
                        if (fields[i].id == "txtphone" && wdm_data.country !='')
                        {
                            if (!isValidNumber(phone, wdm_data.country))
                            {
                                enquiry_field.addClass('wdm-error');
                                err_string += '<li class="error-list-item">' + message + '</li>';
                                error_val = 1;
                            }
                            else
                            {
                                country = wdm_data.country;
                                enquiry_field.val(formatInternational(country, phone));
                                enquiry_field.removeClass('wdm-error');
                            }
                        }//txtphone


                        else if (!validation.test(temp))
                        {

                            enquiry_field.addClass('wdm-error');
                            err_string += '<li class="error-list-item">' + message + '</li>';
                            error_val = 1;
                        }
                        else {

                            enquiry_field.removeClass('wdm-error');
                        }

                    }
            }//for feilds loop
        }//if

        if (error_val == 0)
        {
            jQuery('.wdmquoteup-loader').css('display', 'inline-block');
            jQuery('#submit_value').val(1);
            fun_set_cookie();

            if (jQuery("#" + id_send).closest('.form_input').siblings('.wdm-quoteup-form-inner').find("#contact-cc").is(":checked"))
            {
                var wdm_checkbox_val = 'checked';

            }
            else
            {
               var wdm_checkbox_val = 0;

           }
           quantity =1;
           if(jQuery('input[name="quantity"]').length>0)
           {
            quantity =jQuery('input[name="quantity"]').val();
        }
        validate_enq = {
            action: 'quoteupValidateNonce',
            security: jQuery('#ajax_nonce').val(),
        }
        nonce_error = 0;
        jQuery.post(wdm_data.ajax_admin_url, validate_enq, function (response)
        {
            if (response === '')
            {

                jQuery('.wdmquoteup-loader').css('display', 'none');
                $this.closest('.form_input').siblings('#nonce_error').css('display', 'block');
                nonce_error = 1;

            }
            else
            {

                jQuery('.wdmquoteup-loader').css('display', 'none');
                mydatavar = {
                    action: 'quoteupSubmitWooEnquiryForm',
                    security: jQuery('#ajax_nonce').val(),
                    uemail: jQuery('#author_email').val(),
                    product_name: jQuery('#product_name_' + id_array[1]).val(),
                    product_price: jQuery('#product_price_' + id_array[1]).val(),
                    variation_id : variation_id,
                    variation_detail : variation_detail,
                    product_img: jQuery('#product_img_' + id_array[1]).val(),
                    product_id: jQuery('#product_id_' + id_array[1]).val(),
                    product_quant: quantity,
                    product_url: jQuery('#product_url_' + id_array[1]).val(),
                    cc: wdm_checkbox_val,
                };

                jQuery(".quoteup_registered_parameter").each(function () {
                    mydatavar[jQuery(this).attr('id')] = jQuery(this).val();
                });
                if (fields.length > 0) {

                    for (i = 0; i < fields.length; i++) {

                        if (fields[i].type == 'text' || fields[i].type == 'textarea' || fields[i].type == 'select') {

                            mydatavar[fields[i].id] = $this.closest('.form_input').siblings('.wdm-quoteup-form-inner').find("#" + fields[i].id).val();


                        }
                        else if (fields[i].type == 'radio')
                        {

                            mydatavar[fields[i].id] = $this.closest('.form_input').siblings('.wdm-quoteup-form-inner').find("[name='" + fields[i].id + "']:checked").val();
                        }
                        else if (fields[i].type == 'checkbox')
                        {

                            var selected = "";
                            $this.closest('.form_input').siblings('.wdm-quoteup-form-inner').find("[name='" + fields[i].id + "[]']:checked").each(function () {
                                if (selected == "") {

                                    selected = jQuery(this).val();
                                } else
                                selected += "," + jQuery(this).val();
                            });

                            mydatavar[fields[i].id] = selected;
                        }
                        else if (fields[i].type == 'multiple')
                        {
                            var selected = "";
                            selected = $this.closest('.form_input').siblings('.wdm-quoteup-form-inner').find("#" + fields[i].id).multipleSelect('getSelects').join(',');

                            mydatavar[fields[i].id] = selected;
                        }

                    }
                }
                jQuery('.wdm-quoteup-form').hide();

                $this.closest('.form_input').parent('form').siblings('#success_' + id_array[1]).show();

                jQuery.post(wdm_data.ajax_admin_url, mydatavar, function (response) {
                 if ( response == 'COMPLETED' && wdm_data.redirect != 'n' ) {
                     window.location = wdm_data.redirect;
                 }
             });
            }


        });

}
else
{

    error_field.css( 'display', 'block' );
    error_field.find('ul.error-list').html(err_string);
    return false;
}

return false;
});

});

//create cookie on first run
function fun_set_cookie()
{
    var cname = document.getElementById('custname').value;
    var cemail = document.getElementById('txtemail').value;
    if (cname != '' && cemail != '')
    {
        var d = new Date();
        d.setTime(d.getTime() + (90 * 24 * 60 * 60 * 1000));
        var expires = "expires=" + d.toGMTString();
        document.cookie = "wdmusername=" + cname + "; expires=" + expires + "; path=/";
        document.cookie = "wdmuseremail=" + cemail + "; expires=" + expires + ";path=/";
    }

}


//bootstrap.js


/*!
 * Bootstrap v3.1.1 (http://getbootstrap.com)
 * Copyright 2011-2014 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 */

 if (typeof jQuery === 'undefined') {
    throw new Error('Bootstrap\'s JavaScript requires jQuery')
}

/* ========================================================================
 * Bootstrap: transition.js v3.1.1
 * http://getbootstrap.com/javascript/#transitions
 * ========================================================================
 * Copyright 2011-2014 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * ======================================================================== */


 +function ($) {
    'use strict';

    // CSS TRANSITION SUPPORT (Shoutout: http://www.modernizr.com/)
    // ============================================================

    function transitionEnd() {
        var el = document.createElement('bootstrap')

        var transEndEventNames = {
            'WebkitTransition': 'webkitTransitionEnd',
            'MozTransition': 'transitionend',
            'OTransition': 'oTransitionEnd otransitionend',
            'transition': 'transitionend'
        }

        for (var name in transEndEventNames) {
            if (el.style[name] !== undefined) {
                return {end: transEndEventNames[name]}
            }
        }

        return false // explicit for ie8 (  ._.)
    }

    // http://blog.alexmaccaw.com/css-transitions
    $.fn.emulateTransitionEnd = function (duration) {
        var called = false, $el = this
        $(this).one($.support.transition.end, function () {
            called = true
        })
        var callback = function () {
            if (!called)
                $($el).trigger($.support.transition.end)
        }
        setTimeout(callback, duration)
        return this
    }

    $(function () {
        $.support.transition = transitionEnd()
    })

}(jQuery);



/* ========================================================================
 * Bootstrap: modal.js v3.1.1
 * http://getbootstrap.com/javascript/#modals
 * ========================================================================
 * Copyright 2011-2014 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * ======================================================================== */


 +function ($) {
    'use strict';

    // MODAL CLASS DEFINITION
    // ======================

    var Modal = function (element, options) {
        this.options = options
        this.$element = $(element)
        this.$backdrop =
        this.isShown = null

        if (this.options.remote) {
            this.$element
            .find('.wdm-modal-content')
            .load(this.options.remote, $.proxy(function () {
                this.$element.trigger('loaded.bs.wdm-modal')
            }, this))
        }
    }

    Modal.DEFAULTS = {
        backdrop: true,
        keyboard: true,
        show: true
    }

    Modal.prototype.toggle = function (_relatedTarget) {
        return this[!this.isShown ? 'show' : 'hide'](_relatedTarget)
    }

    Modal.prototype.show = function (_relatedTarget) {
        var that = this
        var e = $.Event('show.bs.wdm-modal', {relatedTarget: _relatedTarget})

        this.$element.trigger(e)

        if (this.isShown || e.isDefaultPrevented())
            return

        this.isShown = true

        this.escape()

        this.$element.on('click.dismiss.bs.wdm-modal', '[data-dismiss="wdm-modal"]', $.proxy(this.hide, this))

        this.backdrop(function () {
            var transition = $.support.transition && that.$element.hasClass('wdm-fade')

            if (!that.$element.parent().length) {
                that.$element.appendTo(document.body) // don't move modals dom position
            }

            that.$element
            .show()
            .scrollTop(0)

            if (transition) {
                that.$element[0].offsetWidth // force reflow
            }

            that.$element
            .addClass('in')
            .attr('aria-hidden', false)

            that.enforceFocus()

            var e = $.Event('shown.bs.wdm-modal', {relatedTarget: _relatedTarget})

            transition ?
                    that.$element.find('.wdm-modal-dialog') // wait for modal to slide in
                    .one($.support.transition.end, function () {
                        that.$element.focus().trigger(e)
                    })
                    .emulateTransitionEnd(300) :
                    that.$element.focus().trigger(e)
                })
}

Modal.prototype.hide = function (e) {
    if (e)
        e.preventDefault()

    e = $.Event('hide.bs.wdm-modal')

    this.$element.trigger(e)

    if (!this.isShown || e.isDefaultPrevented())
        return

    this.isShown = false

    this.escape()

    $(document).off('focusin.bs.wdm-modal')

    this.$element
    .removeClass('in')
    .attr('aria-hidden', true)
    .off('click.dismiss.bs.wdm-modal')

    $.support.transition && this.$element.hasClass('wdm-fade') ?
    this.$element
    .one($.support.transition.end, $.proxy(this.hideModal, this))
    .emulateTransitionEnd(300) :
    this.hideModal()
}

Modal.prototype.enforceFocus = function () {
    $(document)
                .off('focusin.bs.wdm-modal') // guard against infinite focus loop
                .on('focusin.bs.wdm-modal', $.proxy(function (e) {
                    if (this.$element[0] !== e.target && !this.$element.has(e.target).length) {
                        this.$element.focus()
                    }
                }, this))
            }

            Modal.prototype.escape = function () {
                if (this.isShown && this.options.keyboard) {
                    this.$element.on('keyup.dismiss.bs.wdm-modal', $.proxy(function (e) {
                        e.which == 27 && this.hide()
                    }, this))
                } else if (!this.isShown) {
                    this.$element.off('keyup.dismiss.bs.wdm-modal')
                }
            }

            Modal.prototype.hideModal = function () {
                var that = this
                this.$element.hide()
                this.backdrop(function () {
                    that.removeBackdrop()
                    that.$element.trigger('hidden.bs.wdm-modal')
                })
            }

            Modal.prototype.removeBackdrop = function () {
                this.$backdrop && this.$backdrop.remove()
                this.$backdrop = null
            }

            Modal.prototype.backdrop = function (callback) {
                var animate = this.$element.hasClass('fade') ? 'fade' : ''

                if (this.isShown && this.options.backdrop) {
                    var doAnimate = $.support.transition && animate

                    this.$backdrop = $('<div class="wdm-modal-backdrop ' + animate + '" />')
                    .appendTo(document.body)

                    this.$element.on('click.dismiss.bs.wdm-modal', $.proxy(function (e) {
                        if (e.target !== e.currentTarget)
                            return
                        this.options.backdrop == 'static'
                        ? this.$element[0].focus.call(this.$element[0])
                        : this.hide.call(this)
                    }, this))

                    if (doAnimate)
                this.$backdrop[0].offsetWidth // force reflow

            this.$backdrop.addClass('in')

            if (!callback)
                return

            doAnimate ?
            this.$backdrop
            .one($.support.transition.end, callback)
            .emulateTransitionEnd(150) :
            callback()

        } else if (!this.isShown && this.$backdrop) {
            this.$backdrop.removeClass('in')

            $.support.transition && this.$element.hasClass('wdm-fade') ?
            this.$backdrop
            .one($.support.transition.end, callback)
            .emulateTransitionEnd(150) :
            callback()

        } else if (callback) {
            callback()
        }
    }


    // MODAL PLUGIN DEFINITION
    // =======================

    var old = $.fn.modal

    $.fn.modal = function (option, _relatedTarget) {
        return this.each(function () {
            var $this = $(this)
            var data = $this.data('bs.wdm-modal')
            var options = $.extend({}, Modal.DEFAULTS, $this.data(), typeof option == 'object' && option)

            if (!data)
                $this.data('bs.wdm-modal', (data = new Modal(this, options)))
            if (typeof option == 'string')
                data[option](_relatedTarget)
            else if (options.show)
                data.show(_relatedTarget)
        })
    }

    $.fn.modal.Constructor = Modal


    // MODAL NO CONFLICT
    // =================

    $.fn.modal.noConflict = function () {
        $.fn.modal = old
        return this
    }


    // MODAL DATA-API
    // ==============

    $(document).on('click.bs.wdm-modal.data-api', '[data-toggle="wdm-quoteup-modal"]', function (e) {
        var $this = $(this)
        var href = $this.attr('href')
        var $target = $($this.attr('data-target') || (href && href.replace(/.*(?=#[^\s]+$)/, ''))) //strip for ie7
        var option = $target.data('bs.wdm-modal') ? 'toggle' : $.extend({remote: !/#/.test(href) && href}, $target.data(), $this.data())

        if ($this.is('a'))
            e.preventDefault()

        $target
        .modal(option, this)
        .one('hide', function () {
            $this.is(':visible') && $this.focus()
        })
    })

    $(document).on('show.bs.modal', '.wdm-modal', function () {
        $(document.body).addClass('wdm-modal-open');

    })
    .on('hidden.bs.modal', '.wdm-modal', function () {
        $(document.body).removeClass('wdm-modal-open');
    });

}(jQuery);


 // When WooCommerce changes SKU, copy new SKU value in the SKU column of Product Details Table 
 jQuery('.sku').observe('childlist subtree', function(){
    jQuery('#product_sku').val(jQuery(this).text());
});
