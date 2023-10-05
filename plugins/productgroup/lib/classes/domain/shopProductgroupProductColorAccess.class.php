<?php

interface shopProductgroupProductColorAccess
{
	/**
	 * @param $product_id
	 * @param $color_feature_id
	 * @return shopColorValue|null
	 */
	public function getProductColor($product_id, $color_feature_id);
}