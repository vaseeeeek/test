<?php
/**
 * До версии 1.9.1. fix Перенаправление
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */

$model = new waModel();

try {
    //Создаем таблицу shop_cityselect__cookies
    $sql = "CREATE TABLE IF NOT EXISTS `shop_cityselect__cookies` (
              `id` int NOT NULL AUTO_INCREMENT,
              `key` varchar(32) NOT NULL,              
              `data` mediumtext,
              PRIMARY KEY (`id`),
              KEY `key` (`key`)
            ) DEFAULT CHARSET=utf8;";
    $model->exec($sql);
} catch (waDbException $e) {}



