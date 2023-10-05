/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

$.flexdiscount = {
    discount: {},
    init: function (options) {
        var that = this;

        that.initBase(options);
        that.initRouting();
        /* Вызываем js функции */
        $(document).on('click', 'a.js-action', function () {
            $.flexdiscount.activateJsAction($(this));
            return false;
        });
        $(document).on('focus', 'input.error, textarea.error', function () {
            $(this).removeClass("error");
        });
    },
    initBase: function (options) {
        var that = this;
        that.localeStrings = options.localeStrings || {};
        that.pluginUrl = options.pluginUrl || {};

        $.ig_locale = $.extend(that.localeStrings, $.ig_locale);
        if (typeof $__ === "undefined") {
            window.$__ = puttext($.ig_locale);
        }
    },
    initRouting: function () {
        if (typeof ($.History) != "undefined") {
            $.History.bind(function () {
                $.flexdiscount.dispatch();
            });
        }
        $.wa.errorHandler = function (xhr) {
            if ((xhr.status === 403) || (xhr.status === 404)) {
                var text = $(xhr.responseText);
                if (console) {
                    console.log(text);
                }
                if (text.find('.dialog-content').length) {
                    text = $('<div class="block double-padded"></div>').append(text.find('.dialog-content *'));
                } else {
                    text = $('<div class="block double-padded"></div>').append(text.find(':not(style)'));
                }
                $("#s-content").empty().append(text);
                return false;
            }
            return true;
        };
        if ($.shop.options.page !== 'marketing') {
            var hash = window.location.hash;
            if (hash === '#/' || !hash) {
                this.dispatch();
            } else {
                this.setHash(hash);
            }
        }
    },
    initBackend: function () {
        /* Постраничная навигация */
        $("#pagination a").click(function () {
            $.flexdiscount.load($(this).attr("href"));
            return false;
        });
        /* Выделение строк */
        $(document).on('change', ".f-checker", function () {
            var that = $(this);
            if (that.prop('checked')) {
                that.closest(".discount-row").addClass("selected");
            } else {
                that.closest(".discount-row").removeClass("selected");
            }
        });
        /* Изменение названия группы */
        $(document).on('click', ".group-name", function () {
            var that = $(this);
            if (!that.hasClass("edit")) {
                that.find("span").hide();
                that.find("form").show().focus();
            }
        });
        $(document).on('submit', ".group-name form", function () {
            var that = $(this);
            if (!$.flexdiscount.hasLoading(that)) {
                $.flexdiscount.appendLoading(that);
                $.post("?plugin=flexdiscount&action=handler", {
                    data: 'changeGroup',
                    element: 'name',
                    group_id: that.closest(".discount-group").attr("data-id"),
                    name: that.find("input[type='text']").val()
                }, function (response) {
                    if (response.status == 'ok') {
                        that.find("input[type='text']").val(response.data);
                        that.closest(".discount-group").find(".group-name span").text(response.data).show();
                        that.hide();
                    }
                    $.flexdiscount.removeLoading(that);
                });
            }
            return false;
        });
        /* Изменение типа расчета у групп */
        $(document).on('change', ".f-combine", function () {
            var that = $(this);
            $.post("?plugin=flexdiscount&action=handler", {
                data: 'changeGroup',
                element: 'combine',
                group_id: that.closest(".discount-group").attr("data-id"),
                combine: that.val()
            });
            return false;
        });
        /* Сохранение скидок из каталога */
        $(document).on('dblclick', '.discount-value, .affiliate-value', function () {
            var that = $(this);
            if (!that.hasClass('edit')) {
                $('> *', that).hide();
                that.addClass("edit").removeClass("hidden").find('.edit-block').show();
            }
        });
        $(document).on('click', '.edit-block input[type="submit"]', function () {
            var that = $(this);
            if (!that.hasClass("loader2")) {
                that.addClass("loader2");
                var editBlock = that.closest('.edit-block');
                $.post("?plugin=flexdiscount&action=handler", {
                    data: 'editDiscount',
                    id: that.closest(".discount-row").data('id'),
                    type: that.closest('.affiliate-value').length ? 'affiliate' : 'discount',
                    percentage: editBlock.find(".f-perc-val").val(),
                    fixed: editBlock.find(".f-fixed-val").val(),
                    currency: editBlock.find(".f-cur-val").val()
                }, function (response) {
                    var block = that.closest(".editable");
                    if (typeof response.data.percentage !== 'undefined') {
                        if (block.find(".f-percentage-value").length) {
                            block.find(".f-percentage-value").text(response.data.percentage + ' %');
                        } else {
                            block.prepend('<span class="f-percentage-value">' + response.data.percentage + ' %' + '</span>');
                        }
                        editBlock.find(".f-perc-val").val(response.data.percentage);
                    } else {
                        block.find(".f-percentage-value").remove();
                    }
                    if (typeof response.data.fixed !== 'undefined') {
                        block.find(".f-fixed-value").text(response.data.fixed);
                        if (block.find(".f-fixed-value").length) {
                            block.find(".f-fixed-value").text(' + ' + response.data.fixed);
                        } else {
                            block.find('.edit-block').before('<span class="f-fixed-value">' + ' + ' + response.data.fixed + '</span>');
                        }
                        editBlock.find(".f-fixed-val").val(response.data.fixed);
                        if (typeof response.data.currency !== 'undefined') {
                            if (block.find(".f-currency-value").length) {
                                block.find(".f-currency-value").text(' ' + response.data.currency);
                            } else if (block.hasClass('discount-value')) {
                                block.find('.f-fixed-value').after('<span class="f-currency-value">' + ' ' + response.data.currency + '</span>');
                            }
                            editBlock.find(".f-cur-val").val(response.data.currency);
                        } else {
                            block.find(".f-currency-value").remove();
                        }
                    } else {
                        block.find(".f-fixed-value").remove();
                        block.find(".f-currency-value").remove();
                    }
                    that.removeClass("loader2");
                    that.closest('.editable').removeClass("edit").find("> *").show();
                    if (typeof response.data.fixed === 'undefined' && typeof response.data.percentage === 'undefined') {
                        that.closest('.editable').addClass("hidden");
                    }
                    that.closest(".edit-block").hide();
                });
            }
        });
        /* Чистка мусора */
        $(".Zebra_DatePicker").remove();
        $(".discount-group").each(function () {
            $.flexdiscount.initDiscountGroupDrop($(this));
        });
        this.initDiscountsSortAction();
    },
    initDiscountPage: function (options) {
        var that = this;

        if (options.discountId) {
            $.flexdiscount.discount.id = options.discountId;
        }

        /* Switcher */
        that.initSwitcher($('.switcher'));

        /* Выбор валют в ограничении скидки */
        $(".f-limit-currency").change(function () {
            if ($(this).val() == '%') {
                $(".f-percentage-value").show();
            } else {
                $(".f-percentage-value").hide();
            }
        });
        /* Сохранение правила скидок */
        $(document).on('submit', "#flexdiscount-save-form", function () {
            $.flexdiscount.saveDiscount($(this));
            return false;
        });
        /* Открытие/закрытие блока с купонами */
        $(".s-coupon-enable").change(function () {
            if ($(this).prop("checked")) {
                $(".s-coupon-block").slideDown();
            } else {
                $(".s-coupon-block").slideUp();
            }
        });
        /* Выбор настроек "Установить скидку/бонусы на каждый товар/комплект" */
        $(".f-useeachitem").change(function () {
            var that = $(this);
            if (that.prop("checked")) {
                that.closest('.condition-text').siblings('.condition-text').find(':checkbox').prop('checked', false);
            }
        });
        /* Выбор цели условий */
        this.initTargetChosen($(".target-chosen"));

        /* Изменение типа ограничения скидки у товара */
        $(".f-limit-change").change(function () {
            var that = $(this);
            that.closest('.s-limit-wrap').find('.f-limit-type-block').hide().siblings('.f-limit-type-block[data-type=' + that.val() + ']').show();
        });

        /* Изменение типа начисления скидок/бонусов */
        $('.f-discount-type, .f-affiliate-type').change(function () {
            const type = $(this).hasClass('f-discount-type') ? 'discount' : 'affiliate';
            const value = $('.f-' + type + '-type:checked').val();
            $('[data-product-' + type + '-type]').hide();
            $('[data-product-' + type + '-type="' + value + '"]').show();
        });
    },
    /* Иициализация переключателя */
    initSwitcher: function ($field) {
        $field.iButton({ labelOn: "", labelOff: "", className: 'mini' }).change(function () {
            var ibuttonBlock = $(this).closest('.ibutton-checkbox');
            var onLabelSelector = ibuttonBlock.find('.switcher-off'),
                offLabelSelector = ibuttonBlock.find('.switcher-on');
            var additinalField = ibuttonBlock.siblings('.onopen');
            var dependency = $(this).data('dependency') || {};
            if (!this.checked) {
                if (additinalField.length) {
                    additinalField.hide();
                }
                $(dependency).hide();
                $(onLabelSelector).addClass('unselected');
                $(offLabelSelector).removeClass('unselected');
            } else {
                if (additinalField.length) {
                    additinalField.each(function () {
                        var elem = $(this);
                        elem.css('display', (elem.hasClass('inline-block') ? 'inline-' : '') + 'block');
                    });
                }
                $(dependency).show();
                $(onLabelSelector).removeClass('unselected');
                $(offLabelSelector).addClass('unselected');
            }
        }).each(function () {
            var additinalField = $(this).closest('.ibutton-checkbox').siblings('.onopen');
            var dependency = $(this).data('dependency') || {};
            if (!this.checked) {
                if (additinalField.length) {
                    additinalField.hide();
                }
                $(dependency).hide();
            } else {
                if (additinalField.length) {
                    additinalField.each(function () {
                        var elem = $(this);
                        elem.css('display', (elem.hasClass('inline-block') ? 'inline-' : '') + 'block');
                    });
                }
                $(dependency).show();
            }
        });
    },
    /* Сортировка полей */
    initDiscountsSortAction: function () {
        $(".discount-list-body").sortable({
            items: ".discount-row",
            handle: '.icon16.sort',
            placeholder: "sortable-placeholder",
            connectWith: '.discount-group',
            stop: function () {
                $.each($(".discount-group"), function () {
                    if (!$(this).find(".discount-row").length) {
                        $(this).addClass("empty");
                    }
                });
            },
            update: function (event, ui) {
                var item = $(".discount-row[data-id='" + ui.item.attr('data-id') + "']");
                /* Если скидка находилась в группе, удаляем ее из нее */
                if (!item.closest(".discount-group").length && item.attr('data-group-id') !== undefined) {
                    $.post("?plugin=flexdiscount&action=handler", {
                        data: 'removeFromGroup',
                        group_id: item.attr('data-group-id'),
                        fl_id: item.attr('data-id')
                    });
                }
                /* Обновляем порядок сортировки */
                $.flexdiscount.updateDiscountSort($(".discount-row[data-id='" + item.attr('data-id') + "']"));
            }
        });
    },
    updateDiscountSort: function (discount) {
        var index = $(".discount-row").index(discount);
        var beforeId = index === 0 ? null : $(".discount-row").eq(index - 1).attr("data-id");
        $.post("?plugin=flexdiscount&action=handler", {
            data: 'discountSort',
            id: discount.attr("data-id"),
            before_id: beforeId,
            after_id: $(".discount-row").eq(index + 1).attr("data-id")
        });
    },
    initDiscountGroupDrop: function (group) {
        group.droppable({
            activeClass: "ui-state-default",
            hoverClass: "ui-state-hover",
            drop: function (event, ui) {
                $(this).removeClass("empty");
                var row = $(ui.draggable);
                if (!row.closest(".discount-group").length || (group.attr('data-id') !== row.attr("data-group-id"))) {
                    row.removeAttr('style');
                    row.attr("data-group-id", group.attr("data-id"));
                    $(this).prepend(row.prop('outerHTML'));
                    $.post("?plugin=flexdiscount&action=handler", {
                        data: 'addToGroup',
                        group_id: group.attr("data-id"),
                        fl_id: row.attr('data-id')
                    });
                    row.remove();
                }
            },
            out: function () {
                if (!$(this).find(".discount-row").length) {
                    $(this).addClass("empty");
                }
            }
        });
    },
    dispatch: function (hash) {
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
                            if (console) {
                                console.log('Invalid action name:', actionName + 'Action');
                            }
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
            if (console) {
                console.log(e.message, e);
            }
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
        $("#s-content").html('<div class="fl-loading-block">' + $__("Loading...") + '<i class="icon16 loading"></i></div>');
        $.get(url, function (result) {
            if (self.random != r) {
                return;
            }
            $("#s-content").html(result);
            $('html, body').animate({
                scrollTop: 0
            }, 200);
            if (callback) {
                try {
                    callback.call(this);
                } catch (e) {
                    if (console) {
                        console.log('Callback error: ' + e.message, e);
                    }
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
        this.load("?plugin=flexdiscount&action=discountsList");
    },
    appendLoading: function (elem) {
        elem.append(" <i class='icon16 loading'></i>");
    },
    removeLoading: function (elem) {
        elem.find(".loading").remove();
    },
    hasLoading: function (elem) {
        return elem.find(".loading").length;
    },
    /* Создание группы расчета скидок */
    createGroupAction: function (btn) {
        var that = this;
        if (!that.hasLoading(btn)) {
            that.appendLoading(btn);
            $.post("?plugin=flexdiscount&action=handler", { data: 'createGroup' }, function (response) {
                if (response.status == 'ok' && response.data) {
                    var group = $("<div class='discount-group empty' data-id='" + response.data + "'></div>");
                    group.append('<div class="discount-combine"><select class="f-combine"><option value="max">' + $__('MAX') + '</option><option value="mpr">' + $__('MAX PROD') + '</option><option value="min">' + $__('MIN') + '</option><option value="sum">' + $__('SUM') + '</option></select><a href="#/discount/splitGroup/' + response.data + '" class="js-action" title="' + $__("Split group") + '"><i class="icon16 close"></i></a></div>');
                    group.append('<div class="group-name"><span>' + $__('Group name') + '</span><form style="display: none"><input type="text" value="' + $__('Group name') + '"><input type="submit" value="' + $__('Save') + '" /></form></div>');
                    $(".discount-list-body").prepend(group);
                    that.initDiscountGroupDrop(group);
                }
                that.removeLoading(btn);
            });
        }
    },
    /* Появление всплывающего окна для выбора группы */
    dialogMergeAction: function () {
        if (!$(".discount-group").length) {
            alert($__("Create group"));
            return false;
        }
        if ($(".f-checker:checked").length < 1) {
            alert($__("Select at least 1 discount rule"));
            return false;
        }

        var html = "<div class='margin-block top'><ul class='menu-v with-icons'>";
        $(".discount-group").each(function () {
            html += "<li><a href='#/discount/merge/" + $(this).attr('data-id') + "' class='js-action'><i class='icon16 link'></i> " + $(this).find(".group-name").text() + "</a></li>";
        });
        html += "</ul></div>";
        $("#flexdiscount-dialog").waDialog({
            title: $__('Select group') + "<a href='javascript:void(0)' class='dialog-close' onclick='$(this).closest(\".dialog\").trigger(\"close\")'>" + $__('close') + "</a>",
            content: html
        });
    },
    /* Добавление скидок к группе */
    discountMergeAction: function (btn, groupId) {
        var ids = {};
        var collection = '1';
        var beforeId = 0;
        var afterId = 0;
        $(".f-checker:checked").each(function (i) {
            var that = $(this);
            var row = that.closest(".discount-row");
            ids[i] = that.val();
            row.attr('data-group-id', groupId);
            if (i === 0) {
                $(".discount-group[data-id='" + groupId + "']").removeClass('empty').prepend(row);
            } else {
                collection += ',.discount-row[data-id="' + that.val() + '"]';
            }
            if (typeof index == 'undefined') {
                var index = $(".discount-row").index($(".discount-row[data-id='" + that.val() + "']"));
                beforeId = index === 0 ? 0 : $(".discount-row").eq(index - 1).attr("data-id");
                afterId = index === 0 ? 0 : $(".discount-row").eq(index + 1).attr("data-id");
            }
            that.prop('checked', false).change();
        });
        $(".discount-group[data-id='" + groupId + "']").find('.discount-row').first().after($(collection));
        $.post("?plugin=flexdiscount&action=handler", { data: 'addToGroup', group_id: groupId, fl_id: ids });
        /* Обновляем порядок сортировки */
        $.post("?plugin=flexdiscount&action=handler", {
            data: 'discountSort',
            id: ids,
            before_id: beforeId,
            after_id: afterId
        });
        $("#flexdiscount-dialog").trigger("close");
    },
    /* Удаление скидки из группы */
    discountSplitGroupAction: function (btn, groupId) {
        if (!confirm($__("Do you really want to split group"))) {
            return false;
        }
        if (!this.hasLoading(btn)) {
            btn.find("i").removeClass("split").addClass("loading");
            var group = btn.closest(".discount-group");
            if (group.find(".f-checker").length) {
                group.find(".f-checker").prop('checked', true).change();
                this.discountSplitAction(null, null);
            }
            $.post("?plugin=flexdiscount&action=handler", { data: 'removeGroup', group_id: groupId }, function () {
                group.remove();
            });
        }
    },
    discountSplitAction: function (btn, fl_id) {
        if (fl_id) {
            var row = btn.closest(".discount-row");
            var group = btn.closest(".discount-group");
            row.removeAttr("data-group-id");
            $(".discount-group").last().after(row);
            if (!group.find(".discount-row").length) {
                group.addClass("empty");
            }
            $.post("?plugin=flexdiscount&action=handler", { data: 'removeFromGroup', fl_id: fl_id });
            this.updateDiscountSort($(".discount-row[data-id='" + row.attr("data-id") + "']"));
        } else {
            if ($(".f-checker:checked").length < 1) {
                alert($__("Select at least 1 discount rule"));
                return false;
            }
            var ids = {};
            var collection = '1';
            var beforeId = 0;
            var afterId = 0;
            $(".f-checker:checked").each(function (i) {
                var that = $(this);
                var row = that.closest(".discount-row");
                ids[i] = that.val();
                row.removeAttr('data-group-id');
                if (i === 0) {
                    $(".discount-group").last().after(row);
                } else {
                    collection += ',.discount-row[data-id="' + that.val() + '"]';
                }
                if (typeof index == 'undefined') {
                    var index = $(".discount-row").index($(".discount-row[data-id='" + that.val() + "']"));
                    beforeId = index === 0 ? 0 : $(".discount-row").eq(index - 1).attr("data-id");
                    afterId = index === 0 ? 0 : $(".discount-row").eq(index + 1).attr("data-id");
                }
                that.prop('checked', false).change();
            });
            $(".discount-row[data-id='" + ids[0] + "']").after($(collection));
            $.post("?plugin=flexdiscount&action=handler", { data: 'removeFromGroup', fl_id: ids });
            /* Обновляем порядок сортировки */
            $.post("?plugin=flexdiscount&action=handler", {
                data: 'discountSort',
                id: ids,
                before_id: beforeId,
                after_id: afterId
            });
            $.each($(".discount-group"), function () {
                if (!$(this).find(".discount-row").length) {
                    $(this).addClass("empty");
                }
            });
        }
    },
    /* Копирование скидки */
    discountCopyAction: function (btn, fl_id) {
        if (!this.hasLoading(btn)) {
            btn.find("i").removeClass("ss orders-all").addClass("loading");
            $.post("?plugin=flexdiscount&action=handler", { data: 'copyDiscount', id: fl_id }, function (response) {
                if (response.status == 'ok' && response.data) {
                    btn.closest(".discount-row").after(response.data);
                }
                btn.find("i").removeClass("loading").addClass("ss orders-all");
            });
        }
    },
    /* Удаление скидки */
    discountDeleteAction: function (btn, fl_id) {
        if (!this.hasLoading(btn)) {
            if (fl_id) {
                if (!confirm($__("Do you really want to delete discount rule?"))) {
                    return false;
                }
                btn.find("i").removeClass("delete").addClass("loading");
                $.post("?plugin=flexdiscount&action=handler", { data: 'deleteDiscount', ids: fl_id }, function () {
                    if ($(".discount-list").length) {
                        btn.closest(".discount-row").remove();
                    } else {
                        $.flexdiscount.setHash('#/');
                    }
                });
            } else {
                if ($(".f-checker:checked").length < 1) {
                    alert($__("Select at least 1 discount rule"));
                    return false;
                }
                if (!confirm($__("Do you really want to delete discount rules?"))) {
                    return false;
                }
                btn.find("i").removeClass("delete").addClass("loading");
                var checked = $.makeArray($(".f-checker:checked").map(function () {
                    return $(this).val();
                }));
                $.post("?plugin=flexdiscount&action=handler", { data: 'deleteDiscount', ids: checked }, function () {
                    $(".f-checker:checked").closest(".discount-row").remove();
                    btn.find("i").removeClass("loading").addClass("delete");
                });
            }
        }
    },
    /* Изменение статуса скидки */
    discountStatusAction: function (btn, id) {
        var i = btn.find("i");
        var oldClass = i.attr('class');
        if (!i.hasClass("loading")) {
            var status = i.hasClass("lightbulb") ? 0 : 1;
            i.toggleClass().addClass("icon16 loading");
            $.post("?plugin=flexdiscount&action=handler", {
                data: 'discountStatus',
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
    /* Изменение порядка сортировки во фронтенде */
    discountFrontendSortAction: function (btn) {
        btn.prop("disabled", true);
        $.post("?plugin=flexdiscount&action=handler", {
            data: 'discountFrontendSort',
            sort: btn.val(),
            id: btn.closest(".discount-row").data("id")
        }, function () {
            btn.prop("disabled", false);
        }, "json");
    },
    /* Страница скидки */
    discountAction: function (id) {
        if (id) {
            this.load("?plugin=flexdiscount&module=discount&id=" + id);
        }
    },
    /* Создание нового правила скидок */
    discountNewAction: function () {
        this.load("?plugin=flexdiscount&module=discount&id=new");
    },
    /* Создание нового правила запрета */
    discountDenyAction: function () {
        this.load("?plugin=flexdiscount&module=discount&id=new&deny=1");
    },
    /* Создание, редактирование купона */
    discountEditCouponAction: function (btn, couponId) {
        var type = btn.data("type");
        var dialogParams = {
            loading_header: $__("Wait, please..."),
            class: 'condition-dialog',
            url: '?plugin=flexdiscount&module=dialog&action=couponEdit&f_id=' + btn.closest("#flexdiscount-save-form").find("input[name='id']").val() + (couponId ? '&id=' + couponId : '') + (type == 'generator' ? '&type=generator' : ''),
            buttons: '<div class="align-center"><input type="submit" value="' + $__("Save") + '" class="button green"><span class="errormsg" style="margin-left: 10px; display: inline-block"></span></div>',
            onClose: function () {
                $("#coupon-dialog-edit").remove();
            },
            onSubmit: function (form) {
                $.flexdiscount.couponSaveHandler(form, function (response) {
                    if (response.status == 'ok' && response.data)
                        if (!$(".s-coupon-block .coupon-item").length) {
                            var html = "<div class='margin-block bottom'>" +
                                $__('Coupons') + ": <span class='coupon-item s-coupon'>" + response.data.coupons + "</span>; " +
                                $__('coupon generators:') + " <span class='coupon-item s-generator'>" + response.data.generators + "</span>" +
                                "</div>";
                            $(".s-coupon-block > ul").before(html);
                        } else {
                            $(".s-coupon-block .s-coupon").text(response.data.coupons);
                            $(".s-coupon-block .s-generator").text(response.data.generators);
                        }
                    $.flexdiscount.discountCouponListAction();
                    form.trigger('close');
                });
                return false;
            }
        };
        $("body").append("<div id='coupon-dialog-edit'></div>");
        var dialog = $("#coupon-dialog-edit");
        dialog.waDialog(dialogParams);
        return false;
    },
    /* Открытие списка всех купонов */
    discountCouponListAction: function (btn, discountId) {
        var dialogParams = {
            loading_header: $__("Wait, please..."),
            class: 'condition-dialog',
            width: '80%',
            url: '?plugin=flexdiscount&module=dialog&action=couponList&id=' + (discountId ? discountId : $.flexdiscount.discount.id),
            onClose: function () {
                $("#coupon-dialog-list").remove();
            },
            onLoad: function () {
                $(this).attr('data-fl-id', discountId);
            }
        };
        $("body").append("<div id='coupon-dialog-list'></div>");
        var dialog = $("#coupon-dialog-list");
        dialog.waDialog(dialogParams);
        return false;
    },
    /* Подгрузка скидок в группах */
    discountShowMoreAction: function (btn, groupId) {
        if (!this.hasLoading(btn)) {
            this.appendLoading(btn);
            $.post("?plugin=flexdiscount&action=handler&data=discountShowMore", {
                group_id: groupId,
                per_page: $("#fl-groups-per-page").val()
            }, function (response) {
                $.flexdiscount.removeLoading(btn);
                if (response.status == 'ok') {
                    var container = groupId ? btn.closest(".discount-group") : btn.closest(".discount-list-body");
                    container.append(response.data);
                }
                btn.closest("div").remove();
            }, "json");
        }
    },
    /* Сохранение купона */
    couponSaveHandler: function (form, callback) {
        var btn = form.find("input[type='submit']");
        if (!btn.next(".loading").length) {
            btn.after("<i class='icon16 loading'></i>");
            var i = btn.next("i");
            form.find('.errormsg').html('');
            /* Проверка обязательных полей */
            var error = 0;
            form.find(".s-required:visible").each(function () {
                var that = $(this).closest('.field').find("input, select");
                if ($.trim(that.val()) == '') {
                    that.addClass('error');
                    error = 1;
                }
            });
            if (error) {
                form.find('.errormsg').html($__("Fill in required fields"));
                i.remove();
                return false;
            }

            $.ajax({
                url: "?plugin=flexdiscount&module=coupons&action=save",
                dataType: "json",
                type: "post",
                data: form.find("input:visible, select:visible, textarea:visible, input[type='hidden']").serializeArray(),
                success: function (response) {
                    if (response.status == 'ok') {
                        i.removeClass("loading").addClass("yes");
                        var name = form.find('.s-coupon-code').length ? form.find('.s-coupon-code').val() : form.find('.s-coupon-name').val();
                        form.find("h3 span").text(name);
                        if (callback) {
                            try {
                                callback.call(this, response);
                            } catch (e) {
                                if (console) {
                                    console.log('Callback error: ' + e.message, e);
                                }
                            }
                        }
                    }
                    if (response.status == 'fail' && response.errors) {
                        i.removeClass("loading").addClass("cross");
                        if (typeof response.errors.messages !== 'undefined') {
                            for (var error in response.errors.messages) {
                                form.find('.errormsg').append(response.errors.messages[error] + '<br>');
                            }
                        }
                        if (typeof response.errors.fields !== 'undefined') {
                            for (var field in response.errors.fields) {
                                form.find("." + response.errors.fields[field]).addClass("error");
                            }
                        }
                    }
                    setTimeout(function () {
                        i.remove();
                    }, 3000);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    if (console) {
                        console.log(jqXHR, textStatus, errorThrown);
                    }
                    form.find('.errormsg').html($__("Error"));
                    i.remove();
                }
            });
        }
        return false;
    },
    /* Добавление купонов из общего списка к правилу скидок */
    discountCouponsAddAction: function () {
        this.manageCouponsHandler('addCoupons', function (block) {
            block.find(".f-checkbox:checked").each(function () {
                $(this).closest("tr").addClass("bold").attr('title', $__('Coupon is used by this rule'));
                $(this).prop("checked", false);
            });
        });
    },
    /* Удаление купонов из общего списка у правила скидок */
    discountCouponsRemoveAction: function () {
        this.manageCouponsHandler('removeCoupons', function (block) {
            block.find(".f-checkbox:checked").each(function () {
                var that = $(this);
                that.prop("checked", false).closest("tr").removeClass("bold").removeAttr();
                !$(".s-coupons-list-block .f-show-all").prop('checked') && that.closest("tr").remove();
            });
        });
    },
    /* Удаление купонов безвозвратно из общего списка у правила скидок */
    discountCouponsDeleteAction: function () {
        var block = $(".s-coupons-list-block");
        if (!block.find(".f-checkbox:checked").length) {
            alert($__("Select at least one coupon"));
            return false;
        }
        var dialogParams = {
            loading_header: $__("Wait, please..."),
            'min-height': '270px',
            'height': '270px',
            class: 'delete-dialog',
            content: ("<h1 style='position: relative'>" + $__("Coupons delete") + " <a href=\"javascript:void(0)\" onclick='$(this).closest(\".dialog\").trigger(\"close\")' title=\"" + $__('close') + "\" class=\"close\">" + $__('close') + "</a></h1>" +
                "<p>" + $__("Do you really want to delete selected coupons?") + "</p>" +
                "<div class=\"margin-block\">" +
                "<input type='submit' name='delete' class='button red catch-click' value='" + $__('Delete coupon from all discounts. Coupon will be removed totally') + "'>" +
                "</div>"),
            onClose: function () {
                $("#coupon-dialog-delete-all").remove();
            },
            onSubmit: function (d) {
                d.trigger('close');
                $.flexdiscount.manageCouponsHandler('deleteCoupons', function (block) {
                    var countBlock = block.find(".s-count");
                    countBlock.text(parseInt(countBlock.attr("data-count")) - parseInt(block.find(".f-checkbox:checked").length));
                    block.find(".f-checkbox:checked").each(function () {
                        var tr = $(this).closest("tr");
                        tr.next(".table-inside").remove();
                        tr.remove();
                    });
                });
                return false;
            }
        };
        $("body").append("<div id='coupon-dialog-delete-all'></div>");
        var dialog = $("#coupon-dialog-delete-all");
        dialog.waDialog(dialogParams);
    },
    /* Открытие диалога для импорта */
    discountImportCouponsAction: function () {
        $.flexdiscount.longaction.onLoad = function () {
            $.flexdiscount.initImportField($("#longaction-dialog-clone").find("input[type='file']"));
            $("#longaction-dialog-clone").find(".dialog-buttons").html('<div class="align-center"><a class="button red cancel" href="javascript:void(0)">' + $__('cancel') + '</a></div>');
        };
        $.flexdiscount.longaction.openDialog(
            $__('Import'),
            $__('Import coupons'),
            $("#import-dialog").html()
        );
    },
    /* Инициализация поля импорта */
    initImportField: function (field) {
        var progressField = field.siblings(".progressfield-block");
        var dialog = progressField.closest('.dialog');
        field.fileupload({
            autoUpload: true,
            dataType: 'json',
            url: "?plugin=flexdiscount&module=coupons&action=csvUpload",
            progressall: function (e, data) {
                var progress = parseInt(data.loaded / data.total * 100, 10);
                progressField.find(".progressbar-inner").css('width', progress + '%');
                progressField.find(".upload-condition").text($__("Uploading file..."));
            },
            submit: function () {
                progressField.removeClass("hidden").html("<div class=\"progressbar green small align-center\" style=\"width: 70%; margin: 0 auto\"><div class=\"progressbar-outer\"><div class=\"progressbar-inner\" style=\"width: 0;\"></div></div></div><br class='clear-both'><span class=\"upload-condition\"></span><i class=\"icon16 loading\" style=\"margin: 7px 0 0 5px;\"></i><br clear=\"left\" />");
            },
            done: function (e, data) {
                var response = data._response.result;
                if (response && response.status == 'ok') {
                    if (response.status == 'ok' && response.data) {
                        var delimiter = dialog.find("select[name='delimiter']").val();
                        var encoding = dialog.find("select[name='encoding']").val();
                        progressField.closest('.upload-block').remove();
                        /* Импорт */
                        $.flexdiscount.longaction.setParams(
                            "?plugin=flexdiscount&module=coupons&action=imexport",
                            {
                                type: 'import',
                                file: response.data,
                                delimiter: delimiter,
                                encoding: encoding,
                                fl_id: $.flexdiscount.discount.id
                            }
                        );
                        $.flexdiscount.longaction.start(dialog);
                    }
                } else {
                    progressField.html("<span class=\"red\">" + response.errors + "</span>");
                }
                setTimeout(function () {
                    progressField.addClass("hidden");
                }, 5000);
            },
            fail: function () {
                progressField.html("<span class=\"red\">" + $__("Upload failed") + "</span>");
            }
        });
    },
    /* Настройка колонок у правил скидок */
    customizeColumnsAction: function (btn) {
        var type = btn.data('type');
        var dialogParams = {
            'min-height': '200px',
            height: type == 'discount' ? '200px' : '250px',
            width: '500px',
            title: $__("Customize columns"),
            class: 'nopadded',
            disableButtonsOnSubmit: true,
            buttons: "<input type='submit' class='button green' value='" + $__("Save") + "'> " + $__("or") + " <a class='cancel' href='#'>" + $__('close') + "</a>",
            onSubmit: function (d) {
                d.find("input[type=submit]").after("<i class='loading icon16'></i>");
                $.post("?plugin=flexdiscount&action=handler&data=customize" + (type == 'coupon' ? 'Coupon' : '') + "Columns", d.find("form").serializeArray(), function (response) {
                    if (response.status == 'ok' && response.data) {
                        if (type == 'discount') {
                            $(".discount-list").removeClass("col-w-0 col-w-1 col-w-3 col-w-4 col-w-6 col-w-7").addClass("col-w-" + response.data.weight);
                            for (var i in response.data.hide) {
                                $(".discount-list .discount-" + response.data.hide[i]).hide();
                            }
                            for (var i in response.data.show) {
                                $(".discount-list .discount-" + response.data.show[i]).css('display', 'inline-block');
                            }
                        } else {
                            var params = $.flexdiscount.getParams($(".s-coupons-list-block .zebra > thead > tr").attr('data-href'));
                            $.flexdiscount.getCoupons(params);
                        }
                    }
                    d.trigger('close');
                }, "json");
                return false;
            },
            onClose: function () {
                $(this).find("input[type=submit]").next("i").remove();
            }
        };
        var dialogName = (type == 'coupon' ? 'coupon-' : '') + "columns-dialog";
        var dialog = $("#" + dialogName);
        if (!dialog.length) {
            dialogParams['content'] = $("#flexdiscount-" + dialogName).html();
            $("body").append("<div id='" + dialogName + "'></div>");
            dialog = $("#" + dialogName);
        }
        dialog.waDialog(dialogParams);
    },
    manageCouponsHandler: function (data, callback) {
        var block = $(".s-coupons-list-block");
        if (!block.find(".f-checkbox:checked").length) {
            alert($__("Select at least one coupon"));
            return false;
        }
        if (!block.hasClass("is-loading")) {
            block.addClass("is-loading");
            var ids = $.makeArray(block.find(".f-checkbox:checked").map(function () {
                return $(this).val();
            }));
            $.post("?plugin=flexdiscount&action=handler&data=" + data, {
                ids: ids,
                fl_id: ($("#coupon-dialog-list").data('fl-id') !== '' ? $("#coupon-dialog-list").data('fl-id') : $.flexdiscount.discount.id)
            }, function (response) {
                if (response.status == 'ok') {
                    if (callback) {
                        try {
                            callback.call(this, block);
                        } catch (e) {
                            if (console) {
                                console.log('Callback error: ' + e.message, e);
                            }
                        }
                    }
                }
                block.removeClass("is-loading");
            }, "json");
        }
    },
    /* Отобразить все доступные условия */
    showConditionAction: function (btn) {
        var html = $("<div class='condition temp'></div>");
        var conditionsList = $("#condition-template").clone();
        conditionsList.removeAttr("id").show();
        if ($(".f-bundle-checkbox").prop('checked')) {
            conditionsList.find("option[value='num_prod'], option[value='num_all_cat'], option[value='num_all_cat_all'], option[value='num_all_set'], option[value='num_all_type'], option[value='num_feat']").addClass("highlight-condition");
        }

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
            .on('chosen:hiding_dropdown', function () {
                btn.show();
                condBlock.find(".condition.temp").remove();
                var value = select.val();
                if (value == 'add') {
                    $.flexdiscount_conditions.addGroup(condBlock.children(".conditions"));
                } else {
                    $.flexdiscount_conditions.addField(condBlock.children(".conditions"), value);
                }
                $("> .conditions > .condition", condBlock).length <= 1 && condBlock.children(".conditions").removeClass("tree");
            });
    },
    /* Удаление условия */
    deleteConditionAction: function (btn) {
        var condBlock = btn.closest(".condition-block");
        $("> .condition", btn.closest(".conditions")).length == 2 && btn.closest(".conditions").removeClass("tree");
        btn.closest(".condition").remove();
        $.flexdiscount_conditions.updateConditionOperatorBlock(condBlock);
    },
    /* Удаление блока условия */
    deleteConditionBlockAction: function (btn) {
        if (!confirm($__("Do you really want to delete condition group?"))) {
            return false;
        }
        var parentBlock = btn.closest(".condition").closest(".conditions");
        btn.closest(".condition").remove();
        $("> .condition", parentBlock).length == 1 && parentBlock.removeClass("tree");
        $.flexdiscount_conditions.updateConditionOperatorBlock(parentBlock.closest(".condition-block"));
    },
    /* Уточнение деталей скидки */
    editDiscountDetailsAction: function (btn) {
        var detailsSelect = btn.closest(".target-row").find(".details-select");
        var detailsBlock = btn.closest(".target-row").find(".details-block");
        if (detailsSelect.data('chosen') === undefined) {
            detailsSelect.show().chosen({ disable_search_threshold: 10, no_results_text: $__("No result text") })
                .on('chosen:hiding_dropdown', function () {
                    $(this).next('.chosen-container').hide();
                    detailsBlock.removeClass("hidden").show().next().show();
                }).on("change", function () {
                var that = $(this);
                var value = that.val();
                var detailsPriceSelect = " " + $__('among') + " <select class='details-price-select ignore-chosen inherit'>" +
                    "<option value='price'>" + $__('product prices') + "</option>" +
                    "<option value='compare_price'>" + $__('compare prices') + "</option>" +
                    "</select>";
                if (!detailsBlock.hasClass('s-detail-' + value)) {
                    var html = $__(" to") + " ";
                    if (value == 'every') {
                        html += $__("every") + " <input name='details' type='text' onkeypress='$.flexdiscount.isValid(event, /[0-9]/);' class='width50px'> " + $__('th similar product');
                    } else if (value == 'cheapest') {
                        html += "<input name='details' type='text' onkeypress='$.flexdiscount.isValid(event, /[0-9]/);' class='width50px'> " + $__('cheapest products');
                        html += detailsPriceSelect;
                    } else if (value == 'ncheapest' || value == 'ncheapest2') {
                        html += $__("every") + " <input name='details' type='text' onkeypress='$.flexdiscount.isValid(event, /[0-9]/);' class='width50px'> " + $__('th cheapest products') + ' ' + (value == 'ncheapest' ? $__('(in favor of the seller)') : $__('(in favor of the buyer)'));
                        html += detailsPriceSelect;
                    } else if (value == 'expensive') {
                        html += "<input name='details' type='text' onkeypress='$.flexdiscount.isValid(event, /[0-9]/);' class='width50px'> " + $__('the most expensive products');
                        html += detailsPriceSelect;
                    } else if (value == 'nexpensive' || value == 'nexpensive2') {
                        html += $__("every") + " <input name='details' type='text' onkeypress='$.flexdiscount.isValid(event, /[0-9]/);' class='width50px'> " + $__('th the most expensive products') + ' ' + (value == 'nexpensive' ? $__('(in favor of the seller)') : $__('(in favor of the buyer)'));
                        html += detailsPriceSelect;
                    } else if (value == 'subsiquent') {
                        html += $__("all subsequent similar products following by") + " <input name='details' type='text' onkeypress='$.flexdiscount.isValid(event, /[0-9]/);' class='width50px'> " + $__('th product');
                    } else if (value == 'nsubsiquent') {
                        html += "<input name='details' type='text' onkeypress='$.flexdiscount.isValid(event, /[0-9]/);' class='width50px'> " + $__('th product and all subsequent products following by n-product (descending prices)');
                        html += detailsPriceSelect;
                    } else if (value == 'multiple') {
                        html += " <input name='details' type='text' onkeypress='$.flexdiscount.isValid(event, /[0-9]/);' class='width50px'> " + $__('products from') + " <input name='details2' type='text' onkeypress='$.flexdiscount.isValid(event, /[0-9]/);' class='width50px'> " + $__("similar");
                    }
                    html += " <div class='condition-text'><a href='javascript:void(0)' onclick='$(this).closest(\".target-row\").find(\".details-block\").addClass(\"hidden\").hide()'><i class='icon16 no'></i></a></div> ";
                    detailsBlock.toggleClass().addClass("details-block inline-block s-detail-" + value).html(html);
                }
            });
        }

        btn.hide();
        detailsSelect.next(".chosen-container").show();
        detailsSelect.trigger('chosen:close').trigger('chosen:open');
        detailsBlock.addClass("hidden").hide();
    },
    /* Создание всплывающего окна для выбора товара или пользователя */
    openConditionDialogAction: function (btn) {
        var id = btn.data("id");
        var source = btn.data("source");
        var dialogParams = {
            loading_header: $__("Wait, please..."),
            class: 'condition-dialog',
            width: '90%',
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
    /* Сбросить выбор значения, сделанного во всплывающем окне */
    resetDialogSelectionAction: function (btn) {
        btn.hide().prev().text(btn.data('reset')).siblings('.s-value-field').val('');
        btn.parent().siblings('.s-sum-sku').next('.chosen-container').show();
    },
    /* Сбросить значения выпадающего списка */
    resetSelectionAction: function (btn) {
        var select = btn.parent().prevUntil('select').prev().data('chosen');
        if (typeof select !== 'undefined') {
            select.results_reset();
        }
    },
    initTargetChosen: function (select) {
        select.chosen({ disable_search_threshold: 10, no_results_text: $__("No result text") })
            .on('chosen:hiding_dropdown', function () {
                var that = $(this);
                var value = that.val();
                var targetBlock = that.closest(".target-row").find(".target-block");
                if ($.flexdiscount_conditions.isTypeExists(value)) {
                    that.next('.chosen-container').hide();
                    targetBlock.removeClass("hidden").show().next().show();
                } else {
                    that.next('.chosen-container').show();
                    targetBlock.addClass("hidden").hide().next().hide();
                }
                if (value == 'shipping') {
                    targetBlock.closest(".target-row").find(".s-details").hide();
                } else {
                    targetBlock.closest(".target-row").find(".s-details").show();
                    $(".f-shipping-discount").show();
                }
                $.flexdiscount.shippingTargetExists();
            })
            .on("change", function () {
                var that = $(this);
                var value = that.val();
                var targetBlock = that.closest(".target-row").find(".target-block");
                if (!targetBlock.hasClass('s-target-' + value)) {
                    targetBlock.toggleClass().addClass("target-block s-target-" + value);
                    if ($.flexdiscount_conditions.isTypeExists(value)) {
                        targetBlock.html('');
                        $.flexdiscount_conditions.addField(targetBlock, value);
                        targetBlock.next().show();
                    } else {
                        targetBlock.addClass("hidden").hide().next().hide();
                    }
                }
                if (value == 'shipping') {
                    targetBlock.closest(".target-row").find(".s-details").hide();
                } else {
                    targetBlock.closest(".target-row").find(".s-details").show();
                }
                $.flexdiscount.shippingTargetExists();
            });
    },
    /* Изменение целей условия */
    editTargetAction: function (btn) {
        var targetChosen = btn.closest(".target-row").find(".target-chosen");
        btn.closest(".target-row").find(".target-block").hide();
        targetChosen.next(".chosen-container").show();
        targetChosen.trigger('chosen:close').trigger("chosen:open");
    },
    addTargetAction: function () {
        var target = $(".target-row").first().clone();
        target.find(".target-block").html('').removeAttr('style').toggleClass().addClass('target-block hidden').next().hide();
        target.find(".target-chosen").val('all').show().next(".chosen-container").remove();
        target.find(".details-block").hide();
        target.append("<div class='condition-text'><a href='#/delete/target/' class='js-action' title='" + $__('delete') + "'><i class='icon16 delete'></i></a></div>");
        $(".targets .s-add-target").before(target);
        this.initTargetChosen(target.find(".target-chosen"));
        $.flexdiscount.shippingTargetExists();
    },
    /* Удаление цели */
    deleteTargetAction: function (btn) {
        btn.closest('.target-row').remove();
        $.flexdiscount.shippingTargetExists();
    },
    /* Сохранение правила скидок */
    saveDiscount: function (form) {
        var btn = form.find("input[type='submit']");
        if (!btn.next(".loading").length) {
            $("#fixed-save-panel .errormsg").remove();
            btn.after("<i class='icon16 loading'></i>");
            $("#condition-input").val($.flexdiscount_conditions.getJsonConditions());
            $("#target-input").val($.flexdiscount_conditions.getJsonTarget());
            $.ajax({
                url: "?plugin=flexdiscount&module=discount&action=save",
                dataType: "json",
                type: "post",
                data: form.find(".f-save-me").serializeArray(),
                success: function (response) {
                    if (response.status == 'ok' && response.data) {
                        btn.next(".loading").removeClass("loading").addClass("yes");
                        $.flexdiscount.setHash("#/discount/" + response.data);
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
                    if (console) {
                        console.log(jqXHR, textStatus, errorThrown);
                    }
                }
            });
        }
    },
    /* Активируем режим создания комплектов */
    bundleAction: function (btn) {
        var elements = "input[value='num_prod'], input[value='num_all_cat'], input[value='num_all_cat_all'], input[value='num_all_set'], input[value='num_all_type'], input[value='num_feat']";
        var isChecked = btn.prop("checked");
        $(elements).each(function () {
            var condition = $(this).closest('.condition'),
                select = condition.find("select[name='op2']").length ? condition.find("select[name='op2']") : condition.find("select[name='op']");
            select.val('eq_num').prop("disabled", isChecked).trigger("chosen:updated");
        });
        if (isChecked) {
            $(".s-conditions").addClass("bundle-mode");
            $(".f-discounteachbundle").removeClass("hidden");
        } else {
            $(".s-conditions").removeClass("bundle-mode");
            $(".f-discounteachbundle").addClass("hidden");
        }
        $(".s-conditions > .value > .condition-block > .cond-op select").val('and').prop("disabled", isChecked).trigger('change').trigger("chosen:updated");
    },
    /* Изменение видимости правила на витрине */
    changeStorefrontVisibility: function (btn) {
        if (btn.prop('checked')) {
            btn.closest('.margin-block').next().slideUp();
        } else {
            btn.closest('.margin-block').next().slideDown();
        }
    },
    /* Экспорт / импорт */
    longaction: {
        pull: [],
        stopExport: false,
        url: null,
        onLoad: null,
        postParams: {},
        setParams: function (url, postParams) {
            this.url = url;
            this.postParams = postParams;
        },
        resetVars: function () {
            this.pull = [];
            this.stopExport = false;
        },
        openDialog: function (buttonName, title, content, height) {
            this.resetVars();
            var contentBlock = content +
                '<div class="s-longaction-progressbar" style="display:none; margin-top: 20px;">' +
                '<div class="progressbar blue float-left" style="display: none; width: 90%;">' +
                '<div class="progressbar-outer">' +
                '<div class="progressbar-inner" style="width: 0;"></div>' +
                '</div>' +
                '</div>' +
                '<img style="float:left; margin-top:8px;" src="' + $.flexdiscount.wa_url + 'wa-content/img/loading32.gif" />' +
                '<div class="clear"></div>' +
                '<span class="progressbar-description">0.000%</span> ' +
                '<em class="hint">' + $__("Please don't close your browser window until process is over") + '</em>' +
                '<br clear="left" />' +
                '<em class="margin-block top errormsg" style="display: none;">' +
                '<span></span>' +
                '</em>' +
                '</div>' +
                '<div class="s-longaction-report"></div>';
            var dialogParams = {
                height: height || '200px',
                width: '550px',
                title: '<h1 class="align-center">' + title + '</h1>',
                content: contentBlock,
                buttons: '<div class="align-center"><input type="submit" value="' + buttonName + '" class="button green"> ' + $__('or') + ' <a class="button red cancel" href="javascript:void(0)">' + $__('cancel') + '</a></div>',
                disableButtonsOnSubmit: false,
                onSubmit: function (d) {
                    $.flexdiscount.longaction.start(d);
                    return false;
                },
                onClose: function () {
                    var d = $(this);
                    var timer_id = $.flexdiscount.longaction.pull.pop();
                    while (timer_id) {
                        clearTimeout(timer_id);
                        timer_id = $.flexdiscount.longaction.pull.pop();
                    }
                    d.find('.s-longaction-progressbar').hide();
                    d.find(".s-longaction-report").hide();
                    $.flexdiscount.longaction.stopExport = true;
                    $("#longaction-dialog-wrap").remove();
                }
            };
            if (this.onLoad && typeof this.onLoad === 'function') {
                dialogParams['onLoad'] = this.onLoad;
            }
            $("body").append("<div id='longaction-dialog-wrap'></div>");
            var dialog = $("#longaction-dialog").clone();
            dialog.attr("id", "longaction-dialog-clone").appendTo("#longaction-dialog-wrap").waDialog(dialogParams);

        },
        start: function (d) {
            d.find('.dialog-buttons').hide();
            var form = d.find(".s-longaction-progressbar");
            var report = d.find(".s-longaction-report");
            var showError = function (error) {
                form.find('.errormsg').show().find("span").text(error);
                d.find('.dialog-buttons').html('<div class="align-center"><a href="javascript:void(0)" class="button yellow close" onclick=\'$("#longaction-dialog-clone").trigger("close")\'>' + $__('Close') + '</a></div>').show();
                d.find('.s-longaction-progressbar > img').remove();
                d.find('.s-longaction-progressbar > .progressbar').remove();
            };
            report.html('').hide();
            d.find("form :submit").prop("disabled", true);
            d.find('#s-scanning-progressbar').hide();
            d.find("p").hide();
            form.show();
            form.find('.progressbar .progressbar-inner').css('width', '0%');
            form.find('.progressbar-description').text('0.000%');
            form.find('.progressbar').show();
            var processId;
            var cleanup = function () {
                $.post($.flexdiscount.longaction.url, { processId: processId, cleanup: 1 }, function (r) {
                    form.hide();
                    if (r.report) {
                        report.append(r.report).show();
                    }
                }, 'json');
            };
            var step = function (delay) {
                delay = delay || 2000;
                if ($.flexdiscount.longaction.stopExport) {
                    return false;
                }
                var timer_id = setTimeout(function () {
                    $.post($.flexdiscount.longaction.url, { processId: processId }, function (r) {
                        if (!r) {
                            step(3000);
                        } else {
                            if (r && r.error_message) {
                                showError(r.error_message);
                                return false;
                            }
                            if (r && r.ready) {
                                form.find('.progressbar .progressbar-inner').css({
                                    width: '100%'
                                });
                                form.find('.progressbar-description').text('100%');
                                cleanup();
                            } else if (r && r.error) {
                                showError(r.error);
                                return false;
                            } else {
                                if (r && r.progress) {
                                    var progress = parseFloat(r.progress.replace(/,/, '.'));
                                    form.find('.progressbar .progressbar-inner').animate({
                                        'width': progress + '%'
                                    });
                                    form.find('.progressbar-description').text(r.progress);
                                }
                                if (r && r.warning) {
                                    form.find('.progressbar-description').append('<i class="icon16 exclamation"></i><p>' + r.warning + '</p>');
                                }
                                step();
                            }
                        }
                    }, 'json').error(function () {
                        step(3000);
                    });
                }, delay);
                $.flexdiscount.longaction.pull.push(timer_id);
            };
            $.post($.flexdiscount.longaction.url, $.flexdiscount.longaction.postParams, function (r) {
                if (r && r.processId) {
                    if (r && r.error_message) {
                        showError(r.error_message);
                        return false;
                    }
                    processId = r.processId;
                    step(1000); // invoke Runner
                    step(); // invoke Messenger
                } else if (r && r.error) {
                    showError(r.error);
                    return false;
                } else {
                    showError($__('Server error'));
                    return false;
                }
            }, "json").error(function () {
                showError($__('Server error'));
                return false;
            });
        }
    },
    /* Генерация кода купона */
    generateCode: function () {
        var alphabet = "";
        var result = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890";
        for (var i = 0; i < 10; i++) {
            alphabet += result.charAt(Math.floor(Math.random() * result.length));
        }
        return alphabet;
    }
    ,
    isValid: function (evt, pattern) {
        var theEvent = evt || window.event;
        var key = theEvent.keyCode || theEvent.which;
        key = String.fromCharCode(key);
        if (!pattern.test(key) && evt.charCode !== 0) {
            theEvent.returnValue = false;
            if (theEvent.preventDefault)
                theEvent.preventDefault();
        }
    },
    /* Скрытие/открытие содержимого параграфов на странице скидок  */
    changeParagraphVisibilityAction: function (btn) {
        var input = btn.next('input');
        var value = parseInt(input.val()) ? 0 : 1;
        input.val(value);
        btn.html(value ? "&minus;" : "&plus;").closest('h3').next('.field-group').slideToggle(function () {
            if (value && $("#flexdiscount-save-form").length && !$("#flexdiscount-save-form .s-conditions-group").hasClass("inited")) {
                $.flexdiscount_conditions.reinitChosen();
                $("#flexdiscount-save-form .s-conditions-group").addClass("inited");
            }
        });
    },
    /* Проверка, существует ли цель по Доставке. Выполняем дополнительные действия по результату */
    shippingTargetExists: function () {
        var shippingExists = 0,
            onlyShipping = 1;
        $(".target-row").each(function () {
            if ($(this).find(".target-chosen").val() == 'shipping') {
                shippingExists = 1;
            } else {
                onlyShipping = 0;
            }
        });
        /* Если есть правила по доставке, показываем спец поле для ввода скидки на доставку */
        if (shippingExists) {
            $(".f-shipping-discount").show();
        } else {
            $(".f-shipping-discount").hide();
        }
        /* Если в целях только доставка, то скрываем поле для скидки на товар */
        if (onlyShipping) {
            $(".f-product-discount").hide();
        } else {
            $(".f-product-discount").show();
            $('.f-discount-type, .f-affiliate-type').change();
        }
    }
};