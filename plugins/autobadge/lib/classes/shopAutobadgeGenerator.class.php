<?php

class shopAutobadgeGenerator
{

    private static $css = array();
    private static $badge_fonts = array();
    // Дефолтные настройки наклеек
    private static $default_settings = null;

    /**
     * Get css and fonts for badges
     *
     * @return array ('inline_css', 'google_fonts')
     */
    public static function getCss()
    {
        $css = !empty(self::$css) ? implode("", self::$css) : '';
        if ($css) {
            $css = str_replace(';}', '}', $css);
        }
        $google_fonts = array("'Open Sans',sans-serif" => "Open+Sans", "'Open Sans Condensed',sans-serif" => "Open+Sans+Condensed:300", "'Roboto',sans-serif" => "Roboto", "'Roboto Condensed',sans-serif" => "Roboto+Condensed", "'Roboto Slab',serif" => "Roboto+Slab", "'PT Sans',sans-serif" => "PT+Sans", "'Lora',serif" => "Lora", "'Lobster',cursive" => "Lobster", "'Ubuntu',sans-serif" => "Ubuntu", "'Noto Sans',sans-serif" => "Noto+Sans");
        $active_fonts = array();
        foreach (self::$badge_fonts as $bf) {
            if (isset($google_fonts[$bf])) {
                $active_fonts[$bf] = $google_fonts[$bf];
            }
        }
        return array('inline_css' => $css, 'google_fonts' => $active_fonts ? '<link href="https://fonts.googleapis.com/css?family=' . implode('%7C', $active_fonts) . '" class="autobadge-goog-f" data-fonts="' . implode(",", $active_fonts) . '" rel="stylesheet">' : '');
    }

    /**
     * Get css for each badge
     *
     * @return array
     */
    public static function getCssArray()
    {
        return self::$css;
    }

