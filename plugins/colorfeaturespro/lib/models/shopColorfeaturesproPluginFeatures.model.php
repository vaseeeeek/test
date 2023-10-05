<?php

class shopColorfeaturesproPluginFeaturesModel extends shopFeatureModel
{
    protected $table_colorfeaturespro = 'shop_colorfeaturespro';
    protected $table_color_features_values = 'shop_feature_values_color';
    public function getAllValues()
    {
        return $this->query('SELECT fc.*, c.style FROM ' . $this->table_color_features_values . ' fc left join ' . $this->table_colorfeaturespro . ' c ON c.color_id=fc.id ')->fetchAll();
    }
}
