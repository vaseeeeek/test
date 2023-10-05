<?php

$files = array(
	wa()->getAppPath("plugins/bundling/lib/models/shopBundlingProduct.model.php", "shop"),
);

try {
	foreach($files as $file) {
		if(file_exists($file)) {
			waFiles::delete($file, true);
		}
	}
} catch (Exception $e) {  
}