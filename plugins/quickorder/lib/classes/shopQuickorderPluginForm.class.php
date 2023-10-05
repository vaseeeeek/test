<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginForm
{
    private $settings;
    private $phone_mask = false;
    private $button_css = '';
    private $fields;

    public function __construct()
    {
        if ($this->settings === null) {
            $generator = new shopQuickorderPluginGenerator();
            $generator->prepareStyles();
            $extra = $generator->getExtra();
            // Если для ширины кнопки используется процентное значение, добавляем класс к кнопке, чтобы в мобильной растянуть ее на всю ширину
            if (isset($extra['percentage_width'])) {
                $this->button_css = ' q-percentage-width';
            }
            $this->settings = shopQuickorderPluginHelper::getSettings();
        }
    }

    /**
     * Display button for product or cart
     *
     * @param array $product
     * @param string $type - product|cart
     * @param bool $force_form
     * @param bool|string $force_type - form|button
     * @return string
     */
    public function getButton($product = array(), $type = 'product', $force_form = false, $force_type = null)
    {
        $html = "";

        // Получаем список характеристик для товара на витрине, чтобы использовать его для определения доступности товара
        if ($product && $product['sku_type'] == shopProductModel::SKU_TYPE_SELECTABLE) {
            $storefront_view = wa()->getView();
            $storefront_product = $storefront_view->getVars('product');
            $storefront_features = $storefront_view->getVars('sku_features_selectable');
        }

        $button_settings = !empty($this->settings['shared_display_settings']) ? $this->settings['product'] : $this->settings[$type];

        // Контактная информация
        $json_fields = !empty($this->settings['shared_display_settings']) ? $this->settings['fields']['product'] : $this->settings['fields'][$type];
        $this->fields = $this->prepareContactFields(shopQuickorderPluginHelper::decodeToArray($json_fields));
        // Не даем вывести форму, если отсутствуют контактные поля
        if (!$this->fields) {
            return '';
        }

        // Выводим кнопку
        if ((empty($button_settings['hide_button']) && !$force_form && $force_type !== 'form') || $force_type == 'button') {
            $button_name = !empty($button_settings['button_name']) ? $button_settings['button_name'] : '';
            $html .= "<div class='quickorder-button" . ($type == 'cart' ? '-cart' : '') . $this->button_css;
            // Класс кнопки
            $html .= (!empty($button_settings['button_css']) ? ' ' . waString::escapeAll($button_settings['button_css']) : '');
            $html .= "' " . ($type == 'cart' ? shopQuickorderPluginGenerator::CART_BUTTON_ATTR : shopQuickorderPluginGenerator::PRODUCT_BUTTON_ATTR) . " data-quickorder-" . $type . "-button";
            // Если известно, для какого товара используется кнопка, указываем его ID в атрибутах
            if ($product) {
                $html .= " data-quickorder-product-id='" . $product['id'] . "'";
                $html .= " data-quickorder-sku-id='" . $product['sku_id'] . "'";
                // Список характеристик товара
                if (isset($storefront_product) && $storefront_product['id'] == $product['id'] && !empty($storefront_features)) {
                    $html .= " data-features='" . json_encode($storefront_features) . "'";
                }
            }
            if ($button_name) {
                $html .= " title='" . strip_tags($button_name) . "'";
            }
            $html .= " data-button-display='" . (!empty($button_settings['button_display']) ? waString::escapeAll($button_settings['button_display']) : 'table') . "'";
            $html .= ">";
            // Название кнопки
            $html .= $button_name;
            $html .= "</div>";
        } // Выводим форму
        else {
            $inline = $force_type == 'form' ? 1 : ($force_type == 'popup' ? 0 : null);
            $html .= $force_form ? $this->getForm($product, $type, $inline) : $this->getFormLoader($product, $type, $force_type);
        }

        return $html;
    }

    private function getForm($product = array(), $type = 'product', $inline = null)
    {
        static $use_phone_mask;

        $view = new waSmarty3View(wa());

        $form_settings = !empty($this->settings['shared_display_settings']) ? $this->settings['product'] : $this->settings[$type];
        $appearance_settings = !empty($this->settings['shared_appearance_settings']) ? $this->settings['product'] : $this->settings[$type];

        $helper = (new shopQuickorderPluginHelper());
        $cart_products = $type == 'product' ? array($product) : (new shopCart())->items();

        // Товар
        $products = (new shopQuickorderPluginProductData())->prepareProducts($cart_products, !empty($form_settings['product_services']), true, $type == 'product');

        if (!$products) {
            return '';
        }

        $view->assign('products', $products);

        $cart = new shopQuickorderPluginCart($type, $products);

        // Добавление товара в корзину при открытии формы
        if ($type == 'product' && !$inline && !empty($this->settings['after_click']) && $this->settings['after_click'] == 'addtocart') {
            $cart->addProductToDefaultCart();
        }

        // Размер изображения товара
        if (!empty($form_settings['product_image'])) {
            $image_size = $this->getImageSize($form_settings);
            $view->assign('image_size', $image_size['size']);
            $view->assign('image_width', $image_size['width']);
            $view->assign('image_height', $image_size['height']);
        }

        // Поля контакта
        $contact_fields = wa()->getUser()->isAuth() ? wa()->getUser()->load() : array();
        // Все поля
        $all_fields = waContactFields::getAll();
        $view->assign('fields', $this->fields);
        $view->assign('contact_fields', $contact_fields);
        $view->assign('default_address', $cart->getContact()->getFirst('address.shipping'));
        $view->assign('all_fields', $all_fields);
        $view->assign('address_fields', !empty($all_fields['address']) ? $all_fields['address']->getFields() : array());

        // Кнопка отправления
        $button = "<div " . ($type == 'cart' ? shopQuickorderPluginGenerator::CART_FORM_BUTTON_ATTR : shopQuickorderPluginGenerator::PRODUCT_FORM_BUTTON_ATTR) . " data-quickorder-submit-button" . ">";
        $button .= (!empty($form_settings['submit_button']) ? $form_settings['submit_button'] : '');
        $button .= "</div>";
        $view->assign('q_button', $button);

        // Атрибуты формы
        $view->assign('form_attr', $type == 'cart' ? shopQuickorderPluginGenerator::CART_FORM_ATTR : shopQuickorderPluginGenerator::PRODUCT_FORM_ATTR);
        $view->assign('form_head_attr', $type == 'cart' ? shopQuickorderPluginGenerator::CART_FORM_HEAD_ATTR : shopQuickorderPluginGenerator::PRODUCT_FORM_HEAD_ATTR);
        $view->assign('form_footer_attr', $type == 'cart' ? shopQuickorderPluginGenerator::CART_FORM_FOOTER_ATTR : shopQuickorderPluginGenerator::PRODUCT_FORM_FOOTER_ATTR);
        $view->assign('form_titles_attr', $type == 'cart' ? shopQuickorderPluginGenerator::CART_TITLES_ATTR : shopQuickorderPluginGenerator::PRODUCT_TITLES_ATTR);
        $view->assign('form_fields_attr', $type == 'cart' ? shopQuickorderPluginGenerator::CART_FIELDS_ATTR : shopQuickorderPluginGenerator::PRODUCT_FIELDS_ATTR);
        $view->assign('form_layout_attr', 'data-quickorder-layout' . (!empty($appearance_settings['fields_layout']) ? $appearance_settings['fields_layout'] : 2));

        $shipping = $cart->getMethods('shipping');
        if (!empty($shipping)) {
            $view->assign('shipping', $shipping);
        }
        $payment = $cart->getMethods('payment');
        if (!empty($payment)) {
            $view->assign('payment', $payment);
        }

        // Заставляем Гибкие скидки сделать перерасчет
        waRequest::setParam('flexdiscount_force_calculate', 1);
        waRequest::setParam('flexdiscount_skip_caching', 1);

        // Купон
        $storage = wa()->getStorage();
        if (!empty($form_settings['coupon_field'])) {
            $data = $storage->get('shop/checkout');
            $old_coupon = ifset($data, 'coupon_code', '');
            $plugin_coupon = $type !== 'cart' ? $storage->get('quickorder/coupon') : null;
            $coupon_code = $plugin_coupon === null ? $old_coupon : $plugin_coupon;
            $view->assign('coupon_code', $coupon_code);
            if ($coupon_code) {
                $cart->setCoupon($coupon_code);
                $cart_discount = $cart->getDiscount();
                $discount_without_coupon = $cart->getDiscountWithoutCoupon();
                if ($cart_discount == $discount_without_coupon) {
                    $view->assign('coupon_error', 1);
                }
            }
        }

        if (!empty($form_settings['discount_info'])) {
            $view->assign('discount', $cart->getDiscount());
        }
        if (!empty($form_settings['total_price'])) {
            $view->assign('total', $cart->getTotal());
        }
        waRequest::setParam('flexdiscount_skip_caching', 0);

        // Интеграция плагина "Гибкие скидки и бонусы" (flexdiscount)
        // Активные скидки
        if (!empty($this->settings['use_flexdiscount_ad']) && method_exists('shopFlexdiscountPlugin', 'isEnabled')) {
            // Проверяем доступность плагина
            if (shopFlexdiscountPlugin::isEnabled()) {
                $view->assign('use_flexdiscount', 1);
            }
        }

        // Бонусная программа
        if (!empty($form_settings['use_affiliate']) && shopAffiliate::isEnabled()) {
            $affiliate = $helper->getAffiliate($cart, $form_settings);
            $view->assign('affiliate', $affiliate);
        }

        // Проверяем, нужно ли подключать маску для телефона
        if ($use_phone_mask === null && $this->phone_mask) {
            $use_phone_mask = 1;
            $view->assign('use_phone_mask', 1);
        }

        // Возвращаем штатные купоны на место
        if (!empty($form_settings['coupon_field'])) {
            $cart->setCoupon($old_coupon);
        }

        $templates = $helper->getTemplates($this->settings);

        $view->assign('type', $type);
        $view->assign('form_inline', $inline !== null ? $inline : !empty($form_settings['hide_button']));
        $view->assign('form_settings', $form_settings);
        $view->assign('templates', $templates);
        $view->assign('all_settings', $this->settings);
        $view->assign('version', wa('shop')->getPlugin('quickorder')->getVersion());
        $view->assign('plugin_url', wa()->getAppStaticUrl('shop') . "plugins/quickorder");

        return $view->fetch('string:' . $templates['form']['frontend_template']);
    }

    /**
     * Return only loader. Form will be loaded via ajax call and replace this container
     *
     * @param array $product
     * @param string $type
     * @param mixed $force_type
     * @return string
     */
    private function getFormLoader($product = array(), $type = 'product', $force_type = null)
    {
        $html = "";

        $product_id = ifempty($product['id'], 0);
        $sku_id = ifempty($product['sku_id'], 0);

        if (($type == 'product' && $product_id && $sku_id) || $type == 'cart') {
            $force_inline = $force_type == 'form' ? 1 : ($force_type == 'button' ? 0 : null);
            $html .= <<<HTML
                <div class="quickorder-temp-container quickorder-row" data-inline="{$force_inline}" data-type="{$type}" data-product-id="{$product_id}" data-sku-id="{$sku_id}"><span class="quickorder-loading"></span></div>
HTML;
        }
        return $html;
    }

    /**
     * Prepare contact fields
     *
     * @param array $fields
     * @return array
     */
    private function prepareContactFields($fields)
    {
        $data = array();
        if (is_array($fields)) {
            foreach ($fields as $f) {
                $field = array();
                foreach ($f as $v) {
                    $field[$v['name']] = $v['value'];
                }
                if (isset($field['type']) && strpos($field['type'], 'address::') !== false) {
                    $field['address_type'] = substr($field['type'], 9);
                }
                if (isset($field['type']) && $field['type'] == 'phone' && !empty($field['extra'])) {
                    $this->phone_mask = true;
                }
                $data[] = $field;
            }
        }
        return $data;
    }

    /**
     * Get frontend image size
     *
     * @return array
     */
    private function getImageSize($settings)
    {

        $image_width = !empty($settings['image_size_w']) ? (int) $settings['image_size_w'] : 0;
        $image_height = !empty($settings['image_size_h']) ? (int) $settings['image_size_h'] : (isset($settings['image_size_h']) && $settings['image_size_h'] == '' ? '' : 0);
        $image_size = $image_width . ($image_height !== '' ? 'x' . $image_height : '');
        if ($image_size == '0x0') {
            $image_size = '96x96';
        }
        if (wa('shop')->getConfig()->getOption('enable_2x')) {
            $image_size = "{$image_size}@2x";
        }
        return [
            'width' => $image_width,
            'height' => $image_height,
            'size' => $image_size
        ];
    }

}