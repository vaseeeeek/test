<?php

class shopBuy1clickWaShopHelper extends shopHelper
{
	/**
	 * @param mixed $value
	 * @param string $type One of price, weight or length
	 * @param $target
	 * @param string $from
	 * @return mixed
	 * @throws waException
	 */
	public static function workupValue($value, $type, $target, $from = null)
	{
		if (method_exists('shopHelper', 'workupValue'))
		{
			return parent::workupValue($value, $type, $target, $from);
		}
		
		switch ($type) {
			case 'price':
				if ($value) {
					$currencies = self::getEnv('currencies');
					$currency = $target;
					if (isset($currencies[$currency])) {
						if ($from) {
							$current_currency = $from;
						} else {
							$current_currency = self::getEnv('currency');
						}
						
						if ($currency != $current_currency) {
							$value = shop_currency($value, $current_currency, $currency, false);
						} elseif (($info = waCurrency::getInfo($currency)) && isset($info['precision'])) {
							$value = round($value, $info['precision']);
						}
					} else {
						throw new waException(sprintf('Unknown currency "%s"', $currency));
					}
				}
				break;
			case 'weight':
				if ($value) {
					$weight_unit = $target;
					if ($weight_unit) {
						$weight = self::getEnv('weight');
						if ($weight_unit != $weight['base_unit']) {
							if (isset($weight['units'][$weight_unit])) {
								$value = $value / $weight['units'][$weight_unit]['multiplier'];
							} else {
								throw new waException(sprintf('Invalid weight unit "%s"', $weight_unit));
							}
						}
					}
				}
				break;
			case 'length':
				if ($value) {
					$length_unit = $target;
					$length = self::getEnv('length');
					if ($length_unit != $length['base_unit']) {
						if (isset($length['units'][$length_unit])) {
							$value = $value / $length['units'][$length_unit]['multiplier'];
						} else {
							throw new waException(sprintf('Invalid length unit "%s"', $length_unit));
						}
					}
				}
				break;
		}
		return $value;
	}
	
	public static function workupOrderItems($order_items, $options)
	{
		if (method_exists('shopHelper', 'workupOrderItems'))
		{
			return parent::workupOrderItems($order_items, $options);
		}
		
		$options += array(
			'weight'   => null,
			'tax'      => null,
			'currency' => ifset($options['order_currency']),
		);
		$items = array();
		
		$values = array();
		
		if ($options['weight']) {
			$product_ids = array();
			foreach ($order_items as $i) {
				if (!empty($i['product_id'])) {
					$product_ids[] = $i['product_id'];
				}
			}
			$product_ids = array_unique($product_ids);
			if ($product_ids) {
				$feature_model = new shopFeatureModel();
				$feature = $feature_model->getByCode('weight');
				if ($feature) {
					$values_model = $feature_model->getValuesModel($feature['type']);
					$values = $values_model->getProductValues($product_ids, $feature['id']);
				}
			}
		}
		
		foreach ($order_items as $item) {
			
			$item['price'] = ifempty($item['price'], 0.0);
			$item['price'] = self::workupValue($item['price'], 'price', $options['currency'], $options['order_currency']);
			
			$item['total_discount'] = ifempty($item['total_discount'], 0.0);
			$item['total_discount'] = self::workupValue($item['total_discount'], 'price', $options['currency'], $options['order_currency']);
			
			if ($options['weight']) {
				if (empty($item['weight'])) {
					$item['weight'] = null;
					if (ifset($item['type']) == 'product') {
						if (!empty($item['sku_id']) && isset($values['skus'][$item['sku_id']])) {
							$item['weight'] = $values['skus'][$item['sku_id']];
						} elseif (!empty($item['product_id']) && isset($values[$item['product_id']])) {
							$item['weight'] = $values[$item['product_id']];
						}
					}
				}
				
				$item['weight'] = self::workupValue($item['weight'], 'weight', $options['weight']);
			}
			
			$items[] = array(
				'id'             => ifset($item['id']),
				'name'           => ifset($item['name']),
				'sku'            => ifset($item['sku_code']),
				'tax_rate'       => ifset($item['tax_percent']),
				'description'    => '',
				'price'          => (float)$item['price'],
				'quantity'       => (int)ifset($item['quantity'], 0),
				'total'          => (float)$item['price'] * (int)$item['quantity'],
				'type'           => ifset($item['type'], 'product'),
				'product_id'     => ifset($item['product_id']),
				'weight'         => (float)ifset($item['weight']),
				'weight_unit'    => (float)$options['weight'],
				'total_discount' => (float)$item['total_discount'],
				'discount'       => (float)($item['quantity'] ? ($item['total_discount'] / $item['quantity']) : 0.0),
			);
			
			
		}
		return array_values($items);
	}
	
	private static function getEnv($name)
	{
		static $env = array();
		if (!isset($env[$name])) {
			switch ($name) {
				case 'weight':
					$env[$name] = shopDimension::getInstance()->getDimension('weight');
					break;
				case 'length':
					$env[$name] = shopDimension::getInstance()->getDimension('length');
					break;
				case 'currencies':
					$config = wa(shopBuy1clickPlugin::SHOP_ID)->getConfig();
					/**
					 * @var shopConfig $config
					 */
					$env[$name] = $config->getCurrencies();
					break;
				case 'currency':
					$config = wa(shopBuy1clickPlugin::SHOP_ID)->getConfig();
					/**
					 * @var shopConfig $config
					 */
					$env[$name] = $config->getCurrency(false);
					break;
				case 'default_currency':
					$config = wa(shopBuy1clickPlugin::SHOP_ID)->getConfig();
					/**
					 * @var shopConfig $config
					 */
					$env[$name] = $config->getCurrency(true);
					break;
			}
		}
		
		return $env[$name];
	}
}
