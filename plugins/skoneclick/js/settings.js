if (!window.shopSkPluginSettings) {

    var shopSkPluginSettings = (function ($) {

        'use strict';

        var shopSkPluginSettings = function (params) {

            this.init(params);

        };

        shopSkPluginSettings.prototype = {

            _config: {

                container: ".js-sk-main",
                successMsg: "<span class='success'><i style='vertical-align:middle' class='icon16 yes'></i>Сохранено</span>",
                errorMsg: "<span class='error'><i class='icon16 no'></i><span>Допущены ошибки</span></span>"

            },

            init: function (params) {

                var that = this;

                that.params = $.extend({}, that._config, params);

                that.initElements();

                that.initTabs();

                that.onFormAddField();

                that.onFormDeleteField();

                that.onFormSave();

                that.toggleTitle();

            },

            initElements: function () {

                var that = this,
                    elements = {};

                elements.container = $(that.params.container);
                elements.tabs = elements.container.find(".js-sk-tabs");
                elements.tabsHeaders = elements.container.find(".js-sk-tabs-header");
                elements.tabsItems = elements.container.find(".js-sk-tabs-item");

                elements.form = elements.container.find(".js-sk-form");

                elements.generateForm = elements.container.find(".js-sk-generate-form");
                elements.generateBody = elements.container.find(".js-sk-generate-form-body");
                elements.generateFormAddSelect = elements.generateForm.find(".js-sk-generate-form-add-select");
                elements.generateFormAddLink = elements.generateForm.find(".js-sk-generate-form-add-link");
                elements.generateFormDelete = elements.generateForm.find(".js-sk-generate-form-delete");

                that.elements = elements;

            },

            initTabs: function () {
                var that = this,
                    elements = that.elements;


                if (typeof elements.tabs === "undefined" || !elements.tabs.length) {
                    return false;
                }

                elements.tabsHeaders.on("click", function () {
                    var element = $(this),
                        tabName = element.data("tab"),
                        content = elements.tabsItems.filter("[data-tab='" + tabName + "']");

                    if (element.hasClass("selected")) {
                        return false;
                    }

                    elements.tabsHeaders.removeClass("selected");
                    elements.tabsItems.removeClass("selected");

                    element.addClass("selected");
                    content.addClass("selected");

                })
            },

            onFormAddField: function () {
                var that = this,
                    elements = that.elements;

                elements.generateFormAddLink.on("click", function () {
                    var control_id = elements.generateFormAddSelect.val();

                    if (!control_id) {
                        alert("Выберите поле для добавления");
                        return false;
                    }

                    that.params.max_id++;

                    $.post("?plugin=skoneclick&action=fieldAdd", {

                        control_id: control_id

                    }, function (resp) {

                        if (resp.status == "fail") {

                            alert(resp.errors[0]);

                        } else if (resp.status == "ok") {

                            elements.generateBody.append(resp.data.content);
                            elements.generateFormAddSelect.val("");
                            elements.generateFormAddSelect.find("option[value='" + control_id + "']").hide();

                        } else {
                            alert("неизвестная ошибка");
                        }

                    }, "json")

                });

            },

            onFormDeleteField: function () {
                var that = this,
                    elements = that.elements;

                elements.generateForm.on("click", ".js-sk-generate-form-delete", function () {
                    var element = $(this),
                        control_id = element.data("id");

                    if (!confirm("Вы уверены?")) {
                        return false;
                    }
                    element.closest(".js-sk-generate-form-tr").detach();
                    $.post("?plugin=skoneclick&action=fieldDelete", {control_id: control_id}, function (resp) {
                        elements.generateFormAddSelect.find("option[value='" + control_id + "']").show();
                    }, "json")

                });
            },

            onFormSave: function () {
                var that = this,
                    elements = that.elements;

                elements.form.each(function(){
                    var form = $(this),
                    timeout = null;

                    form.on("submit", function (e) {
                        e.preventDefault();
                        var form = $(this),
                            formStatus = form.find(".js-sk-form-status"),
                            tabActive = elements.tabsHeaders.filter(".selected").data("tab");

                        $.post("?plugin=skoneclick&action=formSave", form.serialize(), function (resp) {
                            if (resp.status == "fail") {
                                var errorHtml = $(that.params.errorMsg);
                                if(typeof(resp.errors.text) !== "undefined"){
                                    errorHtml.find("span").text(resp.errors.text);
                                }
                                formStatus.html(errorHtml).show();
                                if (typeof(resp.errors.fields) !== "undefined" && tabActive == "form") {
                                    $.each(resp.errors.fields, function (id, field) {
                                        elements.form.find(".js-sk-generate-form-tr[data-row-id='" + id + "']").addClass("error");
                                    });
                                }
                            } else if (resp.status == "ok") {
                                if (tabActive == "form") {
                                    elements.form.find(".js-sk-generate-form-tr.error").removeClass("error");
                                }
                                formStatus.html(that.params.successMsg).show();
                                if (timeout) {
                                    clearTimeout(timeout);
                                }
                                timeout = setTimeout(function () {
                                    formStatus.hide();
                                }, 3000);
                            }
                        }, "json");

                    });

                });
            },

            toggleTitle: function () {
                var that = this,
                    elements = that.elements;

                elements.generateForm.on("click", ".js-sk-generate-form-name", function () {
                    var element = $(this);

                    element.find("a").hide();
                    element.find("input").attr("type", "text");
                });
            }

        };

        return shopSkPluginSettings;

    })(jQuery);

}
