<?php

interface shopProductgroupProductGroupStorage
{
	/**
	 * @param $id
	 * @return shopProductgroupProductGroup
	 */
	public function getById($id);
	
	/**
	 * @param $product_id
	 * @return shopProductgroupProductGroup[]
	 */
	public function getByProductId($product_id);
	
	public function store(shopProductgroupProductGroup $product_group);
	
	public function delete(shopProductgroupProductGroup $product_group);
	
	public function loadProducts(shopProductgroupProductGroup $product_group);

	/**
	 * @param int[] $product_ids_to_delete
	 */
	public function handleProductsDelete($product_ids_to_delete);
}