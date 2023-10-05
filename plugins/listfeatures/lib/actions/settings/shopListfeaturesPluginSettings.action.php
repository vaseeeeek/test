<?php

class shopListfeaturesPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $hash_settlements = shopListfeaturesPluginHelper::getHashSettlements();

        //change punycode to UTF
        foreach ($hash_settlements as &$settlement) {
            $settlement =  shopListfeaturesPluginHelper::settlementPunycodeToUtf($settlement);
        }
        unset($settlement);

        wa()->getResponse()->addJs(wa()->getConfig()->getBackendUrl(true).'shop/?plugin=listfeatures&action=loc');
        $this->view->assign('settlements', $hash_settlements);
        $this->view->assign('version', wa()->getPlugin('listfeatures')->getVersion());
    }
}
