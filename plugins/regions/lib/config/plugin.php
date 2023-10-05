<?php

return array(
	'name' => 'SEO-регионы',
	'description' => 'Плагин для масштабирования вашего бизнеса',
	'img' => 'img/regions.png',
	'vendor' => '934303',
	'frontend' => true,
	'shop_settings' => true,
	'version' => '2.21.0',
	'handlers' => array(
		'seo_fetch_templates' => 'seoFetchTemplatesHandler',
		'seo_fetch_template_helper' => 'seoFetchTemplateHelperHandler',

		'seofilter_fetch_templates' => 'seoFetchTemplatesHandler',
		'seofilter_fetch_template_helper' => 'seoFetchTemplateHelperHandler',

		'frontend_head' => 'frontendHeadHandler',
		'cart_add' => 'cartAddHandler',
		'checkout_before_region' => 'handleCheckoutBeforeRegion',

		'backend_menu' => 'backendMenuHandler',
		'backend_products' => 'backendProductsHandler',
		'backend_product' => 'backendProductsHandler',

		'rights.config' => 'rightsConfigHandler',

		'sitemap' => 'sitemapHandler',
		'routing' => 'routing',

		'app_sitemap_structure' => 'handleAppSitemapStructure',
	)
);