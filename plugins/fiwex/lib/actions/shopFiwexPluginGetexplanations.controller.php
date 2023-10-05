<?php
class shopFiwexPluginGetexplanationsController extends waJsonController
{
    function execute()
    {
        $feature_id = waRequest::post('feature_id',waRequest::TYPE_INT);
        $table = waRequest::post('table',waRequest::TYPE_STRING);
        $feature_model = new waModel();
        if ($table == 'feature') {
            $result = $feature_model->query('SELECT explanations FROM shop_fiwex_feature_explanations WHERE id = (?)', $feature_id)->fetch();
        } else if($table=='feature_values') {
            $result = $feature_model->query('SELECT explanations FROM shop_fiwex_feat_values_explanations WHERE id = (?)', $feature_id)->fetch();
        }
        $this->response['content'] = $result['explanations'];
    }
}