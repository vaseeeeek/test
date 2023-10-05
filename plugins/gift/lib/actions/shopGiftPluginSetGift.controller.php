<?php

class shopGiftPluginSetGiftController extends waJsonController
{

	public function execute()
	{
		if (!waRequest::isXMLHttpRequest()) {
			throw new waException('Page not found', 404);
		}

		$product_id = waRequest::post('product_id', 0, waRequest::TYPE_INT);
		$ids = waRequest::post('gift_id', array(), waRequest::TYPE_ARRAY_INT);


		$product_model = new shopProductModel();
		// Set present badge
		if (!empty($ids)) {
			$product_model->updateById($product_id, array('badge' => '<div class="badge badge-present"></div>'));
		}
		else {
			$product_model->updateById($product_id, array('badge' => null));
		}

		$model = new shopGiftPluginProductGiftModel;
		$model->setGifts($product_id, $ids);
	}

}