<?php

$model = new shopBundlingProductsModel();

try {
    $model->exec("SELECT sort FROM shop_bundling_products WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_bundling_products ADD sort INT(11) NOT NULL DEFAULT '0'");
}

$files = array(
	wa()->getAppPath("plugins/bundling/templates/actions/error/", "shop"),
	wa()->getAppPath("plugins/bundling/templates/actions/dialog/Dialog.html", "shop"),
	wa()->getAppPath("plugins/bundling/js/bundling.dialog.js", "shop"),
	wa()->getAppPath("plugins/bundling/lib/actions/shopBundlingPluginDialog.action.php", "shop"),
	wa()->getAppPath("plugins/bundling/lib/actions/shopBundlingPluginSetBundlesToProducts.controller.php", "shop"),
);

try {
	foreach($files as $file) {
		if(file_exists($file)) {
			waFiles::delete($file, true);
		}
	}
} catch (Exception $e) {  
}