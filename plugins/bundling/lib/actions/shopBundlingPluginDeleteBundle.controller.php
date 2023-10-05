<?php

class shopBundlingPluginDeleteBundleController extends waJsonController
{
	public function execute()
	{
		$product_id = waRequest::post('product_id', null);
		$bundle_id = waRequest::post('bundle_id');
		
		if(waRequest::post('_csrf') != waRequest::cookie('_csrf'))
			return $this->setError(_wp('CSRF error'));

		$model = new shopBundlingModel();
		$products_model = new shopBundlingProductsModel();
		$model->deleteByField(array(
			'product_id' => $product_id,
			'id' => $bundle_id
		));
		$products_model->deleteByField(array(
			'product_id' => $product_id,
			'bundle_id' => $bundle_id
		));
		
		$this->response = true;
	}
}