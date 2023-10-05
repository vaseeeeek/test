<?php

try
{
	$_settings = new shopRegionsSettingsLegacy();
}
catch (waException $exception)
{
	return;
}

$settings = $_settings->get();

if (isset($settings['window_css_backup']))
{
	return;
}

/*** START css update data ***/
$css_add = '';
$css_patch = array();


$css_add = '
.shop-regions-window .shop-regions-window__region_region span {
	display: block;
	cursor: pointer;
}

.shop-regions-window .shop-regions-window__region_region span:hover {
	color: #ff0000;
}

.shop-regions-window .hidden {
	display: none;
}

.shop-regions-window .selected {
	font-weight: 700;
}

.shop-regions-window .shop-region-window_regions_sidebar, .shop-regions-window .with_regions .shop-regions-window_cities_list {
	max-height: 300px;
	overflow-y: auto;
	overflow-x: hidden;
}

.shop-regions-window .shop-region-window_regions_sidebar {
	width: 40%;
	float: left;
}

.shop-regions-window.searching .shop-region-window_regions_sidebar {
	opacity: .7;
}

.shop-regions-window .with_regions .shop-regions-window_cities_list {
	margin-left: 42%;
}

.shop-regions-window .shop-regions-window_cities_list .sub_header, .shop-regions-window .shop-region-window_regions_sidebar .sub_header {
	margin-bottom: 10px;
	font-weight:700;
	margin-left: 15px;
}

.shop-regions-window .shop-region-window_regions_and_cities:after {
	content: \'\';
	display: block;
	clear: both;
	height: 0;
}


.shop-regions-window .shop-region-window_regions_and_cities.with_regions .shop-regions-window__regions {
	margin-top: 0;
	position: relative;
}
.without_regions .shop-regions-window__regions.search_result {
	margin-left: 15px;
}


.shop-regions-window__region_group .shop-regions-window__regions-letter {
	position:absolute;
}

.shop-regions-window__region:after {
	content: \'\';
	display: block;
	clear: both;
}
.shop-regions-window.searching .js-shop-regions-window__region_group .shop-regions-window__region {
	padding-left: 0;

}
.shop-regions-window.searching .with_regions .search_result .shop-regions-window__region {
	display: inline-block;
	width: 40%;
	margin-right: 1%;
	vertical-align: top;
}

.shop-regions-window.searching .js-shop-regions-window__region_group .shop-regions-window__regions-letter {
	display: none;
}

.shop-regions-window-search {
	display:table;
	position:relative;
	min-width: 240px;
	width: 63%;
}

.shop-regions-button .shop-regions__link {
	cursor: pointer;
	border-bottom: 1px dashed;
	text-decoration: none;
}
.shop-regions-button .shop-regions__link:hover {
	border-color: transparent;
	text-decoration:none;
}


.js-shop-region-window_regions_and_cities.without_regions {
	max-height: 300px;
	overflow-y: auto;
}

.with_regions .js-shop-region-window_search .visible .shop-regions__trigger-switch-city {
	display: inherit;
}

.search_result .region_header {
	display: block;
}

.region_header {
	font-size: 1.3em;
	margin-top: 15px;
	margin-bottom: 14px;
	display: none;
	padding-left: 12px;
}

.shop-regions-window__triggers {
	overflow: hidden;
	padding-left: 15px;
}

.shop-regions-window__search .shop-regions-window__sub-header {
	margin-bottom: 2px;
}

.shop-regions-window__search .shop-regions-window__sub-subheader {
	opacity: 0.75;
	margin-bottom: 8px;
	font-size: 0.95em;
}


.shop-regions-window__region {
	position: relative;
	padding-right: 25px;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;
	box-sizing: border-box;
	margin-bottom: 0.8em;
	padding-right:0;
	/*padding-left:20px;*/
}

.shop-regions-window .with_regions .column {
	float: left;
	width: 50%;
}

.shop-regions-window .with_regions .shop-regions-window__region {
	margin-left: 15px;
}


.shop-regions-window__triggers a {
	text-decoration: none;
	border-bottom: 1px dashed;
	display: inline-block;
}

.shop-regions-window__triggers a:hover {
	text-decoration: none;
	border-bottom-color: transparent;
}
.shop-regions-window .selected_region {
	font-weight: 700;
}

.shop-regions-window .no_found_message {
	display: none;
}

.shop-regions-window .clear_search {
	position: absolute;
	cursor: pointer;
	top: 0;
	bottom: 0;
	right: 12px;
	margin: auto;
	height: 51%;
	display: none;
}
.shop-regions-window.searching .clear_search {
	display: block;
}


