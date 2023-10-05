<?php


class shopBuy1clickWaCart extends shopCart implements shopBuy1clickCart
{
	public function getCode()
	{
		return parent::getCode();
	}
	
	public function addItem($item, $services = array())
	{
		unset($item['id']);
		
		if (isset($item['services']))
		{
			$services = $item['services'];
		}
		
		foreach ($services as $i => $service)
		{
			unset($services[$i]['id']);
		}
		
		parent::addItem($item, $services);
	}
	
	public function setQuantity($item_id, $quantity)
	{
		parent::setQuantity($item_id, $quantity);
	}
	
	public function getHierarchyItems()
	{
		return $this->items(true);
	}
	
	public function getItems()
	{
		return $this->items(false);
	}
	
	public function getTotalWithDiscount()
	{
		return $this->total(true);
	}
	
	public function getTotal()
	{
		return $this->total(false);
	}
	
	public function toArray()
	{
		$items = $this->getHierarchyItems();
		$array_cart = array(
			'code' => $this->getCode(),
			'items' => array(),
			'total' => $this->getTotalWithDiscount(),
		);
		
		foreach ($items as $i => $item) {
			$services = array();
			
			foreach (ifset($item['services'], array()) as $j => $service)
			{
				$services[$j] = array(
					'service_id' => $service['service_id'],
					'service_variant_id' => $service['service_variant_id'],
					'name' => $service['name'],
					'service_name' => $service['service_name'],
					'variant_name' => $service['variant_name'],
					'price' => $service['price'],
					'currency' => $service['currency'],
					'price_currency' => shop_currency($service['price'], $service['currency']),
					'price_currency_html' => shop_currency_html($service['price'], $service['currency']),
				);
			}
			
			$full_price = shop_currency($item['price'] * $item['quantity'], $item['currency'], null, false);
			
			if (isset($item['services'])) {
				foreach ($item['services'] as $s) {
					if (!empty($s['id'])) {
						if (isset($s['variants'])) {
							$full_price += shop_currency($s['variants'][$s['variant_id']]['price'] * $item['quantity'], $s['currency'], null, false);
						} else {
							$full_price += shop_currency($s['price'] * $item['quantity'], $s['currency'], null, false);
						}
					}
				}
			}
			
			$array_cart['items'][$i] = array(
				'id' => $item['id'],
				'product_id' => $item['product_id'],
				'name' => $item['product']['name'],
				'sku_id' => $item['sku_id'],
				'sku_name' => $item['sku_name'],
				'quantity' => $item['quantity'],
				'services' => $services,
				'price' => $item['price'],
				'currency' => $item['currency'],
				'price_currency' => shop_currency($item['price'], $item['currency']),
				'price_currency_html' => shop_currency_html($item['price'], $item['currency']),
				'full_price' => $full_price,
				'full_price_currency' => shop_currency($full_price, true, null),
				'full_price_currency_html' => shop_currency_html($full_price, true, null),
			);
		}
		
		$array_cart['total_currency'] = shop_currency($array_cart['total'], true);
		$array_cart['total_currency_html'] = shop_currency_html($array_cart['total'], true);
		
		return $array_cart;
	}
}