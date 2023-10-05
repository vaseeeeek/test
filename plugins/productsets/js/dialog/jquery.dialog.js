/**
 * Всплывающее окно. v.1.0
 *
 * Входящие параметры:
 * - 'locale' Локаль
 * - 'messages' Локализованные строки. Объект. Смотри структуру that.messages
 * - 'html' Всё содержимое всплывающего окна. Если указано, то параметры ниже игнорируются
 * -= 'content' Контент внутри всплывающего окна
 * -= 'title' Заголовок всплывающего окна
 * -= 'buttons' Кнопки всплывающего окна
 * -= 'loadingContent' Вместо содержимого появляется иконка загрузки.
 * -= 'closeBtn' Кнопка закрытия всплывающего окна (true | html)
 * - 'block' Блок с содержимым. Если не указан, то в качестве блока будет w-dialog-wrapper
 * - 'url' Ссылка, которая при обращении отдаст содержимое всплывающего окна
 * - 'class' Произвольный класс всплывающего окна
 * - 'closeOnlyByLink' Закрывать всплывающее окно только при клике на ссылку
 * - 'position' Расположение блока. Параметры { left: , top: }
 * - 'setPosition' Пользовательская функция для определения расположения окна. Принимает параметры { width: , height: }
 * - 'saveUrl' Ссылка, куда будут отправлены данные формы окна при нажатии "Сохранить"
 * - 'onBgClick' Событие клика в любую область вне окна. Параметры (event, dialog)
 * - 'onBlockClick' Событие клика в область окна. Параметры (event, dialog)
 * - 'onOpen' Событие открытия окна. Параметры ($wrapper, dialog)
 * - 'onClose' Событие закрытия окна. Параметры ($wrapper, dialog)
 * - 'onRefresh' Событие перезагрузки формы. Принимает параметры настроек формы
 * - 'onResize' Событие изменения размеров окна
 * - 'onSubmit' Событие отправки данных формы. Параметры (form, dialog)
 * - 'onSuccess' Событие успешной отправки данных
 * - 'onSuccessCallback' Функция, выполняемая после успешной отправки данных
 */
