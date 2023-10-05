<?php

class shopComplexPluginTransfer
{
	public static function getPluginPrices($plugin, $id)
	{
		if(waConfig::get('is_template') && wa()->getEnv() == 'frontend')
			return;
		
		$class = 'get' . ucfirst($plugin) . 'PluginPrices';
		return self::$class($id);
	}
	
	public static function getPricePluginPrices($id)
	{
		if(waConfig::get('is_template') && wa()->getEnv() == 'frontend')
			return;
		
		$id = intval($id);
		
		$product_skus_model = new shopProductSkusModel();
		$data = $product_skus_model->query("SELECT * FROM `{$product_skus_model->getTableName()}` WHERE `price_plugin_{$id}` != '0.0000'")->fetchAll();

		return array(
			'price_key' => 'price_plugin_' . $id,
			'type_key' => 'price_plugin_type_' . $id,
			'data' => $data
		);
	}
	
	public static function getOptPluginPrices($id)
	{
		if(waConfig::get('is_template') && wa()->getEnv() == 'frontend')
			return;
		
		$id = intval($id);
		
		$opt_plugin = wa('shop')->getPlugin('opt');
		$opt_plugin_model = new shopOptPricesModel();
		$data = $opt_plugin_model->query("SELECT *, '' AS type FROM {$opt_plugin_model->getTableName()} WHERE user_category_id = ?", $id)->fetchAll();

		return array(
			'price_key' => 'price',
			'type_key' => 'type',
			'data' => $data
		);
	}
	
	public static function getPluginPriceParams($plugin)
	{
		if(waConfig::get('is_template') && wa()->getEnv() == 'frontend')
			return;
		
		$class = 'get' . ucfirst($plugin) . 'PluginPriceParams';
		return self::$class();
	}
	
	public static function getPricePluginPriceParams($only_check = false)
	{
		if(waConfig::get('is_template') && wa()->getEnv() == 'frontend')
			return;
		
		if(class_exists('shopPricePlugin')) {
			$price_plugin = wa('shop')->getPlugin('price');
			$price_plugin_model = new shopPricePluginModel();
			$price_plugin_prices = $price_plugin_model->getAll();
			
			if(!empty($price_plugin_prices)) {
				if($only_check)
					return true;
				else {
					$price_plugin_params_model = new shopPricePluginParamsModel();
					$data = array();

					foreach($price_plugin_prices as $price) {
						$price_params = $price_plugin_params_model->getByField('price_id', $price['id']);
						
						$route = $price_params['route_hash'];
						if($route) {							
							$routes = wa()->getRouting()->getByApp('shop');

							foreach($routes as $site => $site_routes) {
								foreach($site_routes as $id => $params) {
									if(md5($site . '/' . $params['url']) == $route) {
										$route = $site . '/' . $id;
										break;
									};
								}
							}
						} else
							$route = 'any';
						
						$price_data = array(
							'id' => $price['id'],
							'name' => $price['name'],
							'condition_mode' => 'and',
							'conditions' => array(
								array(
									'control' => 'storefront',
									'compare' => '=',
									'storefronts' => $route
								),
								array(
									'control' => 'user.category',
									'compare' => '=',
									'user_categories' => ifempty($price_params['category_id'], 'any')
								)
							)
						);
						
						array_push($data, $price_data);
					}

					return $data;
				}
			} else
				return null;
		} else
			return null;
	}
	
	public static function getOptPluginPriceParams($only_check = false)
	{
		if(waConfig::get('is_template') && wa()->getEnv() == 'frontend')
			return;
		
		if(class_exists('shopOptPlugin')) {
			$opt_plugin = wa('shop')->getPlugin('opt');
			$opt_plugin_settings = $opt_plugin->getSettings();
			
			if(!empty($opt_plugin_settings['settlements']) && !empty($opt_plugin_settings['categories'])) {
				if($only_check)
					return true;
				else {
					$data = array();
					
					$storefronts = $opt_plugin_settings['settlements'];
					$categories = $opt_plugin_settings['categories'];
					
					$storefront_conditions = array();
					$routes = wa()->getRouting()->getByApp('shop');
					
					foreach($storefronts as $storefront) {
						foreach($routes as $site => $site_routes) {
							foreach($site_routes as $id => $params) {
								if($site . '/' . substr($params['url'], 0, -2) == $storefront) {
									$storefront = $site . '/' . $id;
									
									array_push($storefront_conditions, array(
										'control' => 'storefront',
										'compare' => '=',
										'storefronts' => $storefront
									));
									
									break;
								};
							}
						}
					}
					
					$category_model = new waContactCategoryModel();
					foreach($categories as $category_label) {
						$category_id = substr($category_label, 3);
						$category = $category_model->getById($category_id);
						
						if(!$category)
							continue;
												
						$conditions = array(
							array(
								'control' => 'user.category',
								'compare' => '=',
								'user_categories' => intval($category_id)
							)
						);
						
						if(!empty($storefront_conditions))
							$conditions[] = array(
									'control' => 'group',
									'mode' => 'or',
									'conditions' => $storefront_conditions
								);

						
						$name = 'Оптовая цена @' . $category['name'];
						$price_data = array(
							'id' => $category_id,
							'name' => $name,
							'condition_mode' => 'and',
							'conditions' => $conditions
						);
						
						if(!empty($opt_plugin_settings['defaults'][$category_id]['percent'])) {
							$defaults = $opt_plugin_settings['defaults'][$category_id];
							$percent = $defaults['percent'];
							$from = $defaults['price'];
							
							if(in_array($from, array('price', 'purchase_price'))) {
								$price_data['default_style'] = -1;
								$price_data['default_from'] = $from == 'purchase_price' ? -1 : 0;
								$price_data['default_value'] = $percent;
							}
						}
						
						array_push($data, $price_data);
					}

					return $data;
				}
			} else
				return null;
		} else
			return null;
	}
}