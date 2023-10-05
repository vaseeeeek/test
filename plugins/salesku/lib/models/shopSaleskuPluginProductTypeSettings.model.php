<?php
class shopSaleskuPluginProductTypeSettingsModel extends shopSaleskuPluginSettingsAbstractModel {
    protected $table = 'shop_salesku_product_type_settings';
    
    public function getByStorefront($storefront_id, $data_id = null) {
            if(is_null($data_id)) {
                $data =  $this->getByField('storefront_id', $storefront_id, true);
                $return = array();
                foreach ($data as $v) {
                    if(!isset($return[$v['product_type_id']])) {
                        $return[$v['product_type_id']] = array();
                    }
                    $return[$v['product_type_id']][$v['key']] = $v['value'];
                }
                return $return;
            } else {
               return $this->getProductTypeByStorefront($storefront_id,  $data_id);
            }
        }
    public function getProductTypeByStorefront($storefront_id,  $product_type_id) {
        $data =  $this->getByField(array(
            'storefront_id' => $storefront_id,
            'product_type_id' => $product_type_id
        ), true);
        $return = array();
        foreach ($data as $v) {
            $return[$v['key']] = $v['value'];
        }
        return $return;
    }
    public function saveByStorefront($storefront_id, $data_id, $values = null) {
        if(is_null($values) && is_array($data_id)) {
            foreach ($data_id as $id => $values)   {
                $this->saveProductTypeByStorefront($storefront_id,$id,$values);
            }
        } else {
            return $this->saveProductTypeByStorefront($storefront_id,  $data_id, $values);
        }
        return true;
    }
    public function saveProductTypeByStorefront($storefront_id,  $product_type_id, $values) {
        if(intval($product_type_id)<1) {
            return false;
        } else {
            $insert = array();
            $type_settings = $this->getByStorefront($storefront_id,  $product_type_id);
            if(!is_array($type_settings)) {
                $type_settings = array();
            }
            foreach ($values as $key => $value) {
                if(array_key_exists($key, $type_settings)) {
                    $this->updateByField(array(
                        'storefront_id' => $storefront_id,
                        'product_type_id' =>  $product_type_id,
                        'key' => $key

                    ), array('value' => $value));
                } else {
                    $insert[] = array(
                        'storefront_id' => $storefront_id,
                        'product_type_id' =>  $product_type_id,
                        'key' => $key,
                        'value' => $value
                    );
                }
                unset($type_settings[$key]);
            }
            if(!empty($type_settings)) {
                foreach ($type_settings as $key => $val) {
                    $this->deleteByField(array(
                        'storefront_id' => $storefront_id,
                        'product_type_id' => $product_type_id,
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