if (typeof igaponovDialog === 'undefined') {

    var igaponovDialog = (function ($) {

        igaponovDialog = function (options) {
            var that = this;

            that.locale = options.locale || 'ru_RU';
            that.messages = {
                'ru_RU': {
                    "Save": "Сохранить",
                    "or": "или",
                    "cancel": "отмена",
                    "Something wrong": "Произошла ошибка",
                    "Fill in required fields": "Заполните обязательные поля",
                    "Your title": "Ваш заголовок"
                }
            };

            if (options.messages !== undefined && typeof options.messages == 'object') {
                $.extend(that.messages, options.messages);
            }
            // DOM
            that.$wrapper = (options["html"] ? $(options["html"]) : '');
            that.url = (options["url"] || '');
            if ((that.url && !that.$wrapper) || options.loadingContent) {
                that.$wrapper = $('<div class="ig-dialog-wrap is-full-screen"><div class="w-dialog-background"></div><div class="w-dialog-wrapper"><div class="w-dialog-block gray-header compact-header"><i class="big-loader"></i></div></div></div>');
            }
            if (!that.url && options.content && !that.$wrapper) {
                options.title = options.title || that.translate('Your title');
                that.$wrapper = $('<div class="ig-dialog-wrap is-full-screen">' +
                    '<div class="w-dialog-background"></div>' +
                    '<div class="w-dialog-wrapper">' +
                    '<div class="w-dialog-block gray-header compact-header">' +
                    '<form action="" method="post">' +
                    '<header class="w-dialog-header">' +
                    '<h1>' + options.title + '</h1>' + '</header>' +
                    '<div class="w-dialog-content margin-block">' + options.content + '</div>' +
                    '<footer class="w-dialog-footer">' +
                    '<div class="margin-block errormsg"></div>' +
                    '<div class="t-actions">' +
                    '<div class="t-layout">' +
                    '<div class="t-column left">' +
                    (options.buttons ? options.buttons : '<input type="submit" class="button green t-button" value="' + that.translate('Save') + '"> ' + that.translate('or') + ' <a href="javascript:void(0)" class="js-close-dialog">' + that.translate('cancel') + '</a>') +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</footer>' +
                    '</form></div></div></div>');
            }
            that.options = options;
            that.closeBtnTemplate = $('<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 9.9 9.9"><path d="M0,8.5l3.5-3.5L0,1.4L1.4,0l3.5,3.5L8.5,0l1.4,1.4L6.4,4.9l3.5,3.5L8.5,9.9L4.9,6.4L1.4,9.9L0,8.5z"></path></svg>');
            that.initVars();

            // VARS
            that.position = (options["position"] || false);
            that.dialogClass = 't-' + Date.now();
            that.saveUrl = (options["saveUrl"] || '');

            // DYNAMIC VARS
            that.is_closed = false;
            that.is_locked = false;
            that.xhr = false;

            that.userPosition = (options["setPosition"] || false);

            // HELPERS
            that.onBgClick = (options["onBgClick"] || false);
            that.onBlockClick = (options["onBlockClick"] || false);
            that.onOpen = (options["onOpen"] || function () {
            });
            that.onClose = (options["onClose"] || function () {
            });
            that.onRefresh = (options["onRefresh"] || false);
            that.onResize = (options["onResize"] || false);
            that.onSubmit = (options["onSubmit"] || null);
            that.onSuccess = (options["onSuccess"] || null);
            that.onSuccessCallback = (options["onSuccessCallback"] || null);

            // INIT
            that.initClass();
        };

        igaponovDialog.prototype.initClass = function () {
            var that = this;

            that.$wrapper.data('igaponovDialog', that).addClass(that.dialogClass).find('.w-dialog-background').addClass(that.dialogClass);

            that.show();
            if (that.url) {
                that.loadDataFromUrl();
            } else {
                that.addCloseBtn();
                that.bindEvents();
            }
        };

        igaponovDialog.prototype.loadDataFromUrl = function () {
            var that = this,
                href = that.url;
            that.is_locked = true;

            if (that.xhr) {
                that.xhr.abort();
                that.xhr = false;
            }

            that.xhr = $.ajax({
                url: href,
                dataType: "html",
                type: "get",
                success: function (html) {
                    var params = {
                        html: html,
                        onBgClick: function (e, d) {
                            if (typeof that.options.closeOnlyByLink === 'undefined') {
                                d.close();
                            }
                        },
                        url: ''
                    };
                    that.close();
                    new igaponovDialog($.extend(that.options, params));

                    that.is_locked = false;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    that.is_locked = false;
                    that.close();
                    if (console) {
                        console.log(jqXHR, textStatus, errorThrown);
                    }
                }
            });
        };

        igaponovDialog.prototype.bindEvents = function () {
            var that = this,
                $document = $(document),
                $block = (that.$block) ? that.$block : that.$wrapper;

            that.$form.on("submit", function (event) {
                event.preventDefault();
                if (!that.is_locked) {
                    if (typeof that.onSubmit == 'function') {
                        that.onSubmit(that.$form, that);
                    } else {
                        that.save();
                    }
                }
                return false;
            });

            $document.on('focus', 'input.error, textarea.error', function () {
                $(this).removeClass("error");
            });

            // Delay binding close events so that dialog does not close immidiately
            // from the same click that opened it.
            setTimeout(function () {

                // $document.on("click", close);
                $document.on("wa_before_load", close);
                that.$wrapper.on("close", close);

                // Click on background, default nothing
                if (that.is_full_screen) {
                    that.$wrapper.off('click').on("click", function (event) {
                        if (!that.onBgClick) {
                            event.stopPropagation();
                        } else {
                            that.onBgClick(event, that);
                        }
                    });
                }

                $block.off('click').on("click", function (event) {
                    if (!that.onBlockClick) {
                        event.stopPropagation();
                    } else {
                        that.onBlockClick(event, that);
                    }
                });

                $(document).on("keyup", function (event) {
                    var escape_code = 27;
                    if (event.keyCode === escape_code) {
                        that.close();
                    }
                });

                $block.off("click", ".js-close-dialog").on("click", ".js-close-dialog", function () {
                    close();
                });

                function close() {
                    if (!that.is_closed) {
                        that.close();
                    }
                    // $document.off("click", close);
                    $document.off("wa_before_load", close);
                }

                if (that.is_full_screen) {
                    $(window).on("resize", onResize);
                }

                function onResize() {
                    var is_exist = $.contains(document, that.$wrapper[0]);
                    if (is_exist) {
                        that.resize();
                    } else {
                        $(window).off("resize", onResize);
                    }
                }

            }, 0);

        };

        igaponovDialog.prototype.addCloseBtn = function () {
            var that = this;

            if (that.$closeBtn.length && !that.$wrapper.find('.ig-dialog-close-btn').length) {
                var closeButton = that.$closeBtn;
                if (typeof that.$closeBtn == 'object') {
                    closeButton = that.$closeBtn.prop('outerHTML');
                }
                closeButton = '<div class="ig-dialog-close-btn js-close-dialog">' + closeButton + '</div>';
                that.$block.append(closeButton);
            }
        };

        igaponovDialog.prototype.initVars = function () {
            var that = this;

            that.$block = (that.options["block"] ? $(that.options["block"]) : false);
            that.$form = that.$wrapper.find("form");
            that.$submitBtn = that.$form.find("input[type='submit']");
            that.is_full_screen = (that.$wrapper.hasClass("is-full-screen"));
            if (that.is_full_screen) {
                that.$block = that.$wrapper.find(".w-dialog-block");
            }
            if (that.options.class) {
                that.$block.addClass(that.options.class);
            }
            if (that.$block === false) {
                that.$block = that.$wrapper;
            }
            that.$closeBtn = that.options.closeBtn ? (that.options.closeBtn === true ? that.closeBtnTemplate : $(that.options.closeBtn)) : '';
        };

        igaponovDialog.prototype.show = function () {
            var that = this;

            $("body").addClass('igaponov-dialog').append(that.$wrapper);
            // that.setPosition();
            if (!that.url) {
                that.onOpen(that.$wrapper, that);
            }
        };

        igaponovDialog.prototype.setPosition = function () {
            var that = this,
                $window = $(window),
                window_w = $window.width(),
                window_h = (that.is_full_screen) ? $window.height() : $(document).height(),
                $block = (that.$block) ? that.$block : that.$wrapper,
                wrapper_w = $block.outerWidth(),
                wrapper_h = $block.outerHeight(),
                pad = 10,
                css;
            if (that.position) {
                css = that.position;

            } else {
                var getPosition = (that.userPosition) ? that.userPosition : getDefaultPosition;
                css = getPosition({
                    width: wrapper_w,
                    height: wrapper_h
                });
            }

            if (css.left > 0) {
                if (css.left + wrapper_w > window_w) {
                    css.left = window_w - wrapper_w - pad;
                }
            }

            if (css.top > 0) {
                if (css.top + wrapper_h > window_h) {
                    css.top = window_h - wrapper_h - pad;
                }
            } else {
                css.top = pad;

                if (that.is_full_screen) {
                    var $content = $block.find(".w-dialog-content");

                    $content.hide();

                    var block_h = $block.outerHeight(),
                        content_h = window_h - block_h - pad * 2;

                    $content
                        .height(content_h)
                        .addClass("is-long-content")
                        .show();
                }
            }

            $block.css(css);

            function getDefaultPosition(area) {
                return {
                    left: parseInt((window_w - area.width) / 2),
                    top: parseInt((window_h - area.height) / 2)
                };
            }
        };

        igaponovDialog.prototype.close = function () {
            var that = this;
            that.is_closed = true;
            if (!that.url) {
                that.onClose(that.$wrapper, that);
            }
            that.$wrapper.remove();
            $("body").removeClass('igaponov-dialog');
        };

        igaponovDialog.prototype.refresh = function (options) {
            var that = this;
            var params = $.extend(that.options, options);

            if (that.onRefresh) {
                that.onRefresh(params);
            } else {
                new igaponovDialog(params);
            }
            that.close();
        };

        igaponovDialog.prototype.resize = function () {
            var that = this,
                animate_class = "is-animated",
                do_animate = true;

            if (do_animate) {
                that.$block.addClass(animate_class);
            }

            // that.setPosition();

            if (that.onResize) {
                that.onResize(that.$wrapper, that);
            }
        };

        igaponovDialog.prototype.translate = function (message) {
            var that = this;

            if (typeof that.messages[that.locale] !== 'undefined' && that.messages[that.locale][message]) {
                return that.messages[that.locale][message];
            }
            return message;
        };

        igaponovDialog.prototype.save = function (data, callback) {
            var that = this,
                successTimeout = null;
            if (!that.is_locked) {
                addLoading();

                that.is_locked = true;

                errorText();
                successTimeout && clearTimeout(successTimeout);
                that.$form.find('.w-dynamic-content').html('');

                /* Проверка обязательных полей */
                if (!checkRequired()) {
                    that.is_locked = false;
                    removeLoading();
                    return false;
                }

                data = data || that.$form.serializeArray();

                $.ajax({
                    url: that.saveUrl,
                    dataType: "json",
                    type: "post",
                    data: data,
                    success: function (response) {
                        that.is_locked = false;
                        removeLoading();

                        if (typeof response !== 'undefined' && response.status == 'fail' && response.errors) {
                            errorText(response.errors);
                        } else {
                            if (typeof that.onSuccess == 'function') {
                                that.onSuccess(that, response);
                            } else {
                                successMessage();
                            }
                            try {
                                if (typeof that.onSuccessCallback == 'function') {
                                    that.onSuccessCallback(response);
                                }
                                if (typeof callback === 'function') {
                                    callback.call(this, that);
                                }
                            } catch (e) {
                                console.log('Callback error: ' + e.message, e);
                            }
                        }
                        that.resize();
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        that.is_locked = false;
                        removeLoading();
                        if (console) {
                            console.log(jqXHR, textStatus, errorThrown);
                        }
                        errorText(that.translate('Something wrong'));
                    }
                });
            }

            function addLoading() {
                that.$submitBtn.after("<i class='icon16 loading'></i>");
            }

            function removeLoading() {
                that.$submitBtn.next("i").remove();
            }

            function successMessage() {
                that.$submitBtn.after("<i class='icon16 yes'></i>");
                successTimeout = setTimeout(function () {
                    removeLoading();
                }, 3000);
            }

            function errorText(text) {
                text = text || '';
                that.$form.find('.errormsg').html(text);
            }

            function checkRequired() {
                var error = 0;
                that.$form.find(".s-required").each(function () {
                    var elem = $(this);
                    if ($.trim(elem.val()) == '') {
                        elem.addClass('error');
                        error = 1;
                    }
                });
                if (error) {
                    errorText(that.translate('Fill in required fields'));
                    return false;
                }
                return true;
            }
        };

        return igaponovDialog;

    })(jQuery);
}