<?php

/*
 * Do not copy that class, it's not universal
 * */

class shopProductsetsPluginGenerator
{
    /* This constants are using only for CSS styles */
    const BUNDLE_BLOCK_ATTR = 'data-productsets-bb';
    const BUNDLE_BLOCK_HEADER_ATTR = 'data-productsets-bbh';
    const BUNDLE_BLOCK_CONTENT_ATTR = 'data-productsets-bbc';
    const BUNDLE_PRICE_ATTR = 'data-productsets-bp';
    const BUNDLE_COMPARE_PRICE_ATTR = 'data-productsets-bcp';
    const BUNDLE_DISCOUNT_ATTR = 'data-productsets-bd';
    const BUNDLE_PRODUCT_PRICE_ATTR = 'data-productsets-bpp';
    const BUNDLE_PRODUCT_COMPARE_PRICE_ATTR = 'data-productsets-bpcp';
    const BUNDLE_BADGE_ATTR = 'data-productsets-bbad';
    const BUNDLE_QUANTITY_ATTR = 'data-productsets-bq';
    const BUNDLE_QUANTITY_FIELD_ATTR = 'data-productsets-bqf';
    const BUNDLE_SKUS_ICON_ATTR = 'data-productsets-bsi';
    const BUNDLE_SKUS_WRAP_ICON_ATTR = 'data-productsets-bswi';
    const BUNDLE_SKU_ATTR = 'data-productsets-bs';
    const BUNDLE_SUBMIT_ATTR = 'data-productsets-bsub';
    const BUNDLE_SLIDER_ARROW_ATTR = 'data-productsets-bsa';
    const BUNDLE_SLIDER_ARROW_BLOCK_ATTR = 'data-productsets-bsab';
    const BUNDLE_PLUS_ATTR = 'data-productsets-bpl';
    const BUNDLE_CHECKBOX_ATTR = 'data-productsets-bc';
    const USERBUNDLE_BLOCKS_ATTR = 'data-productsets-ub';
    const USERBUNDLE_BUTTON_ATTR = 'data-productsets-ubutton';
    const USERBUNDLE_TITLES_ATTR = 'data-productsets-ut';
    const USERBUNDLE_TITLE_LINES_ATTR = 'data-productsets-utl';
    const USERBUNDLE_TITLE_INSIDE_ATTR = 'data-productsets-uti';
    const USERBUNDLE_PRICE_ATTR = 'data-productsets-up';
    const USERBUNDLE_COMPARE_PRICE_ATTR = 'data-productsets-ucp';
    const USERBUNDLE_COUNT_ATTR = 'data-productsets-uc';
    const USERBUNDLE_DISCOUNT_ATTR = 'data-productsets-ud';
    const USERBUNDLE_PRODUCT_PRICE_ATTR = 'data-productsets-upp';
    const USERBUNDLE_PRODUCT_COMPARE_PRICE_ATTR = 'data-productsets-upcp';
    const USERBUNDLE_BADGE_ATTR = 'data-productsets-ubad';
    const USERBUNDLE_QUANTITY_ATTR = 'data-productsets-uq';
    const USERBUNDLE_QUANTITY_FIELD_ATTR = 'data-productsets-uqf';
    const USERBUNDLE_SKUS_ICON_ATTR = 'data-productsets-usi';
    const USERBUNDLE_SKUS_WRAP_ICON_ATTR = 'data-productsets-uswi';
    const USERBUNDLE_SUBMIT_ATTR = 'data-productsets-us';
    const USERBUNDLE_ADD_ATTR = 'data-productsets-ua';
    const USERBUNDLE_ADDED_ATTR = 'data-productsets-uadded';
    const USERBUNDLE_SKU_ATTR = 'data-productsets-usk';
    const SKUS_POPUP_BLOCK_BUNDLE_ATTR = 'data-productsets-spbb';
    const SKUS_POPUP_HEADER_BUNDLE_ATTR = 'data-productsets-sphb';
    const SKUS_POPUP_CONTENT_BUNDLE_ATTR = 'data-productsets-spcb';
    const SKUS_POPUP_CLOSE_BUNDLE_ATTR = 'data-productsets-spclb';
    const SKUS_POPUP_BLOCK_USERBUNDLE_ATTR = 'data-productsets-spbu';
    const SKUS_POPUP_HEADER_USERBUNDLE_ATTR = 'data-productsets-sphu';
    const SKUS_POPUP_CONTENT_USERBUNDLE_ATTR = 'data-productsets-spcu';
    const SKUS_POPUP_CLOSE_USERBUNDLE_ATTR = 'data-productsets-spclu';
    const SUCCESS_POPUP_BLOCK_BUNDLE_ATTR = 'data-productsets-sucpbb';
    const SUCCESS_POPUP_CONTENT_BUNDLE_ATTR = 'data-productsets-sucpcb';
    const SUCCESS_POPUP_TICK_BUNDLE_ATTR = 'data-productsets-sucptb';
    const SUCCESS_POPUP_LINK_BUNDLE_ATTR = 'data-productsets-sucplb';
    const SUCCESS_POPUP_BUTTON_BUNDLE_ATTR = 'data-productsets-sucpbutb';
    const SUCCESS_POPUP_CLOSE_BUNDLE_ATTR = 'data-productsets-sucpclb';
    const SUCCESS_POPUP_BLOCK_USERBUNDLE_ATTR = 'data-productsets-sucpbu';
    const SUCCESS_POPUP_CONTENT_USERBUNDLE_ATTR = 'data-productsets-sucpcu';
    const SUCCESS_POPUP_TICK_USERBUNDLE_ATTR = 'data-productsets-sucptu';
    const SUCCESS_POPUP_LINK_USERBUNDLE_ATTR = 'data-productsets-sucplu';
    const SUCCESS_POPUP_BUTTON_USERBUNDLE_ATTR = 'data-productsets-sucpbutu';
    const SUCCESS_POPUP_CLOSE_USERBUNDLE_ATTR = 'data-productsets-sucpclu';

