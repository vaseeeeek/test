<?php

$asm = new waAppSettingsModel();

//update feature sets
$features = @ unserialize($asm->get('shop.listfeatures', 'features', array()));
$asm->del('shop.listfeatures', 'features');
if ($features && is_array($features)) {
    $features_data = array();
    foreach ($features as $settlement => $config) {
        //add missing set IDs for newly added settlements: bug fix
        $max_set_id = max(array_keys($config));
        $set_id_counter = 0;
        while (++$set_id_counter < $max_set_id) {
            if (!isset($config[$set_id_counter])) {
                $config[$set_id_counter] = array('features' => array());
            }
        }
        ksort($config);

        $features_data[] = array(
            'app_id' => 'shop.listfeatures',
            'name'   => $settlement,
            'value'  => json_encode($config),
        );
    }
    //paranoid: these entries should not exist
    $asm->deleteByField(array(
        'app_id' => 'shop.listfeatures',
        'name'   => array_keys($features),
    ));
    $asm->multipleInsert($features_data);
}

//update templates
$templates = @ unserialize($asm->get('shop.listfeatures', 'templates', array()));
$asm->del('shop.listfeatures', 'templates');
if ($templates && is_array($templates)) {
    $templates_data = array();
    $template_keys = array();
    foreach ($templates as $id => $source) {
        $templates_data[] = array(
            'app_id' => 'shop.listfeatures',
            'name'   => 'template'.$id,
            'value'  => $source,
        );
        $template_keys[] = 'template'.$id;
    }
    //paranoid: these entries should not exist
    $asm->deleteByField(array(
        'app_id' => 'shop.listfeatures',
        'name'   => $template_keys,
    ));
    $asm->multipleInsert($templates_data);
}
