<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
$model = new waModel();
try {
     $sql = <<<SQL
  CREATE TABLE IF NOT EXISTS `shop_productsets_locale` (    
    `set_id` int(11) NOT NULL,    
    `locale` varchar(5) NOT NULL,  
    `msg` varchar(255) NOT NULL,          
    `msg2` varchar(255) DEFAULT NULL,         
    PRIMARY KEY (`set_id`,`locale`, `msg`)           
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 
SQL;
    $model->exec($sql);
} catch (waDbException $e) {
    
}

try {
    $model->exec("SELECT locale FROM shop_productsets WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_productsets ADD locale VARCHAR (5) NOT NULL DEFAULT ''");
}

try {
    $model->exec("SELECT change_skus FROM shop_productsets WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_productsets ADD change_skus INT (1) NOT NULL DEFAULT 0");
}