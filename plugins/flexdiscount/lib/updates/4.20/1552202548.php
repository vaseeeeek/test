<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
$model = new waModel();

try {
    $model->exec("SELECT flexdiscount_minimal_discount_price FROM shop_product_skus WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_product_skus ADD flexdiscount_minimal_discount_price DECIMAL(15,4) NOT NULL DEFAULT '0.0000'");
}

try {
    $model->exec("SELECT flexdiscount_minimal_discount_currency FROM shop_product_skus WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_product_skus ADD flexdiscount_minimal_discount_currency CHAR(3) DEFAULT NULL");
}

try {
    $model->exec("SELECT flexdiscount_minimal_discount_price FROM shop_product WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_product ADD flexdiscount_minimal_discount_price DECIMAL(15,4) NOT NULL DEFAULT '0.0000'");
}

try {
    $model->exec("SELECT flexdiscount_minimal_discount_currency FROM shop_product WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_product ADD flexdiscount_minimal_discount_currency CHAR(3) DEFAULT NULL");
}
