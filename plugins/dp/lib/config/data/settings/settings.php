<?php

/**
 * Настройки плагина по умолчанию
 */
$config = new shopDpServiceConfig();

$settings = array(
	'basic' => array(
		'status' => '1',
		'map_service' => 'yandex',
		'map_params' => array()
	),
	'storefronts' => array(
		'*' => array(
			'cache_calculate' => '259200',
			'date_format' => 'human',
			'date_range_format' => 'range',
			'asset_mode' => 'async',
			'weight' => '1',
			'cost' => '1000',

			'page_cost_mode' => 'inline',
			'page_estimated_date_mode' => 'inline',

			'page_group_status' => '0',
			'page_group_params' => array(),

			'product_status' => '0',
			'product_hook' => 'block_aux',
			'product_calculate_mode' => 'product',

			'product_group_status' => '0',
			'product_group_params' => array(),
			'product_hide_if_no_services' => '0',

			'ip_status' => '1',
			'user_region_status' => '1',
			'ip_regions_plugin_status' => '0',
			'country' => 'rus',
			'region' => '77',
			'city' => 'Москва',

			'shipping_sort' => array(),
			'shipping_status' => array(),
			'shipping_title' => array(),
			'shipping_description' => array(),
			'shipping_pay_on_ship' => array(),
			'shipping_service' => array(),
			'shipping_group' => array(),
			'shipping_actuality' => array(),
			'shipping_placemark_image' => array(),
			'shipping_placemark_color' => array(),
			'shipping_settings' => array(),
			'shipping_payment' => array(),
			'shipping_region_availability' => array(),
			'shipping_image' => array(),

			'shipping_date' => array(),
			'shipping_cost' => array(),
			'shipping_fields' => array(),
			'shipping_async' => array(),
			'shipping_schedule' => array(),
			'shipping_calculation_product' => array(),

			'payment_title' => array(),
			'payment_image' => array(),
			'payment_sort' => array(),

			'product_cost_mode' => 'product',
			'product_estimated_date_mode' => 'inline',
		)
	),

	'themes' => array(
		'*' => array(
			'design_page_city_select_status' => '1',
			'design_page_show_column_headers' => '1',
			'design_page_name_col' => '1',
			'design_page_date_col' => '1',
			'design_page_cost_col' => '1',
			'design_page_payment_col' => '1',
			'design_page_payment_style' => 'icon',
			'design_page_general_color' => '#333333',
			'design_page_link_color' => '#f2994a',
			'design_page_loading_color' => '#f2994a',
			'design_page_delimiter_color' => '#f2f2f2',
			'design_page_gray_color' => '#828282',
			'design_page_free_color' => '#eb001b',
			'design_page_not_available_payment_color' => '#eb001b',

			'design_points_filter_payment' => '1',
			'design_points_filter_work' => '1',
			'design_points_filter_search' => '1',
			'design_points_custom_filter' => '0',
			'design_points_custom_filter_unchecked_color' => '#828282',
			'design_points_custom_filter_checked_color' => '#f2994a',
			'design_points_sort' => '0',
			'design_points_group' => '1',
			'design_points_group_title' => 'Пункты выдачи',
			'design_points_group_switch_mode' => 'header',
			'design_points_general_color' => '#333333',
			'design_points_link_color' => '#f2994a',
			'design_points_search_border_normal_color' => '#e0e0e0',
			'design_points_search_border_focus_color' => '#f2994a',
			'design_points_filtering_button_color' => '#f2994a',

			'design_product_header' => "<div align=\"center\">Информация о доставке\n{\$city_select}</div>",
			'design_product_break_services_status' => '0',
			'design_product_group_style' => 'normal',
			'design_product_background_color' => '#ffffff',
			'design_product_border_color' => '#e0e0e0',
			'design_product_general_color' => '#333333',
			'design_product_link_color' => '#f2994a',
			'design_product_loading_color' => '#f2994a',
			'design_product_gray_color' => '#828282',
			'design_product_delimiter_color' => '#e0e0e0',
			'design_product_tab_color' => '#bdbdbd',
			'design_product_tab_active_color' => '#333333',
			'design_product_tab_border_color' => '#f2994a',

			'design_zones_title' => 'Зоны доставки ({$location.city})',
			'design_zones_error_placemark_status' => '1',
			'design_zones_error_placemark_image' => 'source:/icons/store.svg',

			'design_city_select_title' => 'Укажите свой город',
			'design_city_select_general_color' => '#333333',
			'design_city_select_link_color' => '#f2994a',
			'design_city_select_gray_color' => '#828282',
			'design_city_select_border_color' => '#e0e0e0',
			'design_city_select_border_focus_color' => '#f2994a',

			'design_point_services_params' => array()
		)
	)
);

foreach($config->config as $service => $service_params) {
	if($service_params['type'] == 'points') {
		if(empty($settings['themes']['*']['design_point_services_params'][$service])) {
			$settings['themes']['*']['design_point_services_params'][$service] = array(
				'name' => $service_params['name'],
				'image' => ifempty($service_params, 'image', null),
				'placemark' => ifempty($service_params, 'placemark', null),
				'color' => ifempty($service_params, 'color', null),
				'agregator' => ifempty($service_params, 'agregator_services_params', false)
			);
		}
	}
}

return $settings;
