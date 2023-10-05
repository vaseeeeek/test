<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
$model = new waModel();

try {
    $model->exec("SELECT flexdiscount_item_discount FROM shop_product_skus WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_product_skus ADD flexdiscount_item_discount DECIMAL(15,4)");
}

try {
    $model->exec("SELECT flexdiscount_discount_currency FROM shop_product_skus WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_product_skus ADD flexdiscount_discount_currency CHAR(3) DEFAULT NULL");
}

try {
    $model->exec("SELECT flexdiscount_item_discount FROM shop_product WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_product ADD flexdiscount_item_discount DECIMAL(15,4)");
}

try {
    $model->exec("SELECT flexdiscount_discount_currency FROM shop_product WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_product ADD flexdiscount_discount_currency CHAR(3) DEFAULT NULL");
}

try {
    $model->exec("SELECT flexdiscount_item_affiliate FROM shop_product_skus WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_product_skus ADD flexdiscount_item_affiliate DECIMAL(15,4)");
}

try {
    $model->exec("SELECT flexdiscount_affiliate_currency FROM shop_product_skus WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_product_skus ADD flexdiscount_affiliate_currency CHAR(3) DEFAULT NULL");
}

try {
    $model->exec("SELECT flexdiscount_item_affiliate FROM shop_product WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_product ADD flexdiscount_item_affiliate DECIMAL(15,4)");
}

try {
    $model->exec("SELECT flexdiscount_affiliate_currency FROM shop_product WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_product ADD flexdiscount_affiliate_currency CHAR(3) DEFAULT NULL");
}
