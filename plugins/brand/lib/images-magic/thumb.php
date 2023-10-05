<?php

$LOCATION_NOT_FOUND = "Location: ../../../../../../wa-apps/shop/img/image-not-found.png";

$path = realpath(dirname(__FILE__) . "/../../../../../../");
$config_path = $path . "/wa-config/SystemConfig.class.php";
if (!file_exists($config_path))
{
	header($LOCATION_NOT_FOUND);
	exit;
}

require_once($config_path);
$config = new SystemConfig();
waSystem::getInstance(null, $config);
/** @var shopConfig $shop_config */
$shop_config = wa('shop')->getConfig();

if (array_key_exists('img', $_GET) && is_string($_GET['img']) && trim($_GET['img']) !== '')
{
	$request_file = $_GET['img'];
}
else
{
	$request_file = $shop_config->getRequestUrl(true, true);
	$request_file = preg_replace("@^thumb\.php(/products)?/?@", '', $request_file);
}


// todo понять зачем
wa()->getStorage()->close();



$handler = new shopBrandThumbRequestHandler();
$handler->handle($request_file);
