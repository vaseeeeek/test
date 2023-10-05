<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

$model = new waModel();

try {
    // Изменяем индекс
    $model->exec("ALTER TABLE shop_productsets_cart_items DROP PRIMARY KEY, ADD PRIMARY KEY (`cart_id`,`sku_id`,`product_id`,`bundle_item_id`)");
} catch (waDbException $e) {

}

try {
    // Создаем таблицу shop_productsets_params
    $model->exec("
    CREATE TABLE IF NOT EXISTS `shop_productsets_params` (   
           `productsets_id` int(11) NOT NULL,       
           `param` varchar(255) NOT NULL,           
           `value` text,                            
           PRIMARY KEY (`productsets_id`,`param`),  
           KEY `productsets_id` (`productsets_id`)  
         ) ENGINE=MyISAM DEFAULT CHARSET=utf8      
    ");
} catch (waDbException $e) {

}