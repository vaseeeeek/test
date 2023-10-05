<?php

$model = new waModel();

$model->exec("ALTER TABLE `shop_complex_condition` CHANGE `field` `field` VARCHAR(255);");