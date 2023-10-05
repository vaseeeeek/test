<?php

class shopGiftPluginGiftProducts
{
	protected $_max = 10;
	
	public function __construct($max = 0)
	{
		if ( $max )
			$this->_max = $max;
	}
	
	public function getByGiftId($gift_id)
	{
		$products = array();
		$model = new shopGiftPluginProductGiftModel;
		$product_ids = $model->getProductIds($gift_id);
		
		if ( !empty($product_ids) )
		{
			if ( count($product_ids) > $this->_max )
				$product_ids = array_slice($product_ids,0,$this->_max);
			$collection = new shopProductsCollection($product_ids);
			$products = $collection->getProducts('*');
		}
		return $products;
	}
	
	
	public function getByProductId($product_id)
	{
		$products = array();
		$model = new shopGiftPluginProductGiftModel;
		$gift_ids = $model->getGiftIds($product_id);
		return $this->getByGiftId($gift_ids);
	}
}