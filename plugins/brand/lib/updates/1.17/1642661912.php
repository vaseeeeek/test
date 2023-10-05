<?php

try {
        $model = new waModel();

        $model->exec("
        ALTER TABLE `shop_brand_brand` ADD `empty_page_response_mode` ENUM('DEFAULT','DEFAULT_200','DEFAULT_404','ERROR_404') NOT NULL DEFAULT 'DEFAULT' AFTER `enable_client_sorting`
    ");
} catch (Exception $e) {

}

