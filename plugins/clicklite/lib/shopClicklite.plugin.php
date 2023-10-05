<?php

/**
 * Класс плагина
 *
 * @author Steemy, created by 03.04.2018
 * @link http://steemy.ru/
 */

class shopClicklitePlugin extends shopPlugin
{
    /**
     * Вывод в пункта меню в бекенде магазина
     * @return array|string
     */
    public function backendMenu()
    {
        if(!wa()->getUser()->getRights(wa()->getConfig()->getApplication(), 'clicklite_list', true))
            throw new waRightsException();

        if(!$this->getSettings('status'))
            return array();

        $html = '<li ' . (waRequest::get('plugin') == $this->id ? 'class="selected"' : 'class="no-tab"') . '>
                    <a href="?plugin='.$this->id.'">Купить (lite)</a>
                </li>';

        return array(
            'core_li' => $html
        );
    }

    /**
     * Вывод стилей в хуке frontend_head
     * @return string
     */
    public function frontendHead()
    {
        $pluginSetting = shopClicklitePluginSettings::getInstance();
        $settings = $pluginSetting->getSettings();
        $pluginSetting->getSettingsCheck($settings);

        if(!$settings['status'])
            return '';

        $html = '';
        $v = $settings['plugin_info']['version'] . '-' . $settings['version'];

        $waUrlData = wa()->getDataUrl('plugins/' . $pluginSetting->namePlugin . '/', true, 'shop');

        if(!$settings['style_enable']) $html .= '
    <link href="' . $waUrlData . 'css/' . $pluginSetting->namePlugin . '.css?v' . $v . '" rel="stylesheet" />';

        if(!$settings['script_enable']) $html .= '
    <script src="' . $waUrlData . 'js/' . $pluginSetting->namePlugin . '.js?v' . $v . '"></script>';

        return $html;
    }

    /**
     * Выводит форму в подвале в хуке frontend_footer
     * @return string
     */
    public function frontendFooter()
    {
        $pluginSetting = shopClicklitePluginSettings::getInstance();
        $settings = $pluginSetting->getSettings();

        if(!$settings['status'] || $settings['frontend_footer'])
            return '';

        return self::displayModalForm();
    }

    static function displayModalForm()
    {
        $pluginSetting = shopClicklitePluginSettings::getInstance();
        $settings = $pluginSetting->getSettings();
        $pluginSetting->getSettingsCheck($settings);

        if(!$settings['status'])
            return '';

        $file = wa()->getDataPath('plugins/clicklite/templates/FrontendDisplay.html', true, 'shop');

        if(file_exists($file))
        {
            $settings['antispam'] = md5($_SERVER['REMOTE_ADDR'].'string antispam');
            $settings['currency_info'] = self::getCurrencyInfo();

            $view = wa()->getView();
            $view->assign('settings', $settings);
            $view->assign('contact', wa()->getUser()->isAuth() ? wa()->getUser()->load() : array());

            $templates = json_encode($view->fetch('string:' . file_get_contents($file)));

            $options = json_encode(array(
                "mask" => $settings['mask'] ? $settings['mask_view'] : '',
                "url" => wa()->getRouteUrl('shop/frontend'),
                "yandex" =>  $settings['yandex'],
                "policyCheckbox" => $settings['policy_checkbox'] ? $settings['policy_checkbox'] : '',
                "currency" => $settings['currency_info'],
                "ecommerce" => $settings['ecommerce'] ? 1 : 0,
            ));

            return "
    <script>
        function checkjQuery() {
            if (typeof jQuery != 'undefined') {
                $(function() {
                    $.clicklite.init({$options}, {$templates});
                });
                return;
            }
            setTimeout(function () { checkjQuery(); }, 100);
        };
        checkjQuery();
    </script>
";
        }

        waLog::log('Нет шаблона сплывающей формы', 'shop/clicklite.error.log');
        return '';
    }

