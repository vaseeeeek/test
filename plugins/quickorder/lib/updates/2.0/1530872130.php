<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

$model = new waModel();
try {
    $sql = <<<SQL
  CREATE TABLE IF NOT EXISTS `shop_quickorder_cart_items` (                     
      `id` int(11) NOT NULL AUTO_INCREMENT,                         
      `code` varchar(32) DEFAULT NULL,                              
      `contact_id` int(11) DEFAULT NULL,                            
      `product_id` int(11) NOT NULL,                                
      `sku_id` int(11) NOT NULL,                                    
      `create_datetime` datetime NOT NULL,                          
      `quantity` int(11) NOT NULL DEFAULT '1',                      
      `type` enum('product','service') NOT NULL DEFAULT 'product',  
      `service_id` int(11) DEFAULT NULL,                            
      `service_variant_id` int(11) DEFAULT NULL,                    
      `parent_id` int(11) DEFAULT NULL,                             
      PRIMARY KEY (`id`),                                           
      KEY `code` (`code`)                                           
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8     
SQL;
    $model->exec($sql);

    $sql2 = <<<SQL
  CREATE TABLE IF NOT EXISTS `shop_quickorder_settings` (            
    `storefront` varchar(50) NOT NULL DEFAULT 'all',  
    `field` varchar(50) NOT NULL,                     
    `ext` varchar(50) NOT NULL DEFAULT '',            
    `value` text,                                      
    PRIMARY KEY (`storefront`,`field`,`ext`),          
    KEY `field` (`storefront`)                         
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 
SQL;
    $model->exec($sql2);
} catch (waDbException $e) {

}