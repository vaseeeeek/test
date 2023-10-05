<?php

$model = new waModel();

try {
    $model->query("ALTER TABLE `shop_emailform_emails` DROP INDEX `email`");
} catch (waDbException $e) {
    //в случае неудачи — индекс уже был удален (с версии 1.03)
}