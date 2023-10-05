<?php

class shopProductgroupProductGroup
{
	private $id;
	/** @var shopProductgroupGroup */
	private $group;
	/** @var shopProductgroupGroupProduct[] */
	private $products;
	
	public function getId()
	{
		return $this->id;
	}
	
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return shopProductgroupGroup
	 */
	public function getGroup()
	{
		return $this->group;
	}

	/**
	 * @param shopProductgroupGroup $group
	 */
	public function setGroup($group)
	{
		$this->group = $group;
	}

	/**
	 * @return shopProductgroupGroupProduct[]
	 */
	public function getProducts()
	{
		return $this->products;
	}

	/**
	 * @param shopProductgroupGroupProduct[] $products
	 */
	public function setProducts($products)
	{
		$this->products = $products;
	}
	
	public function toArray()
	{
		$products = $this->getProducts();
		$array_products = array();
		
		foreach ($products as $product)
		{
			$array_products[] = $product->toArray();
		}
		
		return array(
			'id' => $this->getId(),
			'group' => $this->getGroup()->toAssoc(),
			'products' => $array_products,
		);
	}
}