<?php

class shopListfeaturesPluginFrontendFeatureValueAction extends shopFrontendAction
{
    public function execute()
    {
        $set_id = waRequest::param('set_id');
        $feature_id = waRequest::param('feature_id');
        $value_id = waRequest::param('value_id');

        $feature_model = new shopFeatureModel();
        $features_config = shopListfeaturesPluginHelper::getSettlementConfig(null, $set_id, 'features');

        //set product collection
        $feature = $feature_model->getById($feature_id);
        $query = "search/{$feature['code']}.value_id=$value_id";
        $collection = new shopProductsCollection($query);
        $product_order = trim(ifempty($features_config[$feature_id]['product_order'], 'p.name ASC'));
        preg_match('/^(.+)\s+(ASC|DESC)$/i', $product_order, $parts);
        if ($parts) {    //should actually always be true
            $collection->orderBy($parts[1], $parts[2]);
        }
        $this->setCollection($collection);

        //prepare feature properties for passing to template file
        $feature_values_model = $feature_model->getValuesModel($feature['type']);
        $value = $feature_values_model->getFeatureValue($value_id);
        if ($feature['type'] == 'color') {
            $page_title_value = $value->value;
            $filter_title_value = $value->{ifset($features_config[$feature_id]['color_display_mode'], 'html')};
        } else {
            $page_title_value = $filter_title_value = htmlspecialchars($value);
        }

        //assign META values from feature config
        $template_vars = array(
            'feature' => array(
                'name'  => $feature['name'],
                'value' => $page_title_value,
            ),
        );

        //save values of existing template vars with same names to avoid changing them
        $existing_template_vars = array();
        foreach ($template_vars as $template_var_name => $template_var_value) {
            if (!is_null($existing_var_value = $this->view->getVars($template_var_name))) {
                $existing_template_vars[$template_var_name] = $existing_var_value;
            }
        }

        //set response fields
        $this->view->assign($template_vars);
        wa()->getResponse()->setTitle($feature['name'].': '.$page_title_value.' — '.$this->getStoreName());

        $listfeatures_feature_model = new shopListfeaturesPluginFeatureModel();
        $feature_meta_data = $listfeatures_feature_model->getByField(array(
            'settlement' => shopListfeaturesPluginHelper::getSettlementHash(),
            'set_id'     => $set_id,
            'feature_id' => $feature_id,
        ));
        wa()->getResponse()->setMeta('keywords', $this-> view->fetch('string:'.ifset($feature_meta_data['meta_keywords'])));
        wa()->getResponse()->setMeta('description', $this-> view->fetch('string:'.ifset($feature_meta_data['meta_description'])));

        //template vars cleanup
        $this->view->clearAssign(array_keys($template_vars));
        if ($existing_template_vars) {
            $this->view->assign($existing_template_vars);
        }

        //assign template vars & file
        $this->view->assign('title', $feature['name'].': '.$filter_title_value);
        $this->view->assign('frontend_search', wa()->event('frontend_search'));
        $this->setThemeTemplate('search.html');
    }
}
