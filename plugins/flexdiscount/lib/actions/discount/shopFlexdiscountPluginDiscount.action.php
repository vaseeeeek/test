<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginDiscountAction extends waViewAction
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
        $id = waRequest::get("id", 0, waRequest::TYPE_INT);

        $discount = (new shopFlexdiscountPluginModel())->getDiscount($id);

        $encoding = array_diff(mb_list_encodings(), array(
            'pass',
            'wchar',
            'byte2be',
            'byte2le',
            'byte4be',
            'byte4le',
            'BASE64',
            'UUENCODE',
            'HTML-ENTITIES',
            'Quoted-Printable',
            '7bit',
            '8bit',
            'auto',
        ));

        $popular = array_intersect(array('UTF-8', 'Windows-1251', 'ISO-8859-1',), $encoding);

        asort($encoding);
        $encoding = array_unique(array_merge($popular, $encoding));

        // Определяем, есть ли среди целей Доставка
        $has_shipping = 0;
        $has_discount = 1;
        if (!empty($discount['target'])) {
            $target = json_decode($discount['target']);
            if ($target && is_array($target)) {
                $has_discount = 0;
                foreach ($target as $t) {
                    if ($t->target == 'shipping') {
                        $has_shipping = 1;
                    } else {
                        $has_discount = 1;
                    }
                }
            }
        }

        $plugin = shopFlexdiscountApp::get('system')['wa']->getPlugin('flexdiscount');

        $this->view->assign('has_shipping', $has_shipping);
        $this->view->assign('only_shipping', !$has_discount);
        $this->view->assign('encoding', $encoding);
        $this->view->assign('settings_url', (new shopFlexdiscountHelper())->getPluginSettingsPageUrl());
        $this->view->assign('currencies', $this->getConfig()->getCurrencies());
        $this->view->assign("discount", $discount);
        $this->view->assign("settings", shopFlexdiscountApp::get('settings'));
        $this->view->assign('attention', (new shopFlexdiscountHelper())->getBackendAttention());
        $this->view->assign('plugin_url', $plugin->getPluginStaticUrl());
        $this->view->assign('version', $plugin->getVersion());
    }

}
