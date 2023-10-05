<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginAnalytics
{
    protected $order;

    /**
     * Get analytics js code
     *
     * @param array $settings
     * @param string $type - product|cart
     * @return string
     */
    protected function getAnalytics($settings, $type)
    {
        $js = "";
        $analytics = (new shopQuickorderPluginHelper())->getAnalytics($settings, $type);
        // Электронная коммерция Yandex
        if (!empty($analytics['ya_counter']) && !empty($analytics['yaecom'])) {
            $js .= $this->getYandexEcommerce($analytics);
        }
        // Google analytics (analytics.js). Fuck old _gaq
        if (!empty($analytics['ga_counter'])) {
            $js .= $this->getGoogleAnalytics($analytics['ga_counter']);
        }
        if ($js) {
            $js = "<script>{$js}</script>";
            // Подключаем счетчик
            if (!empty($analytics['ya_counter']) || !empty($analytics['yaecom'])) {
                $js = $this->addCounter($analytics['ya_counter'], $analytics['yaecom'], $analytics['yaecom_container']) . $js;
            }
        }
        return $js;
    }

    /**
     * Yandex ecommerce code
     *
     * @param array $analytics
     * @return string
     */
    private function getYandexEcommerce($analytics)
    {
        $result = "try { window.dataLayer = window.dataLayer || [];";
        $result .= "if (window[\"yaCounter{$analytics['ya_counter']}\"] || typeof ym !== 'undefined') {".
                      "var products = [];";
        foreach ($this->order['items'] as $item) {
            $name = htmlspecialchars($item['name'], ENT_QUOTES, 'utf-8');
            $price = $this->formatPrice($item['price']);
            $result .= "products.push({".
                            "id: \"" . $item['id'] . "\",".
                            "name: \"" . $name . "\",".
                            "category: \"\",".
                            "price: \"" . $price . "\",".
                            "quantity: \"" . $item['quantity'] . "\"".
                        "});";
        }
        $result .= "dataLayer.push({".
                      "ecommerce: {".
                          "currencyCode: \"" . $this->order['currency'] . "\",".
                          "purchase: {".
                              "actionField: {".
                                  "id: \"" . $this->order['id'] . "\"".
                                  (!empty($analytics['yaecom_goal_id']) ? ", goal_id: \"" . $analytics['yaecom_goal_id'] . "\"" : "") .
                              "},".
                              "products: products".
                          "}".
                      "}".
                  "});";
        if (waSystemConfig::isDebug()) {
            $result .= "console.log(\"** QDebug ** Yandex ecommerce sended: ID: " . $analytics['ya_counter'] .
                "\\nOrder info:" .
                "\\n- order ID: " . $this->order['id'] .
                "\\n- currency: " . $this->order['currency'] .
                (!empty($analytics['yaecom_goal_id']) ? "\\n- goal_id: " . $analytics['yaecom_goal_id'] : "") .
                "\\n- products:\", JSON.parse(JSON.stringify(products)));";
        }
        $result .= "}} catch(e) {" . (waSystemConfig::isDebug() ? "console.log(e);" : "") . "}";
        return $result;
    }

    /**
     * Add yandex counter, if it's not exist on the storefront
     *
     * @param int $counter_id
     * @param bool $is_ecom
     * @param string $data_layer
     * @return string
     */
    private function addCounter($counter_id, $is_ecom, $data_layer)
    {
        $result = "<!-- Yandex.Metrika counter -->".
                    "<script>".
                    "if (!window[\"yaCounter{$counter_id}\"]) {".
                        "(function (d, w, c) {".
                            "(w[c] = w[c] || []).push(function() {".
                                "try {".
                                    "w.yaCounter{$counter_id} = new Ya.Metrika({".
                                        "id:{$counter_id},".
                                        "clickmap:true,".
                                        "trackLinks:true,".
                                        "accurateTrackBounce:true".
                                         ($is_ecom ? ",ecommerce:\"" . ($data_layer ? $data_layer : "dataLayer") . "\"" : "") .
                                    "});".
                                "} catch(e) { }".
                            "});".
                            "var n = d.getElementsByTagName(\"script\")[0],".
                                "s = d.createElement(\"script\"),".
                                "f = function () { n.parentNode.insertBefore(s, n); };".
                            "s.async = true;".
                            "s.src = \"https://mc.yandex.ru/metrika/watch.js\";".
                            "if (w.opera == \"[object Opera]\") {".
                                "d.addEventListener(\"DOMContentLoaded\", f, false);".
                            "} else { f(); }".
                        "})(document, window, \"yandex_metrika_callbacks\");".
                    "}";
        if (waSystemConfig::isDebug()) {
            $result .= "console.log(\"** QDebug ** Added YaCounter: {$counter_id}\");";
        }
        $result .= "</script>".
                    "<noscript><div><img src=\"https://mc.yandex.ru/watch/{$counter_id}\" style=\"position:absolute; left:-9999px;\" alt=\"\" /></div></noscript>";
        return $result;
    }

    /**
     * Google universal analytics code
     *
     * @param string $counter_id
     * @return string
     */
    private function getGoogleAnalytics($counter_id)
    {
        $title = waRequest::param('title');
        if (!$title) {
            $title = wa('shop')->getConfig()->getGeneralSettings('name');
        }
        if (!$title) {
            $app = wa()->getAppInfo();
            $title = $app['name'];
        }

        $result = "try {";
        $result .= "if (!window.ga) {".
            "(function(i,s,o,g,r,a,m){i[\"GoogleAnalyticsObject\"]=r;i[r]=i[r]||function(){".
            "(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),".
            "m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)".
            "})(window,document,\"script\",\"//www.google-analytics.com/analytics.js\",\"ga\");".
            "ga(\"create\", \"{$counter_id}\", \"auto\");".
            "ga(\"send\", \"pageview\");".
        "}";
        $result .= "if (window.ga) { ga(\"require\", \"ecommerce\", \"ecommerce.js\");";
        $result .= "var products = [];".
            "ga(\"ecommerce:addTransaction\", {".
                "id: \"" . $this->order['id'] . "\",".
                "affiliation: \"" . htmlspecialchars($title, ENT_QUOTES, 'utf-8') . "\",".
                "revenue: \"" . $this->formatPrice($this->order['total']) . "\",".
                "shipping: \"" . $this->formatPrice($this->order['shipping']) . "\",".
                "tax: \"" . $this->formatPrice($this->order['tax']) . "\",".
                "currency: \"" . $this->order['currency'] . "\"".
            "});";

        foreach ($this->order['items'] as $item) {
            $sku = $item['type'] == 'product' ? $item['sku_code'] : '';
            $result .= "var item = {".
                    "id: \"" . $this->order['id'] . "\",".
                    "name: \"" . htmlspecialchars($item['name'], ENT_QUOTES, 'utf-8') . "\",".
                    "sku: \"" . $sku . "\",".
                    "category: \"\",".
                    "price: \"" . $this->formatPrice($item['price']) . "\",".
                    "quantity: \"" . $item['quantity'] . "\"".
                "};".
                "ga(\"ecommerce:addItem\", item);".
                "products.push(item);";
        }
        $result .= "ga(\"ecommerce:send\");";
        if (waSystemConfig::isDebug()) {
            $result .= "console.log(\"** QDebug ** Google analytics: ID: " . $counter_id.
                "\\nOrder info:" .
                "\\n- order ID: " . $this->order['id'] .
                "\\n- currency: " . $this->order['currency'] .
                "\\n- affiliation: " . htmlspecialchars($title, ENT_QUOTES, 'utf-8') .
                "\\n- revenue: " . $this->formatPrice($this->order['total']) .
                "\\n- shipping: " . $this->formatPrice($this->order['shipping']) .
                "\\n- tax: " . $this->formatPrice($this->order['tax']) .
                "\\n- products:\", JSON.parse(JSON.stringify(products)));";
        }
        $result .= "}} catch(e) {" . (waSystemConfig::isDebug() ? "console.log(e);" : "") . "}";
        return $result;
    }

    private function formatPrice($price)
    {
        return str_replace(',', '.', (float) $price);
    }

}