<?php

return array(
	'shipping' => array(
		'title' => _wp('Shipping'),
		'controls' => array(
			'shipping' => array(
				'title' => _wp('_Shipping'),
				'type' => 'compare[=]:shipping:rates'
			),
		)
	),
	'payment' => array(
		'title' => _wp('Payment'),
		'controls' => array(
			'payment' => array(
				'title' => _wp('_Payment'),
				'type' => 'compare[=;!=]:payment'
			),
		)
	),
	'cart' => array(
		'title' => _wp('Cart'),
		'controls' => array(
			'cart.total_without_discount' => _wp('Order sum'),
			'cart.total' => _wp('Discounted order sum'),
		)
	),
	'product' => array(
		'title' => _wp('Product'),
		'controls' => array(
			'product.type' => array(
				'title' => _wp('Product type'),
				'type' => 'compare[=;!=]:types'
			),
			'product.main_category' => array(
				'title' => _wp('Product main category'),
				'type' => 'compare[=;!=]:categories'
			),
			'product.in_category' => array(
				'title' => _wp('Product placed in category'),
				'type' => 'compare[=;!=]:categories'
			), 
			'product.feature' => array(
				'title' => _wp('Product feature'),
				'type' => 'feature_key:compare[=;!=]:feature_value'
			),
			'product.feature_value' => array(
				'title' => _wp('Product feature value'),
				'type' => 'features:compare:input'
			),
			'product.rating' => array(
				'title' => _wp('Product rating'),
				'type' => 'compare:input'
			),
			'product.skus_count' => array(
				'title' => _wp('Product skus count'),
				'type' => 'compare:input'
			),
			'product.any' => array(
				'title' => _wp('Any product'),
				'type' => 'any'
			),
		)
	),
	'cart.products' => array(
		'title' => _wp('Products in cart'),
		'controls' => array(
			'cart.products.count_of_units' => _wp('Product count of units in cart'),
			'cart.products.count' => _wp('Product count in cart'),
			'cart.products.product_count_of_units' => _wp('Current product count of units in cart'),

			'cart.products.count_compares_with_feature' => array(
				'title' => _wp('Product count compares with feature value'),
				'type' => 'compare[!=;=;>=;<=;>;<;==]:features'
			),
			'cart.products.in_category_count' => array(
				'title' => _wp('Product count from category'),
				'type' => 'categories:compare[!=;=;>=;<=;>;<;==]:input'
			),
			'cart.products.with_type_count' => array(
				'title' => _wp('Product count with type'),
				'type' => 'types:compare[!=;=;>=;<=;>;<;==]:input'
			),
			'cart.products.with_feature_count' => array(
				'title' => _wp('Product count with feature value'),
				'type' => 'features:value:compare[!=;=;>=;<=;>;<;==]:input'
			),
			'cart.products.total_in_category' => array(
				'title' => _wp('Sum of products in category'),
				'type' => 'categories:compare:input'
			),
			'cart.products.total_with_type' => array(
				'title' => _wp('Sum of products with type'),
				'type' => 'types:compare:input'
			),
			/*
			'cart.products.with_feature_count' => array(
				'title' => 'Количество товаров с характеристикой',
				'type' => 'feature_key:feature_value:compare[!=;=;>=;<=;>;<;==]:input'
			),
			
			'cart.products.price_of_any' => 'Цена любого товара',
			'cart.products.price_of_each' => 'Цена каждого товара',
			
			'cart.products.most_in_category' => array(
				'title' => 'Больше всего товаров из категории',
				'type' => 'categories'
			),
			'cart.products.most_with_type' => array(
				'title' => 'Больше всего товаров с типом товара',
				'type' => 'types'
			),
			'cart.products.most_with_feature' => array(
				'title' => 'Больше всего товаров с характеристикой',
				'type' => 'feature_key:feature_value'
			),
			
			'cart.products.sum_of_features' => array(
				'title' => 'Сумма значений характеристик товаров',
				'type' => 'features:compare:input'
			),
			
			'cart.products.total_in_category' => array(
				'title' => 'Общая сумма товаров из категории',
				'type' => 'categories:compare:input'
			),
			 */
		)
	),
	'user' => array(
		'title' => _wp('User'),
		'controls' => array(
			'user.category' => array(
				'title' => _wp('User category'),
				'type' => 'compare[!=;=]:user_categories'
			),
			'user.region' => array(
				'title' => _wp('User region'),
				'type' => 'compare[=;!=]:countries:regions'
			),
			'user.shipping_region' => array(
				'title' => _wp('User region (shipping)'),
				'type' => 'compare[=;!=]:countries:regions'
			),
			'user.billing_region' => array(
				'title' => _wp('User region (billing)'),
				'type' => 'compare[=;!=]:countries:regions'
			),
		)
	),
	'storefront' => array(
		'title' => _wp('Storefront'),
		'controls' => array(
			'storefront' => array(
				'title' => _wp('Current storefront'),
				'type' => 'compare[=;!=]:storefronts'
			),
		)
	),
	'global' => array(
		'title' => _wp('Global'),
		'controls' => array(
			'global.cookie' => array(
				'title' => _wp('$_COOKIE'),
				'type' => 'key:compare[!=;=]:value'
			),
			'global.session' => array(
				'title' => _wp('$_SESSION'),
				'type' => 'key:compare[!=;=]:value'
			),
			'global.server' => array(
				'title' => _wp('$_SERVER'),
				'type' => 'key:compare[!=;=]:value'
			),
			'global.get' => array(
				'title' => _wp('$_GET'),
				'type' => 'key:compare[!=;=]:value'
			),
			'global.post' => array(
				'title' => _wp('$_POST'),
				'type' => 'key:compare[!=;=]:value'
			),
		)
	)
);
