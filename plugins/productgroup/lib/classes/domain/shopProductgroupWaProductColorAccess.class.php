<?php

class shopProductgroupWaProductColorAccess implements shopProductgroupProductColorAccess
{
	private $color_value_model;

	public function __construct()
	{
		$this->color_value_model = new shopFeatureValuesColorModel();
	}

	public function getProductColor($product_id, $color_feature_id)
	{
		if (!$color_feature_id)
		{
			return null;
		}

		$sql = "
SELECT val.*
FROM shop_product_features AS pf
	JOIN {$this->color_value_model->getTableName()} AS val
		ON val.id = pf.feature_value_id
WHERE pf.product_id = :product_id AND pf.feature_id = :feature_id
ORDER BY pf.sku_id IS NULL DESC
LIMIT 1
";

		$query_params = [
			'product_id' => $product_id,
			'feature_id' => $color_feature_id,
		];

		$color_value_row = $this->color_value_model
			->query($sql, $query_params)
			->fetchAssoc();

		if (!$color_value_row)
		{
			return null;
		}

		return new shopColorValue($color_value_row);
	}
}