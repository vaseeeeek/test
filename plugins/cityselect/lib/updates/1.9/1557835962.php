<?php
/**
 * До версии 1.9. Перенаправление
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */

$model = new waModel();

try {
    $model->query("ALTER TABLE  `shop_cityselect__region` ADD  `redirect` VARCHAR( 255 ) NOT NULL DEFAULT  ''");
} catch (waDbException $e) {

}



