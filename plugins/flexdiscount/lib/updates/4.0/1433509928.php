<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
$model = new waModel();

try {
    $model->exec("ALTER TABLE shop_flexdiscount_settings CHANGE `value` `value` TEXT NOT NULL");
} catch (waDbException $e) {
    
}

try {
    $index_exist = $model->query("SHOW INDEX FROM shop_flexdiscount_settings WHERE Key_name = 'field_ext'")->fetch();
    if ($index_exist) {
        // Удаляем старый индекс
        $model->exec("ALTER TABLE shop_flexdiscount_settings DROP INDEX field_ext");
        // Добавляем обычный индекс
        $model->exec("ALTER TABLE shop_flexdiscount_settings ADD PRIMARY KEY (field, ext)");
    }
} catch (waDbException $e) {
    
}

try {
    $price_output = $model->query("SELECT * FROM shop_flexdiscount_settings WHERE `field` = 'enable_price_output' && `ext` = ''")->fetchAssoc();
    if ($price_output) {
        $model->exec("UPDATE shop_flexdiscount_settings SET `ext` = 'value' WHERE `field` = 'enable_price_output' && `ext` = ''");
    }
} catch (waDbException $e) {
    
}

try {
    $user_discounts = $model->query("SELECT * FROM shop_flexdiscount_settings WHERE `field` = 'enable_flexdiscount_discounts' && `ext` = ''")->fetchAssoc();
    if ($user_discounts) {
        $model->exec("UPDATE shop_flexdiscount_settings SET `ext` = 'value', `field` = 'flexdiscount_user_discounts' WHERE `field` = 'enable_flexdiscount_discounts' && `ext` = ''");
    }
} catch (waDbException $e) {
    
}

try {
    $settings_model = new shopFlexdiscountSettingsPluginModel();
    $files = array(
        'coupon_form' => dirname(__FILE__) . '/../../config/data/flexdiscount.coupon.form.html',
        'affiliate_block' => dirname(__FILE__) . '/../../config/data/flexdiscount.affiliate.html',
        'available_discounts' => dirname(__FILE__) . '/../../config/data/flexdiscount.available.html',
        'deny_discounts' => dirname(__FILE__) . '/../../config/data/flexdiscount.deny.discounts.html',
        'my_discounts' => dirname(__FILE__) . '/../../config/data/flexdiscount.my.discounts.html',
        'price_discounts' => dirname(__FILE__) . '/../../config/data/flexdiscount.price.html',
        'product_discounts' => dirname(__FILE__) . '/../../config/data/flexdiscount.product.discounts.html',
        'user_discounts' => dirname(__FILE__) . '/../../config/data/flexdiscount.user.discounts.html'
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