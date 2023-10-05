<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopWhatsappPlugin extends shopPlugin
{

    public function frontendProduct($product)
    {
        if ($html = self::show($product)) {
            //Настройки
            $settings = include self::path('config.php');
            $output = array(
                'cart' => !empty($settings['output_places']) && in_array('cart', $settings['output_places']) ? $html : '',
                'menu' => !empty($settings['output_places']) && in_array('menu', $settings['output_places']) ? $html : '',
                'block_aux' => !empty($settings['output_places']) && in_array('block_aux', $settings['output_places']) ? $html : '',
                'block' => !empty($settings['output_places']) && in_array('block', $settings['output_places']) ? $html : '',
            );
            return $output;
        }
    }

    /**
     * Get html of button
     *
     * @staticvar int $first_load
     * @param array $product
     * @param array $params - custom params. Can be:
     *                        - button_name
     *                        - message
     *                        - name (%name% to replace in message)
     *                        - url (%link% to replace in message)
     * @return string
     * @throws Exception
     */
    public static function show($product, $params = array())
    {
        static $first_load = 1;
        $html = "";
        //Настройки
        $settings = include self::path('config.php');

        if (((!empty($settings['only_mobile']) || !isset($settings['only_mobile'])) && self::isMobile()) || (isset($settings['only_mobile']) && empty($settings['only_mobile']))) {

            if (!empty($settings['enable'])) {
                // Произвольные параметры
                $settings['button_name'] = !empty($params['button_name']) ? $params['button_name'] : $settings['button_name'];
                $settings['message'] = !empty($params['message']) ? $params['message'] : $settings['message'];
                $settings['name'] = !empty($params['name']) ? $params['name'] : (!empty($product['name']) ? $product['name'] : "");
                $product_url = !empty($params['url']) ? $params['url'] : (!empty($product['url']) ? $product['url'] : "");

                $styles = "";
                $route_params = array();
                $styles .= !empty($settings['button']['background']) ? "background: #" . $settings['button']['background'] . ";" : "";
                $styles .= !empty($settings['button']['text']) ? "color: #" . $settings['button']['text'] . ";" : "";
                $styles .= !empty($settings['button']['width']) ? "width: " . $settings['button']['width'] . "px;" : "";
                $styles .= !empty($settings['button']['height']) ? "height: " . $settings['button']['height'] . "px;" : "";
                $styles .= !empty($settings['button']['size']) ? "font-size: " . $settings['button']['size'] . "px;" : "";
                $styles .= !empty($settings['button']['padding']) ? "padding: " . $settings['button']['padding'] . ";" : "";
                if (!empty($settings['border']['color'])) {
                    $styles .= !empty($settings['border']['color']) ? "border-color: #" . $settings['border']['color'] . ";" : "";
                    $styles .= !empty($settings['border']['width']) ? "border-width: " . $settings['border']['width'] . "px;" : "";
                    $styles .= !empty($settings['border']['style']) ? "border-style: " . $settings['border']['style'] . ";" : "";
                }
                $styles .= !empty($settings['border']['radius']) ? "-webkit-border-radius: " . $settings['border']['radius'] . "px; -moz-border-radius: " . $settings['border']['radius'] . "px; border-radius:" . $settings['border']['radius'] . "px;" : "";

                if (empty($params['url'])) {
                    $route_params['product_url'] = $product['url'];
                    if (isset($product['category_url'])) {
                        $route_params['category_url'] = $product['category_url'];
                    } else {
                        $route_params['category_url'] = '';
                    }
                    $product_url = wa()->getRouteUrl('shop/frontend/product', $route_params, true);
                }
                if ($first_load) {
                    $plugin_url = wa()->getAppStaticUrl('shop', true) . 'plugins/whatsapp/';
                    $html .= "<style>i.whatsapp-icon{background:url(" . $plugin_url . "img/whatsapp24.png) no-repeat;display:inline-block;width:24px;margin-right:5px;height:24px;vertical-align:middle;padding-top: 5px;}@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {i.whatsapp-icon{background-image:url(" . $plugin_url . "img/whatsapp48.png);background-size:24px 24px;}}</style>";
                    $first_load = 0;
                }
                $view = wa()->getView();
                $text = $view->fetch('string:' . $settings['message']);
                $html .= "<a class='whatsapp-button' title='" . htmlspecialchars(strip_tags($settings['button_name'])) . "' href=\"https://api.whatsapp.com/send?text=" . htmlspecialchars(str_replace(array("%link%", "%name%"), array($product_url, $settings['name']), $text)) . "\"" . (!empty($settings['onclick']) ? ' onclick="' . $settings['onclick'] . '"' : "") . " style=\"display: inline-block;text-decoration:none; " . $styles . "\">" . $settings['button_name'] . "</a>";
            }
        }
        return $html;
    }

    /**
     * Check if device is Mobile
     *
     * @return bool
     */
    private static function isMobile()
    {
        $user_agent = waRequest::server('HTTP_USER_AGENT');

        $patterns = array(
            'ipad' => 'ipad',
            'ipod' => 'ipod',
            'iphone' => 'iphone',
            'android' => 'android'
        );
        foreach ($patterns as $id => $pattern) {
            if (preg_match('/' . $pattern . '/i', $user_agent)) {
                return $id;
            }
        }

        return waRequest::isMobile(false);
    }

    /**
     * Get path to file
     *
     * @param string $file - filename or path
     * @param bool $original - if true - return original path to file
     * @return string - protected path to file
     * @throws Exception
     */
    public static function path($file, $original = false)
    {
        $path = wa()->getDataPath('plugins/whatsapp/' . $file, false, 'shop', true);
        if ($original) {
            return dirname(__FILE__) . '/config/' . $file;
        }
        if (!file_exists($path)) {
            waFiles::copy(dirname(__FILE__) . '/config/' . $file, $path);
        }
        return $path;
    }

}
