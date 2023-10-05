<?php

class shopQuickorderPluginGenerator
{
    /* This constants are using only for CSS styles */
    const PRODUCT_BUTTON_ATTR = 'data-quickorder-pb';
    const CART_BUTTON_ATTR = 'data-quickorder-cb';
    const PRODUCT_FORM_BUTTON_ATTR = 'data-quickorder-pfb';
    const CART_FORM_BUTTON_ATTR = 'data-quickorder-cfb';
    const PRODUCT_FIELDS_ATTR = 'data-quickorder-pfs';
    const CART_FIELDS_ATTR = 'data-quickorder-cfs';
    const PRODUCT_TITLES_ATTR = 'data-quickorder-pt';
    const CART_TITLES_ATTR = 'data-quickorder-ct';
    const PRODUCT_FORM_ATTR = 'data-quickorder-pf';
    const CART_FORM_ATTR = 'data-quickorder-cf';
    const PRODUCT_FORM_HEAD_ATTR = 'data-quickorder-pfh';
    const CART_FORM_HEAD_ATTR = 'data-quickorder-cfh';
    const PRODUCT_FORM_FOOTER_ATTR = 'data-quickorder-pff';
    const CART_FORM_FOOTER_ATTR = 'data-quickorder-cff';

    private static $css = '';
    private static $fonts = array();
    private $extra = array();
    private $current_tab = 'product';
    private $element_attr = array(
        'button2' => array(
            'product' => self::PRODUCT_BUTTON_ATTR,
            'cart' => self::CART_BUTTON_ATTR
        ),
        'form' => array(
            'product' => self::PRODUCT_FORM_ATTR,
            'cart' => self::CART_FORM_ATTR
        ),
        'form_titles' => array(
            'product' => self::PRODUCT_TITLES_ATTR,
            'cart' => self::CART_TITLES_ATTR
        ),
        'form_fields' => array(
            'product' => self::PRODUCT_FIELDS_ATTR,
            'cart' => self::CART_FIELDS_ATTR
        ),
        'form_head' => array(
            'product' => self::PRODUCT_FORM_HEAD_ATTR,
            'cart' => self::CART_FORM_HEAD_ATTR
        ),
        'form_footer' => array(
            'product' => self::PRODUCT_FORM_FOOTER_ATTR,
            'cart' => self::CART_FORM_FOOTER_ATTR
        ),
        'button' => array(
            'product' => self::PRODUCT_FORM_BUTTON_ATTR,
            'cart' => self::CART_FORM_BUTTON_ATTR
        ),
    );

    /**
     * Get css and fonts
     *
     * @return array ('inline_css', 'google_fonts')
     */
    public static function getCss()
    {
        if (self::$css) {
            self::$css = str_replace(';}', '}', self::$css);
        }
        $google_fonts = array("'Open Sans',sans-serif" => "Open+Sans", "'Open Sans Condensed',sans-serif" => "Open+Sans+Condensed:300", "'Roboto',sans-serif" => "Roboto", "'Roboto Condensed',sans-serif" => "Roboto+Condensed", "'Roboto Slab',serif" => "Roboto+Slab", "'PT Sans',sans-serif" => "PT+Sans", "'Lora',serif" => "Lora", "'Lobster',cursive" => "Lobster", "'Ubuntu',sans-serif" => "Ubuntu", "'Noto Sans',sans-serif" => "Noto+Sans");
        $active_fonts = array();
        foreach (self::$fonts as $f) {
            if (isset($google_fonts[$f])) {
                $active_fonts[$f] = $google_fonts[$f];
            }
        }
        return array('inline_css' => self::$css, 'google_fonts' => $active_fonts ? '<link href="https://fonts.googleapis.com/css?family=' . implode('%7C', $active_fonts) . '" rel="stylesheet">' : '');
    }

