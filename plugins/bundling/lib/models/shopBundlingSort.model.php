<?php

class shopBundlingSortModel extends waModel
{
	protected $table = 'shop_bundling_sort';
	
	public function getMaxSort($product_id)
	{
		return $this->query("SELECT MAX(sort) AS max FROM `{$this->getTableName()}` WHERE product_id = {$product_id}")->fetchField('max');
	}
	
	public function push($product_id, $bundle_id)
	{
		$sort = intval($this->getMaxSort($product_id)) + 1;
		$this->insert(array(
			'product_id' => $product_id,
			'bundle_id' => $bundle_id,
			'sort' => $sort
		));
		
		return $sort;
	}
}