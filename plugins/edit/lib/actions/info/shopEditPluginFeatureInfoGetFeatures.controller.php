<?php

class shopEditPluginFeatureInfoGetFeaturesController extends shopEditBackendJsonController
{
	public function execute()
	{
		$feature_model = new shopFeatureModel();

		$options = array();

		$shop_version = wa()->getVersion('shop');
		if (version_compare($shop_version, '8.0.0', '>=')) {
			$options['frontend'] = true;
			$options['status'] = null;
		}

		$features = $feature_model->getFilterFeatures($options, 100500);

		$this->response['features'] = array_values($features);
	}

	protected function stateIsRequired()
	{
		return false;
	}
}