    /**
     * Generate CSS styles and save them to static variable $css
     */
    public function prepareStyles()
    {
        static $cached = 0;

        if (!$cached) {
            //Настройки
            $settings = shopQuickorderPluginHelper::getSettings();
            if (!empty($settings['appearance'])) {
                if (!empty($settings['shared_appearance_settings'])) {
                    $settings['appearance']['cart'] = $settings['appearance']['product'];
                }

                foreach ($settings['appearance'] as $type => $appearance) {
                    $this->current_tab = $type;
                    $appearance = str_replace(array('box-shadow', 'border-radius', 'text-align'), array('box_shadow', 'border_radius', 'text_align'), $appearance);
                    $appearance = shopQuickorderPluginHelper::decode($appearance);
                    foreach ($appearance as $element => $styles) {
                        $prefix = $element !== 'button2' ? '.quickorder-form' . ($element !== 'form' ? ' ' : '') : '';
                        if (isset($styles->hover)) {
                            $normal_styles = $this->generateStyles($styles->normal);
                            if ($normal_styles) {
                                self::$css .= $prefix . "[" . $this->element_attr[$element][$type] . "]{" . $normal_styles . "}";
                            }
                            $hover_styles = $this->generateStyles($styles->hover);
                            if ($hover_styles) {
                                self::$css .= $prefix . "[" . $this->element_attr[$element][$type] . "]:hover{" . $hover_styles . "}";
                            }
                        } else {
                            $element_styles = $this->generateStyles($styles);
                            if ($element_styles) {
                                self::$css .= $prefix . "[" . $this->element_attr[$element][$type] . "]{" . $element_styles . "}";
                            }
                        }
                        // Создаем стили для зависимых элементов формы
                        self::$css .= $this->dependentElementsStyles($element, isset($styles->normal) ? $styles->normal : $styles, isset($styles->hover) ? $styles->hover : null);
                        $this->extraActions($element, isset($styles->normal) ? $styles->normal : $styles);
                    }
                }
            }
            $cached = 1;
        }
    }

