<?php

$settings = new shopRegionsSettings();
$settings->window_columns = 3;
$settings->window_search_enable = 1;
$settings->window_group_by_letter_enable = 0;
$settings->window_popular_enable = 0;
$settings->window_header = 'Укажите свой город';
$settings->window_subheader = 'От этого зависит стоимость доставки и варианты оплаты в ваш регион';
$settings->ip_analyzer_enable = 1;
$settings->ip_analyzer_show = 1;
$settings->auto_select_city_enable = 1;
$settings->hide_category_visibility_block = 1;
$settings->window_regions_sidebar_enable = 0;
$settings->window_sort = 'name';
$settings->button_html = '
<div class="shop-regions-button">
  	Ваш регион:
  	<a class="shop-regions__link shop-regions-button__link shop-regions__link_pseudo shop-regions__trigger-show-window">{$region.name}</a>
</div>
';
$settings->meta_title = '{$title}';
$settings->meta_keywords = '{$meta_keywords}';
$settings->meta_description = '{$meta_description}';
$settings->ip_city_confirm_window_header_template = '{$ip_city.name} ваш город?';