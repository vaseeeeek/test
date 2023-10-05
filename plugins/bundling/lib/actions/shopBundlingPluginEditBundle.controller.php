<?php

class shopBundlingPluginEditBundleController extends waJsonController
{
	public function execute()
	{
		$bundle_id = waRequest::post('bundle_id');
		$title = waRequest::post('title');
		$multiple = waRequest::post('multiple', 0, 'int');
		
		if(waRequest::post('_csrf') != waRequest::cookie('_csrf'))
			return $this->setError(_wp('CSRF error'));

		$model = new shopBundlingModel();
		$this->response = $model->updateById($bundle_id, array(
			'title' => $title,
			'multiple' => $multiple
		));
	}
}