    /**
     * Create styles for elements, which depend on the styles of major elements
     *
     * @param string $active_element
     * @param array $styles
     * @param array|null $hover_styles
     * @return string
     */
    private function dependentElementsStyles($active_element, $styles, $hover_styles = null)
    {
        static $init = null;
        if ($init === null) {
            function addStylesTo($elem, &$elements, $styles)
            {
                if (!isset($elements[$elem])) {
                    $elements[$elem] = array();
                }
                $elements[$elem][] = $styles;
            }

            function implodeStyles($elements)
            {
                $css = '';
                if ($elements) {
                    foreach ($elements as $elem => $styles) {
                        $css .= $elem . '{' . implode('', $styles) . '}';
                    }
                }
                return $css;
            }

            $init = 1;
        }

        $elements = array();
        if ($active_element == 'form_fields') {
            // Рамка методов доставки/оплаты
            if (!empty($styles->border->width) && !empty($styles->border->style) && !empty($styles->border->color)) {
                $css = 'border:1px solid #' . $this->shortHexColor($styles->border->color) . $this->isImportant() . ';';
                addStylesTo('[' . $this->element_attr['form'][$this->current_tab] . '] .quickorder-methods .s-quickorder-method', $elements, $css);
            }
            // Поля формы
            if (isset($styles->width)) {
                unset($styles->width);
            }
            addStylesTo('[' . $this->element_attr['form'][$this->current_tab] . '] .quickorder-methods-form .wa-value input'
                . ',[' . $this->element_attr['form'][$this->current_tab] . '] .quickorder-methods-form .wa-value select'
                . ',[' . $this->element_attr['form'][$this->current_tab] . '] .wa-captcha-input'
                . ',[' . $this->element_attr['form'][$this->current_tab] . '] .quickorder-methods-form .wa-value textarea', $elements, $this->generateStyles($styles));
        }
        if ($active_element == 'button') {
            if (!empty($styles->background->type) && (isset($styles->background->color_rgb) || isset($styles->background->color))) {
                $css = 'background:' . $this->getRgba(isset($styles->background->color_rgb) ? $styles->background->color_rgb : '', isset($styles->background->color) ? $styles->background->color : 'ffffff', '.1') . $this->isImportant() . ';';
                // Задний фон методов доставки/оплаты
                addStylesTo('[' . $this->element_attr['form'][$this->current_tab] . '] .quickorder-methods .s-quickorder-method:hover,' . '[' . $this->element_attr['form'][$this->current_tab] . '] .quickorder-methods .s-quickorder-method.selected', $elements, $css);
                addStylesTo('[' . $this->element_attr['form'][$this->current_tab] . '] .quickorder-quantity-volume:hover', $elements, $css);
            }
            // Кнопка выбора вариации товара во всплывающем окне
            addStylesTo('[' . $this->element_attr['form'][$this->current_tab] . '] .quickorder-popup input[type="button"]', $elements, $this->generateStyles($styles));
            if ($hover_styles) {
                addStylesTo('[' . $this->element_attr['form'][$this->current_tab] . '] .quickorder-popup input[type="button"]:hover', $elements, $this->generateStyles($hover_styles));
            }
        }
        if ($active_element == 'form') {
            // Скругление углов методов доставки/оплаты
            $border_radius = !empty($styles->border_radius) ? $styles->border_radius : null;
            if (!empty($border_radius->value) && $border_radius->value !== '') {
                $unit = !empty($border_radius->unit) ? $border_radius->unit : 'px';
                $radius = (intval($border_radius->value) ? $border_radius->value . $unit : 0) . $this->isImportant() . ';';
                $css = '-webkit-border-radius:' . $radius . '-moz-border-radius:' . $radius . 'border-radius:' . $radius;
                addStylesTo('[' . $this->element_attr['form'][$this->current_tab] . '] .quickorder-methods .s-quickorder-method', $elements, $css);
                addStylesTo('[' . $this->element_attr['form'][$this->current_tab] . '] .quickorder-popup', $elements, $css);
            }
        }
        if ($active_element == 'form_head') {
            // Отступ справа для иконки закрытия окна
            if (!empty($styles->padding->right)) {
                addStylesTo('[' . $this->element_attr['form'][$this->current_tab] . '] [data-quickorder-close]', $elements, 'right:' . $styles->padding->right . 'px;');
            }
            if (!empty($styles->background->type) && (isset($styles->background->color_rgb) || isset($styles->background->color))) {
                // Шапка всплывающего окна выбора вариации товара
                $css = $this->getRgba(isset($styles->background->color_rgb) ? $styles->background->color_rgb : '', isset($styles->background->color) ? $styles->background->color : 'f3f3f3') . $this->isImportant() . ';';
                addStylesTo('[' . $this->element_attr['form'][$this->current_tab] . '] .quickorder-popup-head', $elements, 'background:' . $css);
                addStylesTo('[' . $this->element_attr['form'][$this->current_tab] . '] .quickorder-popup', $elements, 'border:2px solid ' . $css);
            }
            if (!empty($styles->color)) {
                addStylesTo('[' . $this->element_attr['form'][$this->current_tab] . '] .quickorder-popup-head', $elements, $this->generateColor($styles->color));
            }
        }
        if ($active_element == 'button2') {
            $settings = shopQuickorderPluginHelper::getSettings();
            $button_settings = array(
                'cart' => !empty($settings['cart']) ? $settings['cart'] : array(),
                'product' => !empty($settings['product']) ? $settings['product'] : array(),
            );
            if (!empty($settings['shared_display_settings'])) {
                $button_settings['cart'] = $button_settings['product'];
            }
            addStylesTo('[' . $this->element_attr[$active_element][$this->current_tab] . ']', $elements, 'display:'.((!empty($button_settings[$this->current_tab]['button_display']) && $button_settings[$this->current_tab]['button_display'] == 'inline') ? 'inline-block' : 'table'));
        }

        return implodeStyles($elements);
    }

    /**
     * Additional checks. Results save to $this->extra array
     *
     * @param string $active_element
     * @param array $styles
     */
    private function extraActions($active_element, $styles)
    {
        // Ставим флаг, если для ширины кнопки используется процентное значение
        if ($active_element == 'button' && !empty($styles->width->value)) {
            $this->extra['percentage_width'] = 1;
        }
    }

    /**
     *
     * @return array
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * Generate CSS styles
     *
     * @param array $styles
     * @return string
     */
    public function generateStyles($styles)
    {
        $css = "";
        if ($styles) {
            foreach ($styles as $attr => $settings) {
                $method_name = 'generate' . ucfirst($attr);
                if (method_exists($this, $method_name)) {
                    $css .= $this->$method_name($settings);
                }
            }
        }
        return $css;
    }

