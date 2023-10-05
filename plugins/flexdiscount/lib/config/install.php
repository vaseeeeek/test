<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
// Добавление блоков
try {
    $settings_model = new shopFlexdiscountSettingsPluginModel();
    $files = array(
        'coupon_form' => dirname(__FILE__) . '/data/flexdiscount.coupon.form.html',
        'affiliate_block' => dirname(__FILE__) . '/data/flexdiscount.affiliate.html',
        'available_discounts' => dirname(__FILE__) . '/data/flexdiscount.available.html',
        'deny_discounts' => dirname(__FILE__) . '/data/flexdiscount.deny.discounts.html',
        'my_discounts' => dirname(__FILE__) . '/data/flexdiscount.my.discounts.html',
        'price_discounts' => dirname(__FILE__) . '/data/flexdiscount.price.html',
        'product_discounts' => dirname(__FILE__) . '/data/flexdiscount.product.discounts.html',
        'user_discounts' => dirname(__FILE__) . '/data/flexdiscount.user.discounts.html',
        'styles' => dirname(__FILE__) . '/data/flexdiscount.block.styles.css'
    );

    $view = shopFlexdiscountApp::get('system')['wa']->getView();
    foreach ($files as $field => $file) {
        if (file_exists($file)) {
            $contents = $view->fetch('string:' . file_get_contents($file));
            $settings_model->insert(array(
                'field' => $field,
                'ext' => '',
                'value' => $contents
            ), 2);
        }
    }
} catch (waDbException $e) {

}

// Поля минимальной цены
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