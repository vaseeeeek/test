<?php

class shopListfeaturesPluginSettingsSaveController extends waJsonController
{
    public function execute()
    {
        $set_id = waRequest::post('set_id', null, waRequest::TYPE_INT);

        $features_ids = array_keys(waRequest::post('features', array(), waRequest::TYPE_ARRAY));
        //remove useless 'feature' with id 'features'
        if (($id = array_search('features', $features_ids)) !== false) {
            unset($features_ids[$id]);
        }

        //clear set class name if it contains illegal characters
        $class_name = waRequest::post('class_name', '', waRequest::TYPE_STRING_TRIM);
        if (preg_match('/[^\w\-_]+/', $class_name)) {
            $class_name = '';
        }

        $save_result = shopListfeaturesPluginHelper::saveSettlementConfig(array(
            'settlement'  => waRequest::post('settlement'),
            'set_id'      => $set_id,
            'feature_ids' => $features_ids,
            'options'  => array(
                'description' => waRequest::post('description'),
                'data_sort'  => waRequest::post('data_sort', array(), waRequest::TYPE_ARRAY),
                'class_name' => $class_name,
                'template'   => waRequest::post('template'),
                'show_disabled_skus_data' => waRequest::post('show_disabled_skus_data', 0),
                'hide_outofstock_skus_data' => waRequest::post('hide_outofstock_skus_data', 0),
            ),
        ));

        if (!$set_id) {
            $this->response['new_set_id'] = $save_result;
        }
    }
}
