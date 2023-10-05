<?php

class shopBuy1clickWaTempCart extends shopBuy1clickWaCart implements shopBuy1clickTempCart
{
	public function __construct($code = '')
	{
		parent::__construct('any');
		
		if ($code == '')
		{
			do
			{
				$code = md5('buy1click_' . $this->code . '_' . microtime(true));
				$is_exists = $this->_getModel()->countByField('code', $code) != 0;
			}
			while ($is_exists);
		}
		
		$this->code = $code;

		$this->removeContactFromCartItemRows($code);
	}
	
	public function addItem($item, $services = array())
	{
		if (isset($item['services']))
		{
			$services = $item['services'];
		}
		
		$cart_item = $this->_getModel()->getItemByProductAndServices($this->code, $item['product_id'], $item['sku_id'], $services);
		
		if ($cart_item)
		{
			$item_id = $cart_item['id'];
			$this->setQuantity($item_id, $cart_item['quantity'] + $item['quantity']);
		}
		else
		{
			parent::addItem($item, $services);
		}
	}
	
	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	protected function getSessionData($key, $default = null)
	{
		$data = wa()->getStorage()->get("shop/buy1click/cart/{$this->code}");
		
		return isset($data[$key]) ? $data[$key] : $default;
	}
	
	/**
	 * @param string $key
	 * @param mixed $value
	 */
	protected function setSessionData($key, $value)
	{
		$data = wa()->getStorage()->get("shop/buy1click/cart/{$this->code}");
		$data[$key] = $value;
		wa()->getStorage()->set("shop/buy1click/cart/{$this->code}", $data);
	}
	
	public function clearSessionData()
	{
		wa()->getStorage()->remove("shop/buy1click/cart/{$this->code}");
	}
	
	public function clear()
	{
		$this->_getModel()->deleteByField('code', $this->code);
		$this->clearSessionData();
	}
	
	private function _getModel()
	{
		if (method_exists($this, 'model'))
		{
			return $this->model();
		}
		else
		{
			return $this->model;
		}
	}

	private function removeContactFromCartItemRows($code)
	{
		$this->_getModel()->updateByField(array(
			'code' => $code,
		), array(
			'contact_id' => null,
		));
	}
}