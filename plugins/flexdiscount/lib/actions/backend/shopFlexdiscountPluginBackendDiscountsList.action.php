<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginBackendDiscountsListAction extends waViewAction
{

    public function preExecute()
    {
        $user = shopFlexdiscountApp::get('system')['wa']->getUser();
        if (!$user->isAdmin() && !$user->getRights("shop", "flexdiscount_rules")) {
            throw new waRightsException();
        }
    }

    public function execute()
    {
        $wa = shopFlexdiscountApp::get('system')['wa'];
        $model = new shopFlexdiscountPluginModel();

        $discounts = $model->getDiscounts(array("coupons" => 1));

        if ($discounts) {
            $discounts_per_page = max(1, $wa->getStorage()->get('discounts_per_page'));
            $this->view->assign('discounts_per_page', $discounts_per_page);

            // Количество скидок
            $discounts_count = $model->countAll();
            $this->view->assign('discounts_count', $discounts_count);
        }
        $settings = shopFlexdiscountApp::get('settings');
        $true_columns = array("coupons" => 1, "discount" => 3, "affil" => 3);
        $columns = isset($settings['columns']) ? unserialize($settings['columns']) : array("discount", "affil");
        $weight = 0;
        if ($columns) {
            foreach ($columns as $c) {
                $weight += $true_columns[$c];
            }
        }
        $this->view->assign('currencies', $this->getConfig()->getCurrencies());
        $this->view->assign('discounts', $discounts);
        $this->view->assign('settings_url', (new shopFlexdiscountHelper())->getPluginSettingsPageUrl());
        $this->view->assign('attention', (new shopFlexdiscountHelper())->getBackendAttention());
        $this->view->assign('columns', $columns);
        $this->view->assign('weight', $weight);
        $this->view->assign('plugin_url', $wa->getPlugin('flexdiscount')->getPluginStaticUrl());
    }

}
