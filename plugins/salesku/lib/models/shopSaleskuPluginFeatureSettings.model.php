<?php
class shopSaleskuPluginFeatureSettingsModel extends shopSaleskuPluginSettingsAbstractModel {
    protected $table = 'shop_salesku_feature_settings';

    public function getByStorefront($storefront_id, $data_id = null) {
        if(is_null($data_id)) {
            $data =  $this->getByField('storefront_id', $storefront_id, true);
            $return = array();
            foreach ($data as $v) {
                if(!isset($return[$v['feature_id']])) {
                    $return[$v['feature_id']] = array();
                }
                $return[$v['feature_id']][$v['key']] = $v['value'];
            }
            return $return;
        } else {
            return $this->getFeatureByStorefront($storefront_id, $data_id);
        }
    }
    public function getFeatureByStorefront($storefront_id, $feature_id) {
        $data =  $this->getByField(array(
            'storefront_id' => $storefront_id,
            'feature_id' => $feature_id
        ), true);
        $return = array();
        foreach ($data as $v) {
            $return[$v['key']] = $v['value'];
        }
        return $return;
    }
    public function saveByStorefront($storefront_id, $data_id, $values = null) {
        if(is_null($values) && is_array($data_id)) {
            foreach ($data_id as $id => $values) {
                $this->saveFeatureByStorefront($storefront_id, $id, $values);
            }
        }
       return true;
    }
    public function saveFeatureByStorefront($storefront_id, $feature_id, $values) {
        if(intval($feature_id)<1) {
            return false;
        } else {
            $insert = array();
            $feature_settings = $this->getFeatureByStorefront($storefront_id, $feature_id);
            if(!is_array($feature_settings)) {
                $feature_settings = array();
            }
            foreach ($values as $key => $value) {
                if(array_key_exists($key, $feature_settings)) {
                    $this->updateByField(array(
                        'storefront_id'=> $storefront_id,
                        'feature_id' => $feature_id,
                        'key' => $key

                    ), array('value' => $value));
                } else {
                    $insert[] = array(
                        'storefront_id'=> $storefront_id,
                        'feature_id' => $feature_id,
                        'key' => $key,
                        'value' => $value
                    );
                }
                unset($feature_settings[$key]);
            }
            if(!empty($feature_settings)) {
                foreach ($feature_settings as $key => $val) {
                    $this->deleteByField(array(
                        'storefront_id' => $storefront_id,
                        'feature_id' => $feature_id,
                        'key' => $key,
                    ));
                }
            }
            if(!empty($insert)) {
                $this->multipleInsert($insert);
            }
            return true;
        }
    }
}

