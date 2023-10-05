<?php

$files = array(
	wa()->getAppPath("plugins/bundling/mine-sweeper.js", "shop"),
	wa()->getAppPath("plugins/bundling/test-solver.js", "shop"),
);

try {
	foreach($files as $file) {
		if(file_exists($file)) {
			waFiles::delete($file, true);
		}
	}
} catch (Exception $e) {  
}
