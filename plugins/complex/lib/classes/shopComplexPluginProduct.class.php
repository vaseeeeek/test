<?php

class shopComplexPluginProduct
{
	public $processed_products = array();
	public $processed_skus = array();

	private $modified_skus = array();
	private $check_instances = array();
	private $check_results = array();
	private $rules = array();
	private $products = array();
	private $prepared_shop_products = array();
	
	public function __construct($params = array())
	{
		$this->model = new shopComplexPluginPriceModel();
		if(!$this->model->countAll())
			return false;
		
		$this->rule_model = new shopComplexPluginRuleModel();
		
		if(!empty($params['backend']))
			$this->backend = true;
		
		if(!empty($params['settings']))
			$this->settings = $params['settings'];
		
		$this->currency = wa('shop')->getConfig()->getCurrency(true);
		$this->frontend_currency = wa('shop')->getConfig()->getCurrency(false);
		$this->another_currencies = $this->currency != $this->frontend_currency;
	}
	
	public static function getPriceFields($price_id)
	{		
		return array(
			'price_field' => 'complex_plugin_price_' . $price_id,
			'type_field' => 'complex_plugin_type_' . $price_id,
			'from_field' => 'complex_plugin_from_' . $price_id
		);
	}
	
	public function getPriceByPrimary($price, $currency = null)
	{
		if(!$currency)
			$currency = $this->currency;
		
		if($currency != $this->frontend_currency)
			return shop_currency($price, $currency, $this->frontend_currency, null);
		else
			return $price;
	}
	
	public function getPriceTypes()
	{
		if(!isset($this->prices)) {
			$prices = $this->model->getPrices(true);

			$params = array(
				'prices' => &$prices
			);
			wa('shop')->event('complex.get_price_types', $params);

			if(empty($prices)) {
				$prices = array();
			}

			$this->prices = $prices;
		}
		
		return $this->prices;
	}
	
	public function getPriceType($id)
	{
		$prices = $this->getPriceTypes();
		
		return ifset($prices[$id]);
	}
	
	private function getProduct($id)
	{		
		if(!isset($this->products[$id]))
			$this->products[$id] = new shopProduct($id);

		return $this->products[$id];
	}
	
	private function getCurrencyBySku($sku)
	{
		$product_id = $sku['product_id'];
		
		return wao(new shopProductModel())->query("SELECT currency FROM shop_product WHERE id = ?", $product_id)->fetchField('currency');
	}
	
	private function saveProduct($product)
	{
		$product_id = $product['id'];
		
		$this->products[$product_id] = $product;
	}

	/**
	 * @param mixed $product
	 * @return shopComplexPluginCheck
	 */
	private function getCheckInstance($product)
	{
		$product_id = $product['id'];
		
		if(!isset($this->check_instances[$product_id]))
			$this->check_instances[$product_id] = new shopComplexPluginCheck($product);
		
		return $this->check_instances[$product_id];
	}
	
	private function check($id, $product)
	{
		if($product instanceof shopProduct || !empty($product['skus'])) {
			$product_id = $product['id'];
			$this->saveProduct($product);
		} else {
			$product_id = $product;
			$product = $this->getProduct($product_id);
		}
		
		$algorithm = $product['complex_plugin_toggle_prices'];
		
		if(!empty($algorithm) && $algorithm == -1) {
			return false;
		}
				
		if(!isset($this->check_results[$id][$product_id])) {			
			$price = $this->getPriceType($id);
			
			if(!isset($this->rules[$price['rule_id']]))
				$this->rules[$price['rule_id']] = $this->rule_model->getRule($price['rule_id']);
			
			$rule = $this->rules[$price['rule_id']];

			$check_instance = $this->getCheckInstance($product);
			$check = $check_instance->checkConditions($rule['id'], $rule['condition_mode'], $rule['conditions']);

			$params = array(
				'id' => $id,
				'check' => &$check
			);
			wa('shop')->event('complex.check', $params);

			$this->check_results[$id][$product_id] = $check;
		}
		
		return $this->check_results[$id][$product_id];
	}

	private function getPreparedShopProduct($product)
	{
		if (!isset($this->prepared_shop_products[$product['id']])) {
			$p = new shopProduct($product['id']);
			$this->workupProduct($p);

			$this->prepared_shop_products[$product['id']] = $p;
		}

		return $this->prepared_shop_products[$product['id']];
	}

