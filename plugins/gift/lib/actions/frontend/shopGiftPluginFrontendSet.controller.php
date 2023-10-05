<?php

class shopGiftPluginFrontendSetController extends waJsonController
{

	public function execute()
	{
		$data = waRequest::post('data',array());
		$product_id = waRequest::post('product_id',0,waRequest::TYPE_INT);
		if ( !empty($data) && $product_id )
		{
			$storage = wa()->getStorage();
			$cart_gifts = $storage->read('shopGiftPlugin');
			$cart_gifts[$product_id] = $data;
			$storage->write('shopGiftPlugin',$cart_gifts);
		}

		$this->response = '';
	}

}