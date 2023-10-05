/**
 *  dynamicAppearance
 *
 *  dynamicAppearance is compact plugin for jQuery to configure appearance of any element.
 *  Plugin creates object of CSS rules, DOM CSS styles and allows to see changes live on page.
 *
 *  @author     Igor Gaponov <gapon2401@gmail.com>
 *  @version    1.2 (December 27, 2019)
 *  @copyright  (c) 2018 Igor Gaponov
 *  @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE
 *  @package    dynamicAppearance
 *
 *  By default you can change the following CSS attributes:
 *  - background,
 *  - border,
 *  - color,
 *  - box-shadow,
 *  - border-radius,
 *  - font,
 *  - padding,
 *  - margin,
 *  - text-align.
 *
 *************
 *  Example of using dynamicAppearance plugin:
 *************
 *
 *  $(toolbar).dynamicAppearance(options);
 *
 *  where toolbar - element, where appearance toolbar should be created. This container will be filled automatically. Leave it empty.
 *        options: {
 *            (string|jQuery object) liveBlock - (optional) block, appearance of which should be dynamically changed,
 *
 *            (array) plugins - list of plugins, which should be shown in toolbar,
 *                Example: plugins: ['background,color,padding,margin']
 *                Example with params: ['color:svg', 'border-radius:type:all']
 *                It mean's, that color-plugin value of 'svg' option will be set to true
 *                Also border-radius-plugin value of the 'type' option will be set to all
 *
 *            (boolean) showHover - show toolbar for hover event or not,
 *
 *            (array) hoverPlugins - list of plugins, which should be shown in hover toolbar,
 *
 *            (object) pluginDependency - set dependency elements, which should use styles of live block
 *                Example: pluginDependency: {
                            'border-radius': {
                                '.some-form-head': ['border-top-left-radius', 'border-top-right-radius'],
                                '.some-form-footer': ['border-bottom-left-radius', 'border-bottom-right-radius']
                            },
                            'color': {
                                '.some-head:svg', '.some-body'
                            }
                        }
                  This example set:
                      - border-radius value of liveBlock to .some-form-head and .some-form-footer
                      - color value of liveBlock to .some-head and .some-body

                  NOTICE! Now available only border-radius, color dependency
 *
 *             We can define options to plugin in params. Example:
 *                 (string) 'border-radius:type': 'all',
 *                 (string) 'color:svg': true
 *
 *            If we want to set styles to pseudo elements, we can use:
 *            (bool) pseudoBefore
 *            (bool) pseudoAfter
 *        }
 *
 *************
 *  Methods:
 *************
 *
 *  - destroy - kill toolbar totaly,
 *  - reset - clear all CSS styles and toolbar settings,
 *  - getAppearance - get object with CSS styles,
 *  - setAppearance - set CSS styles. Has parameters.
 *        Example: $(toolbar).dynamicAppearance('setAppearance', params);
 *                 where params - object with CSS styles. Identical to object returned in method getAppearance.
 *  - getSettings - get object with settings,
 *  - getCss - get CSS styles for DOM embedding
 *  - getOnlyCSS - get only CSS styles without classes
 *
 *************
 *  Example of calling methods:
 *************
 *
 *  $(toolbar).dynamicAppearance('destroy')
 *  $(toolbar).dynamicAppearance('setAppearance', { color: #ffccff })
 *
 */