    /**
     * Create badges for rule and css styles
     *
     * @param array $product - product info
     * @param array $conditions - rule conditions
     * @param array $params - rule params
     * @param string $class_name - badge css class name
     * @param string $badge_z_index - badge z-index
     * @return string - badge HTML
     */
    public static function createBadge($product, $conditions, $params, $class_name, $badge_z_index)
    {
        $html = '';
        $settings = $conditions->settings;
        $default_settings = isset(self::$default_settings[$conditions->id]) ? self::$default_settings[$conditions->id] : array();
        $css_class = 'autobadge-pl.' . $class_name;

        $content = $dashed_line_style = $badge_text_block_style = $badge_text = $css_styles = $add_class = $badge_style = $badge_data = '';
        $element_styles = array();

        $badge_data = ' data-badge-id="' . $conditions->id . '" data-page="' . (!empty($product['autobadge-page']) ? shopAutobadgeHelper::secureString($product['autobadge-page']) : 'category') . '" data-type="' . (!empty($product['autobadge-type']) ? shopAutobadgeHelper::secureString($product['autobadge-type']) : 'default') . '"';
        $badge_data .= ' data-product-id="' . $product['product']['product_id'] . '"';

        // z-index наклейки
        $badge_style .= 'z-index:' . $badge_z_index . ';';

        // Текст 
        $text = self::generateText($settings, $product);
        $dashed_line_style .= $text['dashed_line_style'];
        $badge_text_block_style .= $text['badge_text_style'];
        $badge_text .= $text['text'];
        if ($text['image']) {
            if ($conditions->id == 'ribbon-4') {
                $badge_text .= $text['image'];
            } else {
                $content .= $text['image'];
            }
        }

        // Хвост 
        if ((!empty($settings->additional->tail) && isset($settings->additional->tail->code)) || (!empty($settings->additional->all_tails) && isset($settings->additional->all_tails->code))) {
            $tail_obj = !empty($settings->additional->tail) ? $settings->additional->tail : $settings->additional->all_tails;
            $content .= $tail_obj->code;
        }

        // Генерируем стили
        // Рамка 
        $border = '';
        if (isset($settings->border) && $settings->border->width) {
            $border .= 'border:' . $settings->border->width . "px " . $settings->border->style . ' #' . self::shortHexColor($settings->border->color) . ';';
        }

        // Задний фон
        $background_data = self::generateBackground($settings);
        $background = $background_data['styles'];

        // Тень
        $box_shadow = '';
        if (!empty($settings->box_shadow->{'x-offset'}) || !empty($settings->box_shadow->{'y-offset'}) || !empty($settings->box_shadow->blur) || !empty($settings->box_shadow->spread)) {
            $shadow = (!empty($settings->box_shadow->{'x-offset'}) ? $settings->box_shadow->{'x-offset'} . 'px' : 0) .
                (!empty($settings->box_shadow->{'y-offset'}) ? ' ' . $settings->box_shadow->{'y-offset'} . 'px' : ' 0') .
                (!empty($settings->box_shadow->blur) ? ' ' . $settings->box_shadow->blur . 'px' : ' 0') .
                (!empty($settings->box_shadow->spread) ? ' ' . $settings->box_shadow->spread . 'px' : ' 0') .
                (!empty($settings->box_shadow->color) ? ' #' . $settings->box_shadow->color : ' #000000') .
                (!empty($settings->box_shadow->insetor) ? ' inset' : '') . ';';
            $box_shadow .= '-webkit-box-shadow:' . $shadow . '-moz-box-shadow:' . $shadow . 'box-shadow:' . $shadow;
        }

        // 4 хвоста
        $tail_width = 0;
        if (!empty($settings->additional->all_tails)) {
            $tail = $settings->additional->all_tails;
            if ($tail->type !== 'hide' && isset($tail->position) && $settings->size->width > 100) {
                $add_class .= ' with-tail';
            }
            if ($tail->type !== 'hide' && isset($tail->position)) {
                $add_class .= ' adjust-w';
                $badge_data .= ' data-badge-class="' . $class_name . '"';
                $tail_size = self::getTailSize($params['preview_width'], $settings->size->width);
                $border_width = isset($settings->border) ? (-1) * (int) $settings->border->width : 0;
                $tails_position = array('top_right' => 1, 'top_left' => 1, 'bottom_right' => 1, 'bottom_left' => 1);
                $double_size = (!in_array('bottom_left', $tail->position) && !in_array('top_left', $tail->position)) || (!in_array('bottom_right', $tail->position) && !in_array('top_right', $tail->position));
                $tail_width = ceil((abs($tail_size) / 2 + (isset($settings->border) ? intval($settings->border->width) / 2 : 0))) * ($double_size ? 2 : 1);
                $offset = (-1) * $tail_width * 2 + $border_width;
                $tail_color = self::getRgba(isset($tail->color_rgb) ? $tail->color_rgb : '', $tail->color, $background_data['background_transparency']);
                foreach ($tail->position as $tp) {
                    switch ($tp) {
                        case 'top_right':
                            $css_styles .= '.' . $css_class . ':after{';
                            $css_styles .= 'border-color:transparent transparent ' . $tail_color . ' ' . $tail_color . ';';
                            $css_styles .= 'top' . ':' . ($offset ? $offset . 'px' : 0) . ';';
                            $css_styles .= 'right' . ':' . ($border_width ? $border_width . 'px' : 0) . ';';
                            break;
                        case 'top_left':
                            $css_styles .= '.' . $css_class . ':before{';
                            $css_styles .= 'border-color:transparent ' . $tail_color . ' ' . $tail_color . ' transparent;';
                            $css_styles .= 'top' . ':' . ($offset ? $offset . 'px' : 0) . ';';
                            $css_styles .= 'left' . ':' . ($border_width ? $border_width . 'px' : 0) . ';';
                            break;
                        case 'bottom_right':
                            $css_styles .= '.' . $css_class . ' .autobadge-pl-tail:after{';
                            $css_styles .= 'border-color:' . $tail_color . ' transparent transparent ' . $tail_color . ';';
                            $css_styles .= 'bottom' . ':' . ($offset ? $offset . 'px' : 0) . ';';
                            $css_styles .= 'right' . ':' . ($border_width ? $border_width . 'px' : 0) . ';';
                            break;
                        case 'bottom_left':
                            $css_styles .= '.' . $css_class . ' .autobadge-pl-tail:before{';
                            $css_styles .= 'border-color:' . $tail_color . ' ' . $tail_color . ' transparent transparent;';
                            $css_styles .= 'bottom' . ':' . ($offset ? $offset . 'px' : 0) . ';';
                            $css_styles .= 'left' . ':' . ($border_width ? $border_width . 'px' : 0) . ';';
                            break;
                    }
                    $css_styles .= 'border-width:' . $tail_width . 'px;';
                    $css_styles .= '}';
                    if (isset($tails_position[$tp])) {
                        unset($tails_position[$tp]);
                    }
                }
                if (!empty($tails_position)) {
                    foreach ($tails_position as $v => $k) {
                        if ($v == 'top_right') {
                            $css_styles .= '.' . $css_class . ':after,';
                        } else if ($v == 'top_left') {
                            $css_styles .= '.' . $css_class . ':before,';
                        } else if ($v == 'bottom_right') {
                            $css_styles .= '.' . $css_class . ' .autobadge-pl-tail:after,';
                        } else if ($v == 'bottom_left') {
                            $css_styles .= '.' . $css_class . ' .autobadge-pl-tail:before,';
                        }
                    }
                    $css_styles = substr($css_styles, 0, -1) . '{border:0;}';
                }
            }
        }

        if (isset($settings->background) && !isset($settings->background->element)) {
            $badge_style .= $background;
            if (isset($settings->border)) {
                $badge_style .= $border;
            }
        }

        if (isset($settings->box_shadow) && !isset($settings->box_shadow->element)) {
            $badge_style .= $box_shadow;
        }

        if ($settings->size->type !== 'input' && isset($default_settings['size']['values'])) {
            $ribbon_size = $default_settings['size']['values'][$settings->size->height][$settings->size->width];
        }

        // Размер
        if ($settings->size->type == 'input') {
            $badge_style .= 'width:' . ($settings->size->width ? $settings->size->width : 'auto') . (isset($settings->size->width_percentage) ? '%' : ($settings->size->width && $settings->size->width !== 'auto' ? 'px' : '')) . ';';
            $badge_style .= 'height:' . ($settings->size->height ? $settings->size->height : 'auto') . ($settings->size->height && $settings->size->height !== 'auto' ? 'px' : '') . ';';
            $badge_style .= (!empty($settings->size->{'max-width'}) ? 'max-width:' . $settings->size->{'max-width'} . 'px;' : '');
            $badge_style .= (!empty($settings->size->{'max-height'}) ? 'max-height:' . $settings->size->{'max-height'} . 'px;' : '');
        } elseif (!empty($ribbon_size)) {
            if (is_array($settings->size->keys->root)) {
                foreach ($settings->size->keys->root as $v) {
                    $badge_style .= $v . ':' . $ribbon_size[0] . 'px;';
                }
            } else {
                $badge_style .= $settings->size->keys->root . ':' . $ribbon_size[0] . 'px;';
            }
        }

        $settings_width = $settings->size->width !== 'auto' ? (float) $settings->size->width : (float) $default_settings['size']['width'];
        $settings_height = $settings->size->height !== 'auto' ? (float) $settings->size->height : (float) $default_settings['size']['height'];

        // Расположение
        if (isset($settings->position)) {
            // Выбор одного из вариантов
            $margins = array('top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0);
            if (isset($settings->position->value)) {
                $parts = $settings->position->value ? explode("_", $settings->position->value) : array('top', 'right');
                foreach ($parts as $i => $p) {
                    switch ($p) {
                        case 'top':
                            $badge_style .= 'top:';
                            if (!empty($tail_width) && (in_array('top_left', $settings->additional->all_tails->position) || in_array('top_right', $settings->additional->all_tails->position))) {
                                $pos_v = 2 * $tail_width;
                            } else {
                                $pos_v = 0;
                            }
                            $badge_style .= $pos_v . ($pos_v ? 'px' : '') . ';';
                            break;
                        case 'right':
                            $badge_style .= 'right:0;';
                            break;
                        case 'left':
                            $badge_style .= 'left:0;';
                            break;
                        case 'bottom':
                            $badge_style .= 'bottom:';
                            if (!empty($tail_width) && (in_array('bottom_left', $settings->additional->all_tails->position) || in_array('bottom_right', $settings->additional->all_tails->position))) {
                                $pos_v = 2 * $tail_width;
                            } else {
                                $pos_v = 0;
                            }
                            $badge_style .= $pos_v . ($pos_v ? 'px' : '') . ';';
                            break;
                        case 'center':
                            if ($i == '0') {
                                $badge_style .= 'top:50%;';
                                if ($settings->size->type == 'input') {
                                    $margins['top'] += (-1) * floatval(($settings->size->height === 'auto' ? 0 : $settings_height) / 2);
                                }
                                // Если высота неопределена, вычислим центр при помощи Javascript
                                if ($settings->size->height === 'auto') {
                                    $add_class .= ' autoposition-h';
                                }
                            } else {
                                // Если ширина неопределена, вычислим центр при помощи Javascript
                                if ($settings->size->width === 'auto') {
                                    $add_class .= ' autoposition-w';
                                }
                                if (!empty($tail_width) && !in_array('bottom_left', $settings->additional->all_tails->position) && !in_array('top_left', $settings->additional->all_tails->position)) {
                                    $badge_style .= 'left:0;';
                                } else if (!empty($tail_width) && !in_array('bottom_right', $settings->additional->all_tails->position) && !in_array('top_right', $settings->additional->all_tails->position)) {
                                    $badge_style .= 'right:0;';
                                } else {
                                    $badge_style .= 'left:' . (isset($settings->size->width_percentage) ? 50 - ($settings->size->width === 'auto' ? 0 : $settings_width) / 2 : 50) . '%;';
                                }
                                if (isset($settings->size->width_percentage) && isset($settings->border) && $settings->border->width > 0) {
                                    $margins['left'] += (-1) * $settings->border->width;
                                }
                                if ($settings->size->type == 'input' && !isset($settings->size->width_percentage)) {
                                    $margins['left'] += (-1) * floatval(($settings->size->width === 'auto' ? 0 : $settings_width) / 2);
                                }
                            }
                            break;
                    }
                }
            }
            // Ручной отступ
            foreach ($settings->position->margins as $k => $v) {
                $cust_v = ((float) $v + (float) $margins[$k]);
                $badge_style .= 'margin-' . $k . ':' . ($cust_v ? $cust_v . 'px' : 0) . ';';
            }
            if ($settings->size->height === 'auto') {
                $badge_data .= 'data-mtop="' . $settings->position->margins->top . '"';
            }
            if ($settings->size->width === 'auto') {
                $badge_data .= 'data-mleft="' . $settings->position->margins->left . '"';
            }
        }

        // Радиус
        if (isset($settings->additional->radius)) {
            if (!empty($settings->additional->radius->attributes)) {
                foreach ($settings->additional->radius->attributes as $a) {
                    $badge_style .= $a . ':' . (intval($settings->additional->radius->value) ? $settings->additional->radius->value . 'px' : 0) . ';';
                }
            } else {
                $badge_style .= self::generateBorderRadius($settings->additional->radius->value);
            }
        }

        // Задний фон, если указан конкретный элемент
        if (!empty($settings->background->element)) {
            switch ($settings->background->element) {
                case "badge-text-block":
                    $badge_text_block_style .= $background;
                    if (!empty($settings->border)) {
                        $badge_text_block_style .= $border;
                    }
            }
        }

        if (!empty($settings->box_shadow->element)) {
            $badge_text_block_style .= $box_shadow;
        }

        // Задний фон зависимых элементов
        if (!empty($settings->background->elements)) {
            foreach ($settings->background->elements as $attr => $val) {
                $elem = '.' . $css_class . (!empty($settings->background->element) ? ' .' . $settings->background->element : '') . $attr;
                $elem_style = '';
                if (is_array($val)) {
                    foreach ($val as $val2) {
                        $elem_style .= $val2 . ':' . ($settings->background->type == 'transparent' ? 'transparent' : ($settings->background->type == 'gradient' ? $background_data['end_gradient'] : $background_data['background_color'])) . ';';
                    }
                } else {
                    $elem_style .= $val . ':' . ($settings->background->type == 'transparent' ? 'transparent' : ($settings->background->type == 'gradient' ? $background_data['end_gradient'] : $background_data['background_color'])) . ';';
                }
                $element_styles = self::addStylesToElement($elem_style, $elem, $element_styles);
            }
        }

        // Зависимость элементов наклейки от размеров
        if (!empty($settings->background) && !empty($settings->size->ratio) && $settings->size->type == 'input') {
            $color = '#' . ($settings->background->type == 'gradient' ? $background_data['end_gradient'] : $background_data['background_color']);
            foreach ($settings->size->ratio as $elem => $v) {
                $elem = '.' . $css_class . $elem;
                $elem_style = '';
                foreach ($v as $attr => $v2) {
                    $elem_style .= $attr . ':' . (!empty($settings->size->width_ratio) ? $settings_width * $settings->size->width_ratio : $settings_height * $settings->size->height_ratio) . 'px solid ' . ($v2 !== 'transparent' ? $color : 'transparent') . ';';
                }
                $element_styles = self::addStylesToElement($elem_style, $elem, $element_styles);
            }
        }

        /* Язык */
        if (!empty($settings->additional->tongue)) {
            $tongue_size = !isset($settings->additional->tongue->size) ? $default_settings['additional']['tongue']['size'] : ((int) $settings->additional->tongue->size < 5 ? 5 : (int) $settings->additional->tongue->size);
            foreach ($settings->additional->tongue->elements as $elem => $v) {
                $elem = '.' . $css_class . $elem;
                $elem_style = '';
                foreach ($v as $attr => $v2) {
                    $elem_style .= $attr . ':-' . $tongue_size . 'px;' . $v2 . ':' . $tongue_size . 'px;';
                }
                $element_styles = self::addStylesToElement($elem_style, $elem, $element_styles);
            }
        }

        // Штрихпунктирная линия
        if (!empty($settings->additional->dashed_line)) {
            $css_styles .= '.' . $css_class . ' .badge-dashed-line:after,.' . $css_class . ' .badge-dashed-line:before{';
            if ($settings->additional->dashed_line->type == 'hide') {
                $css_styles .= 'border:0 none;';
                $dashed_line_style .= 'border:0 none;';
            } else {
                $css_styles .= 'border-color:#' . self::shortHexColor($settings->additional->dashed_line->color) . ';';
                $dashed_line_style .= 'border-color:#' . self::shortHexColor($settings->additional->dashed_line->color) . ';';
            }
            // Радиус
            if (!empty($settings->additional->radius)) {
                if (!empty($settings->additional->radius->attributes)) {
                    foreach ($settings->additional->radius->attributes as $v) {
                        $css_styles .= $v . ':' . (intval($settings->additional->radius->value) ? $settings->additional->radius->value . 'px' : 0) . ';';
                        $dashed_line_style .= $v . ':' . (intval($settings->additional->radius->value) ? $settings->additional->radius->value . 'px' : 0) . ';';
                    }
                } else {
                    $radius_val = self::generateBorderRadius($settings->additional->radius->value);
                    $dashed_line_style .= $radius_val;
                    $css_styles .= $radius_val;
                }
            }
            $css_styles .= '}';
            if ($conditions->id == 'ribbon-3' && ($settings->size->width === 'auto' || $settings->size->height === 'auto')) {
                $badge_text_block_style .= 'padding:';
                $badge_text_block_style .= ($settings->size->height === 'auto' ? ($settings->additional->dashed_line->type !== 'hide' && ($settings->id == 'ribbon-3-rl' || $settings->id == 'ribbon-3-lr') ? '10px' : '5px') : '0') . ' ';
                $badge_text_block_style .= ($settings->size->width === 'auto' ? ($settings->additional->dashed_line->type !== 'hide' && ($settings->id == 'ribbon-3-bt' || $settings->id == 'ribbon-3') ? '10px' : '5px') : '0') . ';';
            }
        }

        // Расположение текста
        if (!empty($settings->torientation)) {
            $value_orient = $settings->torientation == 'vertical' ? 'rotate(-90deg)' : ($settings->torientation == 'vertical_revert' ? 'rotate(-270deg)' : (is_numeric($settings->torientation) ? 'rotate(' . $settings->torientation . 'deg)' : 'none'));
            if ($conditions->id == 'ribbon-6' && $settings->torientation !== 'horizontal') {
                $badge_text_block_style .= 'position:relative;';
            }
            $badge_text_block_style .= '-webkit-transform:' . $value_orient . ';-moz-transform:' . $value_orient . ';-ms-transform:' . $value_orient . ';-o-transform:' . $value_orient . ';transform:' . $value_orient . ';';
        }

        // Размер
        if (!empty($ribbon_size)) {
            // Обрабатываем зависимые значения размера
            $c = 0;
            $elems = array();
            foreach ($settings->size->keys as $attr => $v) {
                if ($attr == 'root') {
                    continue;
                }
                $styles = "";
                if (is_array($v)) {
                    foreach ($v as $j) {
                        $c++;
                        $styles .= $j . ':' . ($ribbon_size[$c] ? $ribbon_size[$c] . 'px' : 0) . ';';
                    }
                } else {
                    $c++;
                    $styles .= $v . ':' . ($ribbon_size[$c] ? $ribbon_size[$c] . 'px' : 0) . ';';
                }
                $elems = self::addStylesToElement($styles, $attr, $elems);
            }

            /* Изменяем высоту */
            if (!empty($settings->size->height_element)) {
                foreach ($settings->size->height_element as $k => $v) {
                    $style = $v . ":" . $settings->size->height . 'px;';
                    $elems = self::addStylesToElement($style, $k, $elems);
                }
            }
            foreach ($elems as $k => $v) {
                switch ($k) {
                    case "badge-text-block":
                        $badge_text_block_style .= $v;
                }
            }
        }

        // Хвост
        if (!empty($settings->additional->tail)) {
            $tail = $settings->additional->tail;
            if (!empty($tail->code)) {
                $css_styles .= '.' . $css_class . ' .autobadge-pl-tail{';
                $css_styles .= 'display:' . ($tail->type == 'hide' ? 'none' : 'block');
                $css_styles .= '}';
            } else {
                if ($tail->type !== 'hide') {
                    $add_class .= ' with-tail';
                }
            }
            if ($tail->type !== 'hide') {
                $css_styles .= '.' . $css_class . (!empty($tail->code) ? ' .autobadge-pl-tail:before{' : ':after{');
                $css_styles .= 'border-width:' . $tail->size . 'px;';
                $offset = !empty($settings->border) ? (-1) * intval($settings->border->width) : 0;
                $tail_color = self::getRgba(isset($tail->color_rgb) ? $tail->color_rgb : '', $tail->color, $background_data['background_transparency']);
                switch ($tail->position) {
                    case 'top_right':
                        $css_styles .= 'border-color:transparent transparent ' . $tail_color . ' ' . $tail_color . ';';
                        $css_styles .= ($settings->orientation == 'top_bottom' ? 'right' : 'top') . ':-' . ($tail->size * 2 - $offset) . 'px;';
                        $css_styles .= ($settings->orientation == 'top_bottom' ? 'top' : 'right') . ':' . ($offset ? $offset . 'px' : 0) . ';';
                        break;
                    case 'top_left':
                        $css_styles .= 'border-color:transparent ' . $tail_color . ' ' . $tail_color . ' transparent;';
                        $css_styles .= ($settings->orientation == 'top_bottom' ? 'left' : 'top') . ':-' . ($tail->size * 2 - $offset) . 'px;';
                        $css_styles .= ($settings->orientation == 'top_bottom' ? 'top' : 'left') . ':' . ($offset ? $offset . 'px' : 0) . ';';
                        break;
                    case 'bottom_right':
                        $css_styles .= 'border-color:' . $tail_color . ' transparent transparent ' . $tail_color . ';';
                        $css_styles .= ($settings->orientation == 'bottom_top' ? 'right' : 'bottom') . ':-' . ($tail->size * 2 - $offset) . 'px;';
                        $css_styles .= ($settings->orientation == 'bottom_top' ? 'bottom' : 'right') . ':' . ($offset ? $offset . 'px' : 0) . ';';
                        break;
                    case 'bottom_left':
                        $css_styles .= 'border-color:' . $tail_color . ' ' . $tail_color . ' transparent transparent;';
                        $css_styles .= ($settings->orientation == 'bottom_top' ? 'left' : 'bottom') . ':-' . ($tail->size * 2 - $offset) . 'px;';
                        $css_styles .= ($settings->orientation == 'bottom_top' ? 'bottom' : 'left') . ':' . ($offset ? $offset . 'px' : 0) . ';';
                        break;
                }
                $css_styles .= '}';
            }
        }
        // Хвосты
        if (!empty($settings->additional->tails)) {
            if ($settings->additional->tails->type == 'hide') {
                $css_styles .= '.' . $css_class . ' .badge-text-block:after,.' . $css_class . ' .badge-text-block:before{border:0 none;}';
                $add_class .= ' without-tail';
            }
        }

        if (!empty($element_styles)) {
            foreach ($element_styles as $k => $v) {
                $css_styles .= $k . '{' . $v . '}';
            }
        }

        // Устанавливаем флаг, что для наклейки необходимо подгрузить CSS стили
        if ($css_styles) {
            $badge_data .= ' data-load-css="' . $class_name . '"';
        }

        $html .= '<div class="autobadge-pl ' . $settings->id . ' product-id-' . $product['product']['product_id'] . ' ' . $class_name . $add_class . '"' . ($badge_data ? $badge_data : '') . ' style="' . $badge_style . '">' . $content;
        if (in_array($conditions->id, array('ribbon-1', 'ribbon-2', 'ribbon-3', 'ribbon-5'))) {
            $html .= '<span class="badge-dashed-line"' . ($dashed_line_style ? " style='" . $dashed_line_style . "'" : '') . '>';
        }
        if ($conditions->id !== 'ribbon-7') {
            $html .= '<span class="badge-text-block"' . ($badge_text_block_style ? " style='" . $badge_text_block_style . "'" : '') . '>';
        }
        $html .= $badge_text;
        if ($conditions->id !== 'ribbon-7') {
            $html .= '</span>';
        }
        if (in_array($conditions->id, array('ribbon-1', 'ribbon-2', 'ribbon-3', 'ribbon-5'))) {
            $html .= '</span>';
        }
        $html .= '</div>';

        if (!isset(self::$css[$css_class])) {
            self::$css[$css_class] = $css_styles;
        }

        return $html;
    }

    /**
     * Generate badge text
     *
     * @param array $settings - badge settings
     * @param array $product
     * @return array ('dashed_line_style', 'badge_text_style', 'text', 'image')
     */
    private static function generateText($settings, $product)
    {
        $badge_text = $dashed_line_style = $badge_text_block_style = $image = '';
        if (!empty($settings->text)) {
            $badge_text = '';
            $k = $text_z_index = $text_k = 0;
            $len = count($settings->text);
            $attrs = array('top', 'right', 'bottom', 'left');
            $plugin_url = shopAutobadgeHelper::getPlugin()->getPluginStaticUrl(true);
            foreach ($settings->text as $st) {
                $z_index = $len - $k;
                // Текст
                if ($st->type == 'text') {
                    $badge_text .= ($text_k > 0 ? '<br>' : '') . '<span data-pos="' . $k . '" style="z-index:' . $z_index . ';';
                    $badge_text .= !empty($st->color) ? 'color:#' . self::shortHexColor($st->color) . ';' : '';
                    $badge_text .= !empty($st->family) ? 'font-family:' . $st->family . ';' : '';
                    $badge_text .= !empty($st->size) ? 'font-size:' . $st->size . 'px;' : '';
                    // Сохраняем отдельно шрифты, чтобы потом понимать, стоит ли подключать Google fonts или нет
                    if (!empty($st->family)) {
                        self::$badge_fonts[$st->family] = $st->family;
                    }
                    if (!empty($st->style)) {
                        if ($st->style == 'bold') {
                            $badge_text .= 'font-style:normal;font-weight:bold;';
                        } else if ($st->style == 'bolditalic') {
                            $badge_text .= 'font-style:italic;font-weight:bold;';
                        } else if ($st->style == 'italic') {
                            $badge_text .= 'font-style:italic;font-weight:normal;';
                        } else {
                            $badge_text .= 'font-style:normal;font-weight:normal;';
                        }
                    }
                    $badge_text .= !empty($st->align) ? 'text-align:' . $st->align . ';' : '';
                    if (!empty($st->margins)) {
                        foreach ($st->margins as $pos => $v) {
                            $badge_text .= $pos . ':' . (empty($v) ? 0 : floatval($v) . 'px') . ';';
                        }
                    }
                    if (!empty($st->shadow) || !isset($st->shadow)) {
                        $badge_text .= 'text-shadow:0.0625em 0.0625em 0.0625em #333;';
                    } else {
                        $badge_text .= 'text-shadow:none;';
                    }
                    if (isset($st->width)) {
                        $badge_text .= 'width:' . (!empty($st->width) && $st->width !== 'auto' ? $st->width . 'px' : 'auto') . ';';
                    }
                    $badge_text .= '">';
                    $badge_text .= isset($st->value) ? self::getText($st->value, $product) : '';
                    $badge_text .= '</span>';
                    if (!$text_z_index) {
                        $text_z_index = $z_index;
                    }
                    $text_k++;
                } elseif ($st->type == 'textarea') {
                    $badge_text .= self::getText($st->content, $product);
                } // Изображение
                else {
                    $src = (strpos($st->src, '%plugin_url%') !== false ? str_replace('%plugin_url%', $plugin_url, $st->src) : $st->src);
                    $image .= "<img data-at2x='" . $src . "' data-pos='" . $k . "' alt='img' src='" . $src . "' style='position:absolute;z-index:" . $z_index . ";";
                    if (!empty($st->width)) {
                        $image .= "max-width:" . $st->width . 'px;';
                    }
                    if (!empty($st->height)) {
                        $image .= "max-height:" . $st->height . 'px;';
                    }
                    if (empty($st->top) && empty($st->right) && empty($st->bottom) && empty($st->left)) {
                        $st->top = 0;
                        $st->right = 0;
                    }
                    foreach ($attrs as $attr) {
                        $image .= $attr . ":" . (isset($st->{$attr}) ? (!empty($st->{$attr}) ? floatval($st->{$attr}) . 'px' : 0) : 'auto') . ';';
                    }
                    $image .= 'border:0 none';
                    $image .= "' />";
                }
                $k++;
            }
            if (!isset($settings->multiline) && !$badge_text) {
                $badge_text .= "<span></span>";
            }
            $dashed_line_style .= 'z-index:' . $text_z_index . ';';
            if ($settings->id == 'ribbon-6') {
                $badge_text_block_style .= 'z-index:' . $text_z_index . ';';
            }
        }
        return array('dashed_line_style' => $dashed_line_style, 'badge_text_style' => $badge_text_block_style, 'text' => $badge_text, 'image' => $image);
    }

    /**
     * Generate badge background styles
     *
     * @param array $settings - badge settings
     * @return array
     */
    private static function generateBackground($settings)
    {
        $background = $background_color = $end_gradient = $background_transparency = '';
        if (isset($settings->background)) {
            $background_transparency = isset($settings->background->transparency) ? $settings->background->transparency : 1;
            $background_color = self::getRgba(isset($settings->background->color_rgb) ? $settings->background->color_rgb : '', isset($settings->background->color) ? $settings->background->color : 'ff0000', $background_transparency);
            $background_color_start = ($settings->background->type == 'gradient' ? self::getRgba(isset($settings->background->gradient->start_color_rgb) ? $settings->background->gradient->start_color_rgb : '', $settings->background->gradient->start, isset($settings->background->gradient->transparency) ? $settings->background->gradient->transparency : 1) : $background_color);
            $background .= 'background:' . ($settings->background->type == 'transparent' ? 'transparent' : $background_color_start) . ';';
            if ($settings->background->type == 'gradient') {
                $background_transparency = isset($settings->background->gradient->transparency) ? $settings->background->gradient->transparency : 1;
                $start_gradient = self::getRgba(isset($settings->background->gradient->start_color_rgb) ? $settings->background->gradient->start_color_rgb : '', $settings->background->gradient->start, $background_transparency);
                $end_gradient = self::getRgba(isset($settings->background->gradient->end_color_rgb) ? $settings->background->gradient->end_color_rgb : '', $settings->background->gradient->end, $background_transparency);
                if ($settings->background->gradient->orientation == 'linear') {
                    $gradient_type = $settings->background->gradient->type == 'to_bottom' ? 'top' : ($settings->background->gradient->type == 'to_left' ? 'right' : ($settings->background->gradient->type == 'to_right' ? 'left' : ($settings->background->gradient->type == 'to_top' ? 'bottom' : '')));
                    $gradient_type2 = $settings->background->gradient->type == 'to_bottom' ? 'to bottom' : ($settings->background->gradient->type == 'to_left' ? 'to left' : ($settings->background->gradient->type == 'to_right' ? 'to right' : ($settings->background->gradient->type == 'to top' ? 'to top' : '')));
                    $background .= 'background:-moz-linear-gradient(' . $gradient_type . ',' . $start_gradient . ' 0,' . $end_gradient . ' 100%);';
                    $background .= 'background:-ms-linear-gradient(' . $gradient_type . ',' . $start_gradient . ' 0,' . $end_gradient . ' 100%);';
                    $background .= 'background:-o-linear-gradient(' . $gradient_type . ',' . $start_gradient . ' 0,' . $end_gradient . ' 100%);';
                    $background .= 'background:-webkit-linear-gradient(' . $gradient_type . ',' . $start_gradient . ' 0,' . $end_gradient . ' 100%);';
                    $background .= 'background:linear-gradient(' . $gradient_type2 . ',' . $start_gradient . ' 0,' . $end_gradient . ' 100%);';
                } else {
                    $gradient_type = $settings->background->gradient->type == 'to_bottom' ? 'top' : ($settings->background->gradient->type == 'to_left' ? 'right' : ($settings->background->gradient->type == 'to_right' ? 'left' : ($settings->background->gradient->type == 'to_top' ? 'bottom' : '')));
                    $background .= 'background:-moz-radial-gradient(center ' . $gradient_type . ',circle farthest-side,' . $start_gradient . ' 0,' . $end_gradient . ' 100%);';
                    $background .= 'background:-ms-radial-gradient(center ' . $gradient_type . ',circle farthest-side,' . $start_gradient . ' 0,' . $end_gradient . ' 100%);';
                    $background .= 'background:-o-radial-gradient(center ' . $gradient_type . ',circle farthest-side,' . $start_gradient . ' 0,' . $end_gradient . ' 100%);';
                    $background .= 'background:-webkit-radial-gradient(center ' . $gradient_type . ',circle farthest-side,' . $start_gradient . ' 0,' . $end_gradient . ' 100%);';
                    $background .= 'background:radial-gradient(circle farthest-side at center ' . $gradient_type . ',' . $start_gradient . ' 0,' . $end_gradient . ' 100%);';
                }
            }
        }
        return array('styles' => $background, 'background_color' => $background_color, 'end_gradient' => $end_gradient, 'background_transparency' => $background_transparency);
    }

    private static function getRgba($rgba_str, $hex, $transparency = 1)
    {
        $rgba = 'rgba(';
        if (!empty($rgba_str)) {
            $rgba .= $rgba_str;
        } else {
            $rgbObj = self::hexToRGB($hex);
            $rgba .= $rgbObj['r'] . ',' . $rgbObj['g'] . ',' . $rgbObj['b'];
        }
        $rgba .= ',' . $transparency . ')';
        return $rgba;
    }

    private static function hexToRgb($hex)
    {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) !== 6) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $hex = intval($hex, 16);
        return array('r' => $hex >> 16, 'g' => ($hex & 0x00FF00) >> 8, 'b' => ($hex & 0x0000FF));
    }

    /**
     * Generate badge border radius styles
     *
     * @param array|string $radius_values - values
     * @return string
     */
    private static function generateBorderRadius($radius_values)
    {
        $border_rad = "";
        $border_rad_val = array();
        foreach ($radius_values as $k => $v) {
            $rad_val = (intval($v) ? $v . 'px' : 0);
            $border_rad_val[$k] = $rad_val;
            $border_rad .= 'border-' . $k . '-radius' . ':' . $rad_val . ';';
        }
        if (is_array($radius_values) && count($radius_values) == 4) {
            if ($border_rad_val['top-left'] == $border_rad_val['top-right'] && $border_rad_val['top-right'] == $border_rad_val['bottom-right'] && $border_rad_val['bottom-right'] == $border_rad_val['bottom-left']) {
                $border_rad = 'border-radius:' . $border_rad_val['top-left'] . ';';
            } elseif ($border_rad_val['top-left'] == $border_rad_val['bottom-right'] && $border_rad_val['top-right'] == $border_rad_val['bottom-left']) {
                $border_rad = 'border-radius:' . $border_rad_val['top-left'] . ' ' . $border_rad_val['top-right'] . ';';
            }
        }
        return $border_rad;
    }

    /**
     * Convert 6 dec hex color to 3 dec
     *
     * @param string $hex
     * @return string E.g #ff0000 converts to #f00
     */
    private static function shortHexColor($hex)
    {
        static $shortand = array();
        if (isset($shortand[$hex])) {
            return $shortand[$hex];
        }
        if (strlen($hex) === 6) {
            if ($hex[0] == $hex[1] && $hex[2] == $hex[3] && $hex[4] == $hex[5]) {
                $shortand[$hex] = $hex[0] . $hex[2] . $hex[4];
                return $shortand[$hex];
            }
        }
        $shortand[$hex] = $hex;
        return $hex;
    }

    /**
     * Calculate badge tail size
     *
     * @param float $cont_w
     * @param float $badge_w
     * @return float
     */
    private static function getTailSize($cont_w, $badge_w)
    {
        return ceil((1 - $badge_w / 100) * $cont_w / 4) * 2;
    }

    /**
     * Collect css styles for elements
     *
     * @param string $styles
     * @param string $elem
     * @param array $element_styles
     * @return array
     */
    private static function addStylesToElement($styles, $elem, $element_styles)
    {
        if (!isset($element_styles[$elem])) {
            $element_styles[$elem] = "";
        }
        $element_styles[$elem] .= $styles;

        return $element_styles;
    }

    /**
     * Get default settings for ribbons
     *
     * @return array
     */
    public function getDefaultRibbonSettings()
    {
        if (self::$default_settings === null) {
            self::$default_settings = include(dirname(__FILE__) . '/../config/config.php');
        }
        return self::$default_settings;
    }

    /**
     * Convert Smarty to the text
     *
     * @param string $text
     * @param array $product
     * @return string
     */
    private static function getText($text, $product)
    {
        // Выполняем простейшую проверку на наличие Smarty
        if (strpos($text, '{') !== false) {
            // Если есть вызов доп параметров, добавляем их к товарам
            if (strpos($text, '$product.params') !== false) {
                $product['product']['params'] = shopAutobadgeData::getProductParams($product['id']);
            }

            // Заменяем конструкции Smarty, чтобы лишний раз не вызывать отрисовку
            $text = str_ireplace(array(
                '$product',
                '{counter ',
                '{$autobadge_product.name}',
                '{$autobadge_product.sku_code}',
                '{$autobadge_product.sku_name}',
                '{$autobadge_product.summary}',
                '{$autobadge_product.create_datetime}',
                '{$autobadge_product.edit_datetime}',
                '{$autobadge_product.rating}',
                '{$autobadge_product.price}',
                '{$autobadge_product.compare_price}',
                '{$autobadge_product.purchase_price}',
                '{$autobadge_product.margin}',
                '{$autobadge_product.margin_comp}',
                '{$autobadge_product.count}',
                '{$autobadge_product.sku_count}',
                '{$autobadge_product.discount_percentage}'
            ), array(
                '$autobadge_product',
                '{autobadge_counter ',
                $product['product']['name'],
                $product['product']['sku_code'],
                $product['product']['sku_name'],
                $product['product']['summary'],
                $product['product']['create_datetime'],
                $product['product']['edit_datetime'],
                $product['product']['rating'],
                $product['product']['price'],
                $product['product']['compare_price'],
                $product['product']['purchase_price'],
                $product['product']['margin'],
                $product['product']['margin_comp'],
                $product['product']['count'],
                $product['product']['sku_count'],
                $product['product']['discount_percentage']
            ), $text);

            // Оптимизация, оптимизация..
            // В цикле перебрать нельзя, потому что shop_currency в этом случае сработают
            if (strpos('{shop_currency($autobadge_product.price, true)}', $text) !== false) {
                $text = str_replace('{shop_currency($autobadge_product.price, true)}', shop_currency($product['product']['price'], true), $text);
            }
            if (strpos('{shop_currency($autobadge_product.compare_price, true)}', $text) !== false) {
                $text = str_replace('{shop_currency($autobadge_product.compare_price, true)}', shop_currency($product['product']['compare_price'], true), $text);
            }
            if (strpos('{shop_currency($autobadge_product.purchase_price, true)}', $text) !== false) {
                $text = str_replace('{shop_currency($autobadge_product.purchase_price, true)}', shop_currency($product['product']['purchase_price'], true), $text);
            }
            if (strpos('{shop_currency_html($autobadge_product.price, true)}', $text) !== false) {
                $text = str_replace('{shop_currency_html($autobadge_product.price, true)}', shop_currency_html($product['product']['price'], true), $text);
            }
            if (strpos('{shop_currency_html($autobadge_product.compare_price, true)}', $text) !== false) {
                $text = str_replace('{shop_currency_html($autobadge_product.compare_price, true)}', shop_currency_html($product['product']['compare_price'], true), $text);
            }
            if (strpos('{shop_currency_html($autobadge_product.purchase_price, true)}', $text) !== false) {
                $text = str_replace('{shop_currency_html($autobadge_product.purchase_price, true)}', shop_currency_html($product['product']['purchase_price'], true), $text);
            }
            if (strpos('{shop_currency($autobadge_product.margin, true)}', $text) !== false) {
                $text = str_replace('{shop_currency($autobadge_product.margin, true)}', shop_currency($product['product']['margin'], true), $text);
            }
            if (strpos('{shop_currency_html($autobadge_product.margin, true)}', $text) !== false) {
                $text = str_replace('{shop_currency_html($autobadge_product.margin, true)}', shop_currency_html($product['product']['margin'], true), $text);
            }
            if (strpos('{shop_currency($autobadge_product.margin_comp, true)}', $text) !== false) {
                $text = str_replace('{shop_currency($autobadge_product.margin_comp, true)}', shop_currency($product['product']['margin_comp'], true), $text);
            }
            if (strpos('{shop_currency_html($autobadge_product.margin_comp, true)}', $text) !== false) {
                $text = str_replace('{shop_currency_html($autobadge_product.margin_comp, true)}', shop_currency_html($product['product']['margin_comp'], true), $text);
            }

            if (strpos($text, '{') !== false) {
                $view = shopAutobadgeHelper::getView();
                $view->assign('autobadge_product', $product['product']);
                return $view->fetch("string:" . $text);
            }
        }
        return $text;
    }

}