    /**
     * Generate background styles
     *
     * @param object $settings
     * @return string
     */
    private function generateBackground($settings)
    {
        $background = $background_color = $end_gradient = $background_transparency = '';
        if (!empty($settings->type)) {
            $background_transparency = isset($settings->transparency) ? $settings->transparency : 1;
            $background_color = $this->getRgba(isset($settings->color_rgb) ? $settings->color_rgb : '', isset($settings->color) ? $settings->color : 'ffffff', $background_transparency);
            $background_color_start = ($settings->type == 'gradient' ? $this->getRgba(isset($settings->gradient->start_color_rgb) ? $settings->gradient->start_color_rgb : '', $settings->gradient->start, isset($settings->gradient->transparency) ? $settings->gradient->transparency : 1) : $background_color);
            $background .= 'background:' . ($settings->type == 'transparent' ? 'transparent' : $background_color_start) . $this->isImportant() . ';';
            if ($settings->type == 'gradient') {
                $background_transparency = isset($settings->gradient->transparency) ? $settings->gradient->transparency : 1;
                $start_gradient = $this->getRgba(isset($settings->gradient->start_color_rgb) ? $settings->gradient->start_color_rgb : '', $settings->gradient->start, $background_transparency);
                $end_gradient = $this->getRgba(isset($settings->gradient->end_color_rgb) ? $settings->gradient->end_color_rgb : '', $settings->gradient->end, $background_transparency);
                if (empty($settings->gradient->orientation) || $settings->gradient->orientation == 'linear') {
                    $gradient_type = (empty($settings->gradient->type) || $settings->gradient->type == 'to_bottom') ? 'top' : ($settings->gradient->type == 'to_left' ? 'right' : ($settings->gradient->type == 'to_right' ? 'left' : ($settings->gradient->type == 'to_top' ? 'bottom' : '')));
                    $gradient_type2 = (empty($settings->gradient->type) || $settings->gradient->type == 'to_bottom') ? 'to bottom' : ($settings->gradient->type == 'to_left' ? 'to left' : ($settings->gradient->type == 'to_right' ? 'to right' : ($settings->gradient->type == 'to top' ? 'to top' : '')));
                    $background .= 'background:-moz-linear-gradient(' . $gradient_type . ',' . $start_gradient . ' 0,' . $end_gradient . ' 100%)' . $this->isImportant() . ';';
                    $background .= 'background:-ms-linear-gradient(' . $gradient_type . ',' . $start_gradient . ' 0,' . $end_gradient . ' 100%)' . $this->isImportant() . ';';
                    $background .= 'background:-o-linear-gradient(' . $gradient_type . ',' . $start_gradient . ' 0,' . $end_gradient . ' 100%)' . $this->isImportant() . ';';
                    $background .= 'background:-webkit-linear-gradient(' . $gradient_type . ',' . $start_gradient . ' 0,' . $end_gradient . ' 100%)' . $this->isImportant() . ';';
                    $background .= 'background:linear-gradient(' . $gradient_type2 . ',' . $start_gradient . ' 0,' . $end_gradient . ' 100%)' . $this->isImportant() . ';';
                } else {
                    $gradient_type = (empty($settings->gradient->type) || $settings->gradient->type == 'to_bottom') ? 'top' : ($settings->gradient->type == 'to_left' ? 'right' : ($settings->gradient->type == 'to_right' ? 'left' : ($settings->gradient->type == 'to_top' ? 'bottom' : '')));
                    $background .= 'background:-moz-radial-gradient(center ' . $gradient_type . ',circle farthest-side,' . $start_gradient . ' 0,' . $end_gradient . ' 100%)' . $this->isImportant() . ';';
                    $background .= 'background:-ms-radial-gradient(center ' . $gradient_type . ',circle farthest-side,' . $start_gradient . ' 0,' . $end_gradient . ' 100%)' . $this->isImportant() . ';';
                    $background .= 'background:-o-radial-gradient(center ' . $gradient_type . ',circle farthest-side,' . $start_gradient . ' 0,' . $end_gradient . ' 100%)' . $this->isImportant() . ';';
                    $background .= 'background:-webkit-radial-gradient(center ' . $gradient_type . ',circle farthest-side,' . $start_gradient . ' 0,' . $end_gradient . ' 100%)' . $this->isImportant() . ';';
                    $background .= 'background:radial-gradient(circle farthest-side at center ' . $gradient_type . ',' . $start_gradient . ' 0,' . $end_gradient . ' 100%)' . $this->isImportant() . ';';
                }
            }
        }
        return $background;
    }

