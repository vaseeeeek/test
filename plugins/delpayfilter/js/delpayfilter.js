/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
$.delpayfilter = {
    storage: null,
    hash: '',
    init: function (params) {
        this.hash = params.hash || '';
        $.delpayfilter.storage = new $.store();
        $.wa.errorHandler = function (xhr) {
            if ((xhr.status === 403) || (xhr.status === 404)) {
                var text = $(xhr.responseText);
                console.log(text);
                if (text.find('.dialog-content').length) {
                    text = $('<div class="block double-padded"></div>').append(text.find('.dialog-content *'));
                } else {
                    text = $('<div class="block double-padded"></div>').append(text.find(':not(style)'));
                }
                $("#delpayfilter-interactive-block").empty().append(text);
                return false;
            }
            return true;
        };

        this.initRouting();

        /* Вызываем js функции */
        $(document).on('click', 'a.js-action', function () {
            $.delpayfilter.activateJsAction($(this));
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
                $.delpayfilter.dispatch();
            });
        }

        var lastPage = $.delpayfilter.storage.get('delpayfilter-last-page-'+this.hash);
        if (lastPage) {
            $.delpayfilter.setHash("#/delpayfilter/filter/" + lastPage[0]);
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
            $.delpayfilter.load($(this).attr("href"));
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
    },
    initFilterPage: function () {
        /* Switcher */
        $('.switcher').iButton({labelOn: "", labelOff: "", className: 'mini'}).change(function () {
            var onLabelSelector = '#' + this.id + '-on-label',
                offLabelSelector = '#' + this.id + '-off-label';
            var additinalField = $(this).closest('.ibutton-checkbox').next('.onopen');
            if (!this.checked) {
                if (additinalField.length) {
                    additinalField.hide();
                }
                $(onLabelSelector).addClass('unselected');
                $(offLabelSelector).removeClass('unselected');
            } else {
                if (additinalField.length) {
                    additinalField.css('display', 'block');
                }
                $(onLabelSelector).removeClass('unselected');
                $(offLabelSelector).addClass('unselected');
            }
        }).each(function () {
            var additinalField = $(this).closest('.ibutton-checkbox').next('.onopen');
            if (!this.checked) {
                if (additinalField.length) {
                    additinalField.hide();
                }
            } else {
                if (additinalField.length) {
                    additinalField.css('display', 'block');
                }
            }
        });

        /* Сохранение правила */
        $(document).on('submit', "#delpayfilter-save-form", function () {
            $.delpayfilter.saveFilter($(this));
            return false;
        });
    },
    dispatch: function (hash) {
        $.delpayfilter.storage.del('delpayfilter-last-page-'+this.hash);
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
        $("#delpayfilter-interactive-block").html($_("Loading...") + '<i class="icon16 loading"></i>');
        $.get(url, function (result) {
            if (self.random != r) {
                return;
            }
            var tmp = $("<div></div>");
            tmp.append(result);
            if (tmp.find("#delpayfilter-interactive-block").length) {
                $("#delpayfilter-interactive-block").html(tmp.find("#delpayfilter-interactive-block"));
            } else {
                $("#delpayfilter-interactive-block").html(result);
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
    /* Копирование фильтра */
    filterCopyAction: function (btn, fl_id) {
        if (!this.hasLoading(btn)) {
            btn.find("i").removeClass("ss orders-all").addClass("loading");
            $.post("?plugin=delpayfilter&action=handler", {data: 'copyFilter', id: fl_id}, function (response) {
                if (response.status == 'ok' && response.data) {
                    btn.closest(".filter-row").after(response.data);
                }
                btn.find("i").removeClass("loading").addClass("ss orders-all");
            });
        }
    },
    /* Удаление фильтров */
    filterDeleteAction: function (btn, fl_id) {
        if (!this.hasLoading(btn)) {
            if (fl_id) {
                var ids = fl_id;
            } else {
                if ($(".f-checker:checked").length < 1) {
                    alert($_("Select at least 1 filter rule"));
                    return false;
                }
                var ids = $.makeArray($(".f-checker:checked").map(function () {
                    return $(this).val();
                }));
            }

            var dialogParams = {
                loading_header: $_("Wait, please..."),
                class: 'delete-dialog',
                height: '200px',
                'min-height': '200px',
                buttons: '<div class="align-center s-buttons-block"><input type="submit" value="' + $_("Delete") + '" class="button red"><span class="errormsg" style="margin-left: 10px; display: inline-block"></span></div>',
                content: '<h1>' + $_('Delete filter') + '<a href="javascript:void(0)" onclick="$(this).closest(\'.dialog\').trigger(\'close\')" title="' + $_('close') + '" class="close dialog-close">' + $_('close') + '</a></h1><p class="align-center" style="margin-top: 60px">' + $_("Do you really want to delete filter rule?") + '</p>',
                onClose: function () {
                    $("#filter-dialog-delete").remove();
                },
                onSubmit: function (form) {
                    $.delpayfilter.appendLoading(form.find(".s-buttons-block"));
                    $.post("?plugin=delpayfilter&action=handler", {data: 'deleteFilter', ids: ids}, function () {
                        if (btn.parent("h2").length) {
                            $.delpayfilter.setHash("#/delpayfilter/");
                        } else if (fl_id) {
                            btn.closest(".filter-row").remove();
                        } else {
                            $(".f-checker:checked").closest(".filter-row").remove();
                        }
                        $.delpayfilter.removeLoading(form.find(".s-buttons-block"));
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
            $.post("?plugin=delpayfilter&action=handler", {data: 'filterStatus', status: status, id: id}, function (response) {
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
    delpayfilterAction: function () {
        this.load("?plugin=delpayfilter&module=settings&first=0");
        $(".Zebra_DatePicker").remove();
    },
    /* Страница фильтра */
    delpayfilterFilterAction: function (id) {
        if (parseInt(id)) {
            $.delpayfilter.storage.set('delpayfilter-last-page-'+this.hash, id);
            this.load("?plugin=delpayfilter&module=filter&id=" + id);
        }
    },
    /* Создание нового правила */
    delpayfilterNewAction: function () {
        this.load("?plugin=delpayfilter&module=filter&id=new");
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
        var select = condBlock.find(".condition-template").chosen({disable_search_threshold: 10, no_results_text: $_("No result text")}).trigger('chosen:open')
            .on('chosen:hiding_dropdown', function (evt, params) {
                btn.show();
                condBlock.find(".condition.temp").remove();
                var value = select.val();
                if (value == 'add') {
                    $.delpayfilter_conditions.addGroup(condBlock.children(".conditions"));
                } else {
                    $.delpayfilter_conditions.addField(condBlock.children(".conditions"), value);
                }
                $("> .conditions > .condition", condBlock).length <= 1 && condBlock.children(".conditions").removeClass("tree");
            });
    },
    /* Удаление условия */
    deleteConditionAction: function (btn) {
        var condBlock = btn.closest(".condition-block");
        $("> .condition", btn.closest(".conditions")).length == 2 && btn.closest(".conditions").removeClass("tree");
        btn.closest(".condition").remove();
        $.delpayfilter_conditions.updateConditionOperatorBlock(condBlock);
    },
    /* Удаление блока условия */
    deleteConditionBlockAction: function (btn) {
        if (!confirm($_("Do you really want to delete condition group?"))) {
            return false;
        }
        var parentBlock = btn.closest(".condition").closest(".conditions");
        btn.closest(".condition").remove();
        $("> .condition", parentBlock).length == 1 && parentBlock.removeClass("tree");
        $.delpayfilter_conditions.updateConditionOperatorBlock(parentBlock.closest(".condition-block"));
    },
    /* Создание всплывающего окна для выбора товара или пользователя */
    openConditionDialogAction: function (btn) {
        var id = btn.data("id");
        var source = btn.data("source");
        var dialogParams = {
            loading_header: $_("Wait, please..."),
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
    initTargetChosen: function (select) {
        select.chosen({disable_search_threshold: 10, no_results_text: $_("No result text")}).trigger('chosen:open')
            .on('chosen:hiding_dropdown', function () {
                var that = $(this);
                var value = that.val();
                var targetBlock = that.closest(".target-row").find(".target-block");
                if ($.delpayfilter_conditions.isTypeExists(value)) {
                    that.next('.chosen-container').hide();
                    targetBlock.removeClass("hidden").show().next().show();
                } else {
                    that.next('.chosen-container').show();
                    targetBlock.addClass("hidden").hide().next().hide();
                }
            })
            .on("change", function () {
                var that = $(this);
                var value = that.val();
                var targetBlock = that.closest(".target-row").find(".target-block");
                if (!targetBlock.hasClass('s-target-' + value)) {
                    targetBlock.toggleClass().addClass("target-block s-target-" + value);
                    if ($.delpayfilter_conditions.isTypeExists(value)) {
                        targetBlock.html('');
                        $.delpayfilter_conditions.addField(targetBlock, value, 'target');
                        targetBlock.next().show();
                    } else {
                        targetBlock.addClass("hidden").hide().next().hide();
                    }
                }
            });
    },
    /* Изменение целей условия */
    editTargetAction: function (btn) {
        var targetChosen = btn.closest(".target-row").find(".target-chosen");
        btn.closest(".target-row").find(".target-block").hide();
        targetChosen.next(".chosen-container").show();
        targetChosen.trigger('chosen:close').trigger("chosen:open");
    },
    addTargetAction: function (btn) {
        var target = $("#target-template").clone();
        target.removeAttr("id").find(".target-block").html('').next().hide();
        target.find(".target-chosen").show().next(".chosen-container").remove();
        target.show().append("<div class='condition-text'><a href='#/delete/target/' class='js-action' title='" + $_('delete') + "'><i class='icon16 delete'></i></a></div>");
        $(".targets .s-add-target").before(target);

        this.initTargetChosen(target.find(".target-chosen"));
    },
    /* Удаление цели */
    deleteTargetAction: function (btn) {
        btn.closest('.target-row').remove();
    },
    /* Сохранение правила */
    saveFilter: function (form) {
        var btn = form.find("input[type='submit']");
        if (!btn.next(".loading").length) {
            $("#fixed-save-panel .errormsg").remove();
            btn.after("<i class='icon16 loading'></i>");

            $("#condition-input").val($.delpayfilter_conditions.getJsonConditions());
            $("#target-input").val($.delpayfilter_conditions.getJsonTarget());

            $.ajax({
                url: "?plugin=delpayfilter&module=filter&action=save",
                dataType: "json",
                type: "post",
                data: form.find(".f-save-me").serializeArray(),
                success: function (response) {
                    if (response.status == 'ok' && response.data) {
                        btn.next(".loading").removeClass("loading").addClass("yes");
                        $.delpayfilter.setHash("#/delpayfilter/filter/" + response.data);
                    } else {
                        btn.next(".loading").removeClass("loading").addClass("no");
                        $("#fixed-save-panel .block").append("<div class='margin-block errormsg'>" + $_("Something wrong") + "</div>");
                    }
                    setTimeout(function () {
                        btn.next("i").remove();
                    }, 3000);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    btn.next(".loading").removeClass("loading").addClass("no");
                    $("#fixed-save-panel .block").append("<div class='margin-block errormsg'>" + $_("Something wrong") + "</div>");
                    setTimeout(function () {
                        btn.next("i").remove();
                    }, 3000);
                    console.log(jqXHR, textStatus, errorThrown);
                }
            });
        }
    },
    /* Сбросить значения выпадающего списка */
    resetSelectionAction: function (btn) {
        var select = btn.parent().prevUntil('select').prev().data('chosen');
        if (typeof select !== 'undefined') {
            select.results_reset();
        }
    }
};