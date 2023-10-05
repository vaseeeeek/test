<?php

class shopBundlingPluginAdd2cartController extends waJsonController
{
	protected function currencyFormat($val, $currency = true)
	{
		return !empty($this->is_html) ? shop_currency_html($val, $currency) : shop_currency($val, $currency);
	}

	public function execute()
	{
		$this->products = waRequest::post('products', array(), 'array');
		
		$code = waRequest::cookie('shop_cart');
		if (!$code) {
			$code = md5(uniqid(time(), true));
			wa()->getResponse()->addHeader('P3P', 'CP="NOI ADM DEV COM NAV OUR STP"');
			wa()->getResponse()->setCookie('shop_cart', $code, time() + 30 * 86400, null, '', false, true);
		}
		
		$this->cart = new shopCart($code);
		$this->cart_model = new shopCartItemsModel();
		foreach($this->products as $data) {
			$product_id = $data['product_id'];
			$sku_id = $data['sku_id'];
			$quantity = $data['quantity'];
						
			$item = $this->cart_model->getItemByProductAndServices($code, $product_id, $sku_id, array());
			
			$event_data = array(
				'product_id' => $product_id,
				'sku_id' => $sku_id,
				'quantity' => &$quantity,
				'type' => empty($item['id']) ? 'add' : 'set'
			);
			wa('shop')->event('bundling_cart_add', $event_data);
			
			if(empty($item['id'])) {
				$data = array(
					'create_datetime' => date('Y-m-d H:i:s'),
					'product_id' => $product_id,
					'sku_id' => $sku_id,
					'quantity' => $quantity,
					'type' => 'product'
				);
				
				$this->cart->addItem($data);
			} else {
				$this->cart->setQuantity($item['id'], $item['quantity'] + $quantity);
			}
		}
		
		$total = $this->cart->total();
		
		$this->response = array(
			'total' => $this->currencyFormat($total),
			'count' => $this->cart->count()
		);
	}
}