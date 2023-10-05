<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

function repairConditions($conditions)
{
    $conditions = shopFlexdiscountConditions::decodeToArray($conditions);
    if (isset($conditions['group_op'])) {
        $conditions['conditions'] = repairConditions($conditions['conditions']);
    } else {
        foreach ($conditions as $k => $c) {
            if (isset($c['group_op'])) {
                $conditions['conditions'] = repairConditions($conditions['conditions']);
            } else {
                if (in_array($c['type'], array('order_int', 'order_prod_int', 'order_count_int', 'order_prod_cat_int', 'order_prod_cat_all_int')) && !isset($c['period_type'])) {
                    $key1 = ($c['type'] == 'order_prod_cat_int' || $c['type'] == 'order_prod_cat_all_int' ? 'ext1' : 'field');
                    $key2 = ($c['type'] == 'order_prod_cat_int' || $c['type'] == 'order_prod_cat_all_int' ? 'ext2' : 'ext');
                    $conditions[$k]['field1'] = $c[$key1];
                    $conditions[$k]['ext1'] = $c[$key2];
                    $conditions[$k]['period_type'] = 'period';
                    unset($conditions[$k][$key1], $conditions[$k][$key2]);
                }
            }
        }
    }
    return $conditions;
}

try {
    $model = new shopFlexdiscountPluginModel();
    foreach ($model->getAll() as $row) {
        if ($row['conditions']) {
            $conditions = repairConditions($row['conditions']);
            $row['conditions'] = json_encode($conditions);
            $model->updateById($row['id'], array("conditions" => $row['conditions']));
        }
    }
} catch (Exception $e) {
    
}

// Удаление ненужных файлов
$files = array(
    dirname(__FILE__) . '/../../../lib/actions/backend/shopFlexdiscountPluginBackendCouponListAutocomplete.controller.php',
    dirname(__FILE__) . '/../../../lib/actions/backend/shopFlexdiscountPluginBackendProductAutocomplete.controller.php',
    dirname(__FILE__) . '/../../../lib/actions/backend/shopFlexdiscountPluginBackendCouponAutocomplete.controller.php',
    dirname(__FILE__) . '/../../../lib/actions/backend/shopFlexdiscountPluginBackendContactAutocomplete.controller.php',
);

foreach ($files as $file) {
    try {
        waFiles::delete($file, true);
    } catch (waException $e) {
        
    }
}