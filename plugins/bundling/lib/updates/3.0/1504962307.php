<?php

$model = new shopBundlingProductsModel();

try {
    $model->exec("SELECT bundled_product_id FROM shop_bundling_products WHERE 0");
} catch (waDbException $e) {
	// bundle products

	$rows = $model->getAll();
	$datas = array();
	foreach($rows as $row) {
		if(!empty($row['product_ids'])) {
			$product_ids = json_decode($row['product_ids'], true);
			foreach($product_ids as $product_id) {
				$data = array(
					'product_id' => $row['product_id'],
					'bundle_id' => $row['bundle_id'],
					'bundled_product_id' => $product_id,
				);
				
				$datas[] = $data;
			}
		}
	}


	try {
		$model->exec("CREATE TABLE `shop_bundling_products_new` (`product_id` int(11) NOT NULL, `bundle_id` int(11) NOT NULL, `bundled_product_id` int(11) NOT NULL DEFAULT '0', `sku_id` int(11) NOT NULL DEFAULT '0', `default_quantity` int(11) NOT NULL DEFAULT '1', `discount` int(11) NOT NULL DEFAULT '0') ENGINE=MyISAM DEFAULT CHARSET=utf8;");
	} catch(waDbException $e) {
		waLog::log($e, 'bundling.update.log');
	}
		
	try {
		$model->exec("ALTER TABLE `shop_bundling_products_new` ADD PRIMARY KEY (`product_id`,`bundle_id`,`bundled_product_id`,`sku_id`);");
	} catch(waDbException $e) {
		waLog::log($e, 'bundling.update.log');
	}

	foreach($datas as $data) {
		try {
			$model->exec("INSERT IGNORE INTO `shop_bundling_products_new` (`product_id`,`bundle_id`,`bundled_product_id`) VALUES (?, ?, ?)", intval($data['product_id']), intval($data['bundle_id']), intval($data['bundled_product_id']));
		} catch(waDbException $e) {
			waLog::log($e, 'bundling.update.log');
		}
	}

	try {
		// $model->exec("DROP TABLE `shop_bundling_products`");
		$model->exec("RENAME TABLE `shop_bundling_products` TO `shop_bundling_products_old`");
		
		try {
			$model->exec("RENAME TABLE `shop_bundling_products_new` TO `shop_bundling_products`");
		} catch(waDbException $e) {
			waLog::log($e, 'bundling.update.log');
		}
	} catch(waDbException $e) {
		waLog::log($e, 'bundling.update.log');
	}
}

// bundle features

try {
    $model->exec("SELECT feature_id FROM shop_bundling_bundles WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_bundling_bundles ADD feature_id INT(11) NULL");
}

try {
    $model->exec("SELECT feature_value FROM shop_bundling_bundles WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_bundling_bundles ADD feature_value INT(11) NULL");
}

// discounts

try {
    $model->exec("SELECT discount FROM shop_bundling_bundles WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_bundling_bundles ADD discount INT(11) NOT NULL DEFAULT '0'");
}

try {
    $model->exec("SELECT default_quantity FROM shop_bundling_bundles WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_bundling_bundles ADD default_quantity INT(11) NOT NULL DEFAULT '1'");
}

$model->exec("ALTER TABLE `shop_bundling_bundles` CHANGE `title` `title` VARCHAR(255);");

// update shevskySettingsControls

$files = array(
	wa()->getAppPath("plugins/bundling/lib/vendors/shevskySettingsControlsV5/", "shop"),
	wa()->getAppPath("plugins/bundling/js/shevskySettingsControlsV5.js", "shop"),
);

try {
	foreach($files as $file) {
		if(file_exists($file)) {
			waFiles::delete($file, true);
		}
	}
} catch (Exception $e) {  
}