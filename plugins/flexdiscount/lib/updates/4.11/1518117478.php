<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

$model = new waModel();
try {
    $index_exist = $model->query("SHOW INDEX FROM shop_flexdiscount_coupon_discount WHERE Key_name = 'fl_id'")->fetch();
    if (!$index_exist) {
        // Добавляем обычный индекс
        $model->exec("ALTER TABLE shop_flexdiscount_coupon_discount ADD INDEX `fl_id` (`fl_id`)");
    }
} catch (Exception $e) {

}
