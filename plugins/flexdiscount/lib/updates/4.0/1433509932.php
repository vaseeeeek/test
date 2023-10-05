<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

// Удаление ненужных файлов

$files = array(
    dirname(__FILE__) . '/../../../js/colorpicker/',
    dirname(__FILE__) . '/../../../js/countdown/',
    dirname(__FILE__) . '/../../../js/jquery.mask.min.js/',
    dirname(__FILE__) . '/../../../img/example.jpg',
    dirname(__FILE__) . '/../../../img/coupon-icons.png',
    dirname(__FILE__) . '/../../../templates/actions/coupons/CouponsPrepareDelete.html',
    dirname(__FILE__) . '/../../../templates/actions/settings/SettingsReadme.html',
    dirname(__FILE__) . '/../../../lib/classes/discounts/',
    dirname(__FILE__) . '/../../../lib/classes/shopFlexdiscountMask.class.php',
    dirname(__FILE__) . '/../../../lib/config/data/flexdiscount.discounts.html',
    dirname(__FILE__) . '/../../../lib/config/data/flexdiscount.form.html',
    dirname(__FILE__) . '/../../../lib/config/config.php',
    dirname(__FILE__) . '/../../../lib/actions/coupons/shopFlexdiscountPluginCouponsPrepareDelete.action.php',
    dirname(__FILE__) . '/../../../lib/actions/settings/shopFlexdiscountPluginSettingsMoreDiscounts.controller.php',
);

for ($i = 2; $i <= 14; $i++) {
    $files[] = dirname(__FILE__) . '/../../../img/example' . $i . '.jpg';
}

foreach ($files as $file) {
    try {
        waFiles::delete($file, true);
    } catch (waException $e) {
        
    }
}