<?php

class shopProductgroupProductGroupProductModel extends waModel
{
	protected $table = 'shop_productgroup_product_group_product';

	/**
	 * @param int[] $product_ids
	 * @return int[]
	 */
	public function getProductGroupIdsByProduct($product_ids)
	{
		if (count($product_ids) === 0)
		{
			return [];
		}

		$sql = "
SELECT product_group_id
FROM {$this->table}
WHERE product_id IN (:ids)
GROUP BY product_group_id
";

		$query_params = ['ids' => $product_ids];

		$group_ids = [];
		foreach ($this->query($sql, $query_params) as $row)
		{
			$group_ids[] = $row['product_group_id'];
		}

		return $group_ids;
	}

	public function countProductsInProductGroup($product_group_id)
	{
		$products_count = $this
			->select('COUNT(product_id)')
			->where('product_group_id = :product_group_id', ['product_group_id' => $product_group_id])
			->fetchField();

		return intval($products_count);
	}
}