<?php

$model = new waModel();

$data = array(
    array("name" => "init", "value" => '0'),
    array("name" => "title", "value" => 'Купить в 1 клик'),
    array("name" => "button", "value" => 'Заказать'),
    array("name" => "text", "value" => ''),
    array("name" => "title_success", "value" => 'Ваша заявка отправлена'),
    array("name" => "text_success", "value" => 'В ближайшее время мы обязательно свяжемся с Вами!'),
    array("name" => "yandex_number", "value" => ''),
    array("name" => "yandex_open", "value" => ''),
    array("name" => "yandex_send", "value" => ''),
    array("name" => "yandex_error", "value" => ''),
    array("name" => "goggle_open_category", "value" => ''),
    array("name" => "goggle_open_action", "value" => ''),
    array("name" => "goggle_send_category", "value" => ''),
    array("name" => "goggle_send_action", "value" => ''),
    array("name" => "goggle_error_category", "value" => ''),
    array("name" => "goggle_error_action", "value" => ''),
);

foreach($data as $item){
    $model->query("REPLACE `shop_skoneclick_defines` (`name`, `value`) VALUES ('{$item["name"]}', '{$item["value"]}')");
}
