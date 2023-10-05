<?php

class shopMassupdatingUpdateCurrencies
{
	public function __construct()
	{
		$this->product_model = new shopProductModel();
		$this->product_skus_model = new shopProductSkusModel();
		
		$this->currency_model = new shopCurrencyModel();
		$this->currencies = $this->currency_model->getCurrencies();
	}
	
	public function update($id, $product, $currency, $update_prices_by_rate)
	{
		$default_currency = $this->plugin->getDefaultCurrency('code');
		
		if(!empty($this->currencies[$currency])) {
			if($currency == $product['currency'])
				return true;
			else {
				$skus = $this->product_skus_model->getByField('product_id', $id, true);
				$update_product = $update_sku = array();
				
				$update_product['currency'] = $currency;
				
				foreach($skus as $sku) {
					$sku_id = $sku['id'];
					
					if($update_prices_by_rate) {
						foreach(array('price', 'purchase_price', 'compare_price') as $type) {
							if(!empty($sku[$type]))
								$update_sku[$sku_id][$type] = shop_currency($sku[$type], $product['currency'], $currency, false);
						}
					} else {
						$update_sku[$sku_id]['primary_price'] = shop_currency($sku['price'], $currency, $default_currency, false);
					}
					
					$this->product_skus_model->updateById($sku_id, $update_sku[$sku_id]);
				}
				
				if(!$update_prices_by_rate) {
					foreach(array('price', 'compare_price', 'min_price', 'max_price') as $type) {
						if(!empty($product[$type]))
							$update_product[$type] = shop_currency($product[$type], $default_currency, $product['currency'], false);
					}
				}
				
				$this->product_model->updateById($id, $update_product);
			}
		} else {
			throw new Exception('Выбранная валюта не найдена!');
		}
	}
}