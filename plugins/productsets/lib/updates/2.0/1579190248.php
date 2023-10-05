<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

// Удаляем лишние колонки и таблицы
$model = new waModel();
try {
    $model->exec("DROP TABLE IF EXISTS shop_productsets_items");
} catch (waDbException $e) {

}

try {
    $model->exec("ALTER TABLE shop_productsets DROP COLUMN `discount`");
} catch (waDbException $e) {

}

try {
    $model->exec("ALTER TABLE shop_productsets DROP COLUMN `currency`");
} catch (waDbException $e) {

}

try {
    $model->exec("ALTER TABLE shop_productsets DROP COLUMN `count`");
} catch (waDbException $e) {

}

try {
    $model->exec("ALTER TABLE shop_productsets DROP COLUMN `usercreate`");
} catch (waDbException $e) {

}

try {
    $model->exec("ALTER TABLE shop_productsets DROP COLUMN `include_product`");
} catch (waDbException $e) {

}

try {
    $model->exec("ALTER TABLE shop_productsets DROP COLUMN `custom_color`");
} catch (waDbException $e) {

}

try {
    $model->exec("ALTER TABLE shop_productsets DROP COLUMN `locale`");
} catch (waDbException $e) {

}

try {
    $model->exec("ALTER TABLE shop_productsets DROP COLUMN `change_skus`");
} catch (waDbException $e) {

}