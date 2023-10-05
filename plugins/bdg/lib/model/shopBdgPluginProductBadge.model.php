<?php

class shopBdgPluginProductBadgeModel extends waModel
{
	protected $table = 'shop_bdg_product_badge';
	
	public function toggle($badge_id,$product_id)
	{
		$data = array(
			'badge_id' => $badge_id,
			'product_id' => $product_id
		);
		$link = $this->getByField($data);
		if ( isset($link['id']) )
			$this->deleteById($link['id']);
		else
			$this->insert($data);
	}
	
	
	public function getProductIds($badge_id)
	{
		$ids = array();
		if ( $rows = $this->getByField('badge_id',$badge_id,true) )
			foreach ( $rows as $row )
				$ids[] = $row['product_id'];
		return $ids;
	}
	
	
	public function getBadgeIds($product_id)
	{
		$ids = array();
		if ( $rows = $this->getByField('product_id',$product_id,true) )
			foreach ( $rows as $row )
				$ids[] = $row['badge_id'];
		return $ids;
	}
	
	
	public function getProductBadges($product_ids)
	{
		$badges = array();
		
		$b = array();
		if ( !empty($product_ids) && is_array($product_ids) )
			if ( $rows = $this->getByField('product_id',$product_ids,true) )
				foreach ( $rows as $row )
					$b[$row['product_id']][$row['badge_id']] = $row['badge_id'];

		if ( !empty($b) )
			foreach ( $b as $product_id=>$v )
				$badges[] = array(
					'product_id' => $product_id,
					'badge_ids' => array_values($v)
				);
		
		return $badges;
	}
	
	
	public function updateCode()
	{
		$model = new shopBdgPluginBadgeModel;
		$codes = $model->getAll('id');
		
		$b = array();
		if ( $rows = $this->getAll() )
			foreach ( $rows as $row )
				$b[$row['product_id']][$row['badge_id']] = $row['badge_id'];
		
		if ( !empty($b) )
		{
			$productModel = new shopProductModel;
			if ( $n_ids = $productModel->select('id')->where("badge LIKE '%badge_%' OR badge LIKE '%-b-%'")->fetchAll('id') )
				foreach ( array_keys($n_ids) as $n_id )
					$productModel->updateById($n_id,array('badge'=>''));
			
			foreach ( $b as $product_id=>$badge_ids )
			{
				$code = '';
				foreach ( $badge_ids as $bid )
					if ( isset($codes[$bid]['code']) )
						$code .= $codes[$bid]['code'];
				
				$productModel->updateById($product_id,array('badge'=>$code));
			}
		}
	}
}