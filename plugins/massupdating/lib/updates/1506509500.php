<?php

$files = array(
	wa()->getAppPath("plugins/massupdating/lib/actions/shopMassupdatingPluginCross.action.php", "shop"),
	wa()->getAppPath("plugins/massupdating/lib/actions/shopMassupdatingPluginCrossSave.controller.php", "shop"),
	wa()->getAppPath("plugins/massupdating/lib/actions/shopMassupdatingPluginDialog.action.php", "shop"),
	wa()->getAppPath("plugins/massupdating/lib/actions/shopMassupdatingPluginDialogSave.controller.php", "shop"),
	wa()->getAppPath("plugins/massupdating/templates/actions/", "shop"),
);

try {
	foreach($files as $file) {
		if(file_exists($file)) {
			waFiles::delete($file, true);
		}
	}
} catch (Exception $e) {  
}