    private static $css = '';
    private $is_important = null;
    private static $fonts = array();
    private $extra = array();
    // Ключи - это элементы внещнего вида из настроек
    private $element_attr = array(
        'bundle_block' => array(
            'bundle' => self::BUNDLE_BLOCK_ATTR,
        ),
        'bundle_block_header' => array(
            'bundle' => self::BUNDLE_BLOCK_HEADER_ATTR,
        ),
        'bundle_block_content' => array(
            'bundle' => self::BUNDLE_BLOCK_CONTENT_ATTR,
        ),
        'bundle_price' => array(
            'bundle' => self::BUNDLE_PRICE_ATTR,
        ),
        'bundle_compare_price' => array(
            'bundle' => self::BUNDLE_COMPARE_PRICE_ATTR,
        ),
        'bundle_discount' => array(
            'bundle' => self::BUNDLE_DISCOUNT_ATTR,
        ),
        'bundle_product_price' => array(
            'bundle' => self::BUNDLE_PRODUCT_PRICE_ATTR,
        ),
        'bundle_product_com_price' => array(
            'bundle' => self::BUNDLE_PRODUCT_COMPARE_PRICE_ATTR,
        ),
        'bundle_badge' => array(
            'bundle' => self::BUNDLE_BADGE_ATTR,
        ),
        'bundle_quantity' => array(
            'bundle' => self::BUNDLE_QUANTITY_ATTR,
        ),
        'bundle_quantity_field' => array(
            'bundle' => self::BUNDLE_QUANTITY_FIELD_ATTR,
        ),
        'bundle_skus_icon' => array(
            'bundle' => self::BUNDLE_SKUS_ICON_ATTR,
        ),
        'bundle_skus_wrap_icon' => array(
            'bundle' => self::BUNDLE_SKUS_WRAP_ICON_ATTR,
        ),
        'bundle_sku' => array(
            'bundle' => self::BUNDLE_SKU_ATTR,
        ),
        'bundle_submit' => array(
            'bundle' => self::BUNDLE_SUBMIT_ATTR,
        ),
        'bundle_slider_arrow' => array(
            'bundle' => self::BUNDLE_SLIDER_ARROW_ATTR,
        ),
        'bundle_block_slider_arrow' => array(
            'bundle' => self::BUNDLE_SLIDER_ARROW_BLOCK_ATTR,
        ),
        'bundle_plus' => array(
            'bundle' => self::BUNDLE_PLUS_ATTR,
            'pseudo' => ['before' => ':before', 'after' => ':after']
        ),
        'bundle_checkbox' => array(
            'bundle' => self::BUNDLE_CHECKBOX_ATTR,
        ),
        'userbundle_blocks' => array(
            'userbundle' => self::USERBUNDLE_BLOCKS_ATTR,
        ),
        'userbundle_button' => array(
            'userbundle' => self::USERBUNDLE_BUTTON_ATTR,
        ),
        'userbundle_titles' => array(
            'userbundle' => self::USERBUNDLE_TITLES_ATTR,
        ),
        'userbundle_title_lines' => array(
            'userbundle' => self::USERBUNDLE_TITLE_LINES_ATTR,
            'pseudo' => ['before' => ':before', 'after' => ':after']
        ),
        'userbundle_title_inside' => array(
            'userbundle' => self::USERBUNDLE_TITLE_INSIDE_ATTR,
        ),
        'userbundle_price' => array(
            'userbundle' => self::USERBUNDLE_PRICE_ATTR,
        ),
        'userbundle_compare_price' => array(
            'userbundle' => self::USERBUNDLE_COMPARE_PRICE_ATTR,
        ),
        'userbundle_count' => array(
            'userbundle' => self::USERBUNDLE_COUNT_ATTR,
        ),
        'userbundle_discount' => array(
            'userbundle' => self::USERBUNDLE_DISCOUNT_ATTR,
        ),
        'userbundle_product_price' => array(
            'userbundle' => self::USERBUNDLE_PRODUCT_PRICE_ATTR,
        ),
        'userbundle_product_com_price' => array(
            'userbundle' => self::USERBUNDLE_PRODUCT_COMPARE_PRICE_ATTR,
        ),
        'userbundle_badge' => array(
            'userbundle' => self::USERBUNDLE_BADGE_ATTR,
        ),
        'userbundle_quantity' => array(
            'userbundle' => self::USERBUNDLE_QUANTITY_ATTR,
        ),
        'userbundle_quantity_field' => array(
            'userbundle' => self::USERBUNDLE_QUANTITY_FIELD_ATTR,
        ),
        'userbundle_skus_icon' => array(
            'userbundle' => self::USERBUNDLE_SKUS_ICON_ATTR,
        ),
        'userbundle_skus_wrap_icon' => array(
            'userbundle' => self::USERBUNDLE_SKUS_WRAP_ICON_ATTR,
        ),
        'userbundle_submit' => array(
            'userbundle' => self::USERBUNDLE_SUBMIT_ATTR,
        ),
        'userbundle_add' => array(
            'userbundle' => self::USERBUNDLE_ADD_ATTR,
        ),
        'userbundle_added' => array(
            'userbundle' => self::USERBUNDLE_ADDED_ATTR,
        ),
        'userbundle_sku' => array(
            'userbundle' => self::USERBUNDLE_SKU_ATTR,
        ),
        'skus_popup_block' => array(
            'bundle' => self::SKUS_POPUP_BLOCK_BUNDLE_ATTR,
            'userbundle' => self::SKUS_POPUP_BLOCK_USERBUNDLE_ATTR,
        ),
        'skus_popup_header' => array(
            'bundle' => self::SKUS_POPUP_HEADER_BUNDLE_ATTR,
            'userbundle' => self::SKUS_POPUP_HEADER_USERBUNDLE_ATTR,
        ),
        'skus_popup_content' => array(
            'bundle' => self::SKUS_POPUP_CONTENT_BUNDLE_ATTR,
            'userbundle' => self::SKUS_POPUP_CONTENT_USERBUNDLE_ATTR,
        ),
        'skus_popup_close' => array(
            'bundle' => self::SKUS_POPUP_CLOSE_BUNDLE_ATTR,
            'userbundle' => self::SKUS_POPUP_CLOSE_USERBUNDLE_ATTR,
            'pseudo' => ['before' => ':before', 'after' => ':after']
        ),
        'success_popup_block' => array(
            'bundle' => self::SUCCESS_POPUP_BLOCK_BUNDLE_ATTR,
            'userbundle' => self::SUCCESS_POPUP_BLOCK_USERBUNDLE_ATTR,
        ),
        'success_popup_content' => array(
            'bundle' => self::SUCCESS_POPUP_CONTENT_BUNDLE_ATTR,
            'userbundle' => self::SUCCESS_POPUP_CONTENT_USERBUNDLE_ATTR,
        ),
        'success_popup_tick' => array(
            'bundle' => self::SUCCESS_POPUP_TICK_BUNDLE_ATTR,
            'userbundle' => self::SUCCESS_POPUP_TICK_USERBUNDLE_ATTR,
        ),
        'success_popup_link' => array(
            'bundle' => self::SUCCESS_POPUP_LINK_BUNDLE_ATTR,
            'userbundle' => self::SUCCESS_POPUP_LINK_USERBUNDLE_ATTR,
        ),
        'success_popup_button' => array(
            'bundle' => self::SUCCESS_POPUP_BUTTON_BUNDLE_ATTR,
            'userbundle' => self::SUCCESS_POPUP_BUTTON_USERBUNDLE_ATTR,
        ),
        'success_popup_close' => array(
            'bundle' => self::SUCCESS_POPUP_CLOSE_BUNDLE_ATTR,
            'userbundle' => self::SUCCESS_POPUP_CLOSE_USERBUNDLE_ATTR,
            'pseudo' => ['before' => ':before', 'after' => ':after']
        ),
    );