;(function (factory) {

    'use strict';

    if (typeof define === 'function' && define.amd) define(['jquery'], factory);

    else if (typeof exports === 'object') factory(require('jquery'));

    else factory(jQuery);

}(function ($) {
    var DynamicAppearance = (function ($) {

        DynamicAppearance = function (block, options) {
            var that = this;

            /* Список всех плагинов */
            that._plugins = {
                'background': {
                    name: $__('Background')
                },
                'border': {
                    name: $__('Border'),
                    colorPicker: true
                },
                'color': {
                    name: $__('Color'),
                    colorPicker: true,
                    svg: false
                },
                'box-shadow': {
                    name: $__('Box shadow'),
                    colorPicker: true
                },
                'border-radius': {
                    name: $__('Border radius'),
                    type: 'single'
                },
                'font': {
                    name: $__('Font')
                },
                'padding': {
                    name: $__('Padding')
                },
                'margin': {
                    name: $__('Margin')
                },
                'text-align': {
                    name: $__('Alignment')
                },
                'width': {
                    name: $__('Width')
                }
            };

            /* Объект со стилями внешнего вида */
            that.appearance = {};
            that.hoverAppearance = {};


            /* Блок, в котором будет созданы элементы управления стилями */
            that.$block = $(block);

            /* ID блока */
            that.id = that.$block.data('dynamicAppearance-id');

            /* Блок, на котором будут демонстрироваться стили */
            that.$liveBlock = options.liveBlock || '';
            if (that.$liveBlock !== '') {
                that.$liveBlock = $(that.$liveBlock);
            }

            /* Список плагинов, которые пользователь хочет отобразить в панели инструментов */
            that.plugins = options.plugins || [];

            /* Отображать отдельно панель инструментов для события наведения */
            that.showHover = options.showHover || false;
            /* Плагины, которые следует отображать для события наведения */
            that.hoverPlugins = options.hoverPlugins || that.plugins;

            /* Активный пункт навигации */
            that.currentNavigation = 'normal';

            /* Настройки пользователя */
            that.customOptions = options || {};

            /* Список плагинов, которые будут отображены в панели инструментов */
            that.toolbarPlugins = {};
            that.hoverToolbarPlugins = {};

            that.pluginDependency = options.pluginDependency || {};

            /* HTML код панели инструментов */
            that.html = "";

            /* INIT */
            that.initClass();
            that.bindEvents();

            /* Активный блок */
            that.$activeBlock = that.$block.find('.f-dynamicappearance-' + that.currentNavigation);
        };

        DynamicAppearance.prototype.initClass = function () {
            var that = this;

            that.initToolbar();
            that.display();
            that.initColorPickers();
        };

        /* Создание панели инструментов */
        DynamicAppearance.prototype.initToolbar = function () {
            var that = this;

            /* Получаем список плагинов, которые будут отображены в панели инструментов */
            if (that.plugins.length) {
                for (var i in that.plugins) {
                    that.toolbarPlugins = $.extend(that.toolbarPlugins, that.getPluginSettings(that.plugins[i]));
                }
            } else {
                for (var i in that._plugins) {
                    that.toolbarPlugins = $.extend(that.toolbarPlugins, that.getPluginSettings(i));
                }
            }

            $.each(that.toolbarPlugins, function (plugin) {
                that.appearance[plugin] = {};
            });

            /* Создаем панель инструментов для события наведения */
            if (that.showHover) {
                /* Список плагинов для события наведения */
                if (that.showHover && that.hoverPlugins.length) {
                    for (var i in that.hoverPlugins) {
                        that.hoverToolbarPlugins = $.extend(that.hoverToolbarPlugins, that.getPluginSettings(that.hoverPlugins[i]));
                    }
                } else if (that.showHover) {
                    for (var i in that._plugins) {
                        that.hoverToolbarPlugins = $.extend(that.hoverToolbarPlugins, that.getPluginSettings(i));
                    }
                }
                $.each(that.hoverToolbarPlugins, function (plugin) {
                    that.hoverAppearance[plugin] = {};
                });
                if (that.showHover) {
                    /* Создаем дополнительную колонку для навигации по состояниям: "обычное", "при наведении"  */
                    that.buildNavigateColumn();
                    /* Генерируем панель инструментов */
                    that.buildToolbar(true);
                }
            }

            /* Генерируем панель инструментов */
            that.buildToolbar();
        };

        /* Получение настроек для плагина */
        DynamicAppearance.prototype.getPluginSettings = function (pluginData) {
            var that = this;

            var pluginParams = pluginData.split(':');
            var plugin = pluginParams[0];
            if (that._plugins[plugin] !== undefined) {
                var pluginSettings = {};
                pluginSettings[plugin] = $.extend({}, that._plugins[plugin]);
                if (pluginParams.length > 1) {
                    pluginSettings[plugin][pluginParams[1]] = typeof pluginParams[2] !== 'undefined' ? (pluginParams[2] == 'true' ? true : (pluginParams[2] == 'false' ? 'false' : pluginParams[2])) : true;
                }
                /* Учитываем доп параметры */
                if (Object.keys(that._plugins[plugin]).length > 1) {
                    $.each(that._plugins[plugin], function (key) {
                        if (key !== 'name' && that.customOptions[plugin + ':' + key] !== undefined) {
                            pluginSettings[plugin][key] = that.customOptions[plugin + ':' + key];
                        }
                    });
                }
                return pluginSettings;
            }
            return {};
        };

        /* Генерирование панели инструментов */
        DynamicAppearance.prototype.buildToolbar = function (isHoverToolbar) {
            var that = this;
            var plugins = isHoverToolbar ? that.hoverToolbarPlugins : that.toolbarPlugins;
            var html = "<div class=\"f-dynamicappearance-toolbar-wrap s-column f-dynamicappearance-" + (isHoverToolbar ? 'hover' : 'normal') + (!that.showHover ? ' s-show' : '') + "\">" +
                "           <div class=\"s-appearance-toolbar f-dynamicappearance-toolbar s-column\">" +
                "               <div class=\"s-head\">" + $__('Properties') + "</div>" +
                "               <select size=\"" + Object.keys(plugins).length + "\" class=\"inherit\">";
            for (var id in plugins) {
                html += "           <option value=\"" + id + "\"" + (!!plugins[id]['colorPicker'] ? ' data-show-colorpicker="1"' : "") + ">" + plugins[id]['name'] + "</option>";
            }
            html += "           </select>" +
                "           </div>";


            that.html += html;

            $.each(plugins, function (plugin) {
                var methodName = 'build' + plugin.charAt(0).toUpperCase() + plugin.slice(1) + 'PluginToolbar';
                if (typeof that[methodName] === 'function') {
                    that.html += "<div class=\"s-column f-" + plugin + "-plugin-settings\">" + that[methodName]() + "</div>";
                }
            });
            that.html += "</div>"; // End of f-dynamicappearance-...
        };

        /* Генерируем колонку с возможностью выбора настроек для обычного режима и для режима наведения */
        DynamicAppearance.prototype.buildNavigateColumn = function () {
            var that = this;

            var html =
                "           <div class=\"s-appearance-toolbar f-dynamicappearance-navigate s-column\">" +
                "               <div class=\"s-head\">" + $__('Condition') + "</div>" +
                "               <select size=\"2\" class=\"inherit\">" +
                "                   <option value='normal'>" + $__('Normal') + "</option>" +
                "                   <option value='hover'>" + $__('On hover') + "</option>" +
                "               </select>" +
                "           </div>";
            that.html += html;
        };

        /* Вывод созданного HTML кода на витрину */
        DynamicAppearance.prototype.display = function () {
            var that = this;

            that.html = "<div class=\"s-appearance-columns\">" +
                that.html +
                "           <div class=\"s-column f-color-picker-column\">" +
                "               <div class=\"margin-block bottom f-transparent-block s-input-group\" style=\"display: none;\">" +
                "                   <span class='width120px'>" + $__("Transparency") + "</span>" +
                "                   <input type=\"number\" value=\"1\" name=\"transparency\" min=\"0\" max=\"1\" step=\"0.05\" class=\"f-input-appearance\" /></div>" +
                "               </div>" +
                "           </div>" +
                "       </div>";

            that.$block.append(that.html);
        };

        /* Инициализация главного блока с палитрой цветов, а также всех дополнительных */
        DynamicAppearance.prototype.initColorPickers = function () {
            var that = this;

            /* Инициализация главного блока с палитрой цветов */
            that.$block.find(".f-color-picker-column").ColorPicker({
                flat: true,
                onChange: function (hsb, hex, rgb) {
                    var activeToolbarPlugins = that.showHover && that.currentNavigation == 'hover' ? that.hoverToolbarPlugins : that.toolbarPlugins;
                    var activeAppearance = that.showHover && that.currentNavigation == 'hover' ? that.hoverAppearance : that.appearance;
                    var options = $.makeArray(that.$activeBlock.find("select:visible option:selected").map(function () {
                        return this.value;
                    }));
                    if (!!activeToolbarPlugins[options[0]]['colorPicker']) {
                        activeAppearance[options[0]]['color'] = hex;
                    } else {
                        switch (options.length) {
                            case 3:
                                activeAppearance[options[0]][options[1]][options[2]] = hex;
                                activeAppearance[options[0]][options[1]]['color_rgb'] = rgb.r + ',' + rgb.g + ',' + rgb.b;
                                break;
                            case 2:
                                activeAppearance[options[0]][options[1]] = hex;
                                break;
                            case 1:
                                activeAppearance[options[0]] = hex;
                                break;
                        }
                        if (options[0] == 'background') {
                            activeAppearance[options[0]]['color_rgb'] = rgb.r + ',' + rgb.g + ',' + rgb.b;
                        }
                    }

                    /* Обновляем демонстрационный блок */
                    that.$liveBlock.length && that.refreshLiveBlock();
                }
            });

            /* Инициализация дополнительных блоков с палитрой цветов */
            that.initColorPicker();
        };
        DynamicAppearance.prototype.bindEvents = function () {
            var that = this;

            /* Выбор активного пункта меню. Используется при активной настройке showHover */
            that.$block.on('change', '.f-dynamicappearance-navigate select', function () {
                that.currentNavigation = this.value;
                that.$activeBlock = that.$block.find('.f-dynamicappearance-' + that.currentNavigation);
                that.$activeBlock.addClass('s-show').siblings('.f-dynamicappearance-toolbar-wrap').removeClass('s-show');
                that.$activeBlock.find('.s-column > select').change();
            });

            /* Выбор плагина в главном столбце панели инструментов */
            that.$block.on('change', '.f-dynamicappearance-toolbar select', function () {
                var plugin = this.value;
                var pluginColumn = that.$activeBlock.find(".f-" + plugin + "-plugin-settings");
                var activeAppearance = that.showHover && that.currentNavigation == 'hover' ? that.hoverAppearance : that.appearance;

                /* Очистка панели инструментов */
                that.cleanToolbar();

                if (pluginColumn.length) {
                    /* Делаем настройки плагина видимыми */
                    pluginColumn.addClass('s-show');
                    !pluginColumn.hasClass('inited') && pluginColumn.addClass('inited');
                }
                that.isShowColorPicker(this) && that.showColorPicker();

                /* Установить дефолтные (или сохраненные) настройки для плагина */
                plugin && that.setPluginSettings(plugin, activeAppearance[plugin]);
            });

            /* Выбор значения в колонке панели инструментов */
            that.$block.on('change', '.f-select-column-value', function () {
                that.selectColumnValue(this);
            });

            /* Обновление значений объекта со стилями */
            that.$block.on('input', '.f-input-appearance', function () {
                that.updateAppearance(this);
            });
            that.$block.on('change', '.f-change-appearance', function () {
                that.updateAppearance(this);
            });

        };

        /* Выбор значения в колонке панели инструментов */
        DynamicAppearance.prototype.selectColumnValue = function (select, ignore) {
            var that = this;

            var mainToolbarProperty = that.getMainToolbarProperty();

            /* Скрываем все дополнительные колонки, которые были до этого открыты у плагина */
            $(select).closest(".f-" + mainToolbarProperty + "-plugin-settings").find(".s-column").removeClass('s-show');
            /* Получаем контейнер с данными в DOM для выбранной характеристики */
            var column = ($(select).data('parent') && that.$activeBlock.find(".f-" + mainToolbarProperty + "-" + $(select).data('parent') + "-column").length ? that.$activeBlock.find(".f-" + mainToolbarProperty + "-" + $(select).data('parent') + "-column") : that.$activeBlock.find(".f-" + mainToolbarProperty + "-" + select.value + "-column"));

            /* Отображаем настройки выбранной колонки */
            column.length && column.addClass('s-show');

            /* Если у выбранной колонки имеются еще вспомогательные, тогда выводим их */
            if ($(":selected", select).data('column') !== undefined) {
                that.$activeBlock.find(".f-" + mainToolbarProperty + $(":selected", select).data('column') + "-column").addClass("s-show");
            }

            /* Проверяем необходимость показа цветовой палитры */
            that.isShowColorPicker(select) ? that.showColorPicker() : that.hideColorPicker();

            /* Проверяем необходимость показа настроек прозрачности */
            that.isShowTransparency(select) ? that.showTransparency() : that.hideTransparency();

            /* Если не указана флаг, то сохраняем выбранные данные */
            if (!ignore) {
                var activeAppearance = that.showHover && that.currentNavigation == 'hover' ? that.hoverAppearance : that.appearance;
                if ($(select).data('parent') !== undefined) {
                    activeAppearance[mainToolbarProperty][$(select).data('parent')][$(select).attr("name")] = $(select).is(":input") ? $(select).val() : $(select).find(":selected").val();
                } else if ($(select).attr("name") !== undefined) {
                    activeAppearance[mainToolbarProperty][$(select).attr("name")] = $(select).find(":selected").val();
                } else {
                    activeAppearance[mainToolbarProperty] = $(select).find(":selected").val();
                }
            }

            /* Обновляем демонстрационный блок */
            that.$liveBlock.length && that.refreshLiveBlock();
        };

        /* Обновление значений объекта со стилями */
        DynamicAppearance.prototype.updateAppearance = function (elem) {
            var that = this;

            elem = $(elem);
            var mainToolbarProperty = that.getMainToolbarProperty();

            var value = elem.is(":input") ? (elem.is(':checkbox') ? (elem.prop('checked') ? 1 : 0) : elem.val()) : elem.find(":selected").val();
            value = elem.attr('data-color') !== undefined ? elem.ColorPickerFixHex(value) : value;
            var obj = {};
            var activeAppearance = that.showHover && that.currentNavigation == 'hover' ? that.hoverAppearance : that.appearance;
            if (elem.data('parent') !== undefined) {
                if (elem.attr("name").match(':') !== null) {
                    var parts = elem.attr("name").split(':');
                    if (activeAppearance[mainToolbarProperty][elem.data('parent')][parts[0]] === undefined) {
                        activeAppearance[mainToolbarProperty][elem.data('parent')][parts[0]] = {};
                    }
                    obj = activeAppearance[mainToolbarProperty][elem.data('parent')][parts[0]];
                    obj[parts[1]] = value;
                } else {
                    if (activeAppearance[mainToolbarProperty][elem.data('parent')] === undefined) {
                        activeAppearance[mainToolbarProperty][elem.data('parent')] = {};
                    }
                    obj = activeAppearance[mainToolbarProperty][elem.data('parent')];
                    obj[elem.attr("name")] = value;
                }
            } else if (elem.attr("name") !== undefined) {
                if (elem.attr("name").match(':') !== null) {
                    var parts = elem.attr("name").split(':');
                    if (activeAppearance[mainToolbarProperty][parts[0]] === undefined) {
                        activeAppearance[mainToolbarProperty][parts[0]] = {};
                    }
                    obj = activeAppearance[mainToolbarProperty][parts[0]];
                    obj[parts[1]] = value;
                } else {
                    obj = activeAppearance[mainToolbarProperty];
                    obj[elem.attr("name")] = value;
                }
            } else {
                activeAppearance[mainToolbarProperty] = value;
            }
            if (elem.attr('data-color') !== undefined) {
                var rgbObj = that.hexToRGB(value);
                obj[elem.attr('name') + '_color_rgb'] = rgbObj.r + ',' + rgbObj.g + ',' + rgbObj.b;
            }

            /* Обновляем демонстрационный блок */
            that.$liveBlock.length && that.refreshLiveBlock();
        };


        /* Установить сохраненные настройки для плагинов */
        DynamicAppearance.prototype.setPluginSettings = function (plugin, settings) {
            var that = this;

            var methodName = 'set' + plugin.charAt(0).toUpperCase() + plugin.slice(1) + 'PluginSettings';
            if (typeof that[methodName] === 'function') {
                that[methodName](settings);
            } else {
                that.$activeBlock.find(".f-" + plugin + "-plugin-settings select:not('.inherit')").val(typeof settings === 'string' ? settings : settings.type).change();
            }

            if (settings.color !== undefined && that.isShowColorPicker($(".f-" + plugin + "-plugin-settings select"))) {
                that.showColorPicker();
                that.$activeBlock.find(".f-color-picker-column").ColorPickerSetColor(settings.color);
            }
        };

        DynamicAppearance.prototype.refreshLiveBlock = function () {
            var that = this;

            that.generateCss();
        };

        /* Генерация CSS стилей  */
        DynamicAppearance.prototype.generateCss = function (withoutClasses) {
            var that = this;

            var blockClass = that.id,
                stylesBlock = '.dynamicAppearance-styles-' + blockClass,
                liveBlockClass = 'dynamicAppearance-liveblock-' + blockClass,
                css = "", hoverCss = "", dependency = "";

            /* Создаем блок со стилями */
            if (that.$liveBlock.length && !$(stylesBlock).length) {
                that.$liveBlock.attr('id', liveBlockClass).before("<div style='display:none' class='dynamicAppearance-styles-" + blockClass + "'></div>");
            }

            /* Генерируем стили */
            if (that.$liveBlock.length && !withoutClasses) {
                css += '<style>';
                css += '#' + liveBlockClass;
                /* Добавляем стили для псевдоэлементов :before и :after */
                if (that.customOptions.pseudoBefore !== undefined && that.customOptions.pseudoAfter !== undefined) {
                    css += ':before,' + '#' + liveBlockClass + ':after';
                } else if (that.customOptions.pseudoBefore !== undefined) {
                    css += ':before';
                } else if (that.customOptions.pseudoAfter !== undefined) {
                    css += ':after';
                }
                css += '{';
            }

            /* Стили для обычного состояния */
            $.each(that.toolbarPlugins, function (plugin) {
                var methodName = 'generate' + plugin.charAt(0).toUpperCase() + plugin.slice(1) + 'PluginCss';
                if (typeof that[methodName] === 'function') {
                    css += that[methodName](that.appearance[plugin]);
                }
                dependency += that.checkPluginDependency(plugin, that.appearance[plugin]);
            });

            /* Стили для состояния наведения */
            if (that.showHover) {
                $.each(that.hoverToolbarPlugins, function (plugin) {
                    var methodName = 'generate' + plugin.charAt(0).toUpperCase() + plugin.slice(1) + 'PluginCss';
                    if (typeof that[methodName] === 'function') {
                        hoverCss += that[methodName](that.hoverAppearance[plugin]);
                    }
                    dependency += that.checkPluginDependency(plugin, that.hoverAppearance[plugin], true);
                });
            }

            if (that.$liveBlock.length && !withoutClasses) {
                css += '}';
                css += dependency;
                if (that.showHover && hoverCss) {
                    css += '#' + liveBlockClass + ':hover{' + hoverCss + '}';
                }

                css += '</style>';
                $(stylesBlock).html(css);
                return css;
            } else {
                return that.showHover ? (css + hoverCss) : css;
            }
        };

        /* Зависимость дополнительных элементов от стилей основных */
        DynamicAppearance.prototype.checkPluginDependency = function (plugin, settings, isHover) {
            var that = this;

            var css = '';
            if (that.pluginDependency[plugin] !== undefined) {
                var methodName = 'generate' + plugin.charAt(0).toUpperCase() + plugin.slice(1) + 'PluginDependencyCss';
                if (typeof that[methodName] === 'function') {
                    css += that[methodName](settings, isHover);
                }
            }
            return css;
        };


        /**********
         *
         * Задний фон. background
         *
         **********/

        /* Построение настроек для Заднего фона */
        DynamicAppearance.prototype.buildBackgroundPluginToolbar = function () {
            var html = "";
            html +=
                "        <div class=\"inline-block\">" +
                "            <div class=\"s-head\">" + $__("Background") + "</div>" +
                "            <select size=\"3\" name=\"type\" class=\"f-select-column-value\">" +
                "                <option value=\"gradient\">" + $__("Gradient") + "</option>" +
                "                <option value=\"color\" data-show-colorpicker='1' data-show-transparent='1'>" + $__("Solid color") + "</option>" +
                "                <option value=\"transparent\">" + $__("Transparent") + "</option>" +
                "            </select>" +
                "        </div>" +
                "        <!-- Градиент -->" +
                "        <div class=\"f-background-gradient-column s-column\">" +
                "            <div class=\"s-head\">" + $__("Gradient") + "</div>" +
                "            <div class=\"margin-block\">" +
                "                <div class=\"s-input-group\">" +
                "                    <span>" + $__("Start") + "</span>" +
                "                    <input type=\"text\" maxlength=\"6\" name=\"start\" data-parent=\"gradient\" class='s-color-picker f-color-picker f-print-hex f-input-appearance' />" +
                "                </div>" +
                "            </div>" +
                "            <div class=\"margin-block bottom\">" +
                "                <div class=\"s-input-group\">" +
                "                    <span>" + $__("End") + "</span>" +
                "                    <input type=\"text\" maxlength=\"6\" name=\"end\" data-parent=\"gradient\" class='s-color-picker f-color-picker f-print-hex f-input-appearance' />" +
                "                </div>" +
                "            </div>" +
                "            <div class=\"margin-block bottom\">" +
                "               <div class=\"s-input-group\">" +
                "                   <span>" + $__("Direction") + "</span> " +
                "                   <select class=\"inherit f-change-appearance\" data-parent=\"gradient\" name=\"type\">" +
                "                       <option value=\"to_right\">" + $__("From left to right") + "</option>" +
                "                       <option value=\"to_left\">" + $__("From right to left") + "</option>" +
                "                       <option value=\"to_bottom\" selected>" + $__("From top to bottom") + "</option>" +
                "                       <option value=\"to_top\">" + $__("From bottom to top") + "</option>" +
                "                   </select>" +
                "               </div>" +
                "            </div>" +
                "            <div class=\"margin-block bottom\">" +
                "                <div class=\"s-input-group\">" +
                "                    <span class='width120px'>" + $__("Transparency") + "</span>" +
                "                    <input type=\"number\" name=\"transparency\" data-parent='gradient' min=\"0\" max=\"1\" step=\"0.05\" value=\"1\" class=\"f-input-appearance\" />" +
                "                </div>" +
                "            </div>" +
                "            <div class=\"margin-block bottom\">" +
                "                <div class=\"s-input-group\">" +
                "                    <span>" + $__("Orientation") + "</span>" +
                "                    <select class=\"inherit f-change-appearance\" data-parent=\"gradient\" name=\"orientation\">" +
                "                        <option value=\"linear\">" + $__("linear") + "</option>" +
                "                        <option value=\"radial\">" + $__("radial") + "</option>" +
                "                    </select>" +
                "                </div>" +
                "            </div>" +
                "        </div>";
            return html;
        };

        /* Установить сохраненные настройки */
        DynamicAppearance.prototype.setBackgroundPluginSettings = function (settings) {
            var that = this;
            var plugin = 'background';
            if (settings === undefined) {
                settings = {};
            }
            if (settings.gradient !== undefined) {
                that.$activeBlock.find(".f-" + plugin + "-gradient-column input[name='start']").val(settings.gradient.start).css('backgroundColor', '#' + settings.gradient.start).trigger('input');
                that.$activeBlock.find(".f-" + plugin + "-gradient-column input[name='end']").val(settings.gradient.end).css('backgroundColor', '#' + settings.gradient.end).trigger('input');
                that.$activeBlock.find(".f-" + plugin + "-gradient-column select[name='type']").val(that.ifundefined(settings.gradient.type, 'to_bottom'));
                that.$activeBlock.find(".f-" + plugin + "-gradient-column select[name='orientation']").val(settings.gradient.orientation);
                that.$activeBlock.find(".f-" + plugin + "-gradient-column input[name='transparency']").val(that.ifundefined(settings.gradient.transparency, 1, 'float'));
            }
            that.$block.find(".f-color-picker-column").ColorPickerSetColor(that.ifundefined(settings.color, 'ffffff'));
            that.$block.find(".f-color-picker-column .f-transparent-block input").val(that.ifundefined(settings.transparency, 1, 'float'));
            that.$activeBlock.find(".f-" + plugin + "-plugin-settings select:not('.inherit')").val(typeof settings === 'string' ? settings : settings.type).change();
        };

        /* Генерация CSS стилей */
        DynamicAppearance.prototype.generateBackgroundPluginCss = function (settings) {
            var that = this;

            var css = '';
            /* Задний фон */
            if (settings !== undefined && settings.type !== undefined) {
                var backgroundColor = settings.color_rgb !== undefined ? this.getRgba(settings.color_rgb, that.ifundefined(settings.color, 'ffffff'), that.ifundefined(settings.transparency, 1, 'float')) : '';

                var gradientTransparency = settings.gradient !== undefined ? that.ifundefined(settings.gradient.transparency, 1, 'float') : 1;
                var backgroundColorStart = (settings.type == 'gradient' && settings.gradient !== undefined ? (settings.gradient.start_color_rgb !== undefined ? this.getRgba(settings.gradient.start_color_rgb, settings.gradient.start, gradientTransparency) : '') : backgroundColor);
                css += backgroundColorStart || settings.type == 'transparent' ? ('background: ' + (settings.type == 'transparent' ? 'transparent' : backgroundColorStart) + ';') : '';
                if (settings.type == 'gradient' && settings.gradient !== undefined) {
                    var startGradient = settings.gradient.start_color_rgb !== undefined ? this.getRgba(settings.gradient.start_color_rgb, settings.gradient.start, gradientTransparency) : '';
                    var endGradient = settings.gradient.end_color_rgb !== undefined ? this.getRgba(settings.gradient.end_color_rgb, settings.gradient.end, gradientTransparency) : '';
                    if (startGradient && endGradient) {
                        if (settings.gradient.orientation == 'linear' || settings.gradient.orientation === undefined) {
                            var gradientType = (settings.gradient.type === undefined || settings.gradient.type == 'to_bottom') ? 'top' : (settings.gradient.type == 'to_left' ? 'right' : (settings.gradient.type == 'to_right' ? 'left' : (settings.gradient.type == 'to_top' ? 'bottom' : '')));
                            var gradientType2 = (settings.gradient.type === undefined || settings.gradient.type == 'to_bottom') ? 'to bottom' : (settings.gradient.type == 'to_left' ? 'to left' : (settings.gradient.type == 'to_right' ? 'to right' : (settings.gradient.type == 'to top' ? 'to top' : '')));
                            css += 'background: -moz-linear-gradient(' + gradientType + ', ' + startGradient + ' 0%, ' + endGradient + ' 100%);';
                            css += 'background: -ms-linear-gradient(' + gradientType + ', ' + startGradient + ' 0%, ' + endGradient + ' 100%);';
                            css += 'background: -o-linear-gradient(' + gradientType + ', ' + startGradient + ' 0%, ' + endGradient + ' 100%);';
                            css += 'background: -webkit-linear-gradient(' + gradientType + ', ' + startGradient + ' 0%,' + endGradient + ' 100%);';
                            css += 'background: linear-gradient(' + gradientType2 + ', ' + startGradient + ' 0%,' + endGradient + ' 100%);';
                        } else {
                            var gradientType = (settings.gradient.type === undefined || settings.gradient.type == 'to_bottom') ? 'top' : (settings.gradient.type == 'to_left' ? 'right' : (settings.gradient.type == 'to_right' ? 'left' : (settings.gradient.type == 'to_top' ? 'bottom' : '')));
                            css += 'background: -moz-radial-gradient(center ' + gradientType + ', circle farthest-side, ' + startGradient + ' 0%, ' + endGradient + ' 100%);';
                            css += 'background: -ms-radial-gradient(center ' + gradientType + ', circle farthest-side, ' + startGradient + ' 0%, ' + endGradient + ' 100%);';
                            css += 'background: -o-radial-gradient(center ' + gradientType + ', circle farthest-side, ' + startGradient + ' 0%, ' + endGradient + ' 100%);';
                            css += 'background: -webkit-radial-gradient(center ' + gradientType + ', circle farthest-side, ' + startGradient + ' 0%,' + endGradient + ' 100%);';
                            css += 'background: radial-gradient(circle farthest-side at center ' + gradientType + ', ' + startGradient + ' 0%,' + endGradient + ' 100%);';
                        }
                    }
                }
            }
            return css;
        };

        /**********
         *
         * Рамка. border
         *
         **********/

        /* Построение настроек */
        DynamicAppearance.prototype.buildBorderPluginToolbar = function () {
            var html = "";
            html +=
                "        <div class=\"inline-block\">" +
                "            <div class=\"s-head\">" + $__("Thickness") + "</div>" +
                "            <select name=\"width\" size=\"9\" class=\"f-change-appearance\">" +
                "                <option value=\"0\">0</option>" +
                "                <option value=\"1\">1px</option>" +
                "                <option value=\"2\">2px</option>" +
                "                <option value=\"3\">3px</option>" +
                "                <option value=\"5\">5px</option>" +
                "                <option value=\"8\">8px</option>" +
                "                <option value=\"10\">10px</option>" +
                "                <option value=\"12\">12px</option>" +
                "                <option value=\"15\">15px</option>" +
                "            </select>" +
                "        </div>" +
                "        <div class=\"inline-block\">" +
                "            <div class=\"s-head\">" + $__("Style") + "</div>" +
                "            <select name=\"style\" size=\"4\" class=\"f-change-appearance\">" +
                "                <option value=\"solid\">" + $__("Solid") + "</option>" +
                "                <option value=\"dotted\">" + $__("Dotted") + "</option>" +
                "                <option value=\"dashed\">" + $__("Dashed") + "</option>" +
                "                <option value=\"double\">" + $__("Double") + "</option>" +
                "            </select>" +
                "        </div>";
            return html;
        };

        /* Установить сохраненные настройки */
        DynamicAppearance.prototype.setBorderPluginSettings = function (settings) {
            var that = this;
            var plugin = 'border';

            that.$activeBlock.find(".f-" + plugin + "-plugin-settings select[name='width']").val(settings.width);
            that.$activeBlock.find(".f-" + plugin + "-plugin-settings select[name='style']").val(settings.style);
            that.$block.find(".f-color-picker-column").ColorPickerSetColor(that.ifundefined(settings.color, 'ffffff'));
        };

        /* Генерация CSS стилей */
        DynamicAppearance.prototype.generateBorderPluginCss = function (settings) {
            var that = this;

            var css = '';
            if (settings !== undefined && settings.width !== undefined && parseInt(settings.width) !== 0 && settings.style !== undefined) {
                css += 'border:' + settings.width + "px " + settings.style + ' #' + that.ifundefined(settings.color, 'ffffff') + ';';
            }
            return css;
        };

        /**********
         *
         * Тень. box-shadow
         *
         **********/

        /* Построение настроек */
        DynamicAppearance.prototype['buildBox-shadowPluginToolbar'] = function () {
            var html = "";
            html +=
                "        <div class=\"inline-block\">" +
                "            <div class=\"head\">" + $__('Values') + "</div>" +
                "            <div class=\"margin-block semi\">" +
                "                <div class=\"s-input-group medium margin-block semi bottom\">" +
                "                    <span>" + $__('X offset') + "</span>" +
                "                    <input type=\"number\" name=\"x-offset\" class=\"width100px f-input-appearance\" /> px" +
                "                </div>" +
                "                <div class=\"s-input-group medium margin-block semi bottom\">" +
                "                    <span>" + $__('Y offset') + "</span>" +
                "                    <input type=\"number\" name=\"y-offset\" class=\"width100px f-input-appearance\" /> px" +
                "                </div>" +
                "                <div class=\"s-input-group medium margin-block semi bottom\">" +
                "                    <span>" + $__('Blur') + "</span>" +
                "                    <input type=\"number\" name=\"blur\" class=\"width100px f-input-appearance\" /> px" +
                "                </div>" +
                "                <div class=\"s-input-group medium margin-block bottom\">" +
                "                    <span>" + $__('Spread') + "</span>" +
                "                    <input type=\"number\" name=\"spread\" class=\"width100px f-input-appearance\" /> px" +
                "                </div>" +
                "                <div><span>" + $__('Inset') + ":</span> <input type=\"checkbox\" name='inset' class='f-change-appearance' /></div>" +
                "            </div>" +
                "        </div>";
            return html;
        };

        /* Установить сохраненные настройки */
        DynamicAppearance.prototype['setBox-shadowPluginSettings'] = function (settings) {
            var that = this;
            var plugin = 'box-shadow';

            var values = ['x-offset', 'y-offset', 'blur', 'spread'];
            for (var i in values) {
                that.$activeBlock.find(".f-" + plugin + "-plugin-settings input[name='" + values[i] + "']").val(settings[values[i]]);
            }
            that.$activeBlock.find(".f-" + plugin + "-plugin-settings input[name='inset']").prop('checked', parseInt(settings.inset));
            that.$block.find(".f-color-picker-column").ColorPickerSetColor(that.ifundefined(settings.color, 'ffffff'));
        };

        /* Генерация CSS стилей */
        DynamicAppearance.prototype['generateBox-shadowPluginCss'] = function (settings) {
            var css = '';

            if (settings !== undefined && ((typeof settings['x-offset'] !== 'undefined' && settings['x-offset'] !== '') || (typeof settings['y-offset'] !== 'undefined' && settings['y-offset'] !== '') || (typeof settings['blur'] !== 'undefined' && settings['blur'] !== '') || (typeof settings['spread'] !== 'undefined' && settings['spread'] !== ''))) {
                var shadow = (typeof settings['x-offset'] !== 'undefined' ? settings['x-offset'] + 'px' : 0) +
                    (typeof settings['y-offset'] !== 'undefined' ? ' ' + settings['y-offset'] + 'px' : ' 0') +
                    (typeof settings['blur'] !== 'undefined' ? ' ' + settings['blur'] + 'px' : ' 0') +
                    (typeof settings['spread'] !== 'undefined' ? ' ' + settings['spread'] + 'px' : ' 0') +
                    (typeof settings['color'] !== 'undefined' ? ' #' + settings['color'] : ' #ffffff') +
                    (typeof settings['inset'] !== 'undefined' && parseInt(settings['inset']) ? ' inset' : '') + ';';
                css += '-webkit-box-shadow:' + shadow + '-moz-box-shadow:' + shadow + 'box-shadow:' + shadow;
            }

            return css;
        };

        /**********
         *
         * Цвет. color
         *
         **********/

        /* Установить сохраненные настройки */
        DynamicAppearance.prototype.setColorPluginSettings = function (settings) {
            var that = this;

            that.$block.find(".f-color-picker-column").ColorPickerSetColor(that.ifundefined(settings.color, 'ffffff'));
        };

        /* Генерация CSS стилей */
        DynamicAppearance.prototype.generateColorPluginCss = function (settings) {
            var that = this;
            var css = '';
            var plugin = 'color';
            var activeToolbarPlugins = that.showHover && that.currentNavigation == 'hover' ? that.hoverToolbarPlugins : that.toolbarPlugins;

            if (settings !== undefined && settings.color !== undefined) {
                css += (activeToolbarPlugins[plugin]['svg'] ? 'fill' : 'color') + ': #' + settings.color + ';';
            }

            return css;
        };

        /**********
         *
         * Скругление углов. border-radius
         *
         **********/

        /* Построение настроек */
        DynamicAppearance.prototype['buildBorder-radiusPluginToolbar'] = function () {
            var that = this;
            var html = "";
            var plugin = 'border-radius';
            var activeToolbarPlugins = that.showHover && that.currentNavigation == 'hover' ? that.hoverToolbarPlugins : that.toolbarPlugins;

            html +=
                "            <div class=\"inline-block\">" +
                "                <div class=\"head\">" + $__('Border radius') + "</div>" +
                "                <div class=\"margin-block type-" + activeToolbarPlugins[plugin].type + "\">" +
                "                    <div class=\"s-input-group f-radius-single s-radius-single\">" +
                "                        <span>" + $__('Radius') + "</span>" +
                "                        <input type=\"number\" name=\"value\" class=\"f-input-appearance width100px\" /> " +
                "                        <select class=\"inherit f-change-appearance\" name=\"unit\"><option selected value=\"\">px</option><option value=\"%\">%</option></select>" +
                "                    </div>" +
                "                    <div class=\"f-radius-all s-radius-all\">" +
                "                        <div class=\"margin-block semi bottom\">" +
                "                            <div class=\"s-input-group medium\">" +
                "                                <span>" + $__('Top-left') + "</span>" +
                "                                <input type=\"number\" name=\"value:top-left\" class=\"width100px f-input-appearance\" /> px" +
                "                            </div>" +
                "                        </div>" +
                "                        <div class=\"margin-block bottom semi\">" +
                "                            <div class=\"s-input-group medium\">" +
                "                                <span>" + $__('Top-right') + "</span>" +
                "                                <input type=\"number\" name=\"value:top-right\" class=\"width100px f-input-appearance\" /> px" +
                "                            </div>" +
                "                        </div>" +
                "                        <div class=\"margin-block bottom semi\">" +
                "                            <div class=\"s-input-group medium\">" +
                "                                <span>" + $__('Bottom-left') + "</span>" +
                "                                <input type=\"number\" name=\"value:bottom-left\" class=\"width100px f-input-appearance\" /> px" +
                "                            </div>" +
                "                        </div>" +
                "                        <div class=\"margin-block bottom semi\">" +
                "                            <div class=\"s-input-group medium\">" +
                "                                <span>" + $__('Bottom-right') + "</span>" +
                "                                <input type=\"number\" name=\"value:bottom-right\" class=\"width100px f-input-appearance\" /> px" +
                "                            </div>" +
                "                        </div>" +
                "                    </div>" +
                "                </div>" +
                "            </div>";
            return html;
        };

        /* Установить сохраненные настройки */
        DynamicAppearance.prototype['setBorder-radiusPluginSettings'] = function (settings) {
            var that = this;
            var plugin = 'border-radius';
            var activeToolbarPlugins = that.showHover && that.currentNavigation == 'hover' ? that.hoverToolbarPlugins : that.toolbarPlugins;

            if (settings.attributes !== undefined || activeToolbarPlugins[plugin]['type'] === 'single') {
                that.$activeBlock.find(".f-" + plugin + "-plugin-settings .f-radius-single").show().find("input").val(settings.value).end().find('select[name="unit"]').val(settings.unit);
                that.$activeBlock.find(".f-" + plugin + "-plugin-settings .f-radius-all").hide();
            } else {
                // that.$activeBlock.find(".f-" + plugin + "-plugin-settings .f-radius-all").show().children().hide();
                for (var i in settings.value) {
                    that.$activeBlock.find(".f-" + plugin + "-plugin-settings .f-radius-all input[name='value:" + i + "']").val(settings.value[i]).closest(".margin-block").show();
                }
                that.$activeBlock.find(".f-" + plugin + "-plugin-settings .f-radius-single").hide();
            }
        };

        /* Генерация CSS стилей */
        DynamicAppearance.prototype['generateBorder-radiusPluginCss'] = function (settings) {
            var that = this;
            var plugin = 'border-radius';
            var css = '';
            var activeToolbarPlugins = that.showHover && that.currentNavigation == 'hover' ? that.hoverToolbarPlugins : that.toolbarPlugins;

            if (settings !== undefined) {
                var unit = (settings.unit !== undefined && settings.unit !== '') ? settings.unit : 'px';
                if (settings.attributes !== undefined || activeToolbarPlugins[plugin]['type'] === 'single') {
                    if (settings.attributes !== undefined) {
                        for (var i in settings.attributes) {
                            css += settings.attributes[i] + ':' + (parseInt(settings.value) ? settings.value : 0) + unit + ';';
                        }
                    } else if (settings.value !== undefined && settings.value !== '') {
                        css += plugin + ':' + (parseInt(settings.value) ? settings.value : 0) + unit + ';';
                    }
                } else if (settings.value !== undefined) {
                    for (var i in settings.value) {
                        css += 'border-' + i + '-radius' + ':' + (parseInt(settings.value[i]) ? settings.value[i] : 0) + unit + ';';
                    }
                }
            }

            return css;
        };

        /* Зависимость дополнительных элементов от основных */
        DynamicAppearance.prototype['generateBorder-radiusPluginDependencyCss'] = function (settings, isHover) {
            var that = this;
            var plugin = 'border-radius';
            var css = '';

            if (settings !== undefined && settings.value !== undefined && settings.value !== '') {
                var unit = (settings.unit !== undefined && settings.unit !== '') ? settings.unit : 'px';
                $.each(that.pluginDependency[plugin], function (elem, attrs) {
                    css += '#dynamicAppearance-liveblock-' + that.id + ' ' + elem + (isHover ? ':hover' : '') + '{';
                    for (var i in attrs) {
                        css += attrs[i] + ':' + (parseInt(settings.value) ? settings.value : 0) + unit + ';';
                    }
                    css += '}';
                });
            }

            return css;
        };

        DynamicAppearance.prototype['generateColorPluginDependencyCss'] = function (settings, isHover) {
            var that = this;
            var plugin = 'color';
            var css = '';

            if (settings !== undefined && settings.color !== undefined) {
                $.each(that.pluginDependency[plugin].split(','), function (i, elem) {
                    var parts = elem.split(':');
                    var revertHoverDependency = typeof parts[1] !== 'undefined' && parts[1] == 'parentHover';
                    if (revertHoverDependency && typeof parts[2] !== 'undefined') {
                        parts[1] = parts[2];
                    }
                    css += (revertHoverDependency ? (parts[0] + (isHover ? ':hover' : '') + ' ') : '');
                    css += '#dynamicAppearance-liveblock-' + that.id;
                    css += (!revertHoverDependency ? (' ' + parts[0] + (isHover ? ':hover' : '')) : '');
                    if (!revertHoverDependency) {
                        css += ',#dynamicAppearance-liveblock-' + that.id + (isHover ? ':hover' : '') + ' ' + parts[0];
                    }
                    css += '{';
                    css += (typeof parts[1] !== 'undefined' && parts[1] == 'svg' ? 'fill' : 'color') + ': #' + settings.color + ';';
                    css += '}';
                });
            }

            return css;
        };

        /**********
         *
         * Шрифт. Font
         *
         **********/

        /* Построение настроек */
        DynamicAppearance.prototype.buildFontPluginToolbar = function () {
            var html = "";
            html +=
                "        <div class=\"inline-block\">" +
                "            <div class=\"head\">" + $__('Size') + "</div>" +
                "            <div class=\"margin-block bottom\">" +
                "                 <select name=\"size\" size=\"30\" class=\"f-change-appearance\">" +
                "                     <option value=\"\" selected>" + $__('Use default') + "</option>";
            for (var i = 8; i <= 36; i++) {
                html += "             <option value=\"" + i + "\">" + i + "</option>";
            }
            html += "             </select>" +
                "            </div>" +
                "        </div>" +
                "        <div class=\"inline-block\">" +
                "            <div class=\"head\">" + $__('Font family') + "</div>" +
                "            <select name=\"family\" size=\"22\" class=\"f-change-appearance\">" +
                "                <option value=\"\" selected>" + $__('Use default') + "</option>" +
                "                <option value=\"Arial,sans-serif\">Arial</option>" +
                "                <option value=\"'Comic Sans MS',cursive,sans-serif\">Comic Sans MS</option>" +
                "                <option value=\"'Courier New',Courier,monospace\">Courier New</option>" +
                "                <option value=\"Georgia,serif\">Georgia</option>" +
                "                <option value=\"Helvetica,sans-serif\">Helvetica</option>" +
                "                <option value=\"'Lucida Sans Unicode','Lucida Grande',sans-serif\">Lucida Sans</option>" +
                "                <option value=\"'Palatino Linotype','Book Antiqua',Palatino,serif\">Palantino Linotype</option>" +
                "                <option value=\"Tahoma,sans-serif\">Tahoma</option>" +
                "                <option value=\"'Times New Roman',Times,serif\">Times New Romans</option>" +
                "                <option value=\"'Trebuchet MS',sans-serif\">Trebuchet MS</option>" +
                "                <option value=\"Verdana,sans-serif\">Verdana</option>" +
                "                <optgroup label=\"" + $__('Google fonts') + "\">" +
                "                    <option value=\"'Open Sans',sans-serif\">Open Sans</option>" +
                "                    <option value=\"'Open Sans Condensed',sans-serif\">Open Sans Condensed</option>" +
                "                    <option value=\"'Roboto',sans-serif\">Roboto</option>" +
                "                    <option value=\"'Roboto Condensed',sans-serif\">Roboto Condensed</option>" +
                "                    <option value=\"'Roboto Slab',serif\">Roboto Slab</option>" +
                "                    <option value=\"'PT Sans',sans-serif\">PT Sans</option>" +
                "                    <option value=\"'Lora',serif\">Lora</option>" +
                "                    <option value=\"'Lobster',cursive\">Lobster</option>" +
                "                    <option value=\"'Ubuntu',sans-serif\">Ubuntu</option>" +
                "                    <option value=\"'Noto Sans',sans-serif\">Noto Sans</option>" +
                "                </optgroup>" +
                "            </select>" +
                "        </div>" +
                "        <div class=\"inline-block\">" +
                "            <div class=\"head\">" + $__('Font style') + "</div>" +
                "            <select name=\"style\" size=\"5\" class=\"f-change-appearance inherit\">" +
                "                <option value=\"\" selected>" + $__('Use default') + "</option>" +
                "                <option value=\"normal\">" + $__('Normal') + "</option>" +
                "                <option value=\"bold\">" + $__('Bold') + "</option>" +
                "                <option value=\"italic\">" + $__('Italic') + "</option>" +
                "                <option value=\"bolditalic\">" + $__('Bold/Italic') + "</option>" +
                "            </select>" +
                "        </div>";
            return html;
        };

        /* Установить сохраненные настройки */
        DynamicAppearance.prototype.setFontPluginSettings = function (settings) {
            var that = this;
            var plugin = 'font';

            that.$activeBlock.find(".f-" + plugin + "-plugin-settings select[name='size']").val(settings.size);
            that.$activeBlock.find(".f-" + plugin + "-plugin-settings select[name='family']").val(settings.family);
            that.$activeBlock.find(".f-" + plugin + "-plugin-settings select[name='style']").val(settings.style);
        };

        /* Генерация CSS стилей */
        DynamicAppearance.prototype.generateFontPluginCss = function (settings) {
            var css = '';
            if (settings !== undefined) {
                css += (settings.family !== undefined && settings.family !== '' ? 'font-family:' + settings.family + ';' : '');
                css += (settings.size !== undefined && settings.size !== '' ? 'font-size:' + settings.size + 'px;' : '');
                if (settings.style !== undefined && settings.style !== '') {
                    if (settings.style == 'bold') {
                        css += 'font-style:normal;font-weight:bold;';
                    } else if (settings.style == 'bolditalic') {
                        css += 'font-style:italic;font-weight:bold;';
                    } else if (settings.style == 'italic') {
                        css += 'font-style:italic;font-weight:normal;';
                    } else {
                        css += 'font-style:normal;font-weight:normal;';
                    }
                }
            }
            return css;
        };

        /**********
         *
         * Внутренний отступ. padding
         *
         **********/

        /* Построение настроек */
        DynamicAppearance.prototype.buildPaddingPluginToolbar = function () {
            var html = "";
            html +=
                "        <div class=\"inline-block\">" +
                "            <div class=\"head\">" + $__('Values') + "</div>" +
                "            <div class=\"margin-block semi\">" +
                "                <div class=\"s-input-group medium margin-block semi bottom\">" +
                "                    <span>" + $__('Top') + "</span>" +
                "                    <input type=\"number\" name=\"top\" class=\"width100px f-input-appearance\" /> px" +
                "                </div>" +
                "                <div class=\"s-input-group medium margin-block semi bottom\">" +
                "                    <span>" + $__('Right') + "</span>" +
                "                    <input type=\"number\" name=\"right\" class=\"width100px f-input-appearance\" /> px" +
                "                </div>" +
                "                <div class=\"s-input-group medium margin-block semi bottom\">" +
                "                    <span>" + $__('Bottom') + "</span>" +
                "                    <input type=\"number\" name=\"bottom\" class=\"width100px f-input-appearance\" /> px" +
                "                </div>" +
                "                <div class=\"s-input-group medium margin-block bottom\">" +
                "                    <span>" + $__('Left') + "</span>" +
                "                    <input type=\"number\" name=\"left\" class=\"width100px f-input-appearance\" /> px" +
                "                </div>" +
                "            </div>" +
                "        </div>";
            return html;
        };

        /* Установить сохраненные настройки */
        DynamicAppearance.prototype.setPaddingPluginSettings = function (settings) {
            var that = this;
            var plugin = 'padding';

            var values = ['top', 'right', 'bottom', 'left'];
            for (var i in values) {
                that.$activeBlock.find(".f-" + plugin + "-plugin-settings input[name='" + values[i] + "']").val(settings[values[i]]);
            }
        };

        /* Генерация CSS стилей */
        DynamicAppearance.prototype.generatePaddingPluginCss = function (settings) {
            var css = '';
            if (settings !== undefined) {
                css += (settings.top !== undefined && settings.top !== '' ? 'padding-top:' + settings.top + 'px;' : '');
                css += (settings.right !== undefined && settings.right !== '' ? 'padding-right:' + settings.right + 'px;' : '');
                css += (settings.bottom !== undefined && settings.bottom !== '' ? 'padding-bottom:' + settings.bottom + 'px;' : '');
                css += (settings.left !== undefined && settings.left !== '' ? 'padding-left:' + settings.left + 'px;' : '');
            }
            return css;
        };

        /**********
         *
         * Внешний отступ. margin
         *
         **********/

        /* Построение настроек */
        DynamicAppearance.prototype.buildMarginPluginToolbar = function () {
            var html = "";
            html +=
                "        <div class=\"inline-block\">" +
                "            <div class=\"head\">" + $__('Values') + "</div>" +
                "            <div class=\"margin-block semi\">" +
                "                <div class=\"s-input-group medium margin-block semi bottom\">" +
                "                    <span>" + $__('Top') + "</span>" +
                "                    <input type=\"number\" name=\"top\" class=\"width100px f-input-appearance\" /> px" +
                "                </div>" +
                "                <div class=\"s-input-group medium margin-block semi bottom\">" +
                "                    <span>" + $__('Right') + "</span>" +
                "                    <input type=\"number\" name=\"right\" class=\"width100px f-input-appearance\" /> px" +
                "                </div>" +
                "                <div class=\"s-input-group medium margin-block semi bottom\">" +
                "                    <span>" + $__('Bottom') + "</span>" +
                "                    <input type=\"number\" name=\"bottom\" class=\"width100px f-input-appearance\" /> px" +
                "                </div>" +
                "                <div class=\"s-input-group medium margin-block bottom\">" +
                "                    <span>" + $__('Left') + "</span>" +
                "                    <input type=\"number\" name=\"left\" class=\"width100px f-input-appearance\" /> px" +
                "                </div>" +
                "            </div>" +
                "        </div>";
            return html;
        };

        /* Установить сохраненные настройки */
        DynamicAppearance.prototype.setMarginPluginSettings = function (settings) {
            var that = this;
            var plugin = 'margin';

            var values = ['top', 'right', 'bottom', 'left'];
            for (var i in values) {
                that.$activeBlock.find(".f-" + plugin + "-plugin-settings input[name='" + values[i] + "']").val(settings[values[i]]);
            }
        };

        /* Генерация CSS стилей */
        DynamicAppearance.prototype.generateMarginPluginCss = function (settings) {
            var css = '';
            if (settings !== undefined) {
                css += (settings.top !== undefined && settings.top !== '' ? 'margin-top:' + settings.top + 'px;' : '');
                css += (settings.right !== undefined && settings.right !== '' ? 'margin-right:' + settings.right + 'px;' : '');
                css += (settings.bottom !== undefined && settings.bottom !== '' ? 'margin-bottom:' + settings.bottom + 'px;' : '');
                css += (settings.left !== undefined && settings.left !== '' ? 'margin-left:' + settings.left + 'px;' : '');
            }
            return css;
        };

        /**********
         *
         * Выравнивание. text-align
         *
         **********/

        /* Построение настроек */
        DynamicAppearance.prototype['buildText-alignPluginToolbar'] = function () {
            var html = "";
            html +=
                "        <div class=\"inline-block\">" +
                "            <div class=\"s-head\">" + $__("Alignment") + "</div>" +
                "            <select size=\"5\" name=\"value\" class=\"f-change-appearance\">" +
                "                <option value=\"\">" + $__('Use default') + "</option>" +
                "                <option value=\"left\">" + $__("To left") + "</option>" +
                "                <option value=\"right\">" + $__("To right") + "</option>" +
                "                <option value=\"center\">" + $__('To center') + "</option>" +
                "                <option value=\"justify\">" + $__('Justify') + "</option>" +
                "            </select>" +
                "        </div>";
            return html;
        };

        /* Установить сохраненные настройки */
        DynamicAppearance.prototype['setText-alignPluginSettings'] = function (settings) {
            var that = this;
            var plugin = 'text-align';

            that.$activeBlock.find(".f-" + plugin + "-plugin-settings select").val(settings.value);
        };

        /* Генерация CSS стилей */
        DynamicAppearance.prototype['generateText-alignPluginCss'] = function (settings) {
            var css = '';
            if (settings !== undefined && settings.value !== undefined && settings.value !== '') {
                css += 'text-align:' + settings.value + ';';
            }
            return css;
        };

        /**********
         *
         * Ширина. width
         *
         **********/

        /* Построение настроек */
        DynamicAppearance.prototype.buildWidthPluginToolbar = function () {
            var that = this;

            var html = "";
            var defaultValues = ['25%', '50%', '75%', '100%'];
            var widthValues = that.customOptions['width:values'] !== undefined && that.customOptions['width:values'].length ? that.customOptions['width:values'] : defaultValues;
            html +=
                "        <div class=\"inline-block\">" +
                "            <div class=\"s-head\">" + $__("Width") + "</div>" +
                "            <select size=\"" + widthValues.length + "\" name=\"value\" class=\"f-change-appearance\">" +
                "                <option value=\"\">" + $__('Use default') + "</option>";
            for (var i in widthValues) {
                html += "        <option value=\"" + widthValues[i] + "\">" + widthValues[i] + "</option>";
            }
            html += "        </select>" +
                "        </div>";
            return html;
        };

        /* Установить сохраненные настройки */
        DynamicAppearance.prototype.setWidthPluginSettings = function (settings) {
            var that = this;
            var plugin = 'width';

            that.$activeBlock.find(".f-" + plugin + "-plugin-settings select").val(settings.value);
        };

        /* Генерация CSS стилей */
        DynamicAppearance.prototype.generateWidthPluginCss = function (settings) {
            var css = '';
            if (settings !== undefined && settings.value !== undefined && settings.value !== '') {
                css += 'width:' + settings.value + ';';
            }
            return css;
        };

        /**********
         *
         * Дополнительные функции
         *
         **********/

        /* Очистка панели инструментов */
        DynamicAppearance.prototype.cleanToolbar = function (all) {
            var that = this;
            var block = (all && that.showHover) ? that.$block : that.$activeBlock;

            block.find(".s-show").removeClass('s-show');
            block.find("select" + (!all ? ':not(.f-dynamicappearance-toolbar select)' : '') + " option:selected").prop('selected', false);

            that.hideColorPicker();
            that.hideTransparency();
        };

        /* Получение названия активного плагина в панели инструментов */
        DynamicAppearance.prototype.getMainToolbarProperty = function () {
            var that = this;

            return that.$activeBlock.find(".f-dynamicappearance-toolbar select").val();
        };

        /* Инициализация выбора цветовой палитры */
        DynamicAppearance.prototype.initColorPicker = function (el) {
            var that = this;

            function initColor(elem) {
                elem = $(elem);
                elem.css('background-color', '#' + elem.attr("data-color")).ColorPicker({
                    color: elem.attr("data-color"),
                    onShow: function (colpkr) {
                        elem.ColorPickerSetColor(elem.attr("data-color") !== undefined ? elem.attr("data-color") : elem.val());
                        $(colpkr).fadeIn(500);
                        return false;
                    },
                    onHide: function (colpkr) {
                        $(colpkr).fadeOut(500);
                        return false;
                    },
                    onChange: function (hsb, hex, rgb) {
                        elem.css('backgroundColor', '#' + hex);
                        elem.attr("data-color", hex).next().val(hex);
                        elem.hasClass("f-print-hex") && elem.val(hex);
                        elem.trigger('input').next(":hidden").trigger('input');
                    }
                }).on('keyup', function () {
                    var hex = elem.ColorPickerFixHex(this.value);
                    elem.ColorPickerSetColor(hex).css('backgroundColor', '#' + hex).attr('data-color', hex).trigger('input');
                });
            }

            if (el) {
                initColor(el);
            } else {
                that.$block.find(".f-color-picker").each(function () {
                    initColor(this);
                });
            }
        };
        DynamicAppearance.prototype.showColorPicker = function () {
            var that = this;

            that.$block.find(".f-color-picker-column").addClass("s-show");
        };
        DynamicAppearance.prototype.hideColorPicker = function () {
            var that = this;

            that.$block.find(".f-color-picker-column").removeClass("s-show");
        };
        DynamicAppearance.prototype.isShowColorPicker = function (column) {
            return $(":selected", column).data("show-colorpicker");
        };
        DynamicAppearance.prototype.showTransparency = function () {
            var that = this;

            that.$block.find(".f-transparent-block").show();
        };
        DynamicAppearance.prototype.hideTransparency = function () {
            var that = this;

            that.$block.find(".f-transparent-block").hide();
        };
        DynamicAppearance.prototype.isShowTransparency = function (select) {
            return $(":selected", select).data("show-transparent");
        };

        DynamicAppearance.prototype.hexToRGB = function (hex) {
            var hex = parseInt(((hex.indexOf('#') > -1) ? hex.substring(1) : hex), 16);
            return {r: hex >> 16, g: (hex & 0x00FF00) >> 8, b: (hex & 0x0000FF)};
        };
        DynamicAppearance.prototype.getRgba = function (rgbaString, hex, transparency) {
            var rgba = 'rgba(';
            if (typeof rgbaString !== 'undefined' && rgbaString !== '' && rgbaString.split(',').length === 3) {
                rgba += rgbaString;
            } else {
                var rgbObj = this.hexToRGB(hex);
                rgba += rgbObj.r + ',' + rgbObj.g + ',' + rgbObj.b;
            }
            rgba += ',' + (transparency !== undefined ? transparency : 1) + ')';
            return rgba;
        };

        DynamicAppearance.prototype.ifundefined = function (obj, defVal, type) {
            if (obj !== undefined) {
                switch (type) {
                    case 'float':
                        obj = parseFloat(obj);
                        break;
                    case 'int':
                        obj = parseInt(obj);
                        break;
                }
            } else {
                obj = defVal;
            }
            return obj;
        };

        /* Уничтожение работы скрипта */
        DynamicAppearance.prototype.destroy = function () {
            var that = this;

            that.$block.html('').removeClass('dynamicAppearance-block').removeData('dynamicAppearance').removeData('dynamicAppearance-id');
            $(".dynamicAppearance-styles-" + that.id).remove();
        };

        /* Сброс всех настроек */
        DynamicAppearance.prototype.reset = function () {
            var that = this;

            $.each(that.toolbarPlugins, function (plugin) {
                that.appearance[plugin] = {};
            });

            if (that.showHover) {
                $.each(that.hoverToolbarPlugins, function (plugin) {
                    that.hoverAppearance[plugin] = {};
                });
            }

            if (that.$liveBlock.length) {
                that.$liveBlock.removeAttr('id');
                $(".dynamicAppearance-styles-" + that.id).remove();
            }
            that.cleanToolbar(true);
        };

        /* Получение объекта со стилями */
        DynamicAppearance.prototype.getAppearance = function () {
            var that = this;

            return that.showHover ? {hover: that.hoverAppearance, normal: that.appearance} : that.appearance;
        };

        /* Получение объекта с настройками */
        DynamicAppearance.prototype.getSettings = function () {
            var that = this;

            return that.showHover ? {
                hover: that.hoverToolbarPlugins,
                normal: that.toolbarPlugins
            } : that.toolbarPlugins;
        };

        /* Получение объекта со стилями бехз классов */
        DynamicAppearance.prototype.getOnlyCSS = function () {
            var that = this;

            return that.generateCss(true);
        };

        /* Установление переданных стилей */
        DynamicAppearance.prototype.setAppearance = function (styles) {
            var that = this;
            if (typeof styles === 'object') {
                that.reset();
                if (that.showHover) {
                    if (styles.hover !== undefined) {
                        that.hoverAppearance = $.extend(true, that.hoverAppearance, styles.hover);
                    }
                    if (styles.normal !== undefined) {
                        that.appearance = $.extend(true, that.appearance, styles.normal);
                    }
                } else {
                    that.appearance = $.extend(true, that.appearance, styles);
                }
                that.refreshLiveBlock();
            }
        };

        /* Получение CSS стилей для встраивания в DOM */
        DynamicAppearance.prototype.getCss = function () {
            var that = this;

            return that.generateCss();
        };

        return DynamicAppearance;

    })(jQuery);

    $.fn.extend({
        dynamicAppearance: function (options, extraOptions) {
            if (typeof options === 'string') {
                var data;
                this.each(function () {
                    var dynamicAppearance;
                    dynamicAppearance = $(this).data('dynamicAppearance');
                    var approvedMethods = ['destroy', 'reset', 'getAppearance', 'getSettings', 'getCss', 'setAppearance', 'getOnlyCSS'];
                    if (typeof options === 'string') {
                        if ($.inArray(options, approvedMethods) !== -1) {
                            if (dynamicAppearance instanceof DynamicAppearance) {
                                data = dynamicAppearance[options](extraOptions !== null ? extraOptions : null);
                            }
                            options.indexOf('get') > -1 ? data : null;
                        }
                    }
                });
                return options.indexOf('get') > -1 ? data : null;
            } else {
                return this.each(function () {
                    var $this, dynamicAppearance;
                    $this = $(this);
                    dynamicAppearance = $this.data('dynamicAppearance');
                    if (!(dynamicAppearance instanceof DynamicAppearance)) {
                        $this.addClass('dynamicAppearance-block').data('dynamicAppearance-id', $('.dynamicAppearance-block').length).data('dynamicAppearance', new DynamicAppearance(this, options));
                    }
                });
            }
        }
    });

}));
