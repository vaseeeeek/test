<?php

$files = array(
	wa()->getAppPath("plugins/bundling/lib/vendors/shevskySettingsControlsV6/", "shop"),
	wa()->getAppPath("plugins/bundling/js/shevskySettingsControlsV6.js", "shop"),
);

try {
	foreach($files as $file) {
		if(file_exists($file)) {
			waFiles::delete($file, true);
		}
	}
} catch (Exception $e) {  
}