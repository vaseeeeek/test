<?php

$price_model = new shopPricePluginModel();

$prices = $price_model->getAll();
foreach ($prices as $price) {
    try {
        $sql = "ALTER TABLE `shop_product_skus` ADD `price_plugin_markup_price_" . (int) $price['id'] . "` ENUM('price','purchase_price') NOT NULL DEFAULT 'price';";
        $price_model->query($sql);
    } catch (waDbException $ex) {

    }
}

try{
    waSystem::getInstance('installer');
    installerHelper::flushCache();
}catch (Exception $exception) {

}