    /**
     * Get css
     *
     * @return string
     */
    private function getCss()
    {
        if (self::$css) {
            self::$css = str_replace(';}', '}', self::$css);
        }

        return self::$css;
    }

    public function getGoogleFont()
    {
        $google_fonts = array("'Open Sans',sans-serif" => "Open+Sans", "'Open Sans Condensed',sans-serif" => "Open+Sans+Condensed:300", "'Roboto',sans-serif" => "Roboto", "'Roboto Condensed',sans-serif" => "Roboto+Condensed", "'Roboto Slab',serif" => "Roboto+Slab", "'PT Sans',sans-serif" => "PT+Sans", "'Lora',serif" => "Lora", "'Lobster',cursive" => "Lobster", "'Ubuntu',sans-serif" => "Ubuntu", "'Noto Sans',sans-serif" => "Noto+Sans");
        $active_fonts = array();
        foreach (self::$fonts as $f) {
            if (isset($google_fonts[$f])) {
                $active_fonts[$f] = $google_fonts[$f];
            }
        }
        return $active_fonts ? '<link href="https://fonts.googleapis.com/css?family=' . implode('%7C', $active_fonts) . '" rel="stylesheet">' : '';
    }

    /**
     * Generate CSS styles and save them to static variable $css
     *
     * @param array $set
     * @return string
     */
    public function getStyles($set)
    {
        self::$css = '';

        $settings = ifempty($set, 'settings', []);
        if (!empty($settings['appearance'])) {
            $touched = [];
            $this->setImportant($settings);
            foreach ($settings['appearance'] as $type => $appearance) {
                if (!$appearance || !in_array($type, ['bundle', 'userbundle'])) {
                    continue;
                }
                $settings['appearance_settings'][$type] = $this->decodeToArray($settings['appearance_settings'][$type]);
                $appearance = str_replace(array('box-shadow', 'border-radius', 'text-align'), array('box_shadow', 'border_radius', 'text_align'), $appearance);
                $appearance = $this->decode($appearance);
                foreach ($appearance as $element => $styles) {
                    // Не даем повторять стили, если для разных типов используются одинаковые атрибуты
                    if (isset($touched[$element][$this->element_attr[$element][$type]])) {
                        continue;
                    }
                    $prefix = '.productsets-wrap[data-id="' . $set['id'] . '"] ';
                    $prefix .= (!empty($this->element_attr[$element]['prefix']) ? $this->element_attr[$element]['prefix'] . ' ' : '');
                    $pseudo = (!empty($this->element_attr[$element]['pseudo']) ? $this->element_attr[$element]['pseudo'] : '');
                    $style_settings = !empty($settings['appearance_settings'][$type][$element]) ? $settings['appearance_settings'][$type][$element] : [];
                    $css_rule = $prefix . "[" . $this->element_attr[$element][$type] . "]";
                    if (isset($styles->hover)) {
                        $normal_styles = $this->generateStyles($styles->normal, $style_settings['normal']);
                        if ($normal_styles) {
                            self::$css .= $css_rule . $this->getPseudo($pseudo, $css_rule) . "{" . $normal_styles . "}";
                        }
                        $hover_styles = $this->generateStyles($styles->hover, $style_settings['hover']);
                        if ($hover_styles) {
                            self::$css .= $css_rule . ":hover" . $this->getPseudo($pseudo, $css_rule . ':hover') . "{" . $hover_styles . "}";
                        }
                    } else {
                        $element_styles = $this->generateStyles($styles, $style_settings);
                        if ($element_styles) {
                            self::$css .= $css_rule . $this->getPseudo($pseudo, $css_rule) . "{" . $element_styles . "}";
                        }
                    }
                    // Создаем стили для зависимых элементов формы
                    $this->extraActions($element, $type, $prefix, $styles, $style_settings);

                    $touched[$element][$this->element_attr[$element][$type]] = 1;
                }
                unset($appearance);
            }
        }

        return self::getCss();
    }

