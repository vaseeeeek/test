/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
$.autobadge = {
    storage: null,
    pluginUrl: '',
    init: function (options) {
        $.autobadge.storage = new $.store();

        let localeStrings = options.localeStrings || {};

        $.ig_locale = $.extend(localeStrings, $.ig_locale);
        if (typeof $__ === "undefined") { window.$__ = puttext($.ig_locale); }

        $.wa.errorHandler = function (xhr) {
            if ((xhr.status === 403) || (xhr.status === 404)) {
                var text = $(xhr.responseText);
                if (text.find('.dialog-content').length) {
                    text = $('<div class="block double-padded"></div>').append(text.find('.dialog-content *'));
                } else {
                    text = $('<div class="block double-padded"></div>').append(text.find(':not(style)'));
                }
                $("#autobadge-interactive-block").empty().append(text);
                return false;
            }
            return true;
        };

        this.initRouting();

        /* Вызываем js функции */
        $(document).on('click', 'a.js-action', function () {
            $.autobadge.activateJsAction($(this));
            return false;
        });
        $(document).on('focus', 'input.error, textarea.error', function () {
            $(this).removeClass("error");
        });

        this.initBackend();
    },
    initRouting: function () {

        var hash = window.location.hash;
        if (typeof ($.History) != "undefined") {
            $.History.bind(function () {
                $.autobadge.dispatch();
            });
        }
        var lastPage = $.autobadge.storage.get('autobadge-last-page');
        if (lastPage) {
            $.autobadge.setHash("#/autobadge/filter/" + lastPage[0]);
            return false;
        }

        if (hash === '#/' || !hash) {
            this.dispatch();
        } else {
            this.setHash(hash);
        }
    },
    initBackend: function () {
        /* Постраничная навигация */
        $(document).on('click', "#pagination a", function () {
            $.autobadge.load($(this).attr("href"));
            return false;
        });

        /* Выделение строк */
        $(document).on('change', ".f-checker", function () {
            var that = $(this);
            if (that.prop('checked')) {
                that.closest(".filter-row").addClass("selected");
            } else {
                that.closest(".filter-row").removeClass("selected");
            }
        });
        /* Выбор плагина из списка для игнорирования */
        $(document).on('change', '.plugin-list :checkbox', function () {
            var that = $(this);
            if (that.prop("checked")) {
                that.closest('li').addClass("selected");
            } else {
                that.closest('li').removeClass("selected");
            }
        });
    },
    /* Иициализация переключателя */
    initSwitcher: function ($field) {
        $field.iButton({labelOn: "", labelOff: "", className: 'mini'}).change(function () {
            var ibuttonBlock = $(this).closest('.ibutton-checkbox');
            var onLabelSelector = ibuttonBlock.find('.switcher-off'),
                offLabelSelector = ibuttonBlock.find('.switcher-on');
            var additinalField = ibuttonBlock.siblings('.onopen');
            if (!this.checked) {
                if (additinalField.length) {
                    additinalField.hide();
                }
                $(onLabelSelector).addClass('unselected');
                $(offLabelSelector).removeClass('unselected');
            } else {
                if (additinalField.length) {
                    additinalField.each(function () {
                        var elem = $(this);
                        elem.css('display', (elem.hasClass('inline-block') ? 'inline-' : '') + 'block');
                    });
                }
                $(onLabelSelector).removeClass('unselected');
                $(offLabelSelector).addClass('unselected');
            }
        }).each(function () {
            var additinalField = $(this).closest('.ibutton-checkbox').siblings('.onopen');
            if (!this.checked) {
                if (additinalField.length) {
                    additinalField.hide();
                }
            } else {
                if (additinalField.length) {
                    additinalField.each(function () {
                        var elem = $(this);
                        elem.css('display', (elem.hasClass('inline-block') ? 'inline-' : '') + 'block');
                    });
                }
            }
        });
    },
    /* Раскрытие списка плагинов */
    showMorePluginsAction: function(btn) {
        btn.siblings('.plugin-list').find('li').slideDown();
        btn.remove();
    },
    initFilterPage: function () {

        this.initSwitcher($('.switcher'));

        /* Сохранение правила */
        $(document).on('submit', "#autobadge-save-form", function () {
            $.autobadge.saveFilter($(this));
            return false;
        });

        /* Очищаем html страницу от модуля ColorPicker */
        $(".sidebar a").click(function () {
            $(".colorpicker, #ui-datepicker-div").remove();
        });

        /* Инициализируем выбор цвета */
        $.each($(".color-icon"), function () {
            $.autobadge.initColorIcon($(this));
        });
        $("#color-picker-column").ColorPicker({
            flat: true,
            onChange: function (hsb, hex, rgb) {
                var options = $.makeArray($(".appearance-columns select:visible option:selected").map(function () {
                    return this.value;
                }));
                if (options[0] !== 'border' && options[0] !== 'box-shadow') {
                    switch (options.length) {
                        case 3:
                            $.autobadge_appearance.ribbons[$.autobadge_appearance.activeClass][$.autobadge_appearance.activeRibbon][options[0]][options[1]][options[2]] = hex;
                            $.autobadge_appearance.ribbons[$.autobadge_appearance.activeClass][$.autobadge_appearance.activeRibbon][options[0]][options[1]]['color_rgb'] = rgb.r + ',' + rgb.g + ',' + rgb.b;
                            break;
                        case 2:
                            $.autobadge_appearance.ribbons[$.autobadge_appearance.activeClass][$.autobadge_appearance.activeRibbon][options[0]][options[1]] = hex;
                            break;
                        case 1:
                            $.autobadge_appearance.ribbons[$.autobadge_appearance.activeClass][$.autobadge_appearance.activeRibbon][options[0]] = hex;
                            break;
                    }
                    if (options[0] == 'background') {
                        $.autobadge_appearance.ribbons[$.autobadge_appearance.activeClass][$.autobadge_appearance.activeRibbon][options[0]]['color_rgb'] = rgb.r + ',' + rgb.g + ',' + rgb.b;
                    }
                } else {
                    $.autobadge_appearance.ribbons[$.autobadge_appearance.activeClass][$.autobadge_appearance.activeRibbon][options[0]]['color'] = hex;
                }
                $.autobadge_appearance.refreshActiveRibbon();
            }
        });

        /* Инициализация наклейки */
        $(".badge-example input").change(function () {
            var that = $(this);
            var targetValue = that.closest(".target-row").find(".target-chosen").val();
            var templateId = targetValue !== 'create' && targetValue.substring(0, 7) !== 'default' ? parseInt(targetValue.substring(10)) : 0;
            $.autobadge_appearance.initRibbon(that.closest(".target-row").attr('data-id'), that.val(), templateId ? true : 0);
        });

        /* Открываем настройки наклейки при нажатии на нее */
        $(document).on('click', ".live-preview .autobadge-pl", function () {
            $.autobadge_appearance.addRibbonEditField($(".target-row[data-id='" + $(this).attr("data-id") + "']"), false);
        });

        /* Сортировка наклеек */
        this.initFieldsSortAction();
        /* Сортировка текстовых слоев */
        this.initTextItemSortAction();

        /* Подсветка наклеек при наведении на кнопку редактирования */
        $(document).on({
            mouseover: function () {
                var that = $(this);
                if (that.is(':animated')) {
                    return false;
                }
                that.data('timer', setTimeout(function () {
                    $.autobadge_appearance.highlightRibbon('ab-target-' + that.closest(".target-row").attr('data-id'));
                }, 400));
            },
            mouseout: function () {
                clearTimeout($(this).data('timer'));
            }
        }, '.s-edit-target');

        /* Редактирование ширины/высоты демонстрационного контейнера */
        $(".live-preview .editable").dblclick(function () {
            var that = $(this);
            that.removeClass('editable').find("span").hide().siblings("input, .disk").removeClass("hidden");
        });
        $(".live-preview .disk").click(function () {
            var that = $(this),
                input = that.siblings("input"),
                $id = $("#autobadge-save-form input[name='id']");
            if ($id.length) {
                input.prop("disabled", true);
                that.removeClass("disk").addClass("loading");
                $.post("?plugin=autobadge&action=handler", {
                    data: 'saveLivePreview',
                    value: input.val(),
                    name: input.attr("name"),
                    id: $id.val()
                }, function (response) {
                    input.prop("disabled", false);
                    if (response.status == 'ok' && response.data) {
                        input.val(response.data).siblings('span').text(response.data);
                    }
                    input.add(that).addClass('hidden').siblings("span").show().parent().addClass("editable");
                    that.addClass("disk").removeClass("loading");
                }, "json");
            } else {
                input.add(that).addClass('hidden').siblings("span").show().parent().addClass("editable");
                input.siblings('span').text(input.val());
            }
        });
        $(".live-preview input").on('input', function () {
            var that = $(this);
            var value = parseInt(that.val());
            value = value <= 150 ? 150 : value;
            if (that.attr("name") == 'width') {
                that.closest(".live-preview").width(value);
            } else {
                that.closest(".live-preview").height(value);
            }
            /* Для наклейки 5 */
            if ($.autobadge_appearance.activeRibbon == 'ribbon-5') {
                $.autobadge_appearance.refreshActiveRibbon();
            }
        });

        /* Плавающее превью */
        $(window).scroll(function () {
            $.autobadge.adjustLivePreview();
        });

        /* Скрытие окна подсказки */
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.tooltip').length) {
                $(".tooltip").hide();
            }
        });
    },
    dispatch: function (hash) {
        $.autobadge.storage.del('autobadge-last-page');
        if (this.stopDispatchIndex > 0) {
            this.stopDispatchIndex--;
            return false;
        }
        if (hash === undefined) {
            hash = this.getHash();
        } else {
            hash = this.cleanHash(hash);
        }
        if (this.previousHash == hash) {
            return;
        }
        this.previousHash = hash;
        hash = hash.replace(/^[^#]*#\/*/, '');
        try {
            if (hash) {
                hash = hash.split('/');
                if (hash[0]) {
                    var actionName = "";
                    var attrMarker = hash.length;
                    for (var i = 0; i < hash.length; i++) {
                        var h = hash[i];
                        if (i < 2) {
                            if (i === 0) {
                                actionName = h;
                            } else if (parseInt(h, 10) != h) {
                                actionName += h.substr(0, 1).toUpperCase() + h.substr(1);
                            } else {
                                attrMarker = i;
                                break;
                            }
                        } else {
                            attrMarker = i;
                            break;
                        }
                    }
                    var attr = hash.slice(attrMarker);

                    if (this[actionName + 'Action']) {
                        this.currentAction = actionName;
                        this.currentActionAttr = attr;
                        if (this[actionName + 'Action']) {
                            this[actionName + 'Action'](attr);
                        } else {
                            console.log('Invalid action name:', actionName + 'Action');
                        }
                    } else {
                        if (console) {
                            console.log('Invalid action name:', actionName + 'Action');
                        }
                    }
                } else {
                    this.defaultAction();
                }
                if (hash.join) {
                    hash = hash.join('/');
                }
            } else {
                this.defaultAction();
            }
        } catch (e) {
            console.log(e.message, e);
        }

        $(document).trigger('hashchange', [hash]);
    },
    setHash: function (hash) {
        hash = this.cleanHash(hash);
        if (!(hash instanceof String) && hash.toString) {
            hash = hash.toString();
        }
        hash = hash.replace(/\/\//g, "/");
        hash = hash.replace(/^.*#/, '');
        if ($.browser && $.browser.safari) {
            if (parent) {
                parent.window.location = parent.window.location.href.replace(/#.*/, '') + '#' + hash;
            } else {
                window.location = location.href.replace(/#.*/, '') + '#' + hash;
            }
        } else if (parent && (!$.browser || !$.browser.msie)) {
            parent.window.location.hash = hash;
        } else {
            location.hash = hash;
        }
        return true;
    },
    getHash: function () {
        return this.cleanHash();
    },
    cleanHash: function (hash) {
        if (typeof hash == 'undefined') {
            hash = window.location.hash.toString();
        }

        if (!hash.length) {
            hash = '' + hash;
        }
        while (hash.length > 0 && hash[hash.length - 1] === '/') {
            hash = hash.substr(0, hash.length - 1);
        }
        hash += '/';
        if (hash[0] != '#') {
            if (hash[0] != '/') {
                hash = '/' + hash;
            }
            hash = '#' + hash;
        } else if (hash[1] && hash[1] != '/') {
            hash = '#/' + hash.substr(1);
        }

        if (hash == '#/') {
            return '';
        }
        return hash;
    },
    load: function (url, callback) {
        var r = Math.random();
        this.random = r;
        var self = this;
        $("#autobadge-interactive-block").html($__("Loading...") + '<i class="icon16 loading"></i>');
        $.get(url, function (result) {
            if (self.random != r) {
                return;
            }
            var tmp = $("<div></div>");
            tmp.append(result);
            if (tmp.find("#autobadge-interactive-block").length) {
                $("#autobadge-interactive-block").html(tmp.find("#autobadge-interactive-block"));
            } else {
                $("#autobadge-interactive-block").html(result);
            }
            $('html, body').animate({
                scrollTop: 0
            }, 200);
            if (callback) {
                try {
                    callback.call(this);
                } catch (e) {
                    console.log('Callback error: ' + e.message, e);
                }
            }
        });
    },
    activateJsAction: function (btn) {
        var hash = this.cleanHash(btn.attr("href"));
        if (hash) {
            hash = hash.replace(/^[^#]*#\/*/, '');
            hash = hash.split('/');
            var actionName = "";
            var param = "";
            for (var i = 0; i < hash.length; i++) {
                var h = hash[i];
                if (i === 0) {
                    actionName = h;
                } else if (parseInt(h, 10) == h) {
                    param = h;
                } else {
                    actionName += h.substr(0, 1).toUpperCase() + h.substr(1);
                }
            }
            if (this[actionName + 'Action']) {
                this[actionName + 'Action'](btn, param);
            } else if (this['activateJsAction']) {
                this['activateJsAction'](actionName, btn, param);
            } else {
                if (console) {
                    console.log('Invalid action name:', actionName + 'Action');
                }
            }
        }
    },
    defaultAction: function () {

    },
    appendLoading: function (elem) {
        elem.append("<i class='icon16 loading'></i>");
    },
    removeLoading: function (elem) {
        elem.find(".loading").remove();
    },
    hasLoading: function (elem) {
        return elem.find(".loading").length;
    },
    /* Удаление фильтров */
    filterDeleteAction: function (btn, fl_id) {
        if (!this.hasLoading(btn)) {
            if (fl_id) {
                var ids = fl_id;
            } else {
                var checkedC = $(".f-checker:checked");
                if (checkedC.length < 1) {
                    alert($__("Select at least 1 filter rule"));
                    return false;
                }
                var ids = $.makeArray(checkedC.map(function () {
                    return $(this).val();
                }));
            }

            var dialogParams = {
                loading_header: $__("Wait, please..."),
                class: 'delete-dialog',
                height: '200px',
                'min-height': '200px',
                buttons: '<div class="align-center s-buttons-block"><input type="submit" value="' + $__("Delete") + '" class="button red"><span class="errormsg" style="margin-left: 10px; display: inline-block"></span></div>',
                content: '<h1>' + $__('Delete filter') + '<a href="javascript:void(0)" onclick="$(this).closest(\'.dialog\').trigger(\'close\')" title="' + $__('close') + '" class="close dialog-close">' + $__('close') + '</a></h1><p class="align-center" style="margin-top: 60px">' + $__("Do you really want to delete filter rule?") + '</p>',
                onClose: function () {
                    $("#filter-dialog-delete").remove();
                },
                onSubmit: function (form) {
                    $.autobadge.appendLoading(form.find(".s-buttons-block"));
                    $.post("?plugin=autobadge&action=handler", {data: 'deleteFilter', ids: ids}, function () {
                        if (btn.parent("h2").length) {
                            $.autobadge.setHash("#/autobadge/");
                        } else if (fl_id) {
                            btn.closest(".filter-row").remove();
                        } else {
                            $(".f-checker:checked").closest(".filter-row").remove();
                        }
                        $.autobadge.removeLoading(form.find(".s-buttons-block"));
                        form.trigger('close');
                    });
                    return false;
                }
            };
            $("body").append("<div id='filter-dialog-delete'></div>");
            var dialog = $("#filter-dialog-delete");
            dialog.waDialog(dialogParams);
        }
    },
    /* Изменение статуса */
    filterStatusAction: function (btn, id) {
        var i = btn.find("i");
        var oldClass = i.attr('class');
        if (!i.hasClass("loading")) {
            var status = i.hasClass("lightbulb") ? 0 : 1;
            i.toggleClass().addClass("icon16 loading");
            $.post("?plugin=autobadge&action=handler", {
                data: 'filterStatus',
                status: status,
                id: id
            }, function (response) {
                if (response.status == 'ok') {
                    if (response.data) {
                        i.toggleClass().addClass("icon16-custom lightbulb");
                    } else {
                        i.toggleClass().addClass("icon16-custom lightbulb-off");
                    }
                } else {
                    i.attr('class', oldClass);
                }
            }, "json");
        }
    },
    /* Копирование фильтра */
    filterCopyAction: function (btn, fl_id) {
        if (!this.hasLoading(btn)) {
            btn.find("i").removeClass("ss orders-all").addClass("loading");
            $.post("?plugin=autobadge&action=handler", {data: 'copyFilter', id: fl_id}, function (response) {
                if (response.status == 'ok' && response.data) {
                    btn.closest(".filter-row").after(response.data);
                }
                btn.find("i").removeClass("loading").addClass("ss orders-all");
            });
        }
    },
    autobadgeAction: function () {
        this.load("?plugin=autobadge&module=settings&first=0", function () {
            $.autobadge.initRuleSortAction();
        });
        $(".Zebra_DatePicker").remove();
        this.cleanColorpicker();
    },
    /* Страница фильтра */
    autobadgeFilterAction: function (id) {
        if (parseInt(id)) {
            $.autobadge.storage.set('autobadge-last-page', id);
            this.load("?plugin=autobadge&module=filter&id=" + id);
        }
    },
    /* Создание нового правила */
    autobadgeNewAction: function () {
        this.load("?plugin=autobadge&module=filter&id=new");
    },
    /* Отобразить все доступные условия */
    showConditionAction: function (btn) {
        var html = $("<div class='condition temp'></div>");
        var conditionsList = $("#condition-template").clone();
        conditionsList.removeAttr("id").show();
        html.append(conditionsList);
        var condBlock = btn.closest(".condition-block");
        btn.hide();
        condBlock.children(".conditions").append(html);
        if ($("> .conditions > .condition", condBlock).length > 1) {
            condBlock.children(".conditions").addClass("tree");
        } else {
            condBlock.children(".conditions").removeClass("tree");
        }
        var select = condBlock.find(".condition-template").chosen({
            disable_search_threshold: 10,
            no_results_text: $__("No result text")
        }).trigger('chosen:open')
            .on('chosen:hiding_dropdown', function (evt, params) {
                btn.show();
                condBlock.find(".condition.temp").remove();
                var value = select.val();
                if (value == 'add') {
                    $.autobadge_conditions.addGroup(condBlock.children(".conditions"));
                } else {
                    $.autobadge_conditions.addField(condBlock.children(".conditions"), value);
                }
                $("> .conditions > .condition", condBlock).length <= 1 && condBlock.children(".conditions").removeClass("tree");
            });
    },
    /* Удаление условия */
    deleteConditionAction: function (btn) {
        var condBlock = btn.closest(".condition-block");
        $("> .condition", btn.closest(".conditions")).length == 2 && btn.closest(".conditions").removeClass("tree");
        btn.closest(".condition").remove();
        $.autobadge_conditions.updateConditionOperatorBlock(condBlock);
    },
    /* Удаление блока условия */
    deleteConditionBlockAction: function (btn) {
        if (!confirm($__("Do you really want to delete condition group?"))) {
            return false;
        }
        var parentBlock = btn.closest(".condition").closest(".conditions");
        btn.closest(".condition").remove();
        $("> .condition", parentBlock).length == 1 && parentBlock.removeClass("tree");
        $.autobadge_conditions.updateConditionOperatorBlock(parentBlock.closest(".condition-block"));
    },
    /* Создание всплывающего окна для выбора товара или пользователя */
    openConditionDialogAction: function (btn) {
        var id = btn.data("id");
        var source = btn.data("source");
        var dialogParams = {
            loading_header: $__("Wait, please..."),
            width: '80%',
            class: 'condition-dialog',
            onLoad: function () {
                btn.addClass("has-dialog");
            },
            onCancel: function () {
                btn.removeClass("has-dialog");
            },
            onClose: function () {
                btn.removeClass("has-dialog");
            }
        };
        if (!$("#condition-dialog-" + id).length) {
            $("body").append("<div id='condition-dialog-" + id + "'></div>");
            dialogParams['url'] = source;
        }
        var dialog = $("#condition-dialog-" + id);
        dialog.waDialog(dialogParams);
        return false;
    },
    initTargetChosen: function (select, skipOpen) {
        skipOpen = skipOpen || false;
        select.chosen({disable_search_threshold: 10, no_results_text: $__("No result text")});
        !skipOpen && select.trigger('chosen:open');
        select.on("change", function () {
            var that = $(this);
            var value = that.val();
            var targetRow = that.closest(".target-row");
            var targetBlock = targetRow.find(".target-block");
            targetBlock.toggleClass().addClass("target-block s-target-" + value);
            targetBlock.html($.autobadge_conditions.getInputCode({
                name: 'type',
                input_type: 'hidden',
                value: value
            })).addClass("hidden").hide();
            $.autobadge_appearance.hideRibbonEditField();
            if (value.substring(0, 7) == 'default') {
                /* Если добавлено больше одной дефолтной наклейки, показываем предупреждение */
                !that.closest(".condition-text").next('.warning').length && that.closest(".condition-text").after('<span class="condition-text highlighted warning">' + $__('You can use only one default Webasyst badge in rule') + '</span>');
                targetBlock.addClass("hidden").hide().next().hide();
                $.autobadge_appearance.deleteRibbon(targetRow.attr("data-id"));
            } else {
                that.closest(".condition-text").removeClass("line-throw").next(".warning").remove();
                var templateId = value !== 'create' ? parseInt(value.substring(10)) : 0;
                $.autobadge_appearance.addRibbonEditField(targetRow, templateId);
                targetBlock.next().show();
            }
            $.autobadge.checkDefaultBadges();
        });
    },
    /* Проверяем, чтобы среди целей была только одна наклейка из дефолтного набора Вебасист */
    checkDefaultBadges: function () {
        var defaultBadges = $(".targets").find("div[class*='s-target-default']");
        if (defaultBadges.length) {
            defaultBadges.each(function (i, v) {
                if (i === 0) {
                    $(this).closest('.condition-text').removeClass("line-throw").next(".warning").hide();
                } else {
                    $(this).closest('.condition-text').addClass("line-throw").next(".warning").show();
                }
            });
        }
        /* Если остались только дефолтные наклейки, скрываем возможность сохранения шаблона */
        if ($('.target-row').not('#target-template').length == defaultBadges.length) {
            $(".s-save-badge").hide();
        }
    },
    /* Изменение целей условия */
    editTargetAction: function (btn) {
        var targetRow = btn.closest(".target-row");
        if (targetRow.find(".autobadge-edit-block").length) {
            targetRow.find(".autobadge-edit-block").toggle();
            targetRow.find(".autobadge-edit-block").is(":visible") && $.autobadge_appearance.highlightRibbon('ab-target-' + targetRow.attr("data-id"));
        } else {
            $.autobadge_appearance.addRibbonEditField(targetRow, false);
        }
    },
    addTargetAction: function (btn, skipOpen) {
        skipOpen = skipOpen || false;
        var target = $("#target-template").clone();
        target.removeAttr("id").find(".target-block").html('').next().hide();
        target.find(".target-chosen").show().next(".chosen-container").remove();
        target.show().append("<div class='condition-text'><a href='#/delete/target/' class='js-action' title='" + $__('delete') + "'><i class='icon16 delete'></i></a></div>");
        $(".targets .s-add-target").before(target);

        var targetId = 1;
        while ($(".target-row[data-id='" + targetId + "']").length) {
            targetId++;
        }
        target.attr("data-id", targetId);

        this.initTargetChosen(target.find(".target-chosen"), skipOpen);
    },
    /* Удаление цели */
    deleteTargetAction: function (btn) {
        var targetRow = btn.closest('.target-row');
        targetRow.find(".autobadge-edit-block").length && $(".autobadge-edit-block").hide().appendTo($(".targets"));
        $.autobadge_appearance.deleteRibbon(targetRow.attr("data-id"));

        targetRow.remove();

        this.checkDefaultBadges();
    },
    /* Сохранение правила */
    saveFilter: function (form) {
        var btn = form.find("input[type='submit']");
        if (!btn.next(".loading").length) {
            $("#fixed-save-panel .errormsg").remove();
            btn.after("<i class='icon16 loading'></i>");

            $("#condition-input").val($.autobadge_conditions.getJsonConditions());
            $("#target-input").val($.autobadge_conditions.getJsonTarget());

            $.ajax({
                url: "?plugin=autobadge&module=filter&action=save",
                dataType: "json",
                type: "post",
                data: form.find(".f-save-me").serializeArray(),
                success: function (response) {
                    if (response.status == 'ok' && response.data) {
                        btn.next(".loading").removeClass("loading").addClass("yes");
                        $.autobadge.setHash("#/autobadge/filter/" + response.data);
                    } else {
                        btn.next(".loading").removeClass("loading").addClass("no");
                        $("#fixed-save-panel .block").append("<div class='margin-block errormsg'>" + $__("Something wrong") + "</div>");
                    }
                    setTimeout(function () {
                        btn.next("i").remove();
                    }, 3000);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    btn.next(".loading").removeClass("loading").addClass("no");
                    $("#fixed-save-panel .block").append("<div class='margin-block errormsg'>" + $__("Something wrong") + "</div>");
                    setTimeout(function () {
                        btn.next("i").remove();
                    }, 3000);
                    console.log(jqXHR, textStatus, errorThrown);
                }
            });
        }
    },
    // Переключение между вкладками
    changeImageMarginsTabAction: function (btn) {
        btn = $(btn);
        var tab = btn.parent();
        if (!tab.hasClass("selected")) {
            $.autobadge_appearance.removeImageMargin(btn.closest("ul").find("li.selected a").data('tab'), btn.closest(".text-item").attr("data-pos"));
            tab.addClass("selected").removeClass("no-tab").siblings().toggleClass().addClass("no-tab");
            tab.closest("ul").next("input").attr("name", btn.data('tab')).trigger('input');
        }
    },
    initColorIcon: function (btn) {
        btn = $(btn);
        btn.css('background-color', '#' + btn.attr("data-color")).ColorPicker({
            color: btn.attr("data-color"),
            onShow: function (colpkr) {
                btn.ColorPickerSetColor(btn.attr("data-color") !== undefined ? btn.attr("data-color") : btn.val());
                $(colpkr).fadeIn(500);
                return false;
            },
            onHide: function (colpkr) {
                $(colpkr).fadeOut(500);
                return false;
            },
            onChange: function (hsb, hex, rgb) {
                btn.css('backgroundColor', '#' + hex);
                btn.attr("data-color", hex).next().val(hex);
                btn.hasClass("print-hex") && btn.val(hex);
                btn.trigger('input').next(":hidden").trigger('input');
            }
        }).on('keyup', function () {
            var hex = btn.ColorPickerFixHex(this.value);
            btn.ColorPickerSetColor(hex).css('backgroundColor', '#' + hex).attr('data-color', hex).trigger('input');
        });
    },
    /* Удаляем ненужные элементы плагина Colorpicker */
    cleanColorpicker: function () {
        $("body > .colorpicker").remove();
    },
    /* Добавление текстовой строчки для наклейки */
    addTextItemLineAction: function (btn) {
        var type = btn.data('type'),
            clone = btn.closest(".value").find(type == 'text' ? ".badge-line" : ".attachment-container").first().clone(),
            posIndex = this.findMaximumAttrValue($(".text-item"), 'data-pos', -1) + 1,
            badgeText = btn.closest(".badge-text");
        clone.find("input, textarea").val('');
        btn.closest(".text-buttons").before(clone.show());
        clone.attr("data-pos", posIndex);
        if (type == 'text') {
            clone.find("input[name='shadow']").prop("checked", true);
            clone.find(".color-icon").attr('data-color', 'ffffff').css('background', '#fff');
            this.initColorIcon(clone.find(".color-icon"));
            badgeText.hasClass("fake-multiline") && badgeText.removeClass("multiline fake-multiline");
        } else {
            clone.removeClass('has-image').find("img").remove();
            clone.find(".tabs").each(function () {
                var that = $(this);
                var firstLi = that.find("li:first");
                firstLi.removeClass("no-tab").addClass("selected").siblings().removeClass("selected").addClass("no-tab");
                that.next().attr("name", firstLi.find('a').data('tab'));
            });
            this.initFileupload(clone.find('.fileupload-attachment'));
        }
    },
    /* Поиск максимального значения у атрибута */
    findMaximumAttrValue: function (elements, attr, defVal) {
        var max = defVal !== null ? defVal : null;
        elements.each(function () {
            var value = parseInt($(this).attr(attr));
            max = (value > max) ? value : max;
        });
        return max;
    },
    /* Меняем значения ширины и высоты местами */
    toggleSizeAction: function (btn) {
        var column = btn.closest(".menu-item");
        var w = column.find(".s-width input").val();
        var h = column.find(".s-height input").val();
        column.find(".s-width input").val(h).trigger('input');
        column.find(".s-height input").val(w).trigger('input');
    },
    appendNewTemplate: function (data) {
        if ($.autobadge_appearance.templates[data.id] === undefined) {
            $.autobadge_appearance.templates[data.id] = {};
        }
        $.autobadge_appearance.templates[data.id]['settings'] = data.settings;
        if (!$("#target-template .autobadge-" + data.id).length) {
            $(".target-chosen .option-autobadge").each(function () {
                $(this).show().append("<option class='autobadge-" + data.id + "' value='autobadge-" + data.id + "'>" + data.name + "</option>").closest("select").trigger("chosen:updated");
            });

            $.autobadge_appearance.templates[data.id] = data;
        } else {
            $(".target-chosen .autobadge-" + data.id).each(function () {
                $(this).text(data.name).closest("select").trigger("chosen:updated");
            });
            $.autobadge_appearance.templates[data.id]['name'] = data.name;
        }
    },
    /* Сохранить шаблон наклейки */
    saveTemplateAction: function (btn) {
        var templateId = btn.attr("data-template-id");
        var dialogParams = {
            'min-height': '200px',
            height: '200px',
            width: '600px',
            title: $__("Save badge template"),
            class: 'nopadded',
            content: '<div class="grey" style="margin: 30px 0 10px">' + $__('Template name') + '</div>' + ' <input type="text" name="name" value="' + ($.autobadge_appearance.templates[templateId] !== undefined ? $.autobadge_appearance.templates[templateId]['name'] : '') + '" style="font-size: 1.2em; padding: 10px; width: 100%;">',
            disableButtonsOnSubmit: true,
            buttons: "<input type='submit' name='new' class='button blue' onclick='$(this).addClass(\"active\")' value='" + $__("Save as new template") + "'> " +
                ($.autobadge_appearance.templates[templateId] !== undefined ? "<input type='submit' name='edit' class='button green' onclick='$(this).addClass(\"active\")' value='" + $__("Save changes") + "'> " : '') +
                $__("or") + " <a class='cancel' href='#'>" + $__('close') + "</a>",
            onSubmit: function (d) {
                d.find(".dialog-window").addClass("is-loading");
                var settings = $.autobadge_appearance.ribbons[$.autobadge_appearance.activeClass][$.autobadge_appearance.activeRibbon];
                /* Удаляем лишние настройки */
                if (settings.size.values !== undefined) {
                    delete settings.size.values;
                }
                $.post("?plugin=autobadge&action=handler&data=saveTemplate", {
                    settings: settings,
                    name: d.find("input[name='name']").val(),
                    type: d.find("input[type='submit'].active").attr("name"),
                    template_id: templateId
                }, function (response) {
                    if (response.status == 'ok' && response.data) {
                        $.autobadge.appendNewTemplate(response.data);
                    }
                    d.find(".dialog-window").removeClass("is-loading");
                    d.trigger('close');
                }, "json");

                return false;
            },
            onClose: function () {
                $("#template-dialog").remove();
            }
        };
        $("body").append("<div id='template-dialog'></div>");
        var dialog = $("#template-dialog");
        dialog.waDialog(dialogParams);
    },
    /* Удалить шаблон */
    removeTemplateAction: function (btn) {
        var templateId = btn.attr("data-template-id");
        var dialogParams = {
            'min-height': '200px',
            height: '200px',
            width: '500px',
            title: $__("Remove badge template"),
            class: 'delete-dialog nopadded',
            content: '<p style="margin: 30px 0 10px">' + $__('Do you really want to remove badge template?') + '</p><p>' + $__('Settings for the current badge will not be changed.') + '</p>',
            disableButtonsOnSubmit: true,
            buttons: "<input type='submit' class='button red' value='" + $__("Remove") + "'> " + $__("or") + " <a class='cancel' href='#'>" + $__('close') + "</a>",
            onSubmit: function (d) {
                d.find(".dialog-window").addClass("is-loading");
                $.post("?plugin=autobadge&action=handler&data=removeTemplate", {template_id: templateId}, function (response) {
                    if (response.status == 'ok') {
                        var targetTemplate = $("#target-template .autobadge-" + templateId),
                            targetChosenTemplate = $(".target-chosen .autobadge-" + templateId);
                        targetTemplate.length && targetTemplate.remove();
                        if (targetChosenTemplate.length) {
                            targetChosenTemplate.each(function () {
                                var that = $(this),
                                    select = that.closest("select");
                                that.remove();
                                select.trigger("chosen:updated");
                            });
                        }
                        btn.hide().closest('.target-row').find('.target-chosen').find('.s-create').prop('selected', true).closest("select").trigger("chosen:updated");
                    }
                    d.find(".dialog-window").removeClass("is-loading");
                    d.trigger('close');
                }, "json");

                return false;
            },
            onClose: function () {
                $("#template-remove-dialog").remove();
            }
        };
        $("body").append("<div id='template-remove-dialog'></div>");
        var dialog = $("#template-remove-dialog");
        dialog.waDialog(dialogParams);
    },
    /* Импорт/экспорт наклеек */
    importExportTemplateAction: function (btn) {
        var type = btn.attr("data-type");
        var dialogParams = {
            'min-height': '200px',
            height: type == 'import' ? '250px' : '200px',
            width: '500px',
            title: (type == 'import' ? $__("Import badge settings") : $__("Export badge settings")),
            class: 'nopadded importexport-dialog',
            content: type == 'import' ? '<h3>' + $__('Import') + '</h3><p><input type="file" name="csv" class="fileupload"><span class="progressfield-block hidden"></span><span class="grey small" style="margin-bottom: 10px;display: block;">' + $__('Choose only CSV files') + '</span><span class="grey">' + $__('Example') + ':</span> <a href="?plugin=autobadge&action=handler&data=exportDownload&file=import_example.csv"><i class="icon16 ss excel" style="vertical-align: middle;"></i> import_example.csv</a></p>' : '<h3>' + $__('Export') + '</h3><p class="export-block align-center"><input name="name" type="text" placeholder="' + $__('Badge template name') + '"><input type="submit" class="button green" value="' + $__("Export badge settings") + '"></p>',
            disableButtonsOnSubmit: true,
            buttons: "<a class='cancel' href='#'>" + $__('close') + "</a>",
            onSubmit: function (d) {
                d.find(".dialog-window").addClass("is-loading");
                $.post("?plugin=autobadge&action=handler&data=exportTemplate", {
                    name: d.find("input[name='name']").val(),
                    settings: $.autobadge_appearance.ribbons[$.autobadge_appearance.activeClass][$.autobadge_appearance.activeRibbon]
                }, function (response) {
                    if (response.status == 'ok') {
                        d.find('.export-block').html(response.data);
                    } else {
                        d.find('.export-block').html('<em class="errormsg">' + $__('Something wrong. Check log files') + '</em>');
                    }
                    d.find(".dialog-window").removeClass("is-loading");
                }, "json");
                return false;
            },
            onLoad: function () {
                type == 'import' && $.autobadge.initImportField($(this).find("input[type='file']"));
            },
            onClose: function () {
                $("#template-import-dialog").remove();
            }
        };
        $("body").append("<div id='template-import-dialog'></div>");
        var dialog = $("#template-import-dialog");
        dialog.waDialog(dialogParams);
    },
    /* Сортировка полей */
    initRuleSortAction: function () {
        $(".filter-list tbody").sortable({
            items: ".filter-row",
            handle: '.icon16.sort',
            update: function (event, ui) {
                var item = $(".filter-row[data-id='" + ui.item.attr('data-id') + "']");
                /* Обновляем порядок сортировки */
                $.autobadge.updateRuleSort($(".filter-row[data-id='" + item.attr('data-id') + "']"));
            }
        });
    },
    updateRuleSort: function (rule) {
        var filterRow = $(".filter-row");
        var index = filterRow.index(rule);
        var beforeId = index === 0 ? null : filterRow.eq(index - 1).attr("data-id");
        $.post("?plugin=autobadge&action=handler", {
            data: 'ruleSort',
            id: rule.attr("data-id"),
            before_id: beforeId,
            after_id: filterRow.eq(index + 1).attr("data-id")
        });
    },
    /* Сортировка наклеек */
    initFieldsSortAction: function () {
        $(".targets").dragsort({
            dragSelector: '.target-row .condition-text > .sort',
            dragSelectorExclude: 'input, textarea, select, checkbox, .rangeslider, .tooltip',
            dragEnd: function () {
                $.autobadge.changeBadgeSort();
            }
        });
    },
    changeBadgeSort: function () {
        var targetRow = $(".target-row"),
            len = targetRow.length;
        if (len > 1) {
            targetRow.each(function () {
                $(".live-preview .autobadge-pl[data-id='" + $(this).attr('data-id') + "']").css('zIndex', len - $(this).index());
            });
        }
    },
    /* Сортировка тексовых слоев */
    initTextItemSortAction: function () {
        $(".badge-text > .value").dragsort({
            dragSelector: '.sort',
            dragSelectorExclude: 'input, textarea, select, checkbox, .rangeslider, .tooltip',
            dragEnd: function () {
                $.autobadge.changeTextItemSort($(this));
            }
        });
    },
    changeTextItemSort: function (elem) {
        var elements = elem.closest('.badge-text').find(".text-item:visible");
        var newTextObj = [];
        var appearance = $.autobadge_appearance;
        elements.each(function (i) {
            newTextObj.push(appearance.ribbons[appearance.activeClass][appearance.activeRibbon]['text'][$(this).attr("data-pos")]);
            $(this).attr("data-pos", i);
        });
        $.autobadge_appearance.ribbons[$.autobadge_appearance.activeClass][$.autobadge_appearance.activeRibbon]['text'] = newTextObj;
        $.autobadge_appearance.refreshActiveRibbon();
    },
    /* Генерирование наклеек */
    buildTargets: function (targets) {
        if (targets.length) {
            for (var i in targets) {
                var target = targets[i];
                if (target.conditions !== undefined) {
                    var targetId = (parseInt(i) + 1);
                    var activeClass = 'ab-target-' + targetId;

                    $.autobadge_appearance.ribbons[activeClass] = {active: target.conditions.id};
                    $.autobadge_appearance.ribbons[activeClass][target.conditions.id] = target.conditions.settings;

                    if ($.autobadge_appearance.ribbons['default_' + target.conditions.id] !== undefined && $.autobadge_appearance.ribbons['default_' + target.conditions.id]['size']['values'] !== undefined) {
                        $.autobadge_appearance.ribbons[activeClass][target.conditions.id]['size']['values'] = $.autobadge_appearance.ribbons['default_' + target.conditions.id]['size']['values'];
                    }
                    $.autobadge_appearance.initRibbon(targetId, target.conditions.id);
                }
            }
        }
    },
    /* Сброс настроек наклейки */
    setDefaultSettingsAction: function (btn) {
        var ribbonClass = btn.attr("data-class");
        delete $.autobadge_appearance.ribbons[$.autobadge_appearance.activeClass][ribbonClass];
        $.autobadge_appearance.initRibbon(btn.closest(".target-row").attr('data-id'), ribbonClass);
    },
    /* Загрузка изображений */
    initFileupload: function (field) {
        var progressField = field.siblings(".progressfield-block");
        field.fileupload({
            autoUpload: true,
            dataType: 'json',
            url: "?plugin=autobadge&module=filter&action=attachmentUpload",
            progressall: function (e, data) {
                var progress = parseInt(data.loaded / data.total * 100, 10);
                progressField.find(".progressbar-inner").css('width', progress + '%');
            },
            submit: function (e, data) {
                progressField.removeClass("hidden").html("<div class=\"progressbar green small float-left\" style=\"width: 70%;\"><div class=\"progressbar-outer\"><div class=\"progressbar-inner\" style=\"width: 0;\"></div></div></div><i class=\"icon16 loading\" style=\"margin: 7px 0 0 5px;\"></i><br class='clear-both' />");
            },
            done: function (e, data) {
                var response = data._response.result;
                if (response && response.status == 'ok') {
                    progressField.addClass("hidden");
                    if (response.data.filelink) {
                        var fieldBlock = data.fileInputClone;
                        if (!fieldBlock.siblings('.attachment-file').length) {
                            var html = "<img src='" + response.data.filelink + "' class='attachment-file'>";
                            fieldBlock.closest('.attachment-container').addClass('has-image');
                        } else {
                            fieldBlock.siblings('.attachment-file').attr("src", response.data.filelink);
                        }
                        var textInput = fieldBlock.closest('.attachment-block').find('input[type="text"]');
                        textInput.val(response.data.filelink);
                        fieldBlock.after(html);
                        $.autobadge_appearance.updateText(textInput, true);
                    }
                } else {
                    progressField.html("<span class=\"red\">" + response.errors + "</span>");
                }
            },
            fail: function (e, data) {
                progressField.html("<span class=\"red\">" + $__("Upload failed") + "</span>");
            }
        });
    },
    /* Импорт наклеек */
    initImportField: function (field) {
        var progressField = field.siblings(".progressfield-block");
        field.fileupload({
            autoUpload: true,
            dataType: 'json',
            url: "?plugin=autobadge&module=filter&action=csvUpload",
            progressall: function (e, data) {
                var progress = parseInt(data.loaded / data.total * 100, 10);
                progressField.find(".progressbar-inner").css('width', progress + '%');
                progressField.find(".upload-condition").text($__("Uploading file..."));
            },
            submit: function (e, data) {
                progressField.removeClass("hidden").html("<div class=\"progressbar green small float-left\" style=\"width: 70%;\"><div class=\"progressbar-outer\"><div class=\"progressbar-inner\" style=\"width: 0;\"></div></div></div><br class='clear-both'><span class=\"upload-condition\"></span><i class=\"icon16 loading\" style=\"margin: 7px 0 0 5px;\"></i><br class='clear-both' />");
            },
            done: function (e, data) {
                var response = data._response.result;
                if (response && response.status == 'ok') {
                    if (response.status == 'ok' && response.data) {
                        $.each(response.data, function (i, v) {
                            $.autobadge.appendNewTemplate(v);
                        });
                        progressField.html("<span class=\"successmsg\">" + $__("Templates added successfully!") + "</span>");
                    }
                } else {
                    progressField.html("<span class=\"red\">" + response.errors + "</span>");
                }
                setTimeout(function () {
                    progressField.addClass("hidden");
                }, 5000);
            },
            fail: function (e, data) {
                progressField.html("<span class=\"red\">" + $__("Upload failed") + "</span>");
            }
        });
    },
    /* Плавающее превью */
    adjustLivePreview: function () {
        var scrollOffset = $(window).scrollTop(),
            livePreview = $(".live-preview");
        if (livePreview.is(':animated') || !livePreview.length) {
            return false;
        }
        var targetsTop = $(".targets").offset().top;
        clearTimeout(livePreview.data('timer'));
        livePreview.data('timer', setTimeout(function () {
            if (scrollOffset >= (targetsTop - 50)) {
                livePreview.stop().animate({"marginTop": scrollOffset - targetsTop + 50}, 500);
            } else {
                livePreview.stop().animate({"marginTop": 0}, 500);
            }
        }, 200));
    },
    /* Список доступных переменных для названия наклейки */
    showVariablesAction: function (btn) {
        var tooltip = $("#tooltip-text");
        $(".s-show-variable").removeClass('active');
        btn.addClass('active');
        tooltip.clone().removeClass('show-text show-image').addClass(btn.data('type') == 'text' ? 'show-text' : 'show-image').show().insertAfter(btn);
    },
    /* Выбор загруженной фотографии для наклейки */
    selectUploadedImageAction: function (btn) {
        var active = $(".s-show-variable.active"),
            textItem = active.closest(".text-item"),
            attachmentFile = textItem.find('img.attachment-file');
        textItem.find("i.loading.attachment-file").remove();
        attachmentFile.hide().after("<i class='icon16 loading attachment-file'></i>");
        $("<img class='attachment-file' src='" + btn.attr('data-link') + "'>").load(function () {
            attachmentFile.replaceWith($(this));
            textItem.find("i.loading.attachment-file").remove();
            active.closest('div').find('input').val(btn.attr('data-link')).trigger('input');
        });
    },
    /* Автоматическое позиционирование и размер */
    autoSizeBadges: function (settings) {
        $(".autoposition-h, .autoposition-w").each(function () {
            var that = $(this);
            var mTop = (that.attr('data-mtop') !== undefined ? parseFloat(that.attr('data-mtop')) : 0);
            var mLeft = (that.attr('data-mleft') !== undefined ? parseFloat(that.attr('data-mleft')) : 0);
            if (that.hasClass('autoposition-h')) {
                that.height(that.height());
                mTop += (-1) * that.height() / 2;
            }
            if (that.hasClass('autoposition-w')) {
                that.width(that.width());
                mLeft += (-1) * that.outerWidth() / 2;
            }
            that.css({
                marginTop: mTop,
                marginLeft: mLeft
            });
        });

        /* Позиционируем текст по центру */
        var activeBadge = $(".live-preview ." + $.autobadge_appearance.activeClass + " .badge-text-block");
        if (!activeBadge.children().length || settings.size.height == 'auto' || $.autobadge_appearance.activeRibbon == 'ribbon-6' || $.autobadge_appearance.activeRibbon == 'ribbon-4') {
            return true;
        }
        activeBadge.css('marginTop', (-1) * activeBadge.height() / 2);
    },
    /* Страница настроек */
    openSettingsAction: function () {
        var dialogParams = {
            loading_header: $__("Wait, please..."),
            class: 'nopadded',
            url: '?plugin=autobadge&module=dialog&action=settings',
            buttons: '<div class="align-center"><input type="submit" value="' + $__("Save") + '" class="button green"><span class="errormsg" style="margin-left: 10px; display: inline-block"></span></div>',
            onClose: function () {
                $("#settings-dialog-edit").remove();
            },
            onSubmit: function (d) {
                var dialowWindow = d.find(".dialog-window");
                if (!dialowWindow.hasClass("is-loading")) {
                    dialowWindow.addClass("is-loading");
                    var button = d.find("input[type='submit']");
                    button.next(".temp").remove();
                    $.post("?plugin=autobadge&action=handler&data=settingsSave", d.find("form").serialize(), function (response) {
                        dialowWindow.removeClass("is-loading");
                        button.after(" <span class='temp'><i class='icon16 yes'></i> " + $__("Saved") + "</span>");
                        setTimeout(function () {
                            button.next(".temp").remove();
                        }, 5000);
                    }, "json");
                }
                return false;
            }
        };
        $("body").append("<div id='settings-dialog-edit'></div>");
        var dialog = $("#settings-dialog-edit");
        dialog.waDialog(dialogParams);
        return false;
    },
    /* Системные настройки */
    openSystemSettingsAction: function() {
        var selector = 'settings-autobadge-dialog';
        var dialogParams = {
            loading_header: $__("Wait, please..."),
            'min-height': '270px',

            class: 'nopadded',
            url: '?plugin=autobadge&module=dialog&action=systemSettings',
            disableButtonsOnSubmit: true,
            buttons: "<input type='submit' class='button green' value='" + $__("Save") + "'> " + $__("or") + " <a class='cancel' href='#'>" + $__('close') + "</a>",
            onClose: function () {
                $("#" + selector).remove();
            },
            onSubmit: function (d) {
                var $submitBtn = d.find("input[type=submit]");
                var $icon = $("<i class='loading icon16'></i>");
                $submitBtn.next('i').remove();
                $submitBtn.after($icon);
                $.post("?plugin=autobadge&action=handler&data=saveSystemSettings", d.find("form").serializeArray(), function (response) {
                    $submitBtn.removeAttr('disabled');
                    $icon.removeClass('loading');
                    if (response.status == 'ok' && response.data) {
                        $icon.addClass('yes');
                    } else {
                        $icon.addClass('no');
                    }
                    setTimeout(function () {
                        $icon.remove();
                    }, 3000);
                }, "json");
                return false;
            }
        };
        $("body").append("<div id='" + selector + "'></div>");
        var dialog = $("#" + selector);
        dialog.waDialog(dialogParams);
    },
    /* Вывод напоминания о необходимости произвести настройки */
    showDocsNotification: function () {
        var dialogParams = {
            content: '<p style="margin-top: 33px;">' + $__("Do not forget to make <a href=\"https://www.webasyst.com/store/plugin/shop/autobadge/setup\" target=\"_blank\">changes to your theme design</a>") + '</p>',
            buttons: '<div class="align-center"><input type="submit" value="' + $__("Thanks for reminder!") + '" class="button green"></div>',
            class: 'nopadded',
            height: '200px',
            'min-height': '200px',
            onClose: function () {
                $("#reminder-dialog").remove();
            },
            onSubmit: function (d) {
                d.trigger('close');
                return false;
            }
        };
        $("body").append("<div id='reminder-dialog'></div>");
        var dialog = $("#reminder-dialog");
        dialog.waDialog(dialogParams);
        return false;
    }
};