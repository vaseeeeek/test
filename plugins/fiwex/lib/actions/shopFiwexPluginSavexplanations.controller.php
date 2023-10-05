<?php
class shopFiwexPluginSavexplanationsController extends waJsonController
{
    function execute()
    {
        $text = waRequest::post('text',waRequest::TYPE_STRING_TRIM);
        $id = waRequest::post('id', waRequest::TYPE_INT);
        $table = waRequest::post('table',waRequest::TYPE_STRING);

        $model = new waModel();
        if ($text == '') {
            $text = NULL;
        }
  
        if ($table == 'feature') {
            $table_name = 'shop_fiwex_feature_explanations';
        } else if($table=='feature_values') {
            $table_name = 'shop_fiwex_feat_values_explanations';
        }
        $state = $model->query("UPDATE ".$table_name." SET explanations = ? WHERE id = ?", $text, $id);

        if ($state) {
            $this->response['state'] = true;
        }
    }
}