	public function workupProducts(&$products, $cache = true)
	{
		foreach($products as &$product) {
			$this->workupProduct($product);
		}
	}
	
	private function workupProduct(&$product, $check = true, $cache = true)
	{
		if(!isset($product['skus'])) {
			$p = $this->getPreparedShopProduct($product);

			$product['price'] = $p['price'];
			$product['compare_price'] = $p['compare_price'];
			$product['min_price'] = $p['min_price'];
			$product['max_price'] = $p['max_price'];
		} else {
			$sku_id = $product['sku_id'];
			if (!array_key_exists($sku_id, $product['skus'])) {
				return;
			}

			$sku = $product['skus'][$sku_id];
			
			$product_id = $product['id'];

			if(!(isset($this->processed_products[$product_id]) && $cache)) {
				foreach($this->getPriceTypes() as $price) {
					$price_id = $price['id'];
					extract(self::getPriceFields($price_id));
					
					$algorithm = $product['complex_plugin_toggle_prices'];

					if((isset($sku[$price_field]) && floatval($sku[$price_field]) != 0) || !empty($price['default_style'])) {
						if(!$check || (empty($algorithm) && $this->check($price_id, $product)) || $algorithm == $price_id) {
							$last_price = $product['price'];

							$skus = $product['skus'];

							$this->workupSkus($skus);
							$product['skus'] = $skus;
							$sku = $skus[$sku_id];
							
							if(empty($sku['complex_plugin_modified'])) {
								continue;
							}

							// product['price'] in main shop currency ($this->currency)
							$product['price'] = $sku['primary_price'];

							if(!empty($sku['complex_plugin_modify_currency']))
								$product['price'] = shop_currency($product['price'], $sku['complex_plugin_from'], $this->currency, null);

							if(!empty($this->settings['compare'])) {
								$sku_compare_price = isset($sku['complex_plugin_prev_compare_price']) ? (float) $sku['complex_plugin_prev_compare_price'] : (float) $sku['compare_price'];

								if(!empty($sku_compare_price)) {
									$compare_if_compare_style = ifset($this->settings['compare_if_compare_style'], 'no');
									if($compare_if_compare_style == 'compare')
										$set_compare = $last_price;
								} else
									$set_compare = $last_price;
								
								if(isset($set_compare) && ($set_compare > $product['price'] || !$this->settings['compare_only_if_discount']))
									$product['compare_price'] = $set_compare;

							}
							
							$min = $product['price'];
							$max = $product['price'];
							foreach($skus as $sku)
								if($sku['primary_price'] > $max)
									$max = $sku['primary_price'];
								elseif($sku['primary_price'] < $min)
									$min = $sku['primary_price'];
								
							$product['min_price'] = $min;
							$product['max_price'] = $max;
							
							//if($this->another_currencies)
							//	$product['price'] = shop_currency($product['price'], $product['currency'], $this->currency, null);
						}
					}
				}

				$this->processed_products[$product_id] = $product;
			} else {
				$copy_product = $this->processed_products[$product_id];

				$product['price'] = $copy_product['price'];
				$product['compare_price'] = $copy_product['compare_price'];
				$product['min_price'] = $copy_product['min_price'];
				$product['max_price'] = $copy_product['max_price'];
			}
		}
	}
	
	public function workupSkus(&$skus, $cache = true)
	{
		foreach($skus as &$sku) {
			$this->workupSku($sku, null, true, null, $cache);
		}
	}
	
	public function getSkuPrice($sku, $type = 'price', $product = null, $price_id = null)
	{
		$this->workupSku($sku, $product, false, $price_id);

		if(!empty($sku['complex_plugin_modified']))
			return $sku[$type];
		else
			return null;
	}
	
