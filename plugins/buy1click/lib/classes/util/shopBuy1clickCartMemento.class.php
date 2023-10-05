<?php


class shopBuy1clickCartMemento
{
	private $original_code;
	private $original_state;
	
	public function replaceTo($code)
	{
		$this->original_state = wa()->getStorage()->get('shop/cart');
		wa()->getStorage()->del('shop/cart');
		$this->original_code = waRequest::cookie('shop_cart');
		$_COOKIE['shop_cart'] = $code;
	}
	
	public function rollback()
	{
		$_COOKIE['shop_cart'] = $this->original_code;
		wa()->getStorage()->set('shop/cart', $this->original_state);
	}
}