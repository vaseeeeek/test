<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginSettingsLoadStorefrontAction extends waViewAction
{
    public function execute()
    {
        $storefront = waRequest::get('storefront');
        if (!$storefront) {
            $storefront = 'all';
        }

        // Поля контакта
        $fields = waContactFields::getAll();
        foreach ($fields as $k => $field) {
            // Удаляем зависимые поля и консолидированное поле адреса
            if ($field instanceof waContactConditionalField || $field->getId() == 'address' || $field->getId() == 'name') {
                unset($fields[$k]);
            }
        }

        // Поля адреса
        $address = waContactFields::get('address');
        $address = $address->getFields();

        // Проверка существования плагина Фильтр доставки и оплаты
        // https://www.webasyst.ru/store/plugin/shop/delpayfilter
        $has_delpayfilter = 0;
        try {
            wa()->getPlugin('delpayfilter');
            $has_delpayfilter = 1;
        } catch (Exception $e) {
        }
        // Проверка существования плагина Гибкие скидки и бонусы
        // https://www.webasyst.ru/store/plugin/shop/flexdiscount
        $has_flexdiscount = 0;
        try {
            wa()->getPlugin('flexdiscount');
            $has_flexdiscount = 1;
        } catch (Exception $e) {
        }
        $plugins = wa()->getConfig()->getPlugins();
        $settings = (new shopQuickorderPluginSettingsModel())->getSettings($storefront);

        $this->view->assign('fields', $fields);
        $this->view->assign('address', $address);
        $this->view->assign('payment', (new shopQuickorderPluginHelper())->getPaymentMethods());
        $this->view->assign('shipping', (new shopQuickorderPluginHelper())->getShippingMethods());
        $this->view->assign('storefront', $storefront);
        $this->view->assign('plugins', $plugins);
        $this->view->assign('has_delpayfilter', $has_delpayfilter);
        $this->view->assign('has_flexdiscount', $has_flexdiscount);
        $this->view->assign('settings', $settings);
        $this->view->assign('templates', (new shopQuickorderPluginHelper())->getTemplates($settings));
    }
}