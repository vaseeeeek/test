<?php

class shopBundlingPluginBackendSaveCategoryBundlesController extends waJsonController
{
	public function execute()
	{
		$id = waRequest::post('id', -1, 'int');
		$title = waRequest::post('title');
		$multiple = waRequest::post('multiple');
		$multiple = $multiple ? 1 : 0;
		
		if($id >= 0) {
			$model = new shopBundlingCategoriesModel();
			$this->response = $model->insert(array(
				'category_id' => $id,
				'title' => $title,
				'multiple' => $multiple
			), 1);
		} else
			$this->setError(_wp('Undefined category'));
	}
}