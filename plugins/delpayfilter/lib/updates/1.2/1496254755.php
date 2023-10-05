<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

function repairConditions($conditions)
{
    $conditions = shopDelpayfilterConditions::decode($conditions);
    if (isset($conditions['group_op'])) {
        $conditions['conditions'] = repairConditions($conditions['conditions']);
    } else {
        foreach ($conditions as $k => $c) {
            if (isset($c['group_op'])) {
                $conditions['conditions'] = repairConditions($conditions['conditions']);
            } else {
                if ($c['type'] == 'user_country' && !isset($c['field'])) {
                    $conditions[$k]['field'] = $c['value'];
                    $conditions[$k]['value'] = '';
                }
            }
        }
    }
    return $conditions;
}

try {
    $model = new shopDelpayfilterPluginModel();
    foreach ($model->getAll() as $row) {
        if ($row['conditions']) {
            $conditions = repairConditions($row['conditions']);
            $row['conditions'] = json_encode($conditions);
            $model->updateById($row['id'], array("conditions" => $row['conditions']));
        }
    }
} catch (Exception $e) {
    
}

try {
    $model->exec("SELECT error_shipping FROM shop_delpayfilter WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_delpayfilter ADD error_shipping TEXT");
}
try {
    $model->exec("SELECT error_payment FROM shop_delpayfilter WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_delpayfilter ADD error_payment TEXT");
}