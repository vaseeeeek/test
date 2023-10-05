<?php

$model = new waModel();
$model->exec(
    "CREATE TABLE IF NOT EXISTS `shop_tageditor_tag` (
      `id` int(11) unsigned NOT NULL,
      `meta_title` varchar(255) NOT NULL DEFAULT '',
      `meta_description` text NOT NULL,
      `meta_keywords` text NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;"
);
