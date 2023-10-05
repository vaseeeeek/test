<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
// Удаление созданных блоков
wa('site');
$ids = array("flexdiscount.form", "flexdiscount.available", "flexdiscount.discounts", "flexdiscount.affiliate", "flexdiscount.product.discounts", "flexdiscount.form_old", "flexdiscount.discounts_old", "flexdiscount.affiliate_old", "flexdiscount.all_old");
try {
    $model = new siteBlockModel();
    foreach ($ids as $id) {
        $block = $model->getById($id);
        if ($block) {
            $model->deleteById($id);
        }
    }
} catch (waDbException $e) {
}

// Поля минимальной цены
$model = new waModel();

try {
    $model->exec("SELECT flexdiscount_minimal_discount_price FROM shop_product_skus WHERE 0");
    $model->exec("ALTER TABLE shop_product_skus DROP COLUMN flexdiscount_minimal_discount_price");
} catch (waDbException $e) {
}

try {
    $model->exec("SELECT flexdiscount_minimal_discount_currency FROM shop_product_skus WHERE 0");
    $model->exec("ALTER TABLE shop_product_skus DROP COLUMN flexdiscount_minimal_discount_currency");
} catch (waDbException $e) {
}

try {
    $model->exec("SELECT flexdiscount_minimal_discount_price FROM shop_product WHERE 0");
    $model->exec("ALTER TABLE shop_product DROP COLUMN flexdiscount_minimal_discount_price");
} catch (waDbException $e) {
}

try {
    $model->exec("SELECT flexdiscount_minimal_discount_currency FROM shop_product WHERE 0");
    $model->exec("ALTER TABLE shop_product DROP COLUMN flexdiscount_minimal_discount_currency");
} catch (waDbException $e) {
}

try {
    $model->exec("SELECT flexdiscount_item_discount FROM shop_product_skus WHERE 0");
    $model->exec("ALTER TABLE shop_product_skus DROP COLUMN flexdiscount_item_discount");
} catch (waDbException $e) {
}

try {
    $model->exec("SELECT flexdiscount_discount_currency FROM shop_product_skus WHERE 0");
    $model->exec("ALTER TABLE shop_product_skus DROP COLUMN flexdiscount_discount_currency");
} catch (waDbException $e) {
}

try {
    $model->exec("SELECT flexdiscount_item_discount FROM shop_product WHERE 0");
    $model->exec("ALTER TABLE shop_product DROP COLUMN flexdiscount_item_discount");
} catch (waDbException $e) {
}

try {
    $model->exec("SELECT flexdiscount_discount_currency FROM shop_product WHERE 0");
    $model->exec("ALTER TABLE shop_product DROP COLUMN flexdiscount_discount_currency");
} catch (waDbException $e) {
}

try {
    $model->exec("SELECT flexdiscount_item_affiliate FROM shop_product_skus WHERE 0");
    $model->exec("ALTER TABLE shop_product_skus DROP COLUMN flexdiscount_item_affiliate");
} catch (waDbException $e) {
}

try {
    $model->exec("SELECT flexdiscount_affiliate_currency FROM shop_product_skus WHERE 0");
    $model->exec("ALTER TABLE shop_product_skus DROP COLUMN flexdiscount_affiliate_currency");
} catch (waDbException $e) {
}

try {
    $model->exec("SELECT flexdiscount_item_affiliate FROM shop_product WHERE 0");
    $model->exec("ALTER TABLE shop_product DROP COLUMN flexdiscount_item_affiliate");
} catch (waDbException $e) {
}

try {
    $model->exec("SELECT flexdiscount_affiliate_currency FROM shop_product WHERE 0");
    $model->exec("ALTER TABLE shop_product DROP COLUMN flexdiscount_affiliate_currency");
} catch (waDbException $e) {
}