.shop-regions-window .shop-regions-window__region_region {
	 display: block;
	 padding: 5px 10px 5px 12px;
	 margin-left: 3px;
	 margin-right: 3px;
 }

.shop-regions-window .shop-regions-window__region_region.selected_region {
	background: #e1dcd3;
	border-top: 1px solid #c8c4bc;
	border-bottom: 1px solid #fff;
	border-radius: 4px;
	margin-top: -1px;
	margin-bottom: -1px;
}


.popular_for_region {
	display: none;
}
.popular_for_region.visible {
	display: block;
}
.popular_city_wrap {
	display: inline;
	margin-right: 7px;
}
.shop-region-window_regions_and_cities .popular_cities {
	font-size: 0.95em;
}

.shop-regions-window .shop-regions-window__region_region.selected_region span {
	background: none !important;
}

@media all and ( max-width: 750px ) {
	.shop-regions-window__wrapper {
		width: 90%;
		padding: 20px 35px;
		left: 0;
		right: 0;
		margin: auto;
		-moz-box-sizing: border-box;
		-webkit-box-sizing: border-box;
		box-sizing: border-box;
	}

	.shop-regions__button-close {
		top: 20px;
		right: 35px;
	}
}

@media all and ( max-width: 600px ) {
	.shop-regions-window__regions-column {
		width: 33% !important;
	}
}

@media all and ( max-width: 520px ) {
	.shop-regions-window__regions-column {
		width: 50% !important;
	}
}

@media all and ( max-width: 400px ) {
	.shop-regions-window__regions-column {
		width: auto !important;
		float: none;
	}

	.shop-regions-window-search {
		width: 100%;
		min-width: 100%;
	}
}
';

$css_patch['.shop-regions__button-close'] = array(
	'right' => '35px',
);
$css_patch['.shop-regions-window__wrapper,
.shop-regions-ip-analyzer__wrapper'] = array(
	'padding' => '30px 40px',
);
$css_patch['.shop-regions-window__wrapper'] = array(
	'top' => '10%',
	'margin-left' => '-370px',
	'width' => '620px',
	'font-family' => 'sans-serif',
	'text-align' => 'left',
);
$css_patch['.shop-regions-window__sub-header'] = array(
	'margin-bottom' => '1.2em',
);
$css_patch['.shop-regions-window__search,
.shop-regions-window__regions_popular'] = array(
	'margin-bottom' => '18px',
	'padding-left' =>  '15px',
);
$css_patch['.shop-regions-window__regions_all'] = array(
	'padding-left' => '15px',
);
$css_patch['.shop-regions-window__regions-column'] = array(
	'position' => 'relative',
);
$css_patch['.shop-regions-window__regions-letter'] = array(
	'margin-left' => '0',
	'width' => '0',
	'position' => 'relative',
	'left' => '-15px',
);
$css_patch['.shop-regions-window-search__input'] = array(
	'padding' => '5px 15px 5px 5px',
	'height' => '30px',
	'line-height' => '30px',
	'-moz-box-sizing' => 'border-box',
	'-webkit-box-sizing' => 'border-box',
	'box-sizing' => 'border-box',
	'width' => '100%',
);
$css_patch['.shop-regions__link'] = array(
	'text-decoration' => 'underline',
);
$css_patch['.shop-regions-window_popular.shop-regions-window_show-all-regions .shop-regions-window__regions_all'] = array(
	'display' => 'block',
);


/*** END css update data ***/

$window_css_update = $settings['window_css'];

foreach ($css_patch as $selector => $values)
{
	$block_pattern = '/' . preg_quote($selector) . '\s*\{[^{}]*?\}/sm';
	if (preg_match($block_pattern, $window_css_update, $matches))
	{
		$css_block = $css_block_patched = $matches[0];

		foreach ($values as $parameter => $value)
		{
			$param_pattern = '/((;|\})?\s*)' . preg_quote($parameter) . '\s*:[^;]+;/sm';
			$css_block_patched = preg_replace($param_pattern, "\$1{$parameter}: {$value};", $css_block_patched, 1, $count);

			if ($count === 0)
			{
				$css_block_patched = preg_replace('/(\s*)\}$/s', "\$1\t{$parameter}: {$value};\$1}", $css_block_patched);
			}
		}
		$window_css_update = str_replace($css_block, $css_block_patched, $window_css_update);
	}
}

$window_css_update .= $css_add;

$settings['window_css_backup'] = $settings['window_css'];
$settings['window_css'] = $window_css_update;
$_settings->update($settings);