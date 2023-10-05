<?php

class shopGiftPluginFrontendEmptyController extends waJsonController
{

	public function execute()
	{
		$product_id = waRequest::post('product_id', 0, waRequest::TYPE_INT);
		if ($product_id) {
			$storage = wa()->getStorage();
			$storage->remove('shopGiftPlugin');
		}

		$this->response = '';
	}

}