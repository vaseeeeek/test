<?php

$model = new waModel();

$sql = "
SELECT `value`
FROM shop_regions_settings
WHERE `name` = 'window_css' 
";

$css = $model->query($sql)->fetchField();

$css .= '
.shop-regions-window.searching .with_regions .shop-regions-window_cities_list {
	margin-left: 0;
}
';

$pattern = '\.shop-regions-window\.searching \.shop-region-window_regions_sidebar\s*\{[^{}]*?\}';
$style = '
.shop-regions-window.searching .shop-region-window_regions_sidebar {
	display: none;
}
';

$css = preg_replace('/' . $pattern . '/sm', $style, $css);


$update_sql = '
UPDATE shop_regions_settings
SET `value` = :value
WHERE `name` = \'window_css\'
';


$model->exec($update_sql, array('value' => $css));