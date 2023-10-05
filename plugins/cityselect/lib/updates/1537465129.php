<?php
/**
 * До версии 1.7. Переменные
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */

$model = new waModel();

try {
    $model->query('SELECT * FROM shop_cityselect__variables_type WHERE 0');
} catch (waDbException $e) {
    shopCityselectHelper::createVariableDB(true);
}



