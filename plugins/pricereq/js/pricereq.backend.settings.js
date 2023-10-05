/**
 * pricereq.backend.settings.js
 * Module pricereqBackendSettings
 */

/*global $, pricereqBackendSettings */

var pricereqBackendSettings = (function () { "use strict";
    //---------------- BEGIN MODULE SCOPE VARIABLES ---------------
    var
        farbtastic_url = "{$wa_url}wa-content/js/farbtastic/farbtastic.js?{$wa->version(true)}",
        htmlTagsEncode, htmlTagsDecode,
        addPricereqForm, checkCommentStatus, checkPrivacyStatus, initColorPicker, setColorPickerElement, setColorPicker, onFormSubmit, changeColorPickerInputValue,
        textBlockHtmlChange, textAttrChange, textInputValueChange, styleChange, changeHandlers, onStatusChange, onCommentStatusChange, onPrivacyStatusChange, onPrivacyCheckboxStatusChange,
        initModule;
    //----------------- END MODULE SCOPE VARIABLES ----------------

    //--------------------- BEGIN DOM METHODS ---------------------
    htmlTagsEncode = function (val) {
        return $("<div/>").text(val).html()
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    };

    htmlTagsDecode = function (val) {
        return $("<div/>").html(val).text();
    };

    addPricereqForm = function ($content, statusChanged) {
        statusChanged = (typeof statusChanged !== 'undefined') ? statusChanged : false;
        
        var pricereqStatus = "{if isset($pricereq_settings.status)}{$pricereq_settings.status}{/if}",
            styleFormBackground = '#' + $('#pricereq_shop_pricereq_style_form_background').val(),
            styleFormHeight = $('#pricereq_shop_pricereq_style_form_height').val() + 'px',
            styleFormWidth = $('#pricereq_shop_pricereq_style_form_width').val() + 'px',
            styleHeaderBackground = 'background: #' + $('#pricereq_shop_pricereq_style_header_background').val() + ';',
            styleHeaderTextColor = 'color: #' + $('#pricereq_shop_pricereq_style_header_text_color').val() + ';',
            textHeaderTitle = htmlTagsEncode( $('#pricereq_shop_pricereq_text_header_title').val() ),
            textNamePlaceholder = htmlTagsEncode( $('#pricereq_shop_pricereq_text_name_placeholder').val() ),
            textPhonePlaceholder = htmlTagsEncode( $('#pricereq_shop_pricereq_text_phone_placeholder').val() ),
            textEmailPlaceholder = htmlTagsEncode( $('#pricereq_shop_pricereq_text_email_placeholder').val() ),
            textCommentPlaceholder = htmlTagsEncode( $('#pricereq_shop_pricereq_text_comment_placeholder').val() ),

            privacyText = htmlTagsEncode( $('#pricereq_shop_pricereq_privacy_text').val() ),
            privacyLinkText = htmlTagsEncode( $('#pricereq_shop_pricereq_privacy_link_text').val() ),
            privacyLinkUrl = htmlTagsEncode( $('#pricereq_shop_pricereq_privacy_link_url').val() ),

            textSubmitButton = htmlTagsEncode( $('#pricereq_shop_pricereq_text_submit_button').val() ),
            styleSubmitBackground = 'background: #' + $('#pricereq_shop_pricereq_style_submit_background').val() + ';',
            styleSubmitTextColor = 'color: #' + $('#pricereq_shop_pricereq_style_submit_text_color').val() + ';',
            styleSubmitHeight = 'height: ' + $('#pricereq_shop_pricereq_style_submit_height').val() + 'px;',
            styleSubmitWidth = 'width: ' + $('#pricereq_shop_pricereq_style_submit_width').val() + 'px;';
        
        var form = $('<form />');

        if (pricereqStatus === 'on' || statusChanged === true) {
            form.addClass('price-req-form').css({
                'background': styleFormBackground,
                'height': styleFormHeight,
                'width': styleFormWidth
            }).prepend(
                '<div class="price-req-header" style="' + styleHeaderBackground + styleHeaderTextColor + '">' + textHeaderTitle + '<span id="price-req-close-x">x</span></div>' +
                '<div class="price-req-input"><input type="text" name="pricereq-name" placeholder="' + textNamePlaceholder + '" value="" /></div>' +
                '<div class="price-req-input"><input type="text" name="pricereq-phone" placeholder="' + textPhonePlaceholder + '" value="" /></div>' +
                '<div class="price-req-input"><input type="text" name="pricereq-email" placeholder="' + textEmailPlaceholder + '" value="" /></div>' +
                '<div class="price-req-input"><textarea name="comment" placeholder="' + textCommentPlaceholder + '"></textarea></div>' +
                '<div class="price-req-input price-req-privacy-agreed-wrapper"><label for="price-req-privacy-agreed">' +
                '<input type="hidden" value="0" name="price-req-privacy-agreed" /><input type="checkbox" value="1" name="price-req-privacy-agreed" id="price-req-privacy-agreed" /> <span>' + privacyText + '</span> <a href="' + privacyLinkUrl + '" target="_blank">' + privacyLinkText + '</a>' +
                '</label> </div>' +
                '<div class="price-req-input"><input id="price-req-submit" type="submit" value="' + textSubmitButton + '" disabled="disabled" style="' + styleSubmitBackground + styleSubmitTextColor + styleSubmitHeight + styleSubmitWidth + '" /></div>'
            );

            $content.before(form);

            $('.price-req-form').fadeIn('500');

            checkCommentStatus();

            checkPrivacyStatus();
        }
    };

    checkCommentStatus = function () {
        var pricereqCommentStatus = "{if isset($pricereq_settings.comment_status)}{$pricereq_settings.comment_status}{/if}";

        if (pricereqCommentStatus !== 'on') {
            $('textarea[name="comment"]').parent('.price-req-input').hide();
        }
    };

    checkPrivacyStatus = function () {
        var pricereqPrivacyStatus = "{if isset($pricereq_settings.privacy_status)}{$pricereq_settings.privacy_status}{/if}",
            pricereqPrivacyCheckboxStatus = "{if isset($pricereq_settings.privacy_checkbox_status)}{$pricereq_settings.privacy_checkbox_status}{/if}",
            pricereqPrivacyCheckboxChecked = "{if isset($pricereq_settings.privacy_checkbox_checked)}{$pricereq_settings.privacy_checkbox_checked}{/if}";

        if (pricereqPrivacyStatus !== 'on') {
            $('.price-req-privacy-agreed-wrapper').hide();
        }

        if (pricereqPrivacyCheckboxStatus !== 'on') {
            $('.price-req-privacy-agreed-wrapper input[type=checkbox]').hide();
        }

        if (pricereqPrivacyCheckboxChecked === 'checked') {
            $('.price-req-privacy-agreed-wrapper input[type=checkbox]').attr('checked', 'checked');
        }
    };

    initColorPicker = function (elements, init) {
    	if ($.fn.farbtastic) {
            init(elements);
        } else {
            $.ajax({
                dataType: "script",
                url: farbtastic_url,
                cache: true
            }).done(function () {
                init(elements);
            });
        }
    };

    setColorPickerElement = function (el) {
        var color_wrapper = el.closest('.value');
        var color_picker = color_wrapper.find('.s-colorpicker');
        var color_replacer = color_wrapper.find('.s-color-replacer');
        var color_input = color_wrapper.find('.s-color');

        var farbtastic = $.farbtastic(color_picker, function(color) {
            color_replacer.find('i').css('background', color);
            color_input.val(color.substr(1));
            color_input.trigger('change');
        });

        farbtastic.setColor('#'+color_input.val());

        color_replacer.click(function () {
            color_picker.slideToggle(200);
            return false;
        });
    };

    setColorPicker = function (color_elements) {
        for (var i = 0; i < color_elements.length; i++) {

            setColorPickerElement( $(color_elements[i]) );

        }
    };
    //--------------------- END DOM METHODS -----------------------

    //------------------- BEGIN EVENT HANDLERS --------------------
    onFormSubmit = function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();

        var f = $(this);

        $.post( f.attr('action'), f.serialize(), function (response) {
            if ( response.status == 'ok' ) {
                $.plugins.message('success', response.data.message);

                f.find('.submit .button').removeClass('red').addClass('green');
                $("#plugins-settings-form-status").hide()
                $("#plugins-settings-form-status span").html(response.data.message);
                $("#plugins-settings-form-status").fadeIn('slow', function () {
                    $(this).fadeOut(1000);
                });

                var pricereqTab = $("#wa-app #mainmenu .tabs").find('li a[href="?plugin=pricereq"]').closest('li');

                if ( $("#plugins-settings-form select[name='shop_pricereq[status]']").val() === 'on' ) {
                    if (pricereqTab.length === 0) {
                        $("#wa-app #mainmenu .tabs li:last").before('<li class="no-tab"><a href="?plugin=pricereq">{_wp("Price request")}</a></li>');
                    }
                } else {
                    pricereqTab.remove();
                }
            } else {
                $.plugins.message('error', response.errors || []);

                f.find('.submit .button').removeClass('green').addClass('red');
                $("#plugins-settings-form-status").hide();
                $("#plugins-settings-form-status span").html(response.errors.join(', '));
                $("#plugins-settings-form-status").fadeIn('slow');
            }
        }, "json");
    };

    textBlockHtmlChange = function (el_changed, el_changing) {
        el_changed.on('change', function (){
            $(document).find(el_changing).html( htmlTagsEncode(el_changed.val()) );
        });
    };

    textAttrChange = function (el_changed, el_changing, el_attr) {
        el_changed.on('change', function (){
            $(document).find(el_changing).attr(el_attr, el_changed.val());
        });
    };

    textInputValueChange = function (el_changed, el_changing) {
        el_changed.on('change', function (){
            $(document).find(el_changing).val(el_changed.val());
        });
    };

    styleChange = function (el_changed, el_changing, css_style_name, stype_postfix, stype_prefix) {
        el_changed.on('change', function (){
            $(document).find(el_changing).css(css_style_name, stype_prefix + el_changed.val() + stype_postfix);
        });
    };

    changeHandlers = function () {
        textBlockHtmlChange( $('#pricereq_shop_pricereq_text_header_title'), '.price-req-header' );
        textAttrChange( $('#pricereq_shop_pricereq_text_name_placeholder'), '.price-req-input input[name="pricereq-name"]', 'placeholder' );
        textAttrChange( $('#pricereq_shop_pricereq_text_phone_placeholder'), '.price-req-input input[name="pricereq-phone"]', 'placeholder' );
        textAttrChange( $('#pricereq_shop_pricereq_text_email_placeholder'), '.price-req-input input[name="pricereq-email"]', 'placeholder' );
        textAttrChange( $('#pricereq_shop_pricereq_text_comment_placeholder'), '.price-req-input textarea[name="comment"]', 'placeholder' );
        textInputValueChange( $('#pricereq_shop_pricereq_text_submit_button'), '#price-req-submit' );

        styleChange($('#pricereq_shop_pricereq_style_form_width'), '.price-req-form', 'width', 'px', '');
        styleChange($('#pricereq_shop_pricereq_style_form_height'), '.price-req-form', 'height', 'px', '');

        styleChange($('#pricereq_shop_pricereq_style_form_background'), '.price-req-form', 'background', '', '#');
        styleChange($('#pricereq_shop_pricereq_style_header_background'), '.price-req-header', 'background', '', '#');
        styleChange($('#pricereq_shop_pricereq_style_header_text_color'), '.price-req-header', 'color', '', '#');

        styleChange($('#pricereq_shop_pricereq_style_submit_width'), '#price-req-submit', 'width', 'px', '');
        styleChange($('#pricereq_shop_pricereq_style_submit_height'), '#price-req-submit', 'height', 'px', '');

        styleChange($('#pricereq_shop_pricereq_style_submit_background'), '#price-req-submit', 'background', '', '#');
        styleChange($('#pricereq_shop_pricereq_style_submit_text_color'), '#price-req-submit', 'color', '', '#');

        textBlockHtmlChange( $('#pricereq_shop_pricereq_privacy_text'), '.price-req-privacy-agreed-wrapper span' );
        textBlockHtmlChange( $('#pricereq_shop_pricereq_privacy_link_text'), '.price-req-privacy-agreed-wrapper a' );
        textAttrChange( $('#pricereq_shop_pricereq_privacy_link_url'), '.price-req-privacy-agreed-wrapper a', 'href' );
    };

    onStatusChange = function () {
        var t = $(this);

        if (t.val() === 'on') {
            addPricereqForm( $('#wa-plugins-content .form'), true );
        } else {
            $('.price-req-form').remove();
        }
    };

    onCommentStatusChange = function () {
        var t = $(this);

        if (t.val() === 'on') {
            $('textarea[name="comment"]').parent('.price-req-input').show();
        } else {
            $('textarea[name="comment"]').parent('.price-req-input').hide();
        }
    };

    onPrivacyStatusChange = function () {
        var t = $(this);

        if (t.val() === 'on') {
            $('.price-req-privacy-agreed-wrapper').show();
        } else {
            $('.price-req-privacy-agreed-wrapper').hide();
        }
    };

    onPrivacyCheckboxStatusChange = function () {
        var t = $(this);

        if (t.val() === 'on') {
            $('.price-req-privacy-agreed-wrapper input[type=checkbox]').show();
        } else {
            $('.price-req-privacy-agreed-wrapper input[type=checkbox]').hide();
        }
    };

    changeColorPickerInputValue = function (input, $color) {
        var color = 0xFFFFFF & parseInt(('' + input.value + 'FFFFFF').replace(/[^0-9A-F]+/gi, '').substr(0, 6), 16);
        $color.css('background', (0xF000000 | color).toString(16).toUpperCase().replace(/^F/, '#'));
    };
    //------------------- END EVENT HANDLERS ----------------------

    //------------------- BEGIN PUBLIC METHODS --------------------
    initModule = function () {
        $('#plugins-settings-form').on('submit', onFormSubmit);

        $('#pricereq_shop_pricereq_status').on('change', onStatusChange);

        $('#pricereq_shop_pricereq_comment_status').on('change', onCommentStatusChange);

        $('#pricereq_shop_pricereq_privacy_status').on('change', onPrivacyStatusChange);

        $('#pricereq_shop_pricereq_privacy_checkbox_status').on('change', onPrivacyCheckboxStatusChange);

        addPricereqForm( $('#wa-plugins-content .form') );

        var color_elements = [
            '#pricereq_shop_pricereq_style_form_background',
            '#pricereq_shop_pricereq_style_header_background',
            '#pricereq_shop_pricereq_style_header_text_color',
            '#pricereq_shop_pricereq_style_submit_background',
            '#pricereq_shop_pricereq_style_submit_text_color',
            '#pricereq_shop_pricereq_style_close_ok_background',
            '#pricereq_shop_pricereq_style_close_error_background',
            '#pricereq_shop_pricereq_style_thanks_text_color'
        ];
        initColorPicker( color_elements, setColorPicker );

        var timer = {};
        $('.s-color').unbind('keydown').bind('keydown', function () {
            if (timer[this.name]) {
                clearTimeout(timer[this.name]);
            }
            var input = this;
            timer[this.name] = setTimeout(function () {
                var $color = $(input).parent().find('.icon16.color');
                changeColorPickerInputValue(input, $color);
            }, 300);
        });

        changeHandlers();

        checkCommentStatus();    

        checkPrivacyStatus();      

        $('.plugin-links a').css({
            'display': 'block',
            'top': '-500px'
        }).animate({
            'top': '0'
        }, 500).animate({
            'top': '-25px'
        }, 100).animate({
            'top': '-35px'
        }, 100).animate({
            'top': '0'
        }, 250);
    };

    return {
        initModule: initModule
    };
    //------------------- END PUBLIC METHODS ----------------------
}());