<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

try {
    $data = [];
    $insert = [];
    $model = new shopProductsetsSettingsPluginModel();

    $data = $model->getByField('field', 'total_block_output', true);
    if ($data) {
        foreach ($data as $d) {
            $insert[] = "('" . $d['productsets_id'] . "', 'layout', 'bundle', '" . json_encode(['total_block_output' => $d['value']], JSON_UNESCAPED_UNICODE) . "')";
            unset($d['value']);
            $model->deleteByField($d);
        }
    }
    if ($insert) {
        $sql = "INSERT IGNORE INTO {$model->getTableName()} (`productsets_id`, `field`, `ext`, `value`) VALUES " . implode(",", $insert);
        $model->exec($sql);
    }
} catch (Exception $e) {

}