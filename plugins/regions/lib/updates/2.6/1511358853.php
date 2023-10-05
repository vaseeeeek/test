<?php

$custom_css_path = wa('shop')->getDataPath('plugins/regions/window.css', true, 'shop', false);
if (file_exists($custom_css_path))
{
	return;
}

$model = new waModel();
$select_css_sql = '
SELECT `value`
FROM shop_regions_settings
WHERE `name` = \'window_css\'
';

$current_css = $model->query($select_css_sql)->fetchField();
$default_css = file_get_contents(wa('shop')->getAppPath('plugins/regions/css/window.css', 'shop'));

if ($current_css != $default_css)
{
	waFiles::write($custom_css_path, $current_css);
}



$cleaner = new shopRegionsCleaner();
$cleaner->clean();