    /**
     * Generate border styles
     *
     * @param object $settings
     * @return string
     */
    private function generateBorder($settings)
    {
        $styles = "";
        if (!empty($settings->width) && !empty($settings->style) && !empty($settings->color)) {
            $styles .= 'border:' . $settings->width . "px " . $settings->style . ' #' . $this->shortHexColor($settings->color) . $this->isImportant() . ';';
        }
        return $styles;
    }

    /**
     * Generate color styles
     *
     * @param object $settings
     * @return string
     */
    private function generateColor($settings)
    {
        $styles = "";
        if (!empty($settings->color)) {
            $styles .= 'color:#' . $this->shortHexColor($settings->color) . $this->isImportant() . ';';
        }
        return $styles;
    }

    /**
     * Generate box-shadow styles
     *
     * @param object $settings
     * @return string
     */
    private function generateBox_shadow($settings)
    {
        $styles = "";
        if (!empty($settings->{'x-offset'}) || !empty($settings->{'y-offset'}) || !empty($settings->blur) || !empty($settings->spread)) {
            $shadow = (!empty($settings->{'x-offset'}) ? $settings->{'x-offset'} . 'px' : 0) .
                (!empty($settings->{'y-offset'}) ? ' ' . $settings->{'y-offset'} . 'px' : ' 0') .
                (!empty($settings->blur) ? ' ' . $settings->blur . 'px' : ' 0') .
                (!empty($settings->spread) ? ' ' . $settings->spread . 'px' : ' 0') .
                (!empty($settings->color) ? ' #' . $settings->color : ' #ffffff') .
                (!empty($settings->inset) ? ' inset' : '') . $this->isImportant() . ';';
            $styles .= '-webkit-box-shadow:' . $shadow . '-moz-box-shadow:' . $shadow . 'box-shadow:' . $shadow;
        }

        return $styles;
    }

    /**
     * Generate border radius styles
     *
     * @param object $settings
     * @return string
     */
    private function generateBorder_radius($settings)
    {
        $styles = "";

        if (!empty($settings->value) && $settings->value !== '') {
            $unit = !empty($settings->unit) ? $settings->unit : 'px';
            $css = (intval($settings->value) ? $settings->value . $unit : 0) . $this->isImportant() . ';';
            $styles .= '-webkit-border-radius:' . $css . '-moz-border-radius:' . $css . 'border-radius:' . $css;
        }

        return $styles;
    }

    /**
     * Generate font styles
     *
     * @param object $settings
     * @return string
     */
    private function generateFont($settings)
    {
        $styles = "";

//        $styles .= !empty($settings->color) ? 'color:#' . $this->shortHexColor($settings->color) . ';' : '';
        $styles .= !empty($settings->family) ? 'font-family:' . $settings->family . $this->isImportant() . ';' : '';
        $styles .= !empty($settings->size) ? 'font-size:' . $settings->size . 'px' . $this->isImportant() . ';' : '';
        // Сохраняем отдельно шрифты, чтобы потом понимать, стоит ли подключать Google fonts или нет
        if (!empty($settings->family)) {
            self::$fonts[$settings->family] = $settings->family;
        }
        if (!empty($settings->style)) {
            if ($settings->style == 'bold') {
                $styles .= 'font-style:normal' . $this->isImportant() . ';font-weight:bold' . $this->isImportant() . ';';
            } else if ($settings->style == 'bolditalic') {
                $styles .= 'font-style:italic' . $this->isImportant() . ';font-weight:bold' . $this->isImportant() . ';';
            } else if ($settings->style == 'italic') {
                $styles .= 'font-style:italic' . $this->isImportant() . ';font-weight:normal' . $this->isImportant() . ';';
            } else {
                $styles .= 'font-style:normal' . $this->isImportant() . ';font-weight:normal' . $this->isImportant() . ';';
            }
        }

        return $styles;
    }

    /**
     * Generate padding styles
     *
     * @param object $settings
     * @return string
     */
    private function generatePadding($settings)
    {
        return $this->generateMarPad('padding', $settings);
    }

    /**
     * Generate margin styles
     *
     * @param object $settings
     * @return string
     */
    private function generateMargin($settings)
    {
        return $this->generateMarPad('margin', $settings);
    }

