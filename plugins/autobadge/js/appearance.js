$.autobadge_appearance = {
    ribbons: {},
    templates: {},
    activeClass: '',
    activeRibbon: '',
    initRibbon: function (targetId, ribbonClass, templateId, skipChanges) {
        this.activeClass = 'ab-target-' + targetId;
        if (this.ribbons[this.activeClass] === undefined) {
            this.ribbons[this.activeClass] = {};
        }
        this.reset(true);
        this.addRibbonToPreview(ribbonClass, targetId);
        /* Если с наклейкой не работали, то загружаем в нее стили по умолчанию */
        if (this.ribbons[this.activeClass][ribbonClass] === undefined) {
            this.ribbons[this.activeClass][ribbonClass] = $.extend(true, {}, this.ribbons['default_' + ribbonClass]);
            $(".default-badge-shape").hide();
        } else {
            $(".default-badge-shape").show().find('a').attr('data-class', ribbonClass);
        }

        /* Загружаем настройки шаблона */
        if (templateId && templateId !== true && !skipChanges) {
            this.ribbons[this.activeClass][ribbonClass] = $.extend(true, {}, this.templates[templateId]['settings']);
        }
        $(".s-save-badge a").attr("data-template-id", templateId ? templateId : 0);
        if (templateId) {
            $(".s-save-badge a.red").show();
        } else {
            $(".s-save-badge a.red").hide();
        }

        $(".appearance-columns, .badge-text, .s-save-badge").show();
        $(".appearance-columns").find(".inited").removeClass("inited");
        this.activeRibbon = ribbonClass;
        this.ribbons[this.activeClass]['active'] = this.activeRibbon;

        this.ribbons[this.activeClass][this.activeRibbon]['orientation'] !== undefined && this.changeRibbonOrientation(this.ribbons[this.activeClass][this.activeRibbon]['orientation']);

        this.initMainMenu(this.ribbons[this.activeClass][ribbonClass]);

        this.setValues('text', this.ribbons[this.activeClass][ribbonClass]);
        this.generateStyles(this.ribbons[this.activeClass][ribbonClass]);
        $.autobadge.changeBadgeSort();
    },
    /* Добавляем поля редактирования наклейки */
    addRibbonEditField: function (targetRow, templateId) {
        var skipChanges = false;
        /* Сбрасываем предыдущие настройки */
        $(".appearance-columns, .badge-text, .s-save-badge").hide();
        $(".badge-example input").prop("checked", false);

        /* Настройки шаблона не подставляем, но сохраняем данные о том, что перед нами шаблон */
        if (templateId === false) {
            var targetValue = targetRow.find(".target-chosen").val();
            skipChanges = true;
            templateId = targetValue !== 'create' ? parseInt(targetValue.substring(10)) : 0;
        }

        targetRow.append($(".autobadge-edit-block").show());
        var activeClass = 'ab-target-' + targetRow.attr("data-id");

        if (this.ribbons[activeClass] !== undefined || (templateId && this.templates[templateId] !== undefined)) {
            var ribbonId = (templateId && this.templates[templateId] !== undefined && !skipChanges ? this.templates[templateId]['settings']['id'].substring(0, 8) : this.ribbons[activeClass]['active']);
            var badgeExample = $(".badge-example input[value='" + ribbonId + "']");
            badgeExample.prop("checked", true);
            this.initRibbon(targetRow.attr("data-id"), badgeExample.val(), templateId, skipChanges);
        }
        this.highlightRibbon(activeClass);
    },
    /* Подсветить наклейку */
    highlightRibbon: function (ribbonClass) {
        var ribbon = $(".live-preview ." + ribbonClass);
        clearTimeout(ribbon.data('timer'));
        if (ribbon.is(':animated')) {
            return false;
        }
        ribbon.addClass('onhover');
        ribbon.data('timer', setTimeout(function () {
            ribbon.removeClass('onhover');
        }, 1000));
    },
    /* Скрыть поле редактирования наклейки */
    hideRibbonEditField: function () {
        $(".autobadge-edit-block").hide();
    },
    /* Удаляем цель */
    deleteRibbon: function (id) {
        var ribbonClass = 'ab-target-' + id;
        if (this.ribbons[ribbonClass] !== undefined) {
            delete this.ribbons[ribbonClass];
            /* Удаляем старую наклейку */
            $(".live-preview ." + ribbonClass).remove();
        }
    },
    /* Скрываем ненужные настройки */
    initMainMenu: function (styles) {
        $("#s-main-menu option").hide();
        for (var i in styles) {
            $("#s-main-menu option[value='" + i + "']").show();
        }

        if (styles.orientations !== undefined) {
            $("#orientation-menu-column option").hide();
            for (var i in styles.orientations) {
                $("#orientation-menu-column option[value='" + i + "']").show();
            }
        }
    },
    /* Добавить наклейку в демонстрационный блок */
    addRibbonToPreview: function (ribbonClass, targetId) {
        /* Если настройки наклейки существуют */
        if (this.ribbons['default_' + ribbonClass] !== undefined) {
            var livePreview = $(".live-preview");
            /* Удаляем старую наклейку */
            $(".live-preview ." + this.activeClass).remove();
            /* Добавляем новую наклейку с дефолтными настройками */
            $(this.ribbons['default_' + ribbonClass]['construction']).addClass(this.activeClass).attr('data-id', targetId).appendTo(livePreview);
        }
    },
    /* Обновить внешний вид наклейки */
    updateStyles: function (btn) {
        btn = $(btn);
        var mainMenuValue = $("#s-main-menu").val();
        var value = btn.is(":input") ? (btn.is(':checkbox') ? (btn.prop('checked') ? 1 : 0) : btn.val()) : btn.find(":selected").val();
        value = btn.attr('data-color') !== undefined ? btn.ColorPickerFixHex(value) : value;
        var obj = {};
        if (btn.data('parent') !== undefined) {
            if (btn.attr("name").match(':') !== null) {
                var parts = btn.attr("name").split(':');
                obj = this.ribbons[this.activeClass][this.activeRibbon][mainMenuValue][btn.data('parent')][parts[0]];
                obj[parts[1]] = value;
            } else {
                obj = this.ribbons[this.activeClass][this.activeRibbon][mainMenuValue][btn.data('parent')];
                obj[btn.attr("name")] = value;
            }
        } else {
            obj = this.ribbons[this.activeClass][this.activeRibbon][mainMenuValue];
            obj[btn.attr("name")] = value;
        }
        if (btn.attr('data-color') !== undefined) {
            var rgbObj = this.hexToRGB(value);
            obj[btn.attr('name') + '_color_rgb'] = rgbObj.r + ',' + rgbObj.g + ',' + rgbObj.b;
        }
        btn.closest('.position-template').length && this.togglePositionMargins(btn.data('hide').split(","));
        this.refreshActiveRibbon();
        $(".default-badge-shape").show().find('a').attr('data-class', this.activeRibbon);
    },
    /* Обновить хвосты наклейки */
    updateAlltailsStyles: function (btn) {
        btn = $(btn);
        var self = this;
        var mainMenuValue = $("#s-main-menu").val();
        this.ribbons[this.activeClass][this.activeRibbon][mainMenuValue][btn.data('parent')][btn.attr("name")] = [];
        $("#alltails_position-appearance-column input:checked").each(function () {
            self.ribbons[self.activeClass][self.activeRibbon][mainMenuValue][btn.data('parent')][btn.attr("name")].push($(this).val());
        });
        this.refreshActiveRibbon();
    },
    /* Обновить текст наклейки */
    updateText: function (btn, isImage) {
        btn = $(btn);
        var name = 'text';
        var pos = parseInt(btn.closest('.text-item').attr("data-pos"));
        if (this.ribbons[this.activeClass][this.activeRibbon][name][pos] === undefined) {
            this.ribbons[this.activeClass][this.activeRibbon][name][pos] = {};
            this.ribbons[this.activeClass][this.activeRibbon][name][pos]['type'] = isImage ? 'image' : 'text';
        }
        if (btn.data('parent') !== undefined) {
            if (this.ribbons[this.activeClass][this.activeRibbon][name][pos][btn.data('parent')] === undefined) {
                this.ribbons[this.activeClass][this.activeRibbon][name][pos][btn.data('parent')] = {};
            }
            this.ribbons[this.activeClass][this.activeRibbon][name][pos][btn.data('parent')][btn.attr("name")] = btn.is(":input") ? btn.val() : btn.find(":selected").val();
        } else {
            this.ribbons[this.activeClass][this.activeRibbon][name][pos][btn.attr("name")] = btn.is(":checkbox") ? (btn.prop("checked") ? 1 : 0) : (btn.is(":input") ? btn.val() : btn.find(":selected").val());
        }
        this.refreshActiveRibbon();
    },
    /* Генерация стилей */
    generateStyles: function (styles) {
        if (styles === undefined) {
            return false;
        }
        var ribbonClass = styles.id,
            defaultStyles = this.ribbons['default_' + ribbonClass.substring(0, 8)],
            className = this.activeClass + '.' + ribbonClass,
            css = "",
            liveRibbon = $(".live-preview ." + this.activeClass);

        /* Создаем блок со стилями для наклейки */
        if (!$("#badge-styles .style-" + this.activeClass + '-' + ribbonClass).length) {
            $("#badge-styles").append("<div class='style-" + this.activeClass + '-' + ribbonClass + "'></div>");
        }

        if (this.activeRibbon !== 'ribbon-6' || this.activeRibbon == 'ribbon-4') {
            if (liveRibbon.hasClass("autoposition-h")) {
                liveRibbon.css('height', '');
            }
            if (liveRibbon.hasClass("autoposition-w")) {
                liveRibbon.css('width', '');
            }
            liveRibbon.removeClass('autoposition-h autoposition-w').css("marginTop", "").css("marginLeft", "");
        }

        /* Текст и изображение */
        if (styles.multiline !== undefined) {
            $(".badge-text").addClass("multiline");
        } else {
            $(".badge-text").removeClass("multiline");
        }
        if (styles.text !== undefined) {
            var badgeText = '',
                image = '',
                k = 0,
                textZIndex = 0,
                textK = 0,
                len = styles.text.length,
                attrs = ['top', 'right', 'bottom', 'left'];
            $(".live-preview ." + this.activeClass + " img").remove();
            $.each(styles.text, function (i, line) {
                if (line === undefined) {
                    return true;
                } else {
                    var zIndex = len - k;
                    /* Текст */
                    if (line.type == 'text') {
                        badgeText += (textK > 0 ? '<br>' : '') + '<span data-pos="' + k + '" style="z-index:' + zIndex + ';';
                        badgeText += (line.color !== undefined && line.color !== '' ? 'color:#' + line.color + ';' : '');
                        badgeText += (line.family !== undefined && line.family !== '' ? 'font-family:' + line.family + ';' : '');
                        badgeText += (line.size !== undefined && line.size !== '' ? 'font-size:' + line.size + 'px;' : '');
                        if (line.style !== undefined && line.style !== '') {
                            if (line.style == 'bold') {
                                badgeText += 'font-style:normal; font-weight:bold;';
                            } else if (line.style == 'bolditalic') {
                                badgeText += 'font-style:italic; font-weight:bold;';
                            } else if (line.style == 'italic') {
                                badgeText += 'font-style:italic; font-weight:normal;';
                            } else {
                                badgeText += 'font-style:normal; font-weight:normal;';
                            }
                        }
                        badgeText += (line.align !== undefined && line.align !== '' ? 'text-align:' + line.align + ';' : '');
                        if (line.margins !== undefined) {
                            for (var pos in line.margins) {
                                badgeText += pos + ':' + (line.margins[pos] == '' ? 0 : parseFloat(line.margins[pos]) + 'px') + ';';
                            }
                        }
                        if (line.shadow !== undefined && line.shadow == '1') {
                            badgeText += 'text-shadow: 0.0625em 0.0625em 0.0625em #333;';
                        } else if (line.shadow == '0') {
                            badgeText += 'text-shadow: none;';
                        }
                        if (line.width !== undefined) {
                            badgeText += 'width:' + (line.width !== '' && line.width !== 'auto' && line.width !== '0' ? line.width + 'px' : 'auto') + ';';
                        }
                        badgeText += '">';
                        badgeText += line.value !== undefined ? line.value : '';
                        badgeText += '</span>';
                        if (!textZIndex) {
                            textZIndex = zIndex;
                        }
                        textK++;
                    }
                    /* Изображение */
                    else if (line.type == 'image') {
                        image += "<img data-pos='" + k + "' src='" + (line.src.indexOf('%plugin_url%') !== -1 ? line.src.replace(/%plugin_url%/, $.autobadge.pluginUrl) : line.src) + "' style='position: absolute; z-index:" + zIndex + ";";
                        if (line.width !== undefined && line.width !== 0 && line.width !== '') {
                            image += "max-width: " + line.width + 'px;';
                        }
                        if (line.height !== undefined && line.height !== 0 && line.height !== '') {
                            image += "max-height: " + line.height + 'px;';
                        }
                        if (line.top === undefined && line.right === undefined && line.bottom === undefined && line.left === undefined) {
                            line.top = 0;
                            line.right = 0;
                        }
                        for (var j in attrs) {
                            if (line[attrs[j]] !== undefined) {
                                image += attrs[j] + ": " + (line[attrs[j]] !== '' ? parseFloat(line[attrs[j]]) : 0) + 'px;';
                            }
                        }
                        image += "' />";
                    }
                    k++;
                }
            });
            if (styles.multiline === undefined && !badgeText) {
                $(".badge-text").addClass("multiline fake-multiline");
                badgeText += "<span></span>";
            }
            var liveRibbonTextBlock = $(".live-preview ." + this.activeClass + " .badge-text-block"),
                liveRibbonDashedLine = $(".live-preview ." + this.activeClass + " .badge-dashed-line");
            liveRibbonTextBlock.html(badgeText);
            liveRibbonDashedLine.length && liveRibbonDashedLine.css('z-index', textZIndex);
            this.activeRibbon == 'ribbon-6' && liveRibbonTextBlock.css('z-index', textZIndex);
            image && $(".live-preview ." + $.autobadge_appearance.activeClass + (this.activeRibbon == 'ribbon-4' ? ' .badge-text-block' : '')).append(image);
        }

        /* Хвост */
        var tail = $(".live-preview ." + this.activeClass + " .autobadge-pl-tail");
        if (styles.additional !== undefined && ((styles.additional.tail !== undefined && styles.additional.tail.code !== undefined) || (styles.additional.all_tails !== undefined && styles.additional.all_tails.code !== undefined)) && !tail.length) {
            var stylesTail = styles.additional.tail !== undefined ? styles.additional.tail : (styles.additional.all_tails !== undefined ? styles.additional.all_tails : '');
            if (stylesTail !== '' && stylesTail.code !== undefined && !tail.length) {
                liveRibbon.append(stylesTail.code);
            }
        }

        /* Генерируем стили */
        css += '<style>';

        /* Рамка */
        var border = '';
        if (styles.border !== undefined) {
            border += 'border:' + styles.border.width + "px " + styles.border.style + ' #' + styles.border.color + ';';
        }

        /* Тень */
        var boxShadow = '';
        if (styles['box-shadow'] !== undefined) {
            var shadow = (typeof styles['box-shadow']['x-offset'] !== 'undefined' ? styles['box-shadow']['x-offset'] + 'px' : 0) +
                (typeof styles['box-shadow']['y-offset'] !== 'undefined' ? ' ' + styles['box-shadow']['y-offset'] + 'px' : ' 0') +
                (typeof styles['box-shadow']['blur'] !== 'undefined' ? ' ' + styles['box-shadow']['blur'] + 'px' : ' 0') +
                (typeof styles['box-shadow']['spread'] !== 'undefined' ? ' ' + styles['box-shadow']['spread'] + 'px' : ' 0') +
                (typeof styles['box-shadow']['color'] !== 'undefined' ? ' #' + styles['box-shadow']['color'] : ' #000000') +
                (typeof styles['box-shadow']['inset'] !== 'undefined' && parseInt(styles['box-shadow']['inset']) ? ' inset' : '') + ';';
            boxShadow += '-webkit-box-shadow:' + shadow + '-moz-box-shadow:' + shadow + 'box-shadow:' + shadow;
        }

        /* Задний фон */
        if (styles.background !== undefined) {
            var backgroundTransparency = styles.background.transparency;
            var backgroundColor = this.getRgba(styles.background.color_rgb, styles.background.color !== undefined ? styles.background.color : 'ff0000', styles.background.transparency);
            var backgroundColorStart = (styles.background.type == 'gradient' ? this.getRgba(styles.background.gradient.start_color_rgb, styles.background.gradient.start, styles.background.gradient.transparency) : backgroundColor);
            var background = 'background: ' + (styles.background.type == 'transparent' ? 'transparent' : backgroundColorStart) + ';';
            if (styles.background.type == 'gradient') {
                backgroundTransparency = styles.background.gradient.transparency;
                var startGradient = this.getRgba(styles.background.gradient.start_color_rgb, styles.background.gradient.start, styles.background.gradient.transparency);
                var endGradient = this.getRgba(styles.background.gradient.end_color_rgb, styles.background.gradient.end, styles.background.gradient.transparency);
                if (styles.background.gradient.orientation == 'linear') {
                    var gradientType = styles.background.gradient.type == 'to_bottom' ? 'top' : (styles.background.gradient.type == 'to_left' ? 'right' : (styles.background.gradient.type == 'to_right' ? 'left' : (styles.background.gradient.type == 'to_top' ? 'bottom' : '')));
                    var gradientType2 = styles.background.gradient.type == 'to_bottom' ? 'to bottom' : (styles.background.gradient.type == 'to_left' ? 'to left' : (styles.background.gradient.type == 'to_right' ? 'to right' : (styles.background.gradient.type == 'to top' ? 'to top' : '')));
                    background += 'background: -moz-linear-gradient(' + gradientType + ', ' + startGradient + ' 0%, ' + endGradient + ' 100%);';
                    background += 'background: -ms-linear-gradient(' + gradientType + ', ' + startGradient + ' 0%, ' + endGradient + ' 100%);';
                    background += 'background: -o-linear-gradient(' + gradientType + ', ' + startGradient + ' 0%, ' + endGradient + ' 100%);';
                    background += 'background: -webkit-linear-gradient(' + gradientType + ', ' + startGradient + ' 0%,' + endGradient + ' 100%);';
                    background += 'background: linear-gradient(' + gradientType2 + ', ' + startGradient + ' 0%,' + endGradient + ' 100%);';
                } else {
                    var gradientType = styles.background.gradient.type == 'to_bottom' ? 'top' : (styles.background.gradient.type == 'to_left' ? 'right' : (styles.background.gradient.type == 'to_right' ? 'left' : (styles.background.gradient.type == 'to_top' ? 'bottom' : '')));
                    background += 'background: -moz-radial-gradient(center ' + gradientType + ', circle farthest-side, ' + startGradient + ' 0%, ' + endGradient + ' 100%);';
                    background += 'background: -ms-radial-gradient(center ' + gradientType + ', circle farthest-side, ' + startGradient + ' 0%, ' + endGradient + ' 100%);';
                    background += 'background: -o-radial-gradient(center ' + gradientType + ', circle farthest-side, ' + startGradient + ' 0%, ' + endGradient + ' 100%);';
                    background += 'background: -webkit-radial-gradient(center ' + gradientType + ', circle farthest-side, ' + startGradient + ' 0%,' + endGradient + ' 100%);';
                    background += 'background: radial-gradient(circle farthest-side at center ' + gradientType + ', ' + startGradient + ' 0%,' + endGradient + ' 100%);';
                }
            }
        }

        /* 4 хвоста */
        if (styles.additional !== undefined && styles.additional.all_tails !== undefined) {
            var tail = styles.additional.all_tails;
            if (tail.type == 'hide' || !tail.position.length || styles.size.width < 101) {
                liveRibbon.removeClass('with-tail');
            } else {
                liveRibbon.addClass('with-tail');
            }

            if (tail.type !== 'hide' && tail.position.length) {
                var tailSize = this.getTailSize($(".live-preview").outerWidth(), styles.size.width);
                var borderWidth = styles.border !== undefined ? (-1) * parseInt(styles.border.width) : 0;
                var tailsPosition = {top_right: 1, top_left: 1, bottom_right: 1, bottom_left: 1};
                var doubleSize = ($.inArray('bottom_left', styles.additional.all_tails.position) === -1 && $.inArray('top_left', styles.additional.all_tails.position) === -1) || ($.inArray('bottom_right', styles.additional.all_tails.position) === -1 && $.inArray('top_right', styles.additional.all_tails.position) === -1);
                var tailWidth = Math.ceil((Math.abs(tailSize) / 2 + (styles.border !== undefined ? parseInt(styles.border.width) / 2 : 0))) * (doubleSize ? 2 : 1);
                var offset = (-1) * tailWidth * 2 + borderWidth;
                var tailColor = this.getRgba(tail.color_rgb, tail.color, backgroundTransparency);
                for (var i in tail.position) {
                    switch (tail.position[i]) {
                        case 'top_right':
                            css += '.' + className + ':after{';
                            css += 'border-color: transparent transparent ' + tailColor + ' ' + tailColor + ';';
                            css += 'top' + ': ' + offset + 'px;';
                            css += 'right' + ': ' + borderWidth + 'px;';
                            break;
                        case 'top_left':
                            css += '.' + className + ':before{';
                            css += 'border-color: transparent ' + tailColor + ' ' + tailColor + ' transparent;';
                            css += 'top' + ': ' + offset + 'px;';
                            css += 'left' + ': ' + borderWidth + 'px;';
                            break;
                        case 'bottom_right':
                            css += '.' + className + ' .autobadge-pl-tail:after{';
                            css += 'border-color: ' + tailColor + ' transparent transparent ' + tailColor + ';';
                            css += 'bottom' + ': ' + offset + 'px;';
                            css += 'right' + ': ' + borderWidth + 'px;';
                            break;
                        case 'bottom_left':
                            css += '.' + className + ' .autobadge-pl-tail:before{';
                            css += 'border-color: ' + tailColor + ' ' + tailColor + ' transparent transparent;';
                            css += 'bottom' + ': ' + offset + 'px;';
                            css += 'left' + ': ' + borderWidth + 'px;';
                            break;
                    }
                    css += 'border-width: ' + tailWidth + 'px;';
                    css += '}';
                    tailsPosition[tail.position[i]] !== undefined && delete tailsPosition[tail.position[i]];
                }
                if (!$.isEmptyObject(tailsPosition)) {
                    $.each(tailsPosition, function (j, v) {
                        if (j == 'top_right') {
                            css += '.' + className + ':after';
                        } else if (j == 'top_left') {
                            css += '.' + className + ':before';
                        } else if (j == 'bottom_right') {
                            css += '.' + className + ' .autobadge-pl-tail:after';
                        } else if (j == 'bottom_left') {
                            css += '.' + className + ' .autobadge-pl-tail:before';
                        }
                        css += '{ border: 0; }';
                    });
                }
            }
        }

        css += '.' + className + '{';
        if (styles.background !== undefined && styles.background.element === undefined) {
            css += background;
            if (styles.border !== undefined) {
                css += border;
            }
        }

        /* Тень */
        if (styles['box-shadow'] !== undefined && styles['box-shadow'].element === undefined) {
            css += boxShadow;
        }

        /* Размер */
        if (styles.size.type !== 'input' && defaultStyles.size.values !== undefined) {
            var ribbonSize = defaultStyles.size.values[styles.size.height][styles.size.width];
        }
        if (styles.size.type == 'input') {
            styles.size.width = (styles.size.width !== '' ? styles.size.width : (styles.size.autowidth !== undefined ? 'auto' : defaultStyles.size.width));
            styles.size.height = (styles.size.height !== '' ? styles.size.height : (styles.size.autowidth !== undefined ? 'auto' : defaultStyles.size.width));
            css += 'width:' + (styles.size.width !== '' && styles.size.width !== 'auto' ? (styles.size.width + (styles.size.width_percentage !== undefined ? '%' : 'px')) : (styles.size.width == 'auto' ? 'auto' : styles.size.width)) + ';';
            css += 'height:' + (styles.size.height !== '' && styles.size.height !== 'auto' ? (styles.size.height + 'px') : styles.size.height) + ';';
            css += 'max-width:' + (styles.size['max-width'] !== undefined && styles.size['max-width'] !== '' ? styles.size['max-width'] + 'px' : 'none') + ';';
            css += 'max-height:' + (styles.size['max-height'] !== undefined && styles.size['max-height'] !== '' ? styles.size['max-height'] + 'px' : 'none') + ';';
        } else if (typeof ribbonSize !== 'undefined') {
            if ($.isArray(styles.size.keys.root)) {
                for (var i in styles.size.keys.root) {
                    css += styles.size.keys.root[i] + ':' + ribbonSize[0] + 'px;';
                }
            } else {
                css += styles.size.keys.root + ':' + ribbonSize[0] + 'px;';
            }
        }

        var settingsWidth = styles.size.width !== 'auto' ? parseFloat(styles.size.width) : 0;
        var settingsHeight = styles.size.height !== 'auto' ? parseFloat(styles.size.height) : 0;

        /* Расположение */
        if (styles.position !== undefined) {
            /* Выбор одного из вариантов */
            var margins = {top: 0, right: 0, bottom: 0, left: 0};
            if (styles.position.value !== undefined) {
                var parts = styles.position.value ? styles.position.value.split('_') : ['top', 'right'];
                for (var i in parts) {
                    switch (parts[i]) {
                        case 'top':
                            css += 'top:';
                            if (typeof tailWidth !== 'undefined' && ($.inArray('top_left', styles.additional.all_tails.position) !== -1 || $.inArray('top_right', styles.additional.all_tails.position) !== -1)) {
                                css += 2 * tailWidth;
                            } else {
                                css += 0;
                            }
                            css += 'px;';
                            break;
                        case 'right':
                            css += 'right:0;';
                            break;
                        case 'left':
                            css += 'left:0;';
                            break;
                        case 'bottom':
                            css += 'bottom:';
                            if (typeof tailWidth !== 'undefined' && ($.inArray('bottom_left', styles.additional.all_tails.position) !== -1 || $.inArray('bottom_right', styles.additional.all_tails.position) !== -1)) {
                                css += 2 * tailWidth;
                            } else {
                                css += 0;
                            }
                            css += 'px;';
                            break;
                        case 'center':
                            if (i == '0') {
                                css += 'top:50%;';
                                if (styles.size.type == 'input') {
                                    margins['top'] += (-1) * parseFloat(settingsHeight / 2);
                                }
                                /* Если высота неопределена, вычислим центр при помощи Javascript */
                                if (styles.size.height == 'auto') {
                                    liveRibbon.addClass('autoposition-h');
                                }
                            } else {
                                /* Если ширина неопределена, вычислим центр при помощи Javascript */
                                if (styles.size.width == 'auto') {
                                    liveRibbon.addClass('autoposition-w');
                                }
                                if (typeof tailWidth !== 'undefined' && $.inArray('bottom_left', styles.additional.all_tails.position) === -1 && $.inArray('top_left', styles.additional.all_tails.position) === -1) {
                                    css += 'left: 0;';
                                } else if (typeof tailWidth !== 'undefined' && $.inArray('bottom_right', styles.additional.all_tails.position) === -1 && $.inArray('top_right', styles.additional.all_tails.position) === -1) {
                                    css += 'right: 0;';
                                } else {
                                    css += 'left:' + (styles.size.width_percentage !== undefined ? 50 - settingsWidth / 2 : 50) + '%;';
                                }
                                if (styles.size.width_percentage !== undefined && styles.border !== undefined && styles.border.width > 0) {
                                    margins['left'] += (-1) * styles.border.width;
                                }
                                if (styles.size.type == 'input' && styles.size.width_percentage === undefined) {
                                    margins['left'] += (-1) * parseFloat(settingsWidth / 2);
                                }
                            }
                            break;
                    }
                }
            }
            /* Ручной отступ */
            for (var pos in styles.position.margins) {
                css += 'margin-' + pos + ':' + (parseFloat(styles.position.margins[pos]) + margins[pos]) + 'px;';
            }
            /* Атрибуты для автопозиционирования */
            liveRibbon.attr('data-mtop', styles.size.height == 'auto' ? styles.position.margins['top'] : 0);
            liveRibbon.attr('data-mleft', styles.size.width == 'auto' ? styles.position.margins['left'] : 0);
        }
        /* Радиус */
        if (styles.additional !== undefined && styles.additional.radius !== undefined) {
            if (styles.additional.radius.attributes !== undefined) {
                for (var i in styles.additional.radius.attributes) {
                    css += styles.additional.radius.attributes[i] + ':' + (parseInt(styles.additional.radius.value) ? styles.additional.radius.value : 0) + 'px;';
                }
            } else {
                for (var i in styles.additional.radius.value) {
                    css += 'border-' + i + '-radius' + ':' + (parseInt(styles.additional.radius.value[i]) ? styles.additional.radius.value[i] : 0) + 'px;';
                }
            }
        }
        css += '}';

        /* Задний фон, если указан конкретный элемент */
        if (styles.background !== undefined && styles.background.element !== undefined) {
            css += '.' + className + ' .' + styles.background.element + '{' + background;
            if (styles.border !== undefined) {
                css += border;
            }
            css += '}';
        }

        /* Задний фон зависимых элементов */
        if (styles.background !== undefined && styles.background.elements !== undefined) {
            for (var attr in styles.background.elements) {
                css += '.' + className + (styles.background.element !== undefined ? ' .' + styles.background.element : '') + attr + '{';
                if ($.isArray(styles.background.elements[attr])) {
                    for (var j in styles.background.elements[attr]) {
                        css += styles.background.elements[attr][j] + ':' + (styles.background.type == 'transparent' ? 'transparent' : (styles.background.type == 'gradient' ? endGradient : backgroundColor)) + ';';
                    }
                } else {
                    css += styles.background.elements[attr] + ':' + (styles.background.type == 'transparent' ? 'transparent' : (styles.background.type == 'gradient' ? endGradient : backgroundColor)) + ';';
                }
                css += '}';
            }
        }

        /* Тень */
        if (styles['box-shadow'] !== undefined && styles['box-shadow'].element !== undefined) {
            css += '.' + className + ' .' + styles.background.element + '{';
            css += boxShadow;
            css += '}';
        }

        /* Зависимость элементов наклейки от размеров */
        if (styles.background !== undefined && styles.size !== undefined && styles.size.ratio !== undefined && styles.size.type == 'input') {
            var color = (styles.background.type == 'gradient' ? endGradient : backgroundColor);
            for (var elem in styles.size.ratio) {
                css += '.' + className + elem + '{';
                for (var attr in styles.size.ratio[elem]) {
                    css += attr + ': ' + (styles.size.width_ratio !== undefined && styles.size.width_ratio !== '0' ? styles.size.width * styles.size.width_ratio : styles.size.height * styles.size.height_ratio) + 'px solid ' + (styles.size.ratio[elem][attr] !== 'transparent' ? color : 'transparent') + ';';
                }
                css += '}';
            }
        }

        /* Язык */
        if (styles.additional !== undefined && styles.additional.tongue !== undefined) {
            var tongueSize = parseInt(styles.additional.tongue.size);
            tongueSize = (tongueSize < 5 ? 5 : tongueSize);
            for (var elem in styles.additional.tongue.elements) {
                css += '.' + className + elem + '{';
                for (var attr in styles.additional.tongue.elements[elem]) {
                    css += attr + ':-' + tongueSize + 'px;';
                    css += styles.additional.tongue.elements[elem][attr] + ':' + tongueSize + 'px;';
                }
                css += '}';
            }
        }

        /* Штрихпунктирная линия */
        if (styles.additional !== undefined && styles.additional.dashed_line !== undefined) {
            css += '.' + className + ' .badge-dashed-line:after, .' + className + ' .badge-dashed-line:before {';
            if (styles.additional.dashed_line.type == 'hide') {
                css += 'border: 0 none;';
            } else {
                css += 'border-color: #' + styles.additional.dashed_line.color + ';';
            }
            css += '}';
            /* Радиус */
            if (styles.additional !== undefined && styles.additional.radius !== undefined) {
                css += '.' + className + ' .badge-dashed-line {';
                if (styles.additional.radius.attributes !== undefined) {
                    for (var i in styles.additional.radius.attributes) {
                        css += styles.additional.radius.attributes[i] + ':' + (parseInt(styles.additional.radius.value) ? styles.additional.radius.value : 0) + 'px;';
                    }
                } else {
                    for (var i in styles.additional.radius.value) {
                        css += 'border-' + i + '-radius' + ':' + (parseInt(styles.additional.radius.value[i]) ? styles.additional.radius.value[i] : 0) + 'px;';
                    }
                }
                css += '}';
            }
            if (this.activeRibbon == 'ribbon-3' && (styles.size.width == 'auto' || styles.size.height == 'auto')) {
                css += '.' + className + ' .badge-text-block{ padding:';
                css += (styles.size.height == 'auto' ? (styles.additional.dashed_line.type !== 'hide' && (styles.id == 'ribbon-3-rl' || styles.id == 'ribbon-3-lr') ? '10px' : '5px') : '0') + ' ';
                css += (styles.size.width == 'auto' ? (styles.additional.dashed_line.type !== 'hide' && (styles.id == 'ribbon-3-bt' || styles.id == 'ribbon-3') ? '10px' : '5px') : '0');
                css += '}';
            }
        }
        /* Расположение текста */
        if (styles.torientation !== undefined) {
            var valueOrient = styles.torientation == 'vertical' ? 'rotate(-90deg)' : (styles.torientation == 'vertical_revert' ? 'rotate(-270deg)' : ($.isNumeric(styles.torientation) ? 'rotate(' + styles.torientation + 'deg)' : 'none'));
            css += '.' + className + ' .badge-text-block{';
            if (this.activeRibbon == 'ribbon-6' && styles.torientation !== 'horizontal') {
                css += 'position: relative;';
            }
            css += '-webkit-transform:' + valueOrient + '; -moz-transform:' + valueOrient + '; -ms-transform:' + valueOrient + '; -o-transform:' + valueOrient + '; transform:' + valueOrient + ';';
            css += '}';
        }

        /* Размер */
        if (typeof ribbonSize !== 'undefined') {
            /* Обрабатываем зависимые значения размера */
            var c = 0;
            var activeAttr = '';
            for (var i in styles.size.keys) {
                if (i == 'root') {
                    continue;
                }
                /* Сохраняем активный атрибут, для которого создаем стили, чтобы несколько раз его не прописывать в CSS */
                if (activeAttr !== i) {
                    activeAttr = i;
                    css += '.' + className + ' .' + activeAttr + '{';
                } else {
                    css += '}';
                }
                if ($.isArray(styles.size.keys[i])) {
                    for (var j in styles.size.keys[i]) {
                        c++;
                        css += styles.size.keys[i][j] + ':' + ribbonSize[c] + 'px;';
                    }
                } else {
                    c++;
                    css += styles.size.keys[i] + ':' + ribbonSize[c] + 'px;';
                }
            }
            css += '}';
            /* Изменяем высоту */
            if (styles.size.height_element !== undefined) {
                for (var i in styles.size.height_element) {
                    css += '.' + className + ' .' + i + '{';
                    css += styles.size.height_element[i] + ":" + styles.size.height + 'px;';
                    css += '}';
                }
            }
        }

        /* Хвост */
        if (styles.additional !== undefined && styles.additional.tail !== undefined) {
            var tail = styles.additional.tail;
            if (tail.code !== undefined) {
                css += '.' + className + ' .autobadge-pl-tail{';
                css += 'display: ' + (tail.type == 'hide' ? 'none' : 'block');
                css += '}';
            } else {
                if (tail.type == 'hide') {
                    liveRibbon.removeClass('with-tail');
                } else {
                    liveRibbon.addClass('with-tail');
                }
            }
            if (tail.type !== 'hide') {
                css += '.' + className + (tail.code !== undefined ? ' .autobadge-pl-tail:before{' : ':after{');
                css += 'border-width: ' + tail.size + 'px;';
                var offset = styles.border !== undefined ? (-1) * parseInt(styles.border.width) : 0;
                var tailColor = this.getRgba(tail.color_rgb, tail.color, backgroundTransparency);
                switch (tail.position) {
                    case 'top_right':
                        css += 'border-color: transparent transparent ' + tailColor + ' ' + tailColor + ';';
                        css += (styles.orientation == 'top_bottom' ? 'right' : 'top') + ': -' + (tail.size * 2 - offset) + 'px;';
                        css += (styles.orientation == 'top_bottom' ? 'top' : 'right') + ': ' + offset + 'px;';
                        break;
                    case 'top_left':
                        css += 'border-color: transparent ' + tailColor + ' ' + tailColor + ' transparent;';
                        css += (styles.orientation == 'top_bottom' ? 'left' : 'top') + ': -' + (tail.size * 2 - offset) + 'px;';
                        css += (styles.orientation == 'top_bottom' ? 'top' : 'left') + ': ' + offset + 'px;';
                        break;
                    case 'bottom_right':
                        css += 'border-color: ' + tailColor + ' transparent transparent ' + tailColor + ';';
                        css += (styles.orientation == 'bottom_top' ? 'right' : 'bottom') + ': -' + (tail.size * 2 - offset) + 'px;';
                        css += (styles.orientation == 'bottom_top' ? 'bottom' : 'right') + ': ' + offset + 'px;';
                        break;
                    case 'bottom_left':
                        css += 'border-color: ' + tailColor + ' ' + tailColor + ' transparent transparent;';
                        css += (styles.orientation == 'bottom_top' ? 'left' : 'bottom') + ': -' + (tail.size * 2 - offset) + 'px;';
                        css += (styles.orientation == 'bottom_top' ? 'bottom' : 'left') + ': ' + offset + 'px;';
                        break;
                }
                css += '}';
            }
        }
        /* Хвосты */
        if (styles.additional !== undefined && styles.additional.tails !== undefined) {
            if (styles.additional.tails.type == 'hide') {
                css += '.' + className + ' .badge-text-block:after, .' + className + ' .badge-text-block:before { border: 0 none; }';
                liveRibbon.addClass('without-tail');
            } else {
                liveRibbon.removeClass('without-tail');
            }
        }

        css += '</style>';
        $("#badge-styles .style-" + this.activeClass + '-' + ribbonClass).html(css);
        $.autobadge.autoSizeBadges(styles);
    },
    getTailSize: function (contW, badgeW) {
        return Math.ceil((1 - badgeW / 100) * contW / 4) * 2;
    },
    /* Выбор основных настроек внешнего вида */
    selectMainMenu: function (select) {
        var column = $("#" + select.value + "-menu-column");
        if (column.length) {
            /* Сбрасываем все настройки */
            this.reset();
            column.addClass('show');

            this.isShowColorPicker(select) && this.showColorPicker();

            /* Установить дефолтные (или сохраненные) настройки */
            this.setValues(select.value, this.ribbons[this.activeClass][this.activeRibbon][select.value]);
            !column.hasClass('inited') && column.addClass('inited');
        }
    },
    /* Установить значения для всех полей */
    setValues: function (column, styles) {
        switch (column) {
            case "size":
                $("#" + column + "-menu-column " + (styles.type == 'input' ? '.s-size' : ".s-width, #" + column + "-menu-column .s-height, .s-toggle-size")).hide();
                $("#" + column + "-menu-column " + (styles.type == 'input' ? ".s-width, #" + column + "-menu-column .s-height, .s-toggle-size" : '.s-size')).css('display', 'inline-block');
                $("#" + column + "-menu-column " + (styles.type == 'input' ? '.s-width' : '.s-size.w') + " input").val(styles.width);
                $("#" + column + "-menu-column " + (styles.type == 'input' ? '.s-height' : '.s-size.h') + " input").val(styles.height);

                $("#" + column + "-menu-column .s-width span").text(styles.width_percentage !== undefined ? '%' : 'px');

                if (styles['max-width'] !== undefined || styles['max-height'] !== undefined) {
                    $("#" + column + "-menu-column .s-max-size").show();
                    styles['max-width'] !== undefined && $("#" + column + "-menu-column .s-max-size input[name='max-width']").val(styles['max-width']);
                    styles['max-height'] !== undefined && $("#" + column + "-menu-column .s-max-size input[name='max-height']").val(styles['max-height']);
                } else {
                    $("#" + column + "-menu-column .s-max-size").hide();
                }

                if (styles.type == 'range' && styles.values !== undefined) {
                    $("#" + column + "-menu-column .s-size.w input").attr("max", Object.values(styles.values)[0].length - 1);
                }
                break;
            case "position":
                if (styles.value !== undefined) {
                    var posTempInput = $("#" + column + "-menu-column .position-template input[value='" + styles.value + "']");
                    $(".position-radio-block").show().find("input").show();
                    posTempInput.prop('checked', true);
                    var parts = posTempInput.data('hide').split(",");
                } else {
                    $(".position-radio-block").hide();
                    var parts = $.makeArray($.map(['left', 'right', 'top', 'bottom'], function (i) {
                        if (styles.margins[i] === undefined) {
                            return i;
                        }
                    }));
                }
                $(".position-value-block").show();

                this.togglePositionMargins(parts);
                for (var i in styles.margins) {
                    $(".position-value-block.s-" + i + " input").val(styles.margins[i]);
                }
                if (styles.avail_positions !== undefined) {
                    var allPositions = ['top_left', 'top_center', 'top_right', 'center_left', 'center_center', 'center_right', 'bottom_left', 'bottom_center', 'bottom_right'];
                    for (var i in allPositions) {
                        if ($.inArray(allPositions[i], styles.avail_positions) === -1) {
                            $("#" + column + "-menu-column .position-template input[value='" + allPositions[i] + "']").hide();
                        }
                    }
                }
                break;
            case "text":
                if (styles.text !== undefined) {
                    var badgeLine = $(".badge-line"),
                        attachCont = $('.attachment-container'),
                        custCont = $('.custom-container'),
                        textButtons = $(".text-buttons"),
                        firstTextLine = badgeLine.first(),
                        firstImageLine = attachCont.first(),
                        firstCustomLine = custCont.first();
                    badgeLine.not(firstTextLine).remove();
                    attachCont.not(firstImageLine).remove();
                    custCont.not(firstCustomLine).remove();
                    firstTextLine.add(firstImageLine).add(firstCustomLine).hide();
                    var k = 0;
                    $.each(styles.text, function (i, line) {
                        if (line === undefined) {
                            return true;
                        }
                        var type = line.type;
                        var firstLine = type == 'text' ? firstTextLine : (type == 'image' ? firstImageLine : firstCustomLine);
                        var clone = firstLine.clone();
                        if (type == 'text') {
                            clone.find("textarea[name='value']").val(line.value);
                            var color = line.color !== undefined ? line.color : '#ffffff';
                            clone.find(".color-icon").attr('data-color', color).css('background', color);
                            var attrs = ['size', 'family', 'style', 'align'];
                            for (var j in attrs) {
                                clone.find("select[name='" + attrs[j] + "']").val(line[attrs[j]]);
                            }
                            clone.find("input[name='width']").val(line.width !== '' || line.width !== 'auto' ? line.width : '');
                            clone.find("input[name='shadow']").prop("checked", line.shadow !== undefined ? parseInt(line.shadow) : true);
                            for (var pos in line.margins) {
                                clone.find("input[name='" + pos + "']").val(line.margins[pos]);
                            }

                        } else if (type == 'textarea') {
                            clone.find("textarea").val(line.content);
                        } else {
                            if (line.src !== undefined && line.src !== '') {
                                if (line.src.indexOf('%plugin_url%') !== -1) {
                                    line.src = line.src.replace(/%plugin_url%/, $.autobadge.pluginUrl);
                                }
                                clone.addClass('has-image').find("input[type='text']").val(line.src);
                                if (clone.find("img").length) {
                                    clone.find("img").attr("src", line.src);
                                } else {
                                    clone.find(".attachment-block").append("<img src='" + line.src + "' class='attachment-file'>");
                                }
                            }
                            clone.find(".tabs").each(function () {
                                var that = $(this);
                                var firstLi = that.find("li:first");
                                firstLi.removeClass("no-tab").addClass("selected").siblings().removeClass("selected").addClass("no-tab");
                                that.next().attr("name", firstLi.find('a').data('tab')).val(0);
                            });
                            var attrs = ['width', 'height', 'top', 'right', 'bottom', 'left'];
                            for (var j in attrs) {
                                if (line[attrs[j]] !== undefined) {
                                    var tab = clone.find(".tabs a[data-tab='" + attrs[j] + "']");
                                    if (tab.length) {
                                        tab.parent().addClass("selected").removeClass('no-tab').siblings().removeClass('selected').addClass('no-tab').parent().next('input').attr("name", attrs[j]).val(line[attrs[j]] ? line[attrs[j]] : 0);
                                    } else {
                                        clone.find("input[name='" + attrs[j] + "']").val(line[attrs[j]]);
                                    }
                                }
                            }
                        }
                        textButtons.before(clone.show());
                        type == 'text' && $.autobadge.initColorIcon(clone.find(".color-icon"));
                        type !== 'text' && $.autobadge.initFileupload(clone.find('.fileupload-attachment'));
                        if (type == 'textarea') {
                            textButtons.hide();
                        } else {
                            textButtons.show();
                        }
                        clone.attr("data-pos", k);
                        k++;
                    });
                }
                break;
            case "additional":
                /* Скрываем опции, которых нет у наклейки */
                $(".additional-menu-select option").hide();
                $.each(styles, function (optionK, val) {
                    $(".additional-menu-select option[value='" + optionK + "']").show();
                    if (optionK == 'radius') {
                        if (val.attributes !== undefined) {
                            $("#" + column + "-menu-column .radius-single").show().find("input").val(val.value);
                            $("#" + column + "-menu-column .radius-all").hide();
                        } else {
                            $("#" + column + "-menu-column .radius-all").show().children().hide();
                            for (var i in val.value) {
                                $("#" + column + "-menu-column .radius-all input[name='value:" + i + "']").val(val.value[i]).closest(".margin-block").show();
                            }
                            $("#" + column + "-menu-column .radius-single").hide();
                        }
                    } else if (optionK == 'dashed_line') {
                        $("#dashed_line-appearance-column select:not('.inherit')").val(val.type);
                    } else if (optionK == 'tail') {
                        $("#tail_size-appearance-column input[name='size']").val(val.size);
                        $("#tail_position-appearance-column option").hide();
                        for (var i in val.avail_position) {
                            $("#tail_position-appearance-column option[value='" + val.avail_position[i] + "']").show();
                        }
                        $("#tail_position-appearance-column select").val(val.position);
                    } else if (optionK == 'tails') {
                        $("#tails-appearance-column select").val(val.type);
                    } else if (optionK == 'all_tails') {
                        $("#alltails_position-appearance-column input").prop('checked', false);
                        if (val.position.length) {
                            for (var i in val.position) {
                                $("#alltails_position-appearance-column input[value='" + val.position[i] + "']").prop('checked', true);
                            }
                        }
                    } else if (optionK == 'tongue') {
                        $("#tongue-appearance-column input[name='size']").val(val.size !== undefined ? val.size : 20);
                    }
                });
                break;
            case "border":
                $("#border-menu-column select[name='width']").val(styles.width);
                $("#border-menu-column select[name='style']").val(styles.style);
                $("#color-picker-column").ColorPickerSetColor(styles.color);
                break;
            case "box-shadow":
                var values = ['x-offset', 'y-offset', 'blur', 'spread'];
                for (var i in values) {
                    $("#box-shadow-menu-column input[name='" + values[i] + "']").val(styles[values[i]]);
                }
                $("#box-shadow-menu-column input[name='inset']").prop('checked', parseInt(styles.inset));
                $("#color-picker-column").ColorPickerSetColor(styles.color);
                break;
            case "background":
                if (styles.gradient !== undefined) {
                    $("#gradient-appearance-column input[name='start']").val(styles.gradient.start).css('backgroundColor', '#' + styles.gradient.start).trigger('input');
                    $("#gradient-appearance-column input[name='end']").val(styles.gradient.end).css('backgroundColor', '#' + styles.gradient.end).trigger('input');
                    $("#gradient-appearance-column select[name='orientation']").val(styles.gradient.orientation);
                    $("#gradient-appearance-column input[name='transparency']").val(styles.gradient.transparency !== undefined ? parseFloat(styles.gradient.transparency) : 1);
                }
                $("#color-picker-column").ColorPickerSetColor(styles.color !== undefined ? styles.color : 'ff0000');
                $("#color-picker-column .s-transparent-block input").val(styles.transparency !== undefined ? parseFloat(styles.transparency) : 1);
            default:
                $("#" + column + "-menu-column select:not('.inherit')").val(typeof styles === 'string' ? styles : styles.type).change();

        }
        if (styles.color !== undefined && this.isShowColorPicker($("#" + column + "-menu-column select"))) {
            this.showColorPicker();
            $("#color-picker-column").ColorPickerSetColor(styles.color);
        }
    },
    /* Скрыть/показать блоки для редактирования отступов наклеек */
    togglePositionMargins: function (hideArray) {

        /* Для наклейки 5 */
        var ribbonStyles = this.ribbons[this.activeClass][this.activeRibbon];
        if (ribbonStyles.additional !== undefined && ribbonStyles.additional.all_tails !== undefined && ribbonStyles.additional.all_tails.position.length) {
            if ($.inArray('top_right', ribbonStyles.additional.all_tails.position) === -1 && $.inArray('bottom_right', ribbonStyles.additional.all_tails.position) === -1) {
                hideArray.push("left");
                var indexRight = hideArray.indexOf('right');
                if (indexRight > -1) {
                    hideArray.splice(indexRight, 1);
                }
            }
        }

        var values = ['top', 'right', 'bottom', 'left'];
        for (var i in values) {
            if ($.inArray(values[i], hideArray) === -1) {
                $(".position-value-block.s-" + values[i]).show();
            } else {
                $(".position-value-block.s-" + values[i]).hide();
            }
        }
    },
    /* Удаление текстовой строки у наклейки */
    removeTextItem: function (btn) {
        var textItem = $(btn).closest(".text-item");
        var sibling = textItem.siblings().first();
        textItem.remove();
        $.autobadge.changeTextItemSort(sibling);
    },
    /* Удаление отступа изображения при смене вкладок */
    removeImageMargin: function (position, pos) {
        if (this.ribbons[this.activeClass][this.activeRibbon]['text'] !== undefined && this.ribbons[this.activeClass][this.activeRibbon]['text'][pos] !== undefined && this.ribbons[this.activeClass][this.activeRibbon]['text'][pos][position] !== undefined) {
            delete this.ribbons[this.activeClass][this.activeRibbon]['text'][pos][position];
        }
    },
    selectColumn: function (select, ignore) {
        var mainMenuValue = $("#s-main-menu").val();

        $(select).closest(".menu-item").find(".column").removeClass('show');
        var column = ($(select).data('parent') && $("#" + $(select).data('parent') + "-appearance-column").length ? $("#" + $(select).data('parent') + "-appearance-column") : $("#" + select.value + "-appearance-column"));
        if (column.length) {
            $(".appearance-columns .appearance-column").removeClass("show");
            column.addClass('show');
        }

        if ($(":selected", select).data('column') !== undefined) {
            $("#" + $(":selected", select).data('column') + "-appearance-column").addClass("show");
        }

        if (this.isShowColorPicker(select) || (select.value == 'dashed_line' && $("#dashed_line-appearance-column select :selected").val() == 'color')) {
            this.showColorPicker();
        } else {
            this.hideColorPicker();
        }
        if (this.isShowTransparency(select)) {
            this.showTransparency();
        } else {
            this.hideTransparency();
        }
        if (!ignore) {
            if ($(select).data('parent') !== undefined) {
                this.ribbons[this.activeClass][this.activeRibbon][mainMenuValue][$(select).data('parent')][$(select).attr("name")] = $(select).is(":input") ? $(select).val() : $(select).find(":selected").val();
            } else if ($(select).attr("name") !== undefined) {
                this.ribbons[this.activeClass][this.activeRibbon][mainMenuValue][$(select).attr("name")] = $(select).find(":selected").val();
            } else {
                this.ribbons[this.activeClass][this.activeRibbon][mainMenuValue] = $(select).find(":selected").val();
            }
        }
        if (mainMenuValue == 'orientation' && this.ribbons[this.activeClass][this.activeRibbon]['orientation'] !== undefined) {
            this.changeRibbonOrientation(this.ribbons[this.activeClass][this.activeRibbon]['orientation']);
        }
        this.refreshActiveRibbon();
    },
    selectAdditionalColumn: function (select) {
        if (select.value !== 'radius' && this.ribbons[this.activeClass][this.activeRibbon]['additional'][select.value]['color'] !== undefined) {
            $("#color-picker-column").ColorPickerSetColor(this.ribbons[this.activeClass][this.activeRibbon]['additional'][select.value]['color']);
        }
        this.selectColumn(select, true);
    },
    /* Изменить направление наклейки */
    changeRibbonOrientation: function (orientation) {
        var mergedRibbon = this.ribbons[this.activeClass][this.activeRibbon]['orientations'][orientation];
        if (mergedRibbon !== undefined && this.ribbons['default_' + mergedRibbon.id] !== undefined) {
            var mergedRibbonId = this.ribbons['default_' + mergedRibbon.id].id;
            /* Очищаем мешающие отображению старые значения */
            this.ribbons[this.activeClass][this.activeRibbon]['background']['elements'] = [];
            this.ribbons[this.activeClass][this.activeRibbon]['size']['ratio'] = [];
            if (this.ribbons[this.activeClass][this.activeRibbon]['additional'] !== undefined) {
                if (this.ribbons[this.activeClass][this.activeRibbon]['additional']['radius'] !== undefined) {
                    if (this.ribbons[this.activeClass][this.activeRibbon]['additional']['radius']['attributes'] !== undefined) {
                        this.ribbons[this.activeClass][this.activeRibbon]['additional']['radius']['attributes'] = [];
                    }
                }
                if (this.ribbons[this.activeClass][this.activeRibbon]['additional']['tongue'] !== undefined) {
                    this.ribbons[this.activeClass][this.activeRibbon]['additional']['tongue']['elements'] = [];
                }
                var tailPosition = (this.ribbons[this.activeClass][this.activeRibbon]['additional'] !== undefined && this.ribbons[this.activeClass][this.activeRibbon]['additional']['tail'] !== undefined ? this.ribbons[this.activeClass][this.activeRibbon]['additional']['tail']['position'] : '');
                var changeRadius = this.ribbons[this.activeClass][this.activeRibbon]['additional']['radius'] !== undefined && this.ribbons[this.activeClass][this.activeRibbon]['additional']['radius']['attributes'] === undefined;
                var oldRadius = (changeRadius ? $.extend(true, {}, this.ribbons[this.activeClass][this.activeRibbon]['additional']['radius']['value']) : '');
                if (changeRadius) {
                    this.ribbons[this.activeClass][this.activeRibbon]['additional']['radius']['value'] = {};
                }
            }
            var oldMargins = this.ribbons[this.activeClass][this.activeRibbon].position !== undefined && this.ribbons[this.activeClass][this.activeRibbon].position.margins !== undefined ? this.ribbons[this.activeClass][this.activeRibbon].position.margins : {};

            /* Объединяем значения наклеек */
            $.extend(true, this.ribbons[this.activeClass][this.activeRibbon], this.ribbons['default_' + mergedRibbon.id]);

            /* Удаляем ненужные значения отступов */
            if (this.ribbons['default_' + mergedRibbon.id].position !== undefined && this.ribbons['default_' + mergedRibbon.id].position.margins !== undefined) {
                var margins = this.ribbons['default_' + mergedRibbon.id].position.margins;
                this.ribbons[this.activeClass][this.activeRibbon].position.margins = {};
                for (var i in margins) {
                    if (oldMargins[i] !== undefined) {
                        this.ribbons[this.activeClass][this.activeRibbon].position.margins[i] = oldMargins[i];
                    } else {
                        this.ribbons[this.activeClass][this.activeRibbon].position.margins[i] = margins[i];
                    }
                }
            }

            if (this.ribbons[this.activeClass][this.activeRibbon]['additional'] !== undefined) {
                var radius = this.ribbons[this.activeClass][this.activeRibbon]['additional']['radius'] !== undefined && this.ribbons[this.activeClass][this.activeRibbon]['additional']['radius']['attributes'] === undefined ? this.ribbons[this.activeClass][this.activeRibbon]['additional']['radius']['value'] : {};
                if (orientation == 'right_left') {
                    tailPosition = (tailPosition == 'top_left' ? 'top_right' : (tailPosition == 'bottom_left' ? 'bottom_right' : tailPosition));
                } else if (orientation == 'left_right') {
                    tailPosition = (tailPosition == 'top_right' ? 'top_left' : (tailPosition == 'bottom_right' ? 'bottom_left' : tailPosition));
                } else if (orientation == 'top_bottom') {
                    tailPosition = (tailPosition == 'bottom_left' ? 'top_left' : (tailPosition == 'bottom_right' ? 'top_right' : tailPosition));
                } else if (orientation == 'bottom_top') {
                    tailPosition = (tailPosition == 'top_left' ? 'bottom_left' : (tailPosition == 'top_right' ? 'bottom_right' : tailPosition));
                }

                if (changeRadius) {
                    for (var i in oldRadius) {
                        var k = i;
                        if (orientation == 'right_left') {
                            k = (i == 'top-left' ? 'top-right' : (i == 'bottom-left' ? 'bottom-right' : i));
                        } else if (orientation == 'left_right') {
                            k = (i == 'top-right' ? 'top-left' : (i == 'bottom-right' ? 'bottom-left' : i));
                        } else if (orientation == 'top_bottom') {
                            k = (i == 'bottom-left' ? 'top-left' : (i == 'bottom-right' ? 'top-right' : i));
                        } else if (orientation == 'bottom_top') {
                            k = (i == 'top-left' ? 'bottom-left' : (i == 'top-right' ? 'bottom-right' : i));
                        }
                        if (radius[k] !== undefined) {
                            radius[k] = oldRadius[i];
                        }
                    }
                }

                /* Если есть хвост у наклейки, меняем его ориентацию */
                if (tailPosition) {
                    this.ribbons[this.activeClass][this.activeRibbon]['additional']['tail']['position'] = tailPosition;
                }

                /* Меняем скругление */
                if (changeRadius) {
                    this.ribbons[this.activeClass][this.activeRibbon]['additional']['radius']['value'] = radius;
                    if (this.activeRibbon == 'ribbon-3') {
                        this.ribbons[this.activeClass][this.activeRibbon]['additional']['radius']['value'] = oldRadius;
                    }
                }
            }

            /* Удаляем старую наклейку */
            var liveRibbon = $(".live-preview ." + this.activeClass),
                targetId = liveRibbon.attr("data-id");
            liveRibbon.remove();
            /* Добавляем новую наклейку с новыми настройками */
            $(this.ribbons[this.activeClass][this.activeRibbon]['construction']).removeClass(this.activeRibbon).addClass(this.activeClass + " " + mergedRibbonId).attr('data-id', targetId).appendTo($(".live-preview"));
            $.autobadge.changeBadgeSort();
        }
    },
    /* Обновить активную наклейку */
    refreshActiveRibbon: function () {
        this.ribbons[this.activeClass] !== undefined && this.generateStyles(this.ribbons[this.activeClass][this.activeRibbon]);
    },
    /* Функции для общего выбора цвета */
    showColorPicker: function () {
        $("#color-picker-column").addClass("show");
    },
    hideColorPicker: function () {
        $("#color-picker-column").removeClass("show");
    },
    showTransparency: function () {
        $("#color-picker-column .s-transparent-block").show();
    },
    hideTransparency: function () {
        $("#color-picker-column .s-transparent-block").hide();
    },
    isShowColorPicker: function (select) {
        return $(":selected", select).data("show-color");
    },
    isShowTransparency: function (select) {
        return $(":selected", select).data("show-transparent");
    },
    /* Сброс настроек наклейки */
    reset: function (all) {
        $(".appearance-columns .show").removeClass('show');
        $(".appearance-columns select" + (!all ? ':not(#s-main-menu)' : '') + " option:selected").prop('selected', false);
        if (all) {
            $(".badge-line:visible").remove();
        }
        this.hideColorPicker();
    },
    hexToRGB: function (hex) {
        var hex = parseInt(((hex.indexOf('#') > -1) ? hex.substring(1) : hex), 16);
        return {r: hex >> 16, g: (hex & 0x00FF00) >> 8, b: (hex & 0x0000FF)};
    },
    getRgba: function (rgbaString, hex, transparency) {
        var rgba = 'rgba(';
        if (typeof rgbaString !== 'undefined' && rgbaString !== '' && rgbaString.split(',').length === 3) {
            rgba += rgbaString;
        } else {
            var rgbObj = this.hexToRGB(hex);
            rgba += rgbObj.r + ',' + rgbObj.g + ',' + rgbObj.b;
        }
        rgba += ',' + (transparency !== undefined ? transparency : 1) + ')';
        return rgba;
    }
};
