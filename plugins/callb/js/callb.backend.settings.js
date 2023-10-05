/**
 * callb.backend.settings.js
 * Module callbBackendSettings
 */

/*global $, callbBackendSettings */

var callbBackendSettings = (function () { "use strict";
    //---------------- BEGIN MODULE SCOPE VARIABLES ---------------
    var
        farbtastic_url = "{$wa_url}wa-content/js/farbtastic/farbtastic.js?{$wa->version(true)}",
        htmlTagsEncode, htmlTagsDecode,
        addCallbForm, addTipBlock, checkCommentStatus, checkPrivacyStatus, initColorPicker, setColorPickerElement, setColorPicker, onFormSubmit, changeColorPickerInputValue,
        textBlockHtmlChange, textAttrChange, textInputValueChange, styleChange, changeHandlers, onStatusChange, onCommentStatusChange, onPrivacyStatusChange, onPrivacyCheckboxStatusChange, tipInfoShow, tipInfoHide,
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

    addCallbForm = function ($content, statusChanged) {
        statusChanged = (typeof statusChanged !== 'undefined') ? statusChanged : false;
        
        var callbStatus = "{if isset($callb_settings.status)}{$callb_settings.status}{/if}";
        var styleFormBackground = '#' + $('#callb_shop_callb_style_form_background').val();
        var styleFormHeight = $('#callb_shop_callb_style_form_height').val() + 'px';
        var styleFormWidth = $('#callb_shop_callb_style_form_width').val() + 'px';
        var styleHeaderBackground = 'background: #' + $('#callb_shop_callb_style_header_background').val() + ';';
        var styleHeaderTextColor = 'color: #' + $('#callb_shop_callb_style_header_text_color').val() + ';';
        var textHeaderTitle = htmlTagsEncode( $('#callb_shop_callb_text_header_title').val() );
        var textNamePlaceholder = htmlTagsEncode( $('#callb_shop_callb_text_name_placeholder').val() );
        var textPhonePlaceholder = htmlTagsEncode( $('#callb_shop_callb_text_phone_placeholder').val() );
        var textCommentPlaceholder = htmlTagsEncode( $('#callb_shop_callb_text_comment_placeholder').val() );

        var privacyText = htmlTagsEncode( $('#callb_shop_callb_privacy_text').val() );
        var privacyLinkText = htmlTagsEncode( $('#callb_shop_callb_privacy_link_text').val() );
        var privacyLinkUrl = htmlTagsEncode( $('#callb_shop_callb_privacy_link_url').val() );

        var textSubmitButton = htmlTagsEncode( $('#callb_shop_callb_text_submit_button').val() );
        var styleSubmitBackground = 'background: #' + $('#callb_shop_callb_style_submit_background').val() + ';';
        var styleSubmitTextColor = 'color: #' + $('#callb_shop_callb_style_submit_text_color').val() + ';';
        var styleSubmitHeight = 'height: ' + $('#callb_shop_callb_style_submit_height').val() + 'px;';
        var styleSubmitWidth = 'width: ' + $('#callb_shop_callb_style_submit_width').val() + 'px;';
        
        var form = $('<form />');

        if (callbStatus === 'on' || statusChanged === true) {
            form.addClass('call-b-form').css({
                'background': styleFormBackground,
                'height': styleFormHeight,
                'width': styleFormWidth
            }).prepend(
                '<div class="call-b-header" style="' + styleHeaderBackground + styleHeaderTextColor + '">' + textHeaderTitle + '<span id="call-b-close-x">x</span></div>' +
                '<div class="call-b-input"><input type="text" name="callb-name" placeholder="' + textNamePlaceholder + '" value="" /></div>' +
                '<div class="call-b-input"><input type="text" name="callb-phone" placeholder="' + textPhonePlaceholder + '" value="" /></div>' +
                '<div class="call-b-input"><textarea name="comment" placeholder="' + textCommentPlaceholder + '"></textarea></div>' +
                '<div class="call-b-input callb-privacy-agreed-wrapper"><label for="callb-privacy-agreed">' +
                '<input type="hidden" value="0" name="callb-privacy-agreed" /><input type="checkbox" value="1" name="callb-privacy-agreed" id="callb-privacy-agreed" /> <span>' + privacyText + '</span> <a href="' + privacyLinkUrl + '" target="_blank">' + privacyLinkText + '</a>' +
                '</label> </div>' +
                '<div class="call-b-input"><input id="call-b-submit" type="submit" value="' + textSubmitButton + '" disabled="disabled" style="' + styleSubmitBackground + styleSubmitTextColor + styleSubmitHeight + styleSubmitWidth + '" /></div>'
            );

            $content.before(form);

            checkCommentStatus();

            checkPrivacyStatus();
        }
    };

    addTipBlock = function ($content) {
        var tipBlock = $('<div />');
        var styleFormWidth = parseInt($('#callb_shop_callb_style_form_width').val()) + 60;

        tipBlock.addClass('tip-block').css({
            'width': styleFormWidth + 'px'
        }).prepend(
            '<h3 id="tip-show"><span>{_wp("Tip for setting up")}</span></h3>' + 
            '<p>{_wp("1) To use the plugin in the Shop app switch on «Status of frontend_head hook» plugin setting and scripts are automatically loaded in your template.")}</p>' + 
            '<p>{_wp("2) To use the plugin in other apps it is necessary to switch off «Status of frontend_head hook» plugin setting and to insert in the template of your application to the end of the tag &#060;head&#062; the following code:")}</p>' + 
            '<p><b>&#123;if $wa->shop&#125;&#123;shopCallbPlugin::display()&#125;&#123;/if&#125;</b></p>' + 
            '<p>{_wp("3) To bind the form to the html-element in the template, you must either create a new or use an existing one.")}</p>' + 
            '<p>{_wp("For example, you have the template has the following element:")}<br />' + 
            '<i>&#060;a href="#" id="call-back-button"&#062;{_wp("Callback")}&#060;/a&#062;</i></p>' + 
            '<p>{_wp("Specify in the «Selector of the button of callback form» plugin setting the next - <b>#call-back-button</b> - and after clicking on this item will open the callback form.")}</p>'
        );

        $content.after(tipBlock);
    };

    checkCommentStatus = function () {
        var callbCommentStatus = "{if isset($callb_settings.comment_status)}{$callb_settings.comment_status}{/if}";

        if (callbCommentStatus !== 'on') {
            $('textarea[name="comment"]').parent('.call-b-input').hide();
        }
    };

    checkPrivacyStatus = function () {
        var callbPrivacyStatus = "{if isset($callb_settings.privacy_status)}{$callb_settings.privacy_status}{/if}",
            callbPrivacyCheckboxStatus = "{if isset($callb_settings.privacy_checkbox_status)}{$callb_settings.privacy_checkbox_status}{/if}",
            callbPrivacyCheckboxChecked = "{if isset($callb_settings.privacy_checkbox_checked)}{$callb_settings.privacy_checkbox_checked}{/if}";

        if (callbPrivacyStatus !== 'on') {
            $('.callb-privacy-agreed-wrapper').hide();
        }

        if (callbPrivacyCheckboxStatus !== 'on') {
            $('.callb-privacy-agreed-wrapper input[type=checkbox]').hide();
        }

        if (callbPrivacyCheckboxChecked === 'checked') {
            $('.callb-privacy-agreed-wrapper input[type=checkbox]').attr('checked', 'checked');
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

                var callbTab = $("#wa-app #mainmenu .tabs").find('li a[href="?plugin=callb"]').closest('li');

                if ( $("#plugins-settings-form select[name='shop_callb[status]']").val() === 'on' ) {
                    if (callbTab.length === 0) {
                        $("#wa-app #mainmenu .tabs li:last").before('<li class="no-tab"><a href="?plugin=callb">{_wp("Callback")}</a></li>');
                    }
                } else {
                    callbTab.remove();
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
        textBlockHtmlChange( $('#callb_shop_callb_text_header_title'), '.call-b-header' );
        textAttrChange( $('#callb_shop_callb_text_name_placeholder'), '.call-b-input input[name="callb-name"]', 'placeholder' );
        textAttrChange( $('#callb_shop_callb_text_phone_placeholder'), '.call-b-input input[name="callb-phone"]', 'placeholder' );
        textAttrChange( $('#callb_shop_callb_text_comment_placeholder'), '.call-b-input textarea[name="comment"]', 'placeholder' );
        textInputValueChange( $('#callb_shop_callb_text_submit_button'), '#call-b-submit' );

        styleChange($('#callb_shop_callb_style_form_width'), '.call-b-form', 'width', 'px', '');
        styleChange($('#callb_shop_callb_style_form_height'), '.call-b-form', 'height', 'px', '');

        styleChange($('#callb_shop_callb_style_form_background'), '.call-b-form', 'background', '', '#');
        styleChange($('#callb_shop_callb_style_header_background'), '.call-b-header', 'background', '', '#');
        styleChange($('#callb_shop_callb_style_header_text_color'), '.call-b-header', 'color', '', '#');

        styleChange($('#callb_shop_callb_style_submit_width'), '#call-b-submit', 'width', 'px', '');
        styleChange($('#callb_shop_callb_style_submit_height'), '#call-b-submit', 'height', 'px', '');

        styleChange($('#callb_shop_callb_style_submit_background'), '#call-b-submit', 'background', '', '#');
        styleChange($('#callb_shop_callb_style_submit_text_color'), '#call-b-submit', 'color', '', '#');

        textBlockHtmlChange( $('#callb_shop_callb_privacy_text'), '.callb-privacy-agreed-wrapper span' );
        textBlockHtmlChange( $('#callb_shop_callb_privacy_link_text'), '.callb-privacy-agreed-wrapper a' );
        textAttrChange( $('#callb_shop_callb_privacy_link_url'), '.callb-privacy-agreed-wrapper a', 'href' );
    };

    onStatusChange = function () {
        var t = $(this);

        if (t.val() === 'on') {
            addCallbForm( $('#wa-plugins-content .form'), true );
        } else {
            $('.call-b-form').remove();
        }
    };

    onCommentStatusChange = function () {
        var t = $(this);

        if (t.val() === 'on') {
            $('textarea[name="comment"]').parent('.call-b-input').show();
        } else {
            $('textarea[name="comment"]').parent('.call-b-input').hide();
        }
    };

    onPrivacyStatusChange = function () {
        var t = $(this);

        if (t.val() === 'on') {
            $('.callb-privacy-agreed-wrapper').show();
        } else {
            $('.callb-privacy-agreed-wrapper').hide();
        }
    };

    onPrivacyCheckboxStatusChange = function () {
        var t = $(this);

        if (t.val() === 'on') {
            $('.callb-privacy-agreed-wrapper input[type=checkbox]').show();
        } else {
            $('.callb-privacy-agreed-wrapper input[type=checkbox]').hide();
        }
    };

    changeColorPickerInputValue = function (input, $color) {
        var color = 0xFFFFFF & parseInt(('' + input.value + 'FFFFFF').replace(/[^0-9A-F]+/gi, '').substr(0, 6), 16);
        $color.css('background', (0xF000000 | color).toString(16).toUpperCase().replace(/^F/, '#'));
    };

    tipInfoShow = function () {
        $('.tip-block p').show();
    };

    tipInfoHide = function () {
        $('.tip-block p').hide();
    };
    //------------------- END EVENT HANDLERS ----------------------

    //------------------- BEGIN PUBLIC METHODS --------------------
    initModule = function () {
        $('#plugins-settings-form').on('submit', onFormSubmit);

        $('#callb_shop_callb_status').on('change', onStatusChange);

        $('#callb_shop_callb_comment_status').on('change', onCommentStatusChange);

        $('#callb_shop_callb_privacy_status').on('change', onPrivacyStatusChange);

        $('#callb_shop_callb_privacy_checkbox_status').on('change', onPrivacyCheckboxStatusChange);

        addTipBlock( $('#wa-plugins-content .form') );

        addCallbForm( $('#wa-plugins-content .form') );

        $(document).on('mouseenter', '.tip-block', tipInfoShow);

        $(document).on('mouseleave', '.tip-block', tipInfoHide);

        var color_elements = [
            '#callb_shop_callb_style_form_background',
            '#callb_shop_callb_style_header_background',
            '#callb_shop_callb_style_header_text_color',
            '#callb_shop_callb_style_submit_background',
            '#callb_shop_callb_style_submit_text_color',
            '#callb_shop_callb_style_close_ok_background',
            '#callb_shop_callb_style_close_error_background',
            '#callb_shop_callb_style_thanks_text_color'
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