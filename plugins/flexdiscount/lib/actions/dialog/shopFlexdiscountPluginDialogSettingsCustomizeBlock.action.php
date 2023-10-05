<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginDialogSettingsCustomizeBlockAction extends waViewAction
{

    public function preExecute()
    {
        $user = shopFlexdiscountApp::get('system')['wa']->getUser();
        if (!$user->isAdmin() && !$user->getRights("shop", "flexdiscount_settings")) {
            throw new waRightsException();
        }
    }

    public function execute()
    {
        // Получаем настройки
        $settings = shopFlexdiscountApp::get('settings');

        $type = waRequest::get("type");

        $files = array(
            'couponForm' => dirname(__FILE__) . '/../../config/data/flexdiscount.coupon.form.html',
            'affiliateBlock' => dirname(__FILE__) . '/../../config/data/flexdiscount.affiliate.html',
            'availDiscounts' => dirname(__FILE__) . '/../../config/data/flexdiscount.available.html',
            'denyDiscounts' => dirname(__FILE__) . '/../../config/data/flexdiscount.deny.discounts.html',
            'myDiscounts' => dirname(__FILE__) . '/../../config/data/flexdiscount.my.discounts.html',
            'priceDiscounts' => dirname(__FILE__) . '/../../config/data/flexdiscount.price.html',
            'productDiscounts' => dirname(__FILE__) . '/../../config/data/flexdiscount.product.discounts.html',
            'userDiscounts' => dirname(__FILE__) . '/../../config/data/flexdiscount.user.discounts.html',
            'styles' => dirname(__FILE__) . '/../../config/data/flexdiscount.block.styles.css'
        );

        $contents = array();
        foreach ($files as $field => $file) {
            $contents[$field] = "";
            if (file_exists($file)) {
                $contents[$field] = $this->view->fetch('string:' . file_get_contents($file));
            }
        }
        $this->view->assign("settings", $settings);
        $this->view->assign("contents", $contents);
        $this->view->assign("type", $type);
        $this->view->assign('plugin_url', shopFlexdiscountApp::get('system')['wa']->getPlugin('flexdiscount')->getPluginStaticUrl());
    }

}
