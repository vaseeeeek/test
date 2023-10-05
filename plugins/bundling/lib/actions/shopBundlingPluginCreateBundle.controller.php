<?php

class shopBundlingPluginCreateBundleController extends waJsonController
{
	public function execute()
	{
		$product_id = waRequest::post('product_id');
		$title = waRequest::post('title');
		$multiple = waRequest::post('multiple', 0, 'int');
		$model = new shopBundlingModel();
		$sort_model = new shopBundlingSortModel();
		$id = $model->insert(array(
			'product_id' => $product_id,
			'title' => $title,
			'multiple' => $multiple
		));
		$sort = $sort_model->push($product_id, $id);
		$this->response = array(
			'id' => $id,
			'sort' => $sort
		);
	}
}