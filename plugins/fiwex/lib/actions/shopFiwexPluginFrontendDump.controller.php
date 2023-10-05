<?php
class shopFiwexPluginFrontendDumpController extends waJsonController
{
    function execute()
    {
        $feature_id = waRequest::post('feature_id','',waRequest::TYPE_INT);
        $table = waRequest::post('table', waRequest::TYPE_STRING);
        $feature_model = new waModel();

        if ($table=='feature') {
            $data = $feature_model->query('SELECT explanations FROM shop_fiwex_feature_explanations WHERE id = ?', $feature_id)->fetch();
            $name = $feature_model->query('SELECT name FROM shop_feature WHERE id = ?', $feature_id)->fetch();

            if (isset($name['name'])) {
                $this->response['title'] = $name['name'];
            } else {
                $this->response['title'] = '';
            }

        } else if($table == 'feature_values') {
            $data = $feature_model->query('SELECT explanations FROM shop_fiwex_feat_values_explanations WHERE id = ?', $feature_id)->fetch();
            $name = $feature_model->query('SELECT value FROM shop_feature_values_varchar WHERE id = ?', $feature_id)->fetch();

            if (isset($name['value'])) {
                $this->response['title'] = $name['value'];
            } else {
                $this->response['title'] = '';
            }
        } else if ($table == 'feature_values_unknown_id') {
            $product_id = waRequest::post('product_id', 0, waRequest::TYPE_INT);
            $index = waRequest::post('index', 0, waRequest::TYPE_INT);

            $tmp_result = $feature_model->query('SELECT spf.feature_value_id as id, sfvv.value as name FROM
                    shop_product_features spf JOIN shop_feature_values_varchar sfvv ON spf.feature_value_id = sfvv.id
                    WHERE spf.product_id = ? AND spf.feature_id = ? ORDER BY sfvv.sort ASC', array($product_id, $feature_id))->fetchAll();

            $tmp_result = $tmp_result[$index];

            if (isset($tmp_result['name'])) {
                $this->response['title'] = $tmp_result['name'];
            } else {
                $this->response['title'] = '';
            }

            $data = $feature_model->query('SELECT explanations FROM shop_fiwex_feat_values_explanations WHERE id = ?', $tmp_result['id'])->fetch();
        }

        if (isset($data['explanations'])) {
            $this->response['explanation'] = $data['explanations'];
        } else {
            $this->response['explanation'] = '';
        }

    }
}