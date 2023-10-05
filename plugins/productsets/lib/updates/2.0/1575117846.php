<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
$model = new waModel();

// Добавляем новые колонки
try {
    $model->exec("SELECT title FROM shop_productsets WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_productsets ADD title VARCHAR (255) NOT NULL DEFAULT ''");
}

try {
    $model->exec("SELECT ruble_sign FROM shop_productsets WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_productsets ADD ruble_sign VARCHAR (4) NOT NULL DEFAULT ''");
}

try {
    $model->exec("SELECT `sort` FROM shop_productsets WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_productsets ADD `sort` INT (11) NOT NULL DEFAULT '0'");
}

try {
    $model->exec("SELECT `description` FROM shop_productsets WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_productsets ADD `description` TEXT");
}

// Удаляем таблицу
try {
    $model->exec("DROP TABLE IF EXISTS shop_productsets_locale");
} catch (waDbException $e) {

}

// Пересоздаем таблицу shop_productsets_cart с полной очисткой данных
try {
    if (!(new shopProductsetsCartPluginModel())->fieldExists('bundle_id')) {
        $model->exec("DROP TABLE IF EXISTS shop_productsets_cart");
    }
} catch (waDbException $e) {

}
try {
    $model->exec("CREATE TABLE IF NOT EXISTS `shop_productsets_cart` (                   
                         `id` int(11) NOT NULL AUTO_INCREMENT,                  
                         `productsets_id` int(11) NOT NULL DEFAULT '0',         
                         `bundle_id` int(11) DEFAULT '0',                       
                         `code` varchar(32) NOT NULL,                           
                         `include_product` tinyint(1) DEFAULT NULL,             
                         PRIMARY KEY (`id`),                                    
                         KEY `code` (`code`)                                    
                       ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
} catch (waDbException $e) {
}

// Пересоздаем таблицу shop_productsets_cart_items с полной очисткой данных
try {
    if (!(new shopProductsetsCartItemsPluginModel())->fieldExists('bundle_item_id')) {
        $model->exec("DROP TABLE IF EXISTS shop_productsets_cart_items");
    }
} catch (waDbException $e) {

}
try {
    $model->exec("CREATE TABLE `shop_productsets_cart_items` (  
                       `cart_id` int(11) NOT NULL,                 
                       `sku_id` int(11) NOT NULL,                  
                       `product_id` int(11) DEFAULT NULL,          
                       `bundle_item_id` int(11) DEFAULT NULL,      
                       `quantity` int(11) DEFAULT NULL,            
                       `is_active` tinyint(1) DEFAULT '0',         
                       `sort` int(11) DEFAULT '0',                 
                       PRIMARY KEY (`cart_id`,`sku_id`),           
                       KEY `cart_id` (`cart_id`)                   
                     ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
} catch (waDbException $e) {
}

// Пересоздаем таблицу shop_productsets_settings с полной очисткой данных
try {
    if (!(new shopProductsetsSettingsPluginModel())->fieldExists('productsets_id')) {
        $model->exec("DROP TABLE IF EXISTS shop_productsets_settings");
    }
} catch (waDbException $e) {

}
try {
    $model->exec("CREATE TABLE `shop_productsets_settings` (        
                         `productsets_id` int(11) NOT NULL DEFAULT '0',  
                         `field` varchar(50) NOT NULL,                   
                         `ext` varchar(50) NOT NULL,                     
                         `value` text,                                   
                         PRIMARY KEY (`productsets_id`,`field`,`ext`),   
                         KEY `field` (`field`),                          
                         KEY `productsets_id` (`productsets_id`),        
                         KEY `ext` (`ext`)                               
                       ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
} catch (waDbException $e) {
}