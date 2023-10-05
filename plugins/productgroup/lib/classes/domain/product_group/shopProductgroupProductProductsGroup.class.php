<?php

/**
 * @property-read shopProductgroupGroup $group
 * @property-read shopProductgroupGroupSettings $group_settings
 * @property-read $scope
 * @property-read $group_products
 * @property-read $product_labels
 * @property-read array|null $current_product
 */
class shopProductgroupProductProductsGroup extends shopProductgroupImmutableStructure
{
	protected $group;
	protected $group_settings;
	protected $scope;
	protected $group_products;
	protected $product_labels;
	protected $current_product;

	public function __construct(
		$group,
		$group_settings,
		$scope,
		$group_products,
		$product_labels,
		$current_product = null
	)
	{
		$this->group = $group;
		$this->group_settings = $group_settings;
		$this->scope = $scope;
		$this->group_products = $group_products;
		$this->product_labels = $product_labels;
		$this->current_product = $current_product;
	}
}