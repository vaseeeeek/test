<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

$model = new waModel();
try {
    $model->exec("ALTER TABLE `shop_productsets_cart_items` CHANGE `bundle_item_id` `bundle_item_id` VARCHAR (50)");
} catch (waDbException $e) {

}