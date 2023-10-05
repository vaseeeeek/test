<?php

class shopBundlingPluginGetFeatureValuesController extends waJsonController
{
	public function execute()
	{
		$id = waRequest::get('id', 0, 'int');
		$model = new shopFeatureValuesVarcharModel();
		$values = $model->getValues('feature_id', $id);
		
		$this->response = $values;
	}
}