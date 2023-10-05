<?php
$model = new waModel;
try{
    $model->query('ALTER TABLE  shop_fiwex_feature_explanations DROP COLUMN name');
    $model->query('ALTER TABLE  shop_fiwex_feat_values_explanations DROP COLUMN value');
}catch(waDbException $e){
    
}