    private function getPseudo($pseudo, $css_rule)
    {
        $result = '';
        if (!empty($pseudo['before']) && !empty($pseudo['after'])) {
            $result .= ':before,' . $css_rule . ':after';
        } else {
            $result .= (!empty($pseudo['before']) ? $pseudo['before'] : (!empty($pseudo['after']) ? $pseudo['after'] : ''));
        }
        return $result;
    }

    /**
     * Additional checks. Results save to $this->extra array
     *
     * @param string $active_element
     * @param string $type
     * @param string $prefix
     * @param array $styles
     * @param array $style_settings
     */
    private function extraActions($active_element, $type, $prefix, $styles, $style_settings)
    {
        if ($active_element == 'bundle_slider_arrow') {
            self::$css .= $prefix . "[" . self::BUNDLE_SLIDER_ARROW_BLOCK_ATTR . "]:hover [" . $this->element_attr[$active_element][$type] . "],";
            self::$css .= $prefix . ".productsets-bundle-item .slick-arrow:hover [" . $this->element_attr[$active_element][$type] . "]"."{" . $this->generateStyles($styles->hover, $style_settings['hover']) . "}";
        } elseif ($active_element == 'bundle_block_content' && !empty($styles->background->type)) {
            $color = $styles->background->type == 'gradient' && isset($styles->background->gradient->start) ? $styles->background->gradient->start : (isset($styles->background->color) ? $styles->background->color : 'fff');
            $color_rgb = $styles->background->type == 'gradient' && isset($styles->background->gradient->start_color_rgb) ? $styles->background->gradient->start_color_rgb : (isset($styles->background->color_rgb) ? $styles->background->color_rgb : '255,255,255');
            self::$css .= $prefix . '.productsets-item-name:before{';
            self::$css .= 'background:-webkit-linear-gradient(top,rgba(' . $color_rgb . ', 0),#' . $color . ' 100%,#' . $color . ' 10%,#' . $color . ');';
            self::$css .= 'background:-ms-linear-gradient(top,rgba(' . $color_rgb . ', 0),#' . $color . ' 100%,#' . $color . ' 10%,#' . $color . ');';
            self::$css .= 'background:-moz-linear-gradient(top,rgba(' . $color_rgb . ', 0),#' . $color . ' 100%,#' . $color . ' 10%,#' . $color . ');';
            self::$css .= 'background:linear-gradient(to bottom,rgba(' . $color_rgb . ', 0),#' . $color . ' 100%,#' . $color . ' 10%,#' . $color . ')}';
        } elseif ($active_element == 'userbundle_add') {
            $color = (!empty($styles->normal) && !empty($styles->normal->color) && !empty($styles->normal->color->color) ? $styles->normal->color->color : '000');
            $hover_color = (!empty($styles->hover) && !empty($styles->hover->color) && !empty($styles->hover->color->color) ? $styles->hover->color->color : '000');
            self::$css .= $prefix . "[" . self::USERBUNDLE_ADD_ATTR . "] svg{fill:#" . $color . "}";
            self::$css .= $prefix . "[" . self::USERBUNDLE_ADD_ATTR . "]:hover svg{fill:#" . $hover_color . "}";
            self::$css .= $prefix . ".productsets-item.added [" . self::USERBUNDLE_ADD_ATTR . "]:hover{" . $this->generateStyles($styles->hover, $style_settings['hover']) . "}";
        } elseif ($active_element == 'userbundle_added') {
            self::$css .= $prefix . ".productsets-item.added [" . self::USERBUNDLE_ADD_ATTR . "]{" . $this->generateStyles($styles, $style_settings) . "}";
        } elseif ($active_element == 'bundle_block_slider_arrow' && !empty($styles->hover->background)) {
            self::$css .= ".productsets-mobile" . $prefix . "[" . self::BUNDLE_SLIDER_ARROW_BLOCK_ATTR . "]{" . $this->generateBackground($styles->hover->background, $style_settings['hover']['background']) . "}";
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
     * @param array $style_settings
     * @return string
     */
    public function generateStyles($styles, $style_settings = [])
    {
        $css = "";
        if ($styles) {
            foreach ($styles as $attr => $settings) {
                $method_name = 'generate' . ucfirst($attr);
                if (method_exists($this, $method_name)) {
                    $css .= $this->$method_name($settings, $style_settings);
                }
            }
        }
        return $css;
    }

    /**
     * Generate background styles
     *
     * @param object $settings
     * @param array $style_settings
     * @return string
     */
    private function generateBackground($settings, $style_settings = [])
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
     * @param array $style_settings
     * @return string
     */
    private function generateBorder($settings, $style_settings = [])
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
     * @param array $style_settings
     * @return string
     */
    private function generateColor($settings, $style_settings = [])
    {
        $styles = "";
        if (!empty($settings->color)) {
            $styles .= (!empty($style_settings['color']['svg']) ? 'fill' : 'color') . ':#' . $this->shortHexColor($settings->color) . $this->isImportant() . ';';
        }
        return $styles;
    }

    /**
     * Generate box-shadow styles
     *
     * @param object $settings
     * @param array $style_settings
     * @return string
     */
    private function generateBox_shadow($settings, $style_settings = [])
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
     * @param array $style_settings
     * @return string
     */
    private function generateBorder_radius($settings, $style_settings = [])
    {
        $styles = "";

        if (!empty($settings->value) && $settings->value !== '') {
            $unit = !empty($settings->unit) ? $settings->unit : 'px';
            if (!is_object($settings->value)) {
                $css = (intval($settings->value) ? $settings->value . $unit : 0) . $this->isImportant() . ';';
                $styles .= '-webkit-border-radius:' . $css . '-moz-border-radius:' . $css . 'border-radius:' . $css;
            } else {
                $border_rad_val = [];
                foreach ($settings->value as $k => $v) {
                    $rad_val = (intval($v) ? $v . $unit : 0);
                    $border_rad_val[$k] = $rad_val;
                    $styles .= 'border-' . $k . '-radius' . ':' . $rad_val . $this->isImportant() . ';';
                }
                if (count($border_rad_val) == 4) {
                    if ($border_rad_val['top-left'] == $border_rad_val['top-right'] && $border_rad_val['top-right'] == $border_rad_val['bottom-right'] && $border_rad_val['bottom-right'] == $border_rad_val['bottom-left']) {
                        $styles = 'border-radius:' . $border_rad_val['top-left'] . $this->isImportant() . ';';
                    } elseif ($border_rad_val['top-left'] == $border_rad_val['bottom-right'] && $border_rad_val['top-right'] == $border_rad_val['bottom-left']) {
                        $styles = 'border-radius:' . $border_rad_val['top-left'] . ' ' . $border_rad_val['top-right'] . $this->isImportant() . ';';
                    }
                }
            }
        }

        return $styles;
    }

    /**
     * Generate font styles
     *
     * @param object $settings
     * @param array $style_settings
     * @return string
     */
    private function generateFont($settings, $style_settings = [])
    {
        $styles = "";

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
     * @param array $style_settings
     * @return string
     */
    private function generatePadding($settings, $style_settings = [])
    {
        return $this->generateMarPad('padding', $settings, $style_settings);
    }

    /**
     * Generate margin styles
     *
     * @param object $settings
     * @param array $style_settings
     * @return string
     */
    private function generateMargin($settings, $style_settings = [])
    {
        return $this->generateMarPad('margin', $settings, $style_settings);
    }

    /**
     * Helper function for generating margin amd padding styles
     *
     * @param string $type
     * @param object $settings
     * @param array $style_settings
     * @return string
     */
    private function generateMarPad($type, $settings, $style_settings = [])
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
     * @param array $style_settings
     * @return string
     */
    private function generateText_align($settings, $style_settings = [])
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
     * @param array $style_settings
     * @return string
     */
    private function generateWidth($settings, $style_settings = [])
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
        return $this->is_important;
    }

    private function setImportant($settings)
    {
        $this->is_important = !empty($settings['appearance']['use_important']) ? ' !important' : '';
    }

    /**
     * Decode JSON object to array
     *
     * @param string|array $json
     * @return array
     */
    private function decodeToArray($json)
    {
        return is_string($json) ? $this->object_to_array(json_decode($json)) : $json;
    }

    private function object_to_array($obj)
    {
        if (is_object($obj)) {
            $obj = (array) $obj;
        }
        if (is_array($obj)) {
            $new = array();
            foreach ($obj as $key => $val) {
                $new[$key] = $this->object_to_array($val);
            }
        } else {
            $new = $obj;
        }
        return $new;
    }

    /**
     * Decode JSON object
     *
     * @param string|array $json
     * @return array
     */
    private function decode($json)
    {
        return is_string($json) ? json_decode($json) : $json;
    }
}
