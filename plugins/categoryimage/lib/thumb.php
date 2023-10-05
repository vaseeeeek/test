<?php

$path = realpath(dirname(__FILE__)."/../../../../../");
$config_path = $path."/wa-config/SystemConfig.class.php";
if (!file_exists($config_path)) {
    header("Location: ../../../wa-apps/shop/img/image-not-found.png");
    exit;
}

require_once($config_path);
$config = new SystemConfig();
waSystem::getInstance(null, $config);
$app_config = wa('shop')->getConfig();
$request_file = $app_config->getRequestUrl(true, true);
$request_file = preg_replace("@^thumb.php/?@", '', $request_file);
$public_path = wa()->getDataPath('categories/', true, 'shop');

$main_thumb_file = false;
$file = false;
$size = false;
if (preg_match('@(?:\d+/)(\d+)\.(\d+(?:x\d+)?)\.([a-z]{3,4})@i', $request_file, $matches)) {
    $category_id = $matches[1];
    $size = $matches[2];
    $file = $category_id.'/'.$category_id.'.'.$matches[3];

    if ($file) {
        $thumbnail_sizes = explode(';', waSystem::getSetting('sizes', '96', array('shop', 'categoryimage')));
        if (in_array($size, $thumbnail_sizes) === false) {
            $file = false;
        }
    }
}
wa()->getStorage()->close();

$original_path = $public_path.$file;
$thumb_path = $public_path.$request_file;
if ($file && file_exists($original_path) && !file_exists($thumb_path)) {
    $thumbs_dir = dirname($thumb_path);
    if (!file_exists($thumbs_dir)) {
        waFiles::create($thumbs_dir);
    }
    shopImage::generateThumb($original_path, $size, false)->save($thumb_path);
    clearstatcache();
}

if ($file && file_exists($thumb_path)) {
    waFiles::readFile($thumb_path);
} else {
    header("HTTP/1.0 404 Not Found");
    exit;
}
