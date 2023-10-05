<?php

$path = wa()->getDataPath('plugins/arrived/config.php', false, 'shop', true);
if (!file_exists($path)) {
	waFiles::copy(dirname(dirname(__FILE__)).'/config/config.php', $path);
} else {
	$settings = include $path;
	$original = include dirname(dirname(__FILE__)).'/config/config.php';
	$original['expiration'] = $settings['expiration'];
	$original['email'] = $settings['email'];
	$original['mail_subject'] = $settings['mail_subject'];
	$original['enable_hook'] = $settings['enable_hook'];
	waUtils::varExportToFile($original, $path);
}