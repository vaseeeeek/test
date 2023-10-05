<?php

class shopFeaturestipsPluginDo
{
    public function GetTipsArrayWithKeyTypeId($item)
    {
        $n_item = array();
        foreach($item as $value) {
            if(!array_key_exists($value['feature_id'], $n_item) || ($n_item[$value['feature_id']]['global'] == 1 && $value['global'] == 0))
            {
                $n_item[$value['feature_id']] = $value;
            }
        }

        return $n_item;
    }
}