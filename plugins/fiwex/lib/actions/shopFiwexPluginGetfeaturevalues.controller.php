<?php
class shopFiwexPluginGetfeaturevaluesController extends waJsonController
{
 function execute()
 {
    $feature_id = waRequest::post('feature_id',waRequest::TYPE_INT);
    $feature_values_model = new shopFeatureValuesVarcharModel();
    $data = $feature_values_model->query('SELECT id, value FROM shop_feature_values_varchar WHERE feature_id = (?)', $feature_id)->fetchAll();
    $this->response['data'] = $data;
 }
}