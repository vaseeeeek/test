<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
$model = new waModel();

// Новые таблицы
try {
    $model->exec("CREATE TABLE IF NOT EXISTS `shop_productsets_bundle` (                
                       `id` int(11) NOT NULL AUTO_INCREMENT,                 
                       `productsets_id` int(11) DEFAULT NULL,                
                       `sort_id` int(11) DEFAULT '0',                        
                       `settings` text,                                      
                       PRIMARY KEY (`id`),                                   
                       KEY `productsets_id` (`productsets_id`)               
                     ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
} catch (waDbException $e) {
}

try {
    $model->exec("CREATE TABLE IF NOT EXISTS `shop_productsets_bundle_item` (            
                        `id` int(11) NOT NULL AUTO_INCREMENT,                  
                        `type` varchar(15) DEFAULT 'product',                  
                        `bundle_id` int(11) DEFAULT NULL,                      
                        `product_id` int(11) DEFAULT '0',                      
                        `sku_id` int(11) DEFAULT '0',                          
                        `parent_id` int(11) DEFAULT '0',                       
                        `sort_id` int(11) DEFAULT '0',                         
                        `settings` text,                                       
                        PRIMARY KEY (`id`),                                    
                        KEY `bundle_id` (`bundle_id`)                          
                      ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
} catch (waDbException $e) {
}

try {
    $model->exec("CREATE TABLE IF NOT EXISTS `shop_productsets_storefront` (               
                       `productsets_id` int(11) NOT NULL,                       
                       `operator` varchar(3) NOT NULL,                          
                       `storefront` varchar(255) NOT NULL,                      
                       PRIMARY KEY (`productsets_id`,`operator`,`storefront`),  
                       KEY `productsets_id` (`productsets_id`),                 
                       KEY `storefront` (`storefront`)                          
                     ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
} catch (waDbException $e) {
}

try {
    $model->exec("CREATE TABLE IF NOT EXISTS `shop_productsets_userbundle` (            
                       `id` int(11) NOT NULL AUTO_INCREMENT,                 
                       `productsets_id` int(11) DEFAULT NULL,                
                       `settings` text,                                      
                       PRIMARY KEY (`id`),                                   
                       UNIQUE KEY `productsets_id` (`productsets_id`)        
                     ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
} catch (waDbException $e) {
}

try {
    $model->exec("CREATE TABLE IF NOT EXISTS `shop_productsets_userbundle_group` (      
                         `id` int(11) NOT NULL AUTO_INCREMENT,                 
                         `userbundle_id` int(11) DEFAULT NULL,                 
                         `sort_id` int(11) DEFAULT '0',                        
                         `settings` text,                                      
                         PRIMARY KEY (`id`),                                   
                         KEY `userbundle_id` (`userbundle_id`)                 
                       ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
} catch (waDbException $e) {
}

try {
    $model->exec("CREATE TABLE IF NOT EXISTS `shop_productsets_userbundle_group_item` (   
                      `id` int(11) NOT NULL AUTO_INCREMENT,                   
                      `group_id` int(11) DEFAULT NULL,                        
                      `product_id` int(11) DEFAULT '0',                       
                      `sku_id` int(11) DEFAULT '0',                           
                      `type` varchar(15) DEFAULT 'product',                   
                      `sort_id` int(11) DEFAULT '0',                          
                      `settings` text,                                        
                      PRIMARY KEY (`id`),                                     
                      KEY `group_id` (`group_id`),                            
                      KEY `type` (`type`)                                     
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
} catch (waDbException $e) {
}

try {
    $model->exec("CREATE TABLE IF NOT EXISTS `shop_productsets_userbundle_item` (       
                        `id` int(11) NOT NULL AUTO_INCREMENT,                 
                        `bundle_id` int(11) DEFAULT NULL,                     
                        `product_id` int(11) DEFAULT '0',                     
                        `sku_id` int(11) DEFAULT '0',                         
                        `type` varchar(15) DEFAULT 'product',                 
                        `sort_id` int(11) DEFAULT '0',                        
                        `settings` text,                                      
                        PRIMARY KEY (`id`),                                   
                        KEY `userbundle_id` (`bundle_id`)                     
                      ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
} catch (waDbException $e) {
}
