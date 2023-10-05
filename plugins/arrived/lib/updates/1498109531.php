<?php

$path = wa()->getDataPath('plugins/arrived/config.php', false, 'shop', true);
if (!file_exists($path)) {
	waFiles::copy(dirname(dirname(__FILE__)).'/config/config.php', $path);
} else {
	$settings = include $path;
	$settings['terms_url'] = "";
	waUtils::varExportToFile($settings, $path);
}