	private function getCurrencies($a, $b, $sku, &$from, &$to, $skip = false)
	{
		$sku_currency = $this->getCurrencyBySku($sku);

		if($this->frontend_currency === $this->currency && $this->currency === $sku_currency) {
			$from = $this->currency;
			$to = $this->currency;

			return true;
		}
		
		if($this->checkCurrency($a, $b, $this->currency, $this->frontend_currency)) {
			$from = $this->currency;
			$to = $this->frontend_currency;
			
			return true;
		} elseif($this->checkCurrency($a, $b, $this->currency, $sku_currency, false)) {
			$from = $this->currency;
			$to = $sku_currency;
			
			return true;
		} elseif($this->checkCurrency($a, $b, $this->frontend_currency, $sku_currency, false)) {
			$from = $this->frontend_currency;
			$to = $sku_currency;
			
			return true;
		} elseif(!$skip) {
			// Ничего не вышло. Определяем по округленным значениям
			$i = 4;
			$error = true;

			do {
				$a_rounded = round($a, $i);
				$b_rounded = round($b, $i);
				
				if($this->getCurrencies($a_rounded, $b_rounded, $sku, $from, $to, true)) {
					$error = false;
					break;
				}
				
				$i--;
			} while($i > 0);
			
			if($error) {
				waLog::log(_wp('Could not determine required currency conversions'), 'wa-apps/shop/plugins/complex/currency.log');
				waLog::dump(array(
					'sku_id' => $sku['id'],
					'sku_currency' => $sku_currency,
					'config_currency' => $this->currency,
					'frontend_currency' => $this->frontend_currency
				), 'wa-apps/shop/plugins/complex/currency.log');
				
				return false;
			}
			
			return true;
		} else
			return false;
	}
	
	private function checkCurrency($a, $b, $in, $out)
	{
		return shop_currency($a, $in, $out, false) == $b;
	}
	
	private function workupSku(&$sku, $product = null, $check = true, $given_price_id = null, $cache = true)
	{
		$sku_id = $sku['id'];
		
		if(!is_null($product) && $product instanceof shopProduct)
			$this->saveProduct($product);
		
		if($check) {
			if(!empty($sku['complex_plugin_modified']))
				return false;
		}

		if(!(isset($this->processed_skus[$sku_id]) && $cache)) {
			foreach($this->getPriceTypes() as $price) {
				$price_id = $price['id'];

				extract(self::getPriceFields($price_id));
				if(is_null($product))
					$product = $this->getProduct($sku['product_id']);
				
				$algorithm = $product['complex_plugin_toggle_prices'];

				if((isset($sku[$price_field]) && floatval($sku[$price_field]) != 0) || !empty($price['default_style'])) {
					if((!$check && $given_price_id == $price_id) || ($check && ((empty($algorithm) && $this->check($price_id, $product)) || $algorithm == $price_id))) {
						if(!isset($sku['complex_plugin_last_primary_price']))
							$sku['complex_plugin_last_primary_price'] = $sku['primary_price'];
						else
							$sku['primary_price'] = $sku['complex_plugin_last_primary_price'];
						if(!isset($sku['complex_plugin_last_price']))
							$sku['complex_plugin_last_price'] = $sku['price'];
						else
							$sku['price'] = $sku['complex_plugin_last_price'];
						if(!isset($sku['complex_plugin_purchase_price']))
							$sku['complex_plugin_purchase_price'] = $sku['purchase_price'];
						else
							$sku['purchase_price'] = $sku['complex_plugin_purchase_price'];
						
						$last_primary_price = $sku['primary_price'];
						$last_price = $sku['price'];

						if($last_price != $last_primary_price) {
							$convert = true;
				
							$a = $last_primary_price;
							$b = $last_price;
							
							if(!$this->getCurrencies($a, $b, $sku, $from, $to))
								return false;
						} else
							$convert = false;
						
						$calculated_price = $this->calculatePrice($price_id, $sku, !$convert, $price);

						if($calculated_price === false) {
							continue;
						}
						
						if(is_array($calculated_price)) {
							$convert = true;
							$from = $calculated_price['from'];
							if(!isset($to))
								$to = $calculated_price['to'];
							$calculated_price = $calculated_price['price'];
							
							$sku['complex_plugin_modify_currency'] = true;
							$sku['complex_plugin_from'] = $from;
							$sku['complex_plugin_to'] = $to;
						}
							
						$sku['primary_price'] = $calculated_price;

						if($convert) {
							$sku['price'] = shop_currency($sku['primary_price'], $from, $to, null);
						} else
							$sku['price'] = $sku['primary_price'];

						$sku['complex_plugin_modified'] = true;
						$sku['complex_plugin_modified_sort'] = intval($price['sort']);
						$this->modified_skus[$sku_id] = $price_id;

						if(!empty($this->settings['compare'])) {
							if(!empty($sku['compare_price'])) {
								$compare_if_compare_style = ifset($this->settings['compare_if_compare_style'], 'no');
								if($compare_if_compare_style == 'compare')
									$set_compare = $last_price;
							} else
								$set_compare = $last_price;

							if(isset($set_compare) && ($set_compare > $sku['price'] || !$this->settings['compare_only_if_discount'])) {
								$sku['complex_plugin_prev_compare_price'] = $sku['compare_price'];
								$sku['compare_price'] = $set_compare;
							}
						}
					}
				}
			}

			$this->processed_skus[$sku_id] = $sku;
		} else {
			$sku = $this->processed_skus[$sku_id];
		}
	}
	
