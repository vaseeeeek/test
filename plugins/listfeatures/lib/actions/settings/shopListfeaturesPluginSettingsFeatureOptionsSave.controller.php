<?php

class shopListfeaturesPluginSettingsFeatureOptionsSaveController extends waController
{
    public function execute()
    {
        $feature_id = waRequest::post('feature_id');
        $options = waRequest::post('options', array(), waRequest::TYPE_ARRAY);

        shopListfeaturesPluginHelper::saveFeatureOptions(array(
            'settlement' => waRequest::post('settlement'),
            'set_id'     => waRequest::post('set_id'),
            'feature_id' => waRequest::post('feature_id'),
            'options'    => $this->prepareFeatureOptions($options, $feature_id),
        ));
    }

    private function prepareFeatureOptions($options, $feature_id)
    {
        foreach ($options as $id => &$value) {
            switch ($id) {
                case 'values_limit':
                    $value = (int) trim($value);
                    if ($value < 1) {
                        unset($options[$id]);
                    }
                    break;
                case 'name':
                case 'remaining_indicator':
                    $value = trim($value);
                    if (!strlen($value)) {
                        unset($options[$id]);
                    }
                    break;
                case 'class_name':
                    $value = trim($value);
                    if (!preg_match('/^\w+$/', $value)) {
                        unset($options[$id]);
                    }
                    break;
                case 'delimiter':
                    if (!strlen($options[$id])) {
                        unset($options[$id]);
                    }
                    break;
            }
        }

        return $options;
    }
}
