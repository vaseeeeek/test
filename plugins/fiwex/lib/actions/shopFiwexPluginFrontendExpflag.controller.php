<?php
class shopFiwexPluginFrontendExpflagController extends waJsonController
{
    function execute()
    {
        $feat_model = new waModel();
        $page = waRequest::post('page', '', waRequest::TYPE_STRING);

        //Закидываем айдишники значений с пояснениями
        $result = $feat_model->query('SELECT ffv.id, ffv.feature_id, ffv.explanations FROM shop_fiwex_feat_values_explanations ffv
                                             JOIN shop_fiwex_feature_explanations ff ON ffv.feature_id = ff.id AND ffv.explanations <> ""')->fetchAll();

        $flag_arr = Array();
        $flag_feat_arr = Array();

        foreach ($result as $key => $val) {
            $flag_arr[$val['id']] = $val['id'];
            $flag_feat_arr[$val['feature_id']] = $val['feature_id'];
        }

        $this->response['feat_val_data'] = $flag_arr;
        $this->response['feat_id'] = $flag_feat_arr;


        //Если определяем наличие пояснений для страницы товара
        if ($page == 'product') {
            //Получаем список значений характеристик с пояснениями
            $keys = array_keys($flag_arr);

            if (!empty($keys)) {
                $result = $feat_model->query('SELECT `id`, `feature_id`, `value` FROM `shop_feature_values_varchar` WHERE `id` IN (?)', array($keys))->fetchAll();

                if (!empty($result)) {
                    $result = $this->structuringData($result);
                } else {
                    $result = array();
                }
            } else {
                $result = array();
            }

            $this->response['features_values'] = $result;
        }
        unset($flag_arr);

        //Закидываем айдишники всех характеристик, имеющих пояснения
        $flag_arr = Array();
        $result = $feat_model->query("SELECT id FROM shop_fiwex_feature_explanations WHERE explanations <> ''")->fetchAll();

        foreach ($result as $key => $val) {
            $flag_arr[] = $val['id'];
        }

        $this->response['feat_full_data'] = $flag_arr;

    }

    /**
     * Получение списка значений характеристик в требуемом формате
     *
     * @param array $input_data
     * @return array
     */
    private function structuringData(array $input_data)
    {
        $result = array();

        foreach ($input_data as $key => $val) {
            if (!isset($result[$val['feature_id']])) {
                $result[$val['feature_id']] = array();
            }

            array_push($result[$val['feature_id']], $val['value']);
        }

        return $result;
    }
}