    /**
     * Выводит кнопку в списке товаров
     * @param array $p - массив настроек продукта
     * @return string
     */
    static public function displayListButton($p)
    {
        $pluginSetting = shopClicklitePluginSettings::getInstance();
        $settings = $pluginSetting->getSettings();
        $pluginSetting->getSettingsCheck($settings);

        if(!$settings['status'] || !self::availible($p))
            return '';

        if(self::checkParams($settings['button_view'], $p['id']))
            return '';

        $settingsButton = array();
        self::addInSettings($settingsButton, $p);

        return '<button type="button" data-product="'
                . htmlspecialchars(json_encode($settingsButton), ENT_QUOTES, 'UTF-8') .
                '" class="clicklite__buttonView ' . $settings['list_class'] .
                '">' . $settings['list_name'] . '</button>';
    }

    /**
     * Выводит кнопку в карточке товара в хуке frontend_product.cart
     * @param array $product - массив настроек продукта
     * @return array
     */
    public function frontendProduct($product)
    {
        return array(
            'cart' => self::getProductButton($product, false),
        );
    }

    /**
     * Выводит кнопку в карточке товара
     * @param array $product - массив настроек продукта
     */
    static public function displayProductButton($product)
    {
        echo self::getProductButton($product);
    }

    /**
     * Возвращает кнопку
     * @param $product
     * @param $view - нужен для проверки показа при выводе хуком
     * @return string - button
     */
    static private function getProductButton($product, $view = true)
    {
        $pluginSetting = shopClicklitePluginSettings::getInstance();
        $settings = $pluginSetting->getSettings();
        $pluginSetting->getSettingsCheck($settings);

        if(!$settings['status'] || (!$view && !$settings['product_hook']) || !self::availible($product))
            return '';

        if(self::checkParams($settings['button_view'], $product['id']))
            return '';

        return '<button type="button" data-product="'
            . self::getSettingsButton($product) .
            '" class="clicklite__buttonView ' . $settings['product_class'] .
            '">' . $settings['product_name'] . '</button>';
    }

    /**
     * Проверка существования параметра у товара для запрета показа
     * @param $view - активна или нет настройка
     * @param $id - id продукта
     * @return bool
     */
    static private function checkParams($view, $id)
    {
        if($view) {
            $shopProductParamsModel = new shopProductParamsModel();
            $params = $shopProductParamsModel->get($id);
            if(!empty($params['clicklite']) && $params['clicklite'] == 1)
                return true;
        }

        return false;
    }

    /**
     * Выводит форму в карточке товара
     * @param array $product - массив настроек продукта
     * @return string
     */
    static public function displayProductForm($product)
    {
        $pluginSetting = shopClicklitePluginSettings::getInstance();
        $settings = $pluginSetting->getSettings();
        $pluginSetting->getSettingsCheck($settings);


        if((!$settings['status'] || !self::availible($product)) || self::checkParams($settings['button_view'], $product['id']))
            return '';

        $antispam = md5($_SERVER['REMOTE_ADDR'].'string antispam');

        $settings['antispam'] = $antispam;

        $skus = $product['skus'];
        $skusFirst = array_shift($skus);

        $settings['product_sku'] = $skusFirst['id'];
        $settings['product_id'] = $product['id'];

        $settings['settings_button'] = self::getSettingsButton($product);

        $view = wa()->getView();
        $view->assign('settings', $settings);
        $view->assign('contact', wa()->getUser()->isAuth() ? wa()->getUser()->load() : array());

        return $view->fetch(wa()->getDataPath('plugins/clicklite/templates/FrontendForm.html', true, 'shop'));
    }

    /**
     * Выводит кнопку в корзине в хуке frontend_cart
     * @param array $product - массив настроек продукта
     * @return string
     */
    public function frontendCart()
    {
        $pluginSetting = shopClicklitePluginSettings::getInstance();
        $settings = $pluginSetting->getSettings();

        if(!$settings['status'] || !$settings['cart_hook'])
            return '';

        return '<button type="button" class="clickliteCart__buttonView ' . $settings['cart_class'] .
            '">' . $settings['cart_name'] . '</button>';
    }

    /**
     * Выводит кнопку в корзине в произвольном месте
     */
    static public function displayCartButton()
    {
        $pluginSetting = shopClicklitePluginSettings::getInstance();
        $settings = $pluginSetting->getSettings();

        if(!$settings['status'])
            return '';

        echo '<input type="button" class="clickliteCart__buttonView ' . $settings['cart_class'] .
            '" value="' . $settings['cart_name'] . '" />';
    }

