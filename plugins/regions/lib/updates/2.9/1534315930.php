<?php

$custom_css_path = wa('shop')->getDataPath('plugins/regions/window.css', true, 'shop', false);
if (file_exists($custom_css_path))
{
	$current_css = file_get_contents($custom_css_path);

	if ($current_css)
	{
		$current_css .= '
.shop-regions-window-search__button,
.shop-regions-ip-analyzer__button {
	display: inline-block;
}

.shop-regions-window__wrapper,
.shop-regions-ip-analyzer__wrapper {
	text-align: left;
}
';

		file_put_contents($custom_css_path, $current_css);
	}
}


$cleaner = new shopRegionsCleaner();
$cleaner->clean();