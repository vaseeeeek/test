<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
$model = new waModel();
try {
    $model->exec("SELECT symbols FROM shop_flexdiscount_coupon WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_flexdiscount_coupon ADD symbols VARCHAR (100) NOT NULL DEFAULT ''");
}
try {
    $model->exec("SELECT prefix FROM shop_flexdiscount_coupon WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_flexdiscount_coupon ADD prefix VARCHAR (30) NOT NULL DEFAULT ''");
}
try {
    $model->exec("SELECT length FROM shop_flexdiscount_coupon WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_flexdiscount_coupon ADD length TINYINT (2) NOT NULL DEFAULT '0'");
}
try {
    $model->exec("SELECT start FROM shop_flexdiscount_coupon WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_flexdiscount_coupon ADD start DATETIME");
}
try {
    $model->exec("SELECT type FROM shop_flexdiscount_coupon WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_flexdiscount_coupon ADD type VARCHAR (9) NOT NULL DEFAULT 'coupon'");
}
try {
    $model->exec("SELECT name FROM shop_flexdiscount_coupon WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_flexdiscount_coupon ADD name VARCHAR (30) NOT NULL DEFAULT ''");
}
try {
    $model->exec("ALTER TABLE shop_flexdiscount_coupon CHANGE `expire_datetime` `end` DATETIME");
} catch (waDbException $e) {
    
}
try {
    $model->exec("ALTER TABLE shop_flexdiscount_coupon CHANGE `code` `code` VARCHAR (50) NOT NULL DEFAULT ''");
} catch (waDbException $e) {
    
}
try {
    $model->exec("SELECT create_datetime FROM shop_flexdiscount_coupon WHERE 0");
    $model->exec("ALTER TABLE shop_flexdiscount_coupon DROP COLUMN create_datetime");
} catch (waDbException $e) {
    
}
try {
    $model->exec("SELECT color FROM shop_flexdiscount_coupon WHERE 0");
    $model->exec("ALTER TABLE shop_flexdiscount_coupon DROP COLUMN color");
} catch (waDbException $e) {
    
}
try {
    $model->exec("SELECT sort FROM shop_flexdiscount_coupon WHERE 0");
    $model->exec("ALTER TABLE shop_flexdiscount_coupon DROP COLUMN sort");
} catch (waDbException $e) {
    
}
try {
    $index_exist = $model->query("SHOW INDEX FROM shop_flexdiscount_coupon WHERE Key_name = 'code'")->fetch();
    if ($index_exist && isset($index_exist['Non_unique']) && !$index_exist['Non_unique']) {
        // Удаляем старый индекс
        $model->exec("ALTER TABLE shop_flexdiscount_coupon DROP INDEX code");
        // Добавляем обычный индекс
        $model->exec("ALTER TABLE shop_flexdiscount_coupon ADD INDEX `code` (`code`)");
    }
} catch (waDbException $e) {
    
}
try {
    $index_exist2 = $model->query("SHOW INDEX FROM shop_flexdiscount_coupon_order WHERE Key_name = 'code'")->fetch();
    if (!$index_exist2) {
        // Добавляем обычный индекс
        $model->exec("ALTER TABLE shop_flexdiscount_coupon_order ADD INDEX `code` (`code`)");
    }
} catch (waDbException $e) {
    
}
try {
    $model->exec("SELECT code FROM shop_flexdiscount_coupon_order WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_flexdiscount_coupon_order ADD code VARCHAR (50) NOT NULL DEFAULT ''");
}
try {
    $model->exec("SELECT reduced FROM shop_flexdiscount_coupon_order WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_flexdiscount_coupon_order ADD reduced TINYINT (1) NOT NULL DEFAULT '1'");
}