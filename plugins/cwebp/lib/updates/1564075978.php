<?php

$sql = <<<SQL
CREATE TABLE IF NOT EXISTS `shop_cwebp_queue` (
 `source` varchar(191) NOT NULL,
 `destination` varchar(191) NOT NULL,
 PRIMARY KEY (`source`),
 UNIQUE KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;

$model = new waModel();
$model->exec($sql);