    /**
     * Helper function for generating margin amd padding styles
     *
     * @param string $type
     * @param object $settings
     * @return string
     */
    private function generateMarPad($type, $settings)
    {
        $styles = "";

        $values = array();
        if (!empty($settings->top) && $settings->top !== '') {
            $values['top'] = (float) $settings->top;
        }
        if (!empty($settings->right) && $settings->right !== '') {
            $values['right'] = (float) $settings->right;
        }
        if (!empty($settings->bottom) && $settings->bottom !== '') {
            $values['bottom'] = (float) $settings->bottom;
        }
        if (!empty($settings->left) && $settings->left !== '') {
            $values['left'] = (float) $settings->left;
        }

        $styles .= isset($values['top']) ? $type . '-top:' . $values['top'] . 'px' . $this->isImportant() . ';' : '';
        $styles .= isset($values['right']) ? $type . '-right:' . $values['right'] . 'px' . $this->isImportant() . ';' : '';
        $styles .= isset($values['bottom']) ? $type . '-bottom:' . $values['bottom'] . 'px' . $this->isImportant() . ';' : '';
        $styles .= isset($values['left']) ? $type . '-left:' . $values['left'] . 'px' . $this->isImportant() . ';' : '';

        if (count($values) === 4) {
            if ($values['top'] == $values['right'] && $values['right'] == $values['bottom'] && $values['bottom'] == $values['left'] && $values['left'] == $values['top']) {
                $styles = $type . ':' . $values['right'] . 'px' . $this->isImportant() . ';';
            } elseif ($values['top'] == $values['bottom'] && $values['right'] == $values['left']) {
                $styles = $type . ':' . $values['top'] . 'px ' . $values['right'] . 'px' . $this->isImportant() . ';';
            } elseif ($values['top'] !== $values['bottom'] && $values['right'] == $values['left']) {
                $styles = $type . ':' . $values['top'] . 'px ' . $values['right'] . 'px ' . $values['bottom'] . 'px' . $this->isImportant() . ';';
            } else {
                $styles = $type . ':' . $values['top'] . 'px ' . $values['right'] . 'px ' . $values['bottom'] . 'px ' . $values['left'] . 'px' . $this->isImportant() . ';';
            }
        }
        return $styles;
    }

    /**
     * Generate text align styles
     *
     * @param object $settings
     * @return string
     */
    private function generateText_align($settings)
    {
        $styles = "";
        if (!empty($settings->value)) {
            $styles .= 'text-align:' . $settings->value . $this->isImportant() . ';';
        }
        return $styles;
    }

    /**
     * Generate width styles
     *
     * @param object $settings
     * @return string
     */
    private function generateWidth($settings)
    {
        $styles = "";
        if (!empty($settings->value)) {
            $styles .= 'width:' . $settings->value . $this->isImportant() . ';';
        }
        return $styles;
    }

    private function getRgba($rgba_str, $hex, $transparency = 1)
    {
        $rgba = 'rgba(';
        if (!empty($rgba_str)) {
            $rgba .= $rgba_str;
        } else {
            $rgbObj = $this->hexToRGB($hex);
            $rgba .= $rgbObj['r'] . ',' . $rgbObj['g'] . ',' . $rgbObj['b'];
        }
        $rgba .= ',' . $transparency . ')';
        return $rgba;
    }

    private function hexToRgb($hex)
    {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) !== 6) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $hex = intval($hex, 16);
        return array('r' => $hex >> 16, 'g' => ($hex & 0x00FF00) >> 8, 'b' => ($hex & 0x0000FF));
    }

    /**
     * Convert 6 dec hex color to 3 dec
     *
     * @param string $hex
     * @return string E.g #ffffff converts to #f00
     */
    private function shortHexColor($hex)
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
     * Should we use !important in CSS rules
     *
     * @return string
     */
    private function isImportant()
    {
        static $is_important = array();
        if (!isset($is_important[$this->current_tab])) {
            $is_important[$this->current_tab] = '';
            //Настройки
            $settings = shopQuickorderPluginHelper::getSettings();
            $tab_settings = !empty($settings['shared_appearance_settings']) ? $settings['product'] : $settings[$this->current_tab];
            if (!empty($tab_settings['use_important'])) {
                $is_important[$this->current_tab] = ' !important ';
            }
        }
        return $is_important[$this->current_tab];
    }
}
