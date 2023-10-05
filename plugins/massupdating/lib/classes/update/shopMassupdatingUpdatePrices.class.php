<?php

class shopMassupdatingUpdatePrices
{
	public function __construct()
	{
		$this->product_model = new shopProductModel();
		$this->product_skus_model = new shopProductSkusModel();
	}
	
	public function round($value, $precision = 0, $mode = 'up', $minus = false)
	{
		if(!in_array($precision, array('1000', '100', '10', '0', '1', '2')))
			$precision = 0;
		
		if(!in_array($mode, array('up', 'down')))
			$mode = 'up';
		
		if($precision == 0)
			$rounded = $mode == 'up' ? ceil($value) : floor($value);
		elseif(in_array($precision, array('1', '2')))
			$rounded = $mode == 'up' ? (ceil($value * ($precision == 2 ? 100 : 10)) / ($precision == 2 ? 100 : 10)) : (floor($value * ($precision == 2 ? 100 : 10)) / ($precision == 2 ? 100 : 10));
		elseif(in_array($precision, array('1000', '100', '10'))) {
			$rounded = $mode == 'up' ? (ceil($value / $precision) * $precision) : (floor($value / $precision) * $precision);

			if($minus)
				$rounded = $rounded - 1;
		}

		return $rounded;
	}
	
	public function price($from_price, $action, $to, $product, $currency)
	{
		$price = false;
		
		if($currency == '%') {
			if($action == 'fix') {
				return false;
			}
			
			$price = $action == 'minus' ? ($from_price - $from_price * $to / 100) : ($from_price + $from_price * $to / 100);
		} else {
			if($action == 'fix') {
				if($product['currency'] != $currency) {
					$price = shop_currency($to, $currency, $product['currency'], false);
				} else
					$price = $to;
			} else {
				if($product['currency'] != $currency) {
					$current_to = shop_currency($to, $currency, $product['currency'], false);
					$price = $action == 'minus' ? ($from_price - $current_to) : ($from_price + $current_to);
				} else
					$price = $action == 'minus' ? ($from_price - $to) : ($from_price + $to);
			}
		}
		
		return $price;
	}
	
	public function update($params)
	{
		extract($params);
		
		
				if($action == 'compare') $to  = -1;
				
				$currencies = waCurrency::getAll('code');
				$currencies[] = '%';
				$default_currency = $this->plugin->getDefaultCurrency('code');
				
				if(!in_array($currency, $currencies) || empty($action) || !in_array($action, array('plus', 'minus', 'fix', 'compare'))) {
					throw new Exception('Неверные параметры для изменения цен');
				} else {
					if($to == 0 && !$update_empty) {
						throw new Exception('Если вы не хотите изменять цены, а произвести манипуляции только над зачеркнутыми ценами, уберите галочку с "Не обновлять оставленные пустыми поля"');
					}
										
					$update_product = $update_sku = array();
					
					$skus = $this->product_skus_model->getByField('product_id', $id, true);
					foreach($skus as $sku) {
						$sku_id = $sku['id'];
						
						if($action == 'compare') {
							$from_price = $sku['price'];
							$update_sku[$sku_id]['price'] = $sku['compare_price'] > 0 ? $sku['compare_price'] : $sku['price'];
						} else {
							if($from == 'purchase' && $sku['purchase_price'] == 0) {
								throw new Exception('Нельзя изменять цену относительно закупочной цены, когда она не установлена');
								return false;
							}
								
							$from_price = $from == 'purchase' ? $sku['purchase_price'] : $sku['price'];
							$update_sku[$sku_id]['price'] = $this->price($from_price, $action, $to, $product, $currency);

							if($update_sku[$sku_id]['price'] === false) {
								throw new Exception('Фиксированная цена не может быть в процентах');
							}
						}

						if($action != 'compare' && $action != 'fix') {
							$update_sku[$sku_id]['price'] = $this->round($update_sku[$sku_id]['price'], ifset($precision, 0), ifset($mode, 'up'), ifset($minus, false));
						}
						
						$update_sku[$sku_id]['compare_price'] = $compare == 3 ? 0 : ($compare == 2 ? $sku['compare_price'] : $from_price);
						
						$update_sku[$sku_id]['primary_price'] = shop_currency($update_sku[$sku_id]['price'], $product['currency'], $default_currency, false);
						
						if($price_type == 'purchase')
							$this->product_skus_model->updateById($sku_id, array(
								'purchase_price' => $update_sku[$sku_id]['price']
							));
						else
							$this->product_skus_model->updateById($sku_id, $update_sku[$sku_id]);

						if($product['currency'] != $default_currency) { // Для нахождение мин. и макс.
							$update_sku[$sku_id]['price'] = shop_currency($update_sku[$sku_id]['price'], $product['currency'], $default_currency, false);
						}

						if(!empty($update_product['min_price'])) {
							if($update_sku[$sku_id]['price'] < $update_product['min_price'])
								$update_product['min_price'] = $update_sku[$sku_id]['price'];
						} else
							$update_product['min_price'] = $update_sku[$sku_id]['price'];
												
						if(!empty($update_product['max_price'])) {
							if($update_sku[$sku_id]['price'] > $update_product['max_price'])
								$update_product['max_price'] = $update_sku[$sku_id]['price'];
						} else
							$update_product['max_price'] = $update_sku[$sku_id]['price'];
					}
					
					if($action == 'compare') {
						$update_product['price'] = $product['compare_price'] > 0 ? $product['compare_price'] : $product['price'];
						if($product['sku_type'] == 1) {
							$update_product['base_price_selectable'] = $product['compare_price_selectable'];
						}
					} elseif($action == 'fix') {
						$update_product['price'] = $update_product['min_price'];
						if($product['sku_type'] == 1) {
							$update_product['base_price_selectable'] = $update_product['min_price'];
						}
					} else { 
						$update_product['price'] = $from == 'purchase' ? $update_product['min_price'] : $this->price($product['price'], $action, $to, $product, $currency);
						$update_product['price'] = $this->round($update_product['price'], ifset($precision, 0), ifset($mode, 'up'), ifset($minus, false));

						if($product['sku_type'] == 1) {
							$update_product['base_price_selectable'] = $update_product['price'];
						}
					}

					$update_product['compare_price'] = $compare == 3 ? 0 : ($compare == 2 ? $product['compare_price'] : $product['price']);
					if($product['sku_type'] == 1) {
						$update_product['compare_price_selectable'] = $compare == 3 ? 0 : ($compare == 2 ? $product['compare_price_selectable'] : $product['base_price_selectable']);
					}

					if($price_type != 'purchase')
						$this->product_model->updateById($id, $update_product);
				};
	}
}