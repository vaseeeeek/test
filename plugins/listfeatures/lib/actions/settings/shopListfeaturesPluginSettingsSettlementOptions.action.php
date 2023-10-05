<?php

class shopListfeaturesPluginSettingsSettlementOptionsAction extends waViewAction
{
    public function execute()
    {
        $settlement = waRequest::post('settlement');
        $config = shopListfeaturesPluginHelper::getSettlementConfig($settlement);
        $sets = $config && is_array($config) ? array_keys($config) : null;
        if ($sets) {
            sort($sets);
        }
        $this->view->assign('sets', $sets);
    }
}
