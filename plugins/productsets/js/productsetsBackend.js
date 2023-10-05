var ProductsetsBackendPlugin = (function ($) {

    ProductsetsBackendPlugin = function (options) {
        var that = this;

        that.storage = new $.store();
        that.$wrap = options.$wrap;
        that.is_deleting = 0;

        that.initClass();
        that.bindEvents();
    };

    ProductsetsBackendPlugin.prototype.initClass = function () {
        var that = this;

        /* Сортировка */
        that.$wrap.find('.productsets-list tbody').sortable({
            distance: 5,
            opacity: 0.75,
            items: 'tr',
            handle: '.sort',
            cursor: 'move',
            tolerance: 'pointer',
            update: function () {
                /* Обновляем порядок сортировки */
                that.updateRuleSort();
            }
        });

        /* Импорт настроек внешнего вида из других комплектов */
        $(document).off('click', '.js-import-appearance').on('click', '.js-import-appearance', function () {
            const elem = $(this);
            const isListPage = that.$wrap.find('.productsets-list').length;
            const id = isListPage ? 0 : that.$wrap.find('.f-tab-general input[name="id"]').val();
            let ids = [];
            if (isListPage) {
                $.each(that.$wrap.find(".f-selector:checked"), function (i, v) {
                    ids.push($(v).val());
                });
                if (!ids.length) {
                    return false;
                }
            }
            new igaponovDialog({
                url: '?plugin=productsets&module=dialog&action=importAppearance' + (id ? '&id=' + id : ''),
                class: 'import-appearance-dialog',
                onSubmit: function ($form, dialog) {
                    var $submitBtn = $form.find(":submit");
                    if (!$submitBtn.next(".loading").length) {
                        $submitBtn.after("<i class='icon16 loading'></i>");
                        $.post("?plugin=productsets&action=importAppearance", { id: $form.find(':selected').val(), ids: ids }, function (response) {
                            if (response.status == 'ok') {
                                if (!isListPage) {
                                    if (Object.entries(response.data).length) {
                                        $.each(response.data, function (tab, data) {
                                            if (data !== '') {
                                                var toolbar = $('.f-appearance-' + tab + ' .f-appearance-toolbar');
                                                $.each(data, function (type, appearance) {
                                                    var block = toolbar.find('.f-' + type + '-appearance.dynamicAppearance-block');
                                                    if (block.length) {
                                                        block.dynamicAppearance('setAppearance', appearance);
                                                    }
                                                });
                                            }
                                        });
                                    } else {
                                        for (var tab of ['bundle', 'userbundle']) {
                                            $('.f-appearance-' + tab + ' .f-clear-appearance').click();
                                        }
                                    }
                                } else {
                                    elem.find('i').removeClass('design').addClass('yes');
                                    setTimeout(function () {
                                        elem.find('i').removeClass('yes').addClass('design');
                                    }, 3000);
                                }
                                dialog.close();
                            }
                        }).always(function () {
                            $submitBtn.next('i').remove();
                        });
                    }
                }
            });
        });
    };

    ProductsetsBackendPlugin.prototype.updateRuleSort = function () {
        var that = this;

        var ids = $.makeArray(that.$wrap.find('.productsets-list tbody tr').map(function () {
            return $(this).data('id');
        }));
        $.post('?plugin=productsets&action=sortSets', { ids: ids });
    };

    ProductsetsBackendPlugin.prototype.bindEvents = function () {
        var self = this;

        /* Изменение статусов комплектов */
        $(document).off("click", ".js-productsets-status").on("click", ".js-productsets-status", function () {
            var that = $(this);
            var i = that.find("i");
            if (!i.hasClass('loading')) {
                i.removeClass("productsets-pl").addClass("loading");
                $.post("?plugin=productsets&action=updateStatus", {
                    id: that.data('id'),
                    status: (i.hasClass('lightbulb') ? 0 : 1)
                }, function (response) {
                    if (response.status == 'ok') {
                        if (response.data == 1) {
                            i.removeClass('loading lightbulb-off').addClass('lightbulb productsets-pl');
                        } else {
                            i.removeClass('loading lightbulb').addClass('lightbulb-off productsets-pl');
                        }
                    } else {
                        alert($__('Something wrong'));
                        that.find("i").removeClass("loading").addClass("productsets-pl");
                    }
                }, "json");
            }
        });

        /* Копрование комплектов */
        $(document).off("click", ".js-productsets-copy").on("click", ".js-productsets-copy", function () {
            var that = $(this);
            var i = that.find("i");
            if (!i.hasClass('loading')) {
                i.removeClass("stack").addClass("loading");
                $.post("?plugin=productsets&action=duplicate", {
                    id: that.data('id')
                }, function (response) {
                    if (response.status == 'ok' && response.data) {
                        that.closest('tr').after(response.data);
                    } else {
                        alert($__('Something wrong'));
                    }
                }, "json").always(function () {
                    that.find("i").removeClass("loading").addClass("stack");
                });
            }
        });

        /* Удаление комплектов */
        $(document).off("click", ".js-productsets-delete").on("click", ".js-productsets-delete", function () {
            var that = $(this);
            var ids = [];
            if (that.hasClass("single-deletion")) {
                ids.push(that.data('id'));
            } else if (self.$wrap.find(".f-selector").length) {
                $.each(self.$wrap.find(".f-selector:checked"), function (i, v) {
                    ids.push($(v).val());
                });
            } else if (typeof that.data('id') !== 'undefined') {
                ids.push(that.data('id'));
            }

            if (!self.is_deleting && ids.length) {
                self.is_deleting = 1;

                new igaponovDialog({
                    title: $__('Removing the set'),
                    content: $__('Do you really want to delete 1 set?', 'Do you really want to delete {n} sets?', ids.length, { n: ids.length }),
                    closeBtn: true,
                    buttons: '<input type="submit" class="button green t-button" value="' + $__('Yes') + '"> <button style="margin-left: 5px;" class="button red t-button js-close-dialog">' + $__('No') + '</button>',
                    onSubmit: function (form, d) {
                        d.$submitBtn.after("<i class='icon16 loading'></i>");
                        if (that.hasClass("single-deletion")) {
                            that.find("i").removeClass("delete").addClass("loading");
                        } else {
                            that.after("<i class='icon16 loading temp-loading'>");
                        }

                        $.post("?plugin=productsets&action=delete", {
                            ids: ids
                        }, function (response) {
                            if (response.status == 'ok') {
                                if (that.hasClass("single-deletion")) {
                                    that.closest("tr").fadeOut(function () {
                                        $(this).remove();
                                    });
                                } else if (!$(".f-selector").length) {
                                    $.wa.setHash('#/productsets/');
                                } else {
                                    $.products.dispatch();
                                }
                            } else {
                                alert($__('Something wrong'));
                                if (that.hasClass("single-deletion")) {
                                    that.find("i").removeClass("loading").addClass("delete");
                                } else {
                                    that.next(".temp-loading").remove();
                                }
                            }
                        }, "json").always(function () {
                            self.is_deleting = 0;
                            d.close();
                        });
                    },
                    onClose: function () {
                        self.is_deleting = 0;
                        return false;
                    },
                    onBgClick: function (e, d) {
                        d.close();
                    }
                });
            }
        });

        /* Выделение строки таблицы */
        $(document).off("change", ".f-selector").on("change", ".f-selector", function () {
            var checkbox = $(this);
            if (checkbox.is(":checked")) {
                checkbox.closest("tr").addClass("selected");
            } else {
                checkbox.closest("tr").removeClass("selected");
            }
            /* Изменение значений индикатора для удаления */
            $(".productsets-indicator").text($(".f-selector:checked").length);
        });

        /* Выделение всех комплектов */
        $(document).off("click", ".f-selector-all").on("click", ".f-selector-all", function () {
            var that = $(this);
            if (!that.hasClass("checked")) {
                $(".f-selector").prop('checked', true).change();
                that.addClass("checked");
            } else {
                $(".f-selector").prop('checked', false).change();
                that.removeClass("checked");
            }
        });

        /* Постраничная навигация */
        $(document).off("click", ".js-load-more").on("click", ".js-load-more", function () {
            var btn = $(this);
            if (!btn.next('i').length) {
                btn.after("<i class='icon16 loading'></i>");
                var page = parseInt($(this).data('page')) + 1;
                $.get('?plugin=productsets&page=' + page, function (response) {
                    var $sets = $(response).find('.productsets-list tbody tr');
                    self.$wrap.find('.f-load-more').replaceWith($sets);
                });
            }
            return false;
        });
    };
    return ProductsetsBackendPlugin;

})(jQuery);

