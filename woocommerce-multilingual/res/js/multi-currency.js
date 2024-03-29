
jQuery( function($){

    WCML_Multi_Currency = {

        _currency_languages_saving : 0,

        init:  function(){

            $(document).ready( function(){

                WCML_Multi_Currency.setup_multi_currency_toggle();

                $(document).on('change','.currency_code select', WCML_Multi_Currency.select_currency);

                $(document).on('click','.delete_currency', WCML_Multi_Currency.delete_currency);

                $(document).on('click', '.wcml_currency_options .currency_options_save', WCML_Multi_Currency.save_currency);

                $(document).on('click','.js-display-tooltip', WCML_Multi_Currency.tooltip);

                $(document).on('click', '.currency_languages a.otgs-ico-no', WCML_Multi_Currency.enable_currency_for_language);
                $(document).on('click', '.currency_languages a.otgs-ico-yes', WCML_Multi_Currency.disable_currency_for_language);

                $(document).on('change', '.default_currency select', WCML_Multi_Currency.change_default_currency);

                WCML_Multi_Currency.setup_currencies_sorting();

                $(document).on('click','input[name="currency_switcher_style"]', WCML_Multi_Currency.update_currency_switcher_style);

                $(document).on('change','#wcml_curr_sel_orientation', WCML_Multi_Currency.set_currency_switcher_orientation);

                $(document).on('keyup','input[name="wcml_curr_template"]', WCML_Multi_Currency.setup_currency_switcher_template_keyup);
                $(document).on('change','input[name="wcml_curr_template"]', WCML_Multi_Currency.setup_currency_switcher_template_change);

                $(document).on('change','.currency_option_position', WCML_Multi_Currency.price_preview);
                $(document).on('change','.currency_option_thousand_sep', WCML_Multi_Currency.price_preview);
                $(document).on('change','.currency_option_decimal_sep', WCML_Multi_Currency.price_preview);
                $(document).on('change','.currency_option_decimals', WCML_Multi_Currency.price_preview);
                $(document).on('change','.currency_code select', WCML_Multi_Currency.price_preview);

                if($('#wcml_mc_options').length){
                    WCML_Multi_Currency.wcml_mc_form_submitted = false;
                    WCML_Multi_Currency.read_form_fields_status();

                    window.onbeforeunload = function(e) {
                        if(
                            ( !WCML_Multi_Currency.wcml_mc_form_submitted && WCML_Multi_Currency.form_fields_changed() ) ||
                            WCML_Multi_Currency.is_update_currency_lang_in_progress()
                        ){
                            return $('#wcml_warn_message').val();
                        }
                    }

                    $('#wcml_mc_options').on('submit', function(){
                        WCML_Multi_Currency.wcml_mc_form_submitted = true;
                    })
                }


            } );

        },

        setup_multi_currency_toggle: function(){

            $('#multi_currency_independent').change(function(){

                if($(this).attr('checked') == 'checked'){
                    $('#currency-switcher').fadeIn();
                    $('#multi-currency-per-language-details').fadeIn();
                }else{
                    $('#multi-currency-per-language-details').fadeOut();
                    $('#currency-switcher').fadeOut();
                }

            })


        },

        select_currency: function(){
            var parent = $(this).closest('.wcml_currency_options');
            var close_button = parent.find('.wcml-dialog-close-button');
            close_button.attr('data-currency', $(this).val());
            close_button.attr('data-symbol', $(this).find('option:selected').attr('data-symbol'));
            parent.find('.this-currency').html( $(this).val() );

        },

        delete_currency: function(e){

            e.preventDefault();
            var is_return = false;
            var currency        = $(this).data('currency');
            var currency_name   = $(this).data('currency_name');
            var currency_symbol = $(this).data('currency_symbol');

            $( '.currency_lang_table .wcml-row-currency-lang:first .currency_languages').each( function(){
                if( !WCML_Multi_Currency.check_currency_language( $(this).find('li').data('lang'), currency ) ){
                    is_return = true;
                    return false;
                }
            });

            if( is_return ){
                return;
            }

            $('#currency_row_' + currency + ' .currency_action_update').hide();
            var ajaxLoader = $('<span class="spinner" style="visibility: visible;margin:0;">');
            $(this).hide();
            $(this).parent().append(ajaxLoader).show();

            $.ajax({
                type : "post",
                url : ajaxurl,
                data : {
                    action: "wcml_delete_currency",
                    wcml_nonce: $('#del_currency_nonce').val(),
                    code: currency
                },
                success: function(response) {
                    $('#currency_row_' + currency).remove();
                    $('#currency_row_langs_' + currency).remove();
                    $('#currency_row_del_' + currency).remove();

                    $('#wcml_currencies_order .wcml_currencies_order_'+ currency).remove();

                    $('#wcml_currency_options_code_').prepend('<option data-symbol="' + currency_symbol + '" value="' + currency + '">' + currency_name + '</option>');
                    $('#wcml_currency_options_code_').val(currency).trigger('change');

                    //remove from default currency list
                    $('#currency-lang-table').find('tr.default_currency select').each( function(){
                        $(this).find("option[value='"+currency+"']").remove();
                    });

                    WCML_Multi_Currency.currency_switcher_preview();
                },
                done: function() {
                    ajaxLoader.remove();
                }
            });

            return false;

        },

        save_currency: function(){

            var parent = $(this).closest('.wcml-dialog-container');

            var chk_rate = WCML_Multi_Currency.check_on_numeric(parent,'.ext_rate');
            var chk_deci = WCML_Multi_Currency.check_on_numeric(parent,'.currency_option_decimals');
            var chk_autosub = WCML_Multi_Currency.check_on_numeric(parent,'.abstract_amount');

            if(chk_rate || chk_deci || chk_autosub){
                return false;
            }

            $('.wcml-currency-options-dialog :submit, .wcml-currency-options-dialog :button').prop('disabled', true);
            var currency = parent.find('[name="currency_options[code]"]').val();

            var ajaxLoader = $('<span class="spinner" style="visibility:visible;position:absolute;margin-left:10px;"></span>');

            ajaxLoader.show();
            $(this).parent().prepend(ajaxLoader);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: parent.find('[name^="currency_options"]').serialize() + '&action=wcml_save_currency&wcml_nonce=' + jQuery('#wcml_save_currency_nonce').val(),
                success: function(response){
                    parent.dialog('close');

                    WCML_Multi_Currency.currency_switcher_preview();

                    if( $('#currency_row_' + currency).length == 0 ) {

                        var tr = $('#currency-table tr.wcml-row-currency:last').clone();
                        tr.attr('id', 'currency_row_' + currency);

                        var edit_link = tr.find('.wcml-col-edit a');
                        edit_link.attr('data-content', 'wcml_currency_options_' + currency);
                        edit_link.attr('data-currency', currency);
                        edit_link.data('dialog', 'wcml_currency_options_' + currency);
                        edit_link.removeClass('hidden');

                        $('#currency-table').find('tr.default_currency').before( tr );

                        var tr = $('#currency-lang-table tr.wcml-row-currency-lang:last').clone();
                        tr.attr('id', 'currency_row_langs_' + currency);
                        $('#currency-lang-table').find('tr.default_currency').before( tr );

                        tr.find('.on a').each( function(){
                            $(this).attr('data-currency', currency);
                        });

                        //add to default currency list
                        $('#currency-lang-table').find('tr.default_currency select').each( function(){
                            $(this).append('<option value="'+currency+'">'+currency+'</option>');
                        });

                        //add to orders list
                        $('#wcml_currencies_order').append('<li class="wcml_currencies_order_'+currency+' ui-sortable-handle" cur="'+currency+'">'+response.currency_name_formatted+'</li>');

                        var tr = $('#currency-delete-table tr.wcml-row-currency-del:last').clone();
                        tr.attr('id', 'currency_row_del_' + currency);

                        var del_link = tr.find('.delete_currency');
                        del_link.removeClass('hidden');
                        del_link.attr('data-currency', currency);
                        del_link.attr('data-currency_name', response.currency_name);
                        del_link.attr('data-currency_symbol', response.currency_symbol);
                        $('#currency-delete-table').find('tr.default_currency').before( tr );

                    }

                    $('#currency_row_' + currency + ' .wcml-col-currency').html(response.currency_name_formatted);
                    $('#currency_row_' + currency + ' .wcml-col-rate').html(response.currency_meta_info);

                    $('#wcml_currency_options_' + currency).remove();
                    $('#wcml_mc_options').before(response.currency_options);

                    $('#wcml_currency_options_code_ option[value="'+currency+'"]').remove();
                }

            })

            return false;
        },

        check_on_numeric: function(parent, elem){

            var messageContainer = $('<span class="wcml-error">');

            if(!WCML_Multi_Currency.is_number(parent.find(elem).val())){
                if(parent.find(elem).parent().find('.wcml-error').size() == 0){
                    parent.find(elem).parent().append( messageContainer );
                    messageContainer.text( parent.find(elem).data('message') );
                }
                return true;
            }else{
                if(parent.find(elem).parent().find('.wcml-error').size() > 0){
                    parent.find(elem).parent().find('.wcml-error').remove();
                }
                return false;
            }

        },

        tooltip: function(){
            var $thiz = $(this);

            // hide this pointer if other pointer is opened.
            $('.wp-pointer').fadeOut(100);

            $(this).pointer({
                content: '<h3>'+$thiz.data('header')+'</h3><p>'+$thiz.data('content')+'</p>',
                position: {
                    edge: 'left',
                    align: 'center',
                    offset: '15 0'
                }
            }).pointer('open');
        },

        enable_currency_for_language: function(e){

            if( WCML_Multi_Currency.is_update_currency_lang_in_progress() ) return false;

            e.preventDefault();
            $(this).addClass('spinner').removeClass('otgs-ico-no').css('visibility', 'visible');

            var index = $(this).closest('tr')[0].rowIndex;
            $('.default_currency select[rel="'+$(this).data('language')+'"]').append('<option value="'+$(this).data('currency')+'">'+$(this).data('currency')+'</option>');
            WCML_Multi_Currency.update_currency_lang($(this),1,0);

            var title_alt = $(this).data( 'title-alt' );
            $(this).data( 'title-alt', $(this).attr('title') );
            $(this).attr('title', title_alt);

        },

        disable_currency_for_language: function(e){

            if( WCML_Multi_Currency.is_update_currency_lang_in_progress() ) return false;

            e.preventDefault();

            $(this).addClass('spinner').removeClass('otgs-ico-yes').css('visibility', 'visible');

            var lang = $(this).data('language');

            if( !WCML_Multi_Currency.check_currency_language( lang ) ){
                $(this).removeClass('spinner').addClass('otgs-ico-yes');
                return;
            }

            var index = $(this).closest('tr')[0].rowIndex;

            if($('.currency_languages select[rel="'+$(this).data('language')+'"]').val() == $(this).data('currency')){
                WCML_Multi_Currency.update_currency_lang($(this),0,1);
            }else{
                WCML_Multi_Currency.update_currency_lang($(this),0,0);
            }
            $('.default_currency select[rel="'+$(this).data('language')+'"] option[value="'+$(this).data('currency')+'"]').remove();

            var title_alt = $(this).data( 'title-alt' );
            $(this).data( 'title-alt', $(this).attr('title') );
            $(this).attr('title', title_alt);

        },

        check_currency_language: function( lang, currency ){

            var elem = $( '#currency-lang-table a.otgs-ico-yes[data-language="'+lang+'"]' );

            if( currency ){
                elem = $( '#currency-lang-table a.otgs-ico-yes[data-language="'+lang+'"]:not([data-currency="'+currency+'"]' );
            }

            if( elem.length == 0 ){
                alert( $( '#wcml_warn_disable_language_massage' ).val() );
                return false;
            }

            return true;

        },

        is_update_currency_lang_in_progress: function(){
            var is =
                ( typeof WCML_Multi_Currency._update_currency_lang_sync_flag != 'undefined' )
                && WCML_Multi_Currency._update_currency_lang_sync_flag == 1;

            return is;
        },

        set_update_currency_lang_in_progress: function( val ){
            WCML_Multi_Currency._update_currency_lang_sync_flag = val;
        },

        update_currency_lang: function(elem, value, upd_def){

            WCML_Multi_Currency._currency_languages_saving++;
            $('#wcml_mc_options :submit').attr('disabled','disabled');

            $('input[name="wcml_mc_options"]').attr('disabled','disabled');

            var lang = elem.data('language');
            var code = elem.data('currency');
            discard = true;

            WCML_Multi_Currency.set_update_currency_lang_in_progress( 1 );

            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'wcml_update_currency_lang',
                    value: value,
                    lang: lang,
                    code: code,
                    wcml_nonce: $('#update_currency_lang_nonce').val()
                },
                success: function(){
                    if(upd_def){
                        WCML_Multi_Currency.update_default_currency(lang,0);
                    }
                },
                complete: function() {
                    $('input[name="wcml_mc_options"]').removeAttr('disabled');
                    discard = false;

                    elem.removeClass('spinner').css('visibility', 'visible');
                    if(value){
                        elem.addClass('otgs-ico-yes');
                    }else{
                        elem.addClass('otgs-ico-no');
                    }

                    WCML_Multi_Currency._currency_languages_saving--;
                    if(WCML_Multi_Currency._currency_languages_saving == 0){
                        $('#wcml_mc_options :submit').removeAttr('disabled');
                    }
                    WCML_Multi_Currency.set_update_currency_lang_in_progress( 0 );
                }
            });

        },

        change_default_currency: function(){
            WCML_Multi_Currency.update_default_currency($(this).attr('rel'), $(this).val(), $(this) );
        },

        update_default_currency: function(lang, code, select){
            $('#wcml_mc_options_submit').attr('disabled', 'disabled');
            if( select ){
                var ajaxLoader = $('<span class="spinner" style="visibility: visible;float:none;position: absolute">');
                select.parent().append(ajaxLoader);
            }

            discard = true;
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'wcml_update_default_currency',
                    lang: lang,
                    code: code,
                    wcml_nonce: $('#wcml_update_default_currency_nonce').val()
                },
                complete: function(){
                    discard = false;
                    $('#wcml_mc_options_submit').removeAttr('disabled');
                    if( select ) {
                        select.parent().find('.spinner').remove();
                    }
                }
            });
        },

        is_number: function(n){
            return !isNaN(parseFloat(n)) && isFinite(n);
        },

        setup_currencies_sorting: function(){

            $('#wcml_currencies_order').sortable({
                update: function(){
                    $('.wcml_currencies_order_ajx_resp').fadeIn();
                    var currencies_order = [];
                    $('#wcml_currencies_order').find('li').each(function(){
                        currencies_order.push($(this).attr('cur'));
                    });
                    $.ajax({
                        type: "POST",
                        url: ajaxurl,
                        dataType: 'json',
                        data: {
                            action: 'wcml_currencies_order',
                            wcml_nonce: $('#wcml_currencies_order_order_nonce').val(),
                            order: currencies_order.join(';')
                        },
                        success: function(resp){
                            fadeInAjxResp('.wcml_currencies_order_ajx_resp', resp.message);
                            WCML_Multi_Currency.currency_switcher_preview();
                        }
                    });
                }
            });

        },

        currency_switcher_preview: function (){
            var template = $('input[name="wcml_curr_template"]').val();
            if(!template){
                template = $('#currency_switcher_default').val();
            }

            var ajaxLoader = $('<span class="spinner" style="visibility: visible;">');
            $('#wcml_curr_sel_preview').html(ajaxLoader);

            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: 'wcml_currencies_switcher_preview',
                    wcml_nonce: $('#wcml_currencies_switcher_preview_nonce').val(),
                    switcher_type: $('input[name="currency_switcher_style"]:checked').val(),
                    orientation: $('#wcml_curr_sel_orientation').val(),
                    template: template
                },
                success: function(resp){
                    $('#wcml_curr_sel_preview').html(resp);
                }
            });
        },

        update_currency_switcher_style: function(e){

            if( $(this).val() == 'list' ){
                $('#wcml_curr_sel_orientation_list_wrap').show();
            }else{
                $('#wcml_curr_sel_orientation_list_wrap').hide();
            }
            WCML_Multi_Currency.currency_switcher_preview();
        },

        set_currency_switcher_orientation: function(e){
            WCML_Multi_Currency.currency_switcher_preview();
        },

        setup_currency_switcher_template_keyup: function(e){
            discard = true;
            $(this).closest('.wcml-section').find('.button-wrap input').css("border-color","#1e8cbe");
            WCML_Multi_Currency.currency_switcher_preview();
        },

        setup_currency_switcher_template_change: function(e){
            if(!$(this).val()){
                $('input[name="wcml_curr_template"]').val($('#currency_switcher_default').val())
            }
        },

        price_preview: function(){

            var parent = $(this).closest('.wcml_currency_options');

            var position = parent.find('.currency_option_position').val();
            var thousand_sep = parent.find('.currency_option_thousand_sep').val();
            var thousand_sep = parent.find('.currency_option_thousand_sep').val();
            var decimal_sep  = parent.find('.currency_option_decimal_sep').val();
            var symbol       = $(this).closest('.wcml_currency_options').find('.wcml-dialog-close-button').attr('data-symbol');
            var decimals     = '56789'.substr(0, parent.find('.currency_option_decimals').val());
            if(decimals == ''){
                decimal_sep = '';
            }

            var format   = '';

            switch(position){
                case 'left':
                    format = '{symbol}1{thousand_sep}234{decimal_sep}{decimals}';
                    break;
                case 'right':
                    format = '1{thousand_sep}234{decimal_sep}{decimals}{symbol}';
                    break;
                case 'left_space':
                    format = '{symbol}&nbsp;1{thousand_sep}234{decimal_sep}{decimals}';
                    break;
                case 'right_space':
                    format = '1{thousand_sep}234{decimal_sep}{decimals}&nbsp;{symbol}';
                    break;

            }

            var preview = format.replace(/\{symbol\}/, symbol).replace(/\{thousand_sep\}/, thousand_sep).replace(
                /\{decimal_sep\}/, decimal_sep).replace(/\{decimals\}/, decimals);

            parent.find('.wcml-co-preview-value').html( preview );

            return false;

        },

        read_form_fields_status: function(){
            this.mc_form_status = $('#wcml_mc_options').serialize();
        },

        form_fields_changed: function(){
            return this.mc_form_status != $('#wcml_mc_options').serialize();
        }

    }


    WCML_Multi_Currency.init();


} );