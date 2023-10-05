<?php

class shopListfeaturesPluginSettingsSetDeleteController extends waController
{
    public function execute()
    {
        shopListfeaturesPluginHelper::saveSettlementConfig(array(
            'settlement'  => waRequest::post('settlement'),
            'set_id'      => waRequest::post('set_id'),
            'feature_ids' => null,
        ));
    }
}
