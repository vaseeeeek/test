<?php

class shopBdgPluginBadgesController extends waJsonController
{
	public function execute()
	{
		$product_ids = waRequest::post('ids',array(),waRequest::TYPE_ARRAY);
		
		$badges = array();
		if ( !empty($product_ids) )
		{
			$model = new shopBdgPluginProductBadgeModel;
			$badges = $model->getProductBadges($product_ids);
		}
		
		$this->response['badges'] = $badges;
	}

}