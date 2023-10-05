<?php
class shopSaleskuPluginSettingsModel extends shopSaleskuPluginSettingsAbstractModel
{
    protected $table = 'shop_salesku_settings';

    public function getByStorefront($storefront_id, $data_id = null) {
        if(!is_null($data_id)) {
            $data = $this->getByField(array(
                'storefront_id' => $storefront_id,
                'key' => $data_id
            ));
            if(!empty($data)) {
                return $data['value'];
            }
            return null;
        } else {
            $data = $this->getByField('storefront_id', $storefront_id, true);
            $return = array();
            foreach ($data as $v) {
                $return[$v['key']] = $v['value'];
            }
            return $return;
        }

    }
    public function saveByStorefront($storefront_id, $data_id, $values = null)
    {
        if(is_null($values) && is_array($data_id)) {
            $insert = array();
            $settings = $this->getByStorefront($storefront_id);
            if(!is_array($settings)) {
                $settings = array();
            }
            foreach ($data_id as $key => $value) {
                if(array_key_exists($key, $settings)) {
                    $this->updateByField(array(
                        'storefront_id' => $storefront_id,
                        'key' => $key

                    ), array('value' => $value));
                } else {
                    $insert[] = array(
                        'storefront_id'=> $storefront_id,
                        'key' => $key,
                        'value' => $value
                    );
                }
                unset($settings[$key]);
            }
            if(!empty($settings)) {
                foreach ($settings as $key => $val) {
                    $this->deleteByField(array(
                        'storefront_id' => $storefront_id,
                        'key' => $key
                    ));
                }
            }
            if(!empty($insert)) {
                $this->multipleInsert($insert);
            }
            return true;
        } elseif(!is_array($data_id) && !is_null($values)) {
            $setting = $this->getByStorefront($storefront_id, $data_id);
            if(!empty($setting)) {
                $this->updateByField(array(
                    'storefront_id' => $storefront_id,
                    'key' => $data_id

                ), array('value' => $values));
            } else {
                $this->insert(array(
                    'storefront_id'=> $storefront_id,
                    'key' => $data_id,
                    'value' => $values
                ));
            }
        }

    }
}