    /**
     * Возвращает настройки кнопки в json
     * @param $product
     * @return string - json
     */
    static private function getSettingsButton($product)
    {
        $skus = $product->skus;

        if(count($skus) > 0)
        {
            $skusSELECT = array();
            $featuresSELECT = $product->features_selectable;

            $productFeaturesModel = new shopProductFeaturesModel();
            $skuFeatures = $productFeaturesModel->getSkuFeatures($product->id);

            if ($product->sku_type == shopProductModel::SKU_TYPE_SELECTABLE)
            {
                foreach ($skus as $id => $sku)
                {
                    $skuF = "";
                    foreach ($featuresSELECT as $fId => $fVal)
                    {
                        if(!empty($skuFeatures[$id][$fId]))
                            $skuF .= $fId . ":" . $skuFeatures[$id][$fId] . ";";
                    }

                    $skusSELECT[$skuF] = self::getArraySku($id, $sku, $product->status);
                }
            }
            else
            {
                foreach ($skus as $id => $sku)
                {
                    $skusSELECT[$id] = self::getArraySku($id, $sku, $product->status);
                }
            }

        }

        self::addInSettings($settingsButton, $product);
        $settingsButton['skusSELECT']   = $skusSELECT;

        return htmlspecialchars(json_encode($settingsButton), ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param array $settings - настройки к которым добавить
     * @param array $p - продукт
     */
    static private function addInSettings(&$settings, $p)
    {
        $image = array(
            'product_id' => $p['id'],
            'id' => $p['image_id'],
            'ext' => $p['ext']
        );

        $settings['id']     = $p['id'];
        $settings['sku_id'] = $p['sku_id'];
        $settings['image']  = shopImage::getUrl($image,'48x48@2x');
        $settings['price']  = $p['price'];
        $settings['name']   = htmlspecialchars($p['name']);
    }

    /**
     * Возвращает массив для sku
     * @param int $id
     * @param array $sku
     * @param bool $status
     * @return array
     */
    static private function getArraySku($id, $sku, $status)
    {
        return array(
            'id' => $id,
            'name' => htmlspecialchars($sku['name']),
            'price' => $sku['price'],
            'image_id' => $sku['image_id'],
            'available' => $status && $sku['available'] &&
                (wa()->getConfig()->getGeneralSettings('ignore_stock_count') || $sku['count'] === null || $sku['count'] > 0),
        );
    }

    /**
     * Получаем CurrencyInfo
     * @return array
     */
    static private function getCurrencyInfo()
    {
        $currency = waCurrency::getInfo(wa()->getConfig()->getCurrency(false));
        $locale = waLocale::getInfo(wa()->getLocale());
        return array(
            'code'          => $currency['code'],
            'sign'          => $currency['sign'],
            'sign_html'     => !empty($currency['sign_html']) ? $currency['sign_html'] : $currency['sign'],
            'sign_position' => isset($currency['sign_position']) ? $currency['sign_position'] : 1,
            'sign_delim'    => isset($currency['sign_delim']) ? $currency['sign_delim'] : ' ',
            'decimal_point' => $locale['decimal_point'],
            'frac_digits'   => $locale['frac_digits'],
            'thousands_sep' => $locale['thousands_sep'],
        );
    }

    /**
     * Проверям товар на активность заказа
     * @param $product
     * @return bool
     */
    static private function availible($product)
    {
        $available = false;

        if(wa()->getConfig()->getGeneralSettings('ignore_stock_count'))
        {
            $available = true;
        }
        elseif (isset($product['skus']))
        {
            if (count($product['skus']) > 1)
            {
                return true;
            }
            else
            {
                $sku = $product['skus'][$product['sku_id']];
                $available = $product['status'] && $sku['available'] && ($sku['count'] === null || $sku['count'] > 0);
            }
        }
        else
        {
            $available = $product['count'] === null || $product['count'] > 0;
        }

        return $available;
    }

    /**
     * Права доступа
     * @param waRightConfig $config
     */
    public function rightsConfig(waRightConfig $config)
    {
        $config->addItem('clicklite_header', 'Купить в 1 клик (lite)', 'header');
        $config->addItem('clicklite_settings', 'Доступ к настройкам');
        $config->addItem('clicklite_list', 'Доступ к списку заказов');
    }
}