	public static function roundPrice($id, $final_price, $price = null)
	{
		$id = intval($id);
		
		if(!$price) {
			$price_model = new shopComplexPluginPriceModel();
			$price = $price_model->getById($id);
		}

		switch($price['rounding']) {
			case 0:
				return $final_price;
				break;
			default:
				return shopRounding::round($final_price, $price['rounding'], false);
				break;
		}
	}
	
	private function calculatePrice($id, $sku, $round = true, $price = null, $convert = true)
	{
		extract(self::getPriceFields($id));
		$complex_price = $sku[$price_field];
		$type = $sku[$type_field];
		$from = $sku[$from_field];
		
		if(is_null($price))
			$price = $this->getPriceType($id);
		
		if(floatval($complex_price) == 0 && !empty($price['default_style']) && !empty($price['default_value'])) {
			$complex_price = $price['default_value'];
			
			switch($price['default_style']) {
				case 1:
					$type = '%';
					break;
				case -1:
					$type = '-%';
					break;
			}
			
			$from = $price['default_from'];
		}
		
		if(in_array($type, array('+', '-', '')) && $from != -1) {
			$currency = $this->getCurrencyBySku($sku);

			if($currency != $this->currency)
				$complex_price = shop_currency($complex_price, $currency, $this->currency, null);
		}
		
		switch($from) {
			/* case 1:
				$original = floatval($sku['compare_price']);
				break; */
			case -1:
				$original = floatval($sku['purchase_price']);
				break;
			default:
				$original = floatval($sku['primary_price']);
				break;
		}
		
		/* if($from) {
			if(!isset($currency))
				$currency = $this->getCurrencyBySku($sku);
			
			$original = floatval(shop_currency($original, $currency, $this->currency, null));
		} */

		if($original == 0 && $type != '')
			return false;
		
		$final_price = self::getPrice($original, $complex_price, $type);
		
		if($round) {
			$final_price = self::roundPrice($id, $final_price, $price);
		}
		
		if($from == -1) {
			return array(
				'from' => $this->getCurrencyBySku($sku),
				'to' => $this->frontend_currency,
				'price' => $final_price
			);
		} else
			return $final_price;
	}
	
	public static function skuPrice($id, $sku, $round = true, $price = null, $convert = true)
	{		
		extract(self::getPriceFields($id));
		$complex_price = $sku[$price_field];
		$type = $sku[$type_field];
		$from = $sku[$from_field];

		switch($from) {
			case 1:
				$original = $sku['compare_price'];
				break;
			case -1:
				$original = $sku['purchase_price'];
				break;
			default:
				$original = $sku['primary_price'];
				break;
		}

		$final_price = self::getPrice($original, $complex_price, $type, $convert);
		
		if($round) {
			$final_price = self::roundPrice($id, $final_price, $price);
		}
		
		return $final_price;
	}
	
	public static function productPrice($id, $product, $sku, $price = null)
	{
		$sku_price = self::skuPrice($id, $sku, false, $price);
		
		return $sku_price;
	}
	
	public static function getPrice($original, $price, $type)
	{
		$original = floatval($original);
		$price = floatval($price);

		switch($type) {
			case '':
				$final_price = $price;
				break;
			case '%':
				$original = floatval($original);
				$final_price = $original + ($original * $price / 100);
				break;
			case '-%':
				$original = floatval($original);
				$final_price = $original - ($original * $price / 100);
				break;
			case '-':
				$original = floatval($original);
				$final_price = $original - $price;
				break;
			case '+':
				$original = floatval($original);
				$final_price = $original + $price;
				break;
		}
		
		return $final_price;
	}
}
