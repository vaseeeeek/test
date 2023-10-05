<?php

class shopBdgPluginSaveController extends waJsonController
{
	public function execute()
	{
		if ( waRequest::post('single',0,waRequest::TYPE_INT) )
			$this->_saveSingleProductBadge();
		else
			$this->_saveProductBadges();
	}
	
	
	private function _saveSingleProductBadge()
	{
		$badge_id = waRequest::post('id',0,waRequest::TYPE_INT);
		$product_id = waRequest::post('product_id',0,waRequest::TYPE_INT);
		if ( $badge_id && $product_id )
		{
			$productBadgeModel = new shopBdgPluginProductBadgeModel;
			$productBadgeModel->toggle($badge_id,$product_id);
			$badge_ids = $productBadgeModel->getBadgeIds($product_id);
			
			$model = new shopBdgPluginBadgeModel;
			$codes = $model->getAll('id');
			
			$code = '';
			if ( !empty($badge_ids) )
				foreach ( $badge_ids as $bid )
					if ( isset($codes[$bid]['code']) )
						$code .= $codes[$bid]['code'];
			
			$productModel = new shopProductModel;
			$productModel->updateById($product_id,array('badge'=>$code));
		}
	}
	
	
	private function _saveProductBadges()
	{
		$badge_id = waRequest::post('id',0,waRequest::TYPE_INT);
		if ( $badge_id )
		{
			$model = new shopBdgPluginBadgeModel;
			$codes = $model->getAll('id');
			
			$ids = $this->_getIds();
			if ( !empty($ids) )
			{
				$productModel = new shopProductModel;
				$productBadgeModel = new shopBdgPluginProductBadgeModel;
				
				foreach ( $ids as $id )
				{
					$productBadgeModel->toggle($badge_id,$id);
					$badge_ids = $productBadgeModel->getBadgeIds($id);
					
					$code = '';
					if ( !empty($badge_ids) && is_array($badge_ids) )
						foreach ( $badge_ids as $bid )
							if ( isset($codes[$bid]['code']) )
								$code .= $codes[$bid]['code'];
					
					$productModel->updateById($id,array('badge'=>$code));
				}
			}
		}
	}
	
	
	protected function _getIds()
	{
		$ids = array();
		$data = waRequest::post('data',array(),waRequest::TYPE_ARRAY);
		$offset = waRequest::post('offset',0,waRequest::TYPE_INT);
		if ( !empty($data) )
		{
			if ( $data[0]['name'] == 'hash' )
			{
				$collection = new shopProductsCollection($data[0]['value']);
				$ids = array_keys($collection->getProducts('*', $offset, 50));
			}
			else
				foreach ( $data as $k=>$v )
					if ( $k >= $offset && $k < $offset + 50 )
						$ids[] = $v['value'];
		}
		return $ids;
	}

}