<?php

$model = new waModel();

// add new column
$qry = "ALTER TABLE shop_advancedservices ADD divider INT(11) NOT NULL DEFAULT 0 after ondefault ";

try {
    $model->query('SELECT divider FROM shop_advancedservices WHERE 0');

} catch (waDbException $e) {
  
    $model->exec($qry);
}