$.productsets = {
    init: function (options) {
        this.url = options.url || '';
        this.locale = options.locale || '';
        new ProductsetsBackendPlugin({
            $wrap: $('.productsets-plugin-wrap')
        });
    },
    load: function (url) {
        $("#s-content").html("<div class='block double-padded'>" + $__('Loading') + " <i class='icon16 loading'></i></div>");
        $.products.load(url, function () {
            $.productsets.initView();
            let $title = $('h1 span');
            document.title = $__('Product sets') + ($title.length ? ' - ' + $title.html() : '');
        });
    },
    initView: function () {
        var sidebar = $('#s-sidebar');
        sidebar.find('li.selected').removeClass('selected');
        sidebar.find('#s-productsets').addClass('selected');

        var hash = this.getHash();
        var navigation = $("#productsets-navigation");
        navigation.find('li.selected').removeClass('selected');
        navigation.find("a").each(function () {
            if (hash == $(this).attr('href')) {
                $(this).parent().addClass("selected");
            }
        });
    },
    getHash: function (hash) {
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
    isValid: function (evt, pattern) {
        var theEvent = evt || window.event;
        var key = theEvent.keyCode || theEvent.which;
        key = String.fromCharCode(key);
        if (!pattern.test(key) && evt.charCode !== 0) {
            theEvent.returnValue = false;
            if (theEvent.preventDefault) {
                theEvent.preventDefault();
            }
        }
    }
};

/* Страницы в горизонтальном меню */
$.products.productsetsAction = function () {
    $.productsets.load('?plugin=productsets');
};

$.products.productsetsEditAction = function (id) {
    $.productsets.load('?plugin=productsets&action=edit' + (id ? '&id=' + id : ''));
};
