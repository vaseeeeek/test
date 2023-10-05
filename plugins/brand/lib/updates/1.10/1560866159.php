<?php

$server = '';

if (array_key_exists('SERVER_SOFTWARE', $_SERVER))
{
	$server = $_SERVER['SERVER_SOFTWARE'];
}
elseif (array_key_exists('SERVER_SIGNATURE', $_SERVER))
{
	$server = $_SERVER['SERVER_SIGNATURE'];
}

if (!is_string($server) || trim($server) === '')
{
	return false;
}

$server = strtolower(trim($server));

$server_is_nginx = strpos($server, 'nginx') !== false;

if (!$server_is_nginx && waSystemConfig::systemOption('mod_rewrite'))
{
	$model = new waModel();

	$model->exec("
INSERT INTO
shop_brand_settings
(storefront, name, setting) VALUES ('*', 'use_optimized_images', '1')
ON DUPLICATE KEY UPDATE setting = setting
");
}
