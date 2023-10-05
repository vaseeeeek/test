<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
$model = new waModel();

try {
    $app_settings = new waAppSettingsModel();
    $update_sort = $app_settings->get('shop', 'flexdiscount.update_sort');
    if (!$update_sort) {
        // Определяем порядок сортировки
        $fl_model = new shopFlexdiscountPluginModel();
        $discounts = $fl_model->select("*")->order("sort ASC")->fetchAll();
        if ($discounts) {
            foreach ($discounts as $k => $d) {
                $fl_model->updateById($d['id'], array("sort" => $k));
            }
        }
        $app_settings->set('shop', 'flexdiscount.update_sort', 1);
    }
} catch (Exception $e) {
    
}

try {
    $sql = <<<SQL
  CREATE TABLE IF NOT EXISTS `shop_flexdiscount_group` (  
    `id` int(11) NOT NULL AUTO_INCREMENT,   
    `name` varchar(50) DEFAULT '',          
    `combine` varchar(3) NOT NULL,      
    PRIMARY KEY (`id`)                      
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 
SQL;
    $model->exec($sql);

    $sql2 = <<<SQL
  CREATE TABLE IF NOT EXISTS `shop_flexdiscount_group_discount` (  
    `group_id` int(11) NOT NULL,                     
    `fl_id` int(11) NOT NULL,                        
    PRIMARY KEY (`group_id`,`fl_id`)                 
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 
SQL;
    $model->exec($sql2);

    $sql3 = <<<SQL
  CREATE TABLE IF NOT EXISTS `shop_flexdiscount_params` (  
    `fl_id` int(11) NOT NULL,            
    `field` varchar(50) NOT NULL,        
    `ext` varchar(50) DEFAULT '',            
    `value` text,
    PRIMARY KEY (`fl_id`,`field`,`ext`)    
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8  
SQL;
    $model->exec($sql3);
    $sql4 = <<<SQL
  CREATE TABLE IF NOT EXISTS `shop_flexdiscount_order_params` (  
    `order_id` int(11) NOT NULL,                   
    `name` varchar(30) NOT NULL,                   
    `value` text,                                  
    PRIMARY KEY (`order_id`,`name`),               
    KEY `name` (`name`)                            
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8  
SQL;
    $model->exec($sql4);
    $sql5 = <<<SQL
  CREATE TABLE IF NOT EXISTS `shop_flexdiscount_coupon_discount` (  
    `coupon_id` int(11) NOT NULL,                     
    `fl_id` int(11) NOT NULL,                         
    PRIMARY KEY (`coupon_id`,`fl_id`)                 
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8  
SQL;
    $model->exec($sql5);
} catch (waDbException $e) {
    
}