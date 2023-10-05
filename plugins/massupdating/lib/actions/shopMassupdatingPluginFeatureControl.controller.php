<?php

/*
 * mail@shevsky.com
 */

class shopMassupdatingPluginFeatureControlController extends waViewController
{
	public function execute()
	{
		$product_ids = waRequest::post('product_ids', '');
		$product_ids = explode(',', $product_ids);
		$feature_id = waRequest::post('feature_id', 0, 'int');
		
		$plugin = wa('shop')->getPlugin('massupdating');
		$features = $plugin->getFeaturesControls($feature_id, $product_ids);
		
		echo $features[$feature_id]['control'];
	}
}