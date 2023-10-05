<?php

class shopProductgroupGroupProduct
{
	private $product;
	private $label;
	/** @var bool */
	private $is_primary;
	private $sort;

	public function getProduct()
	{
		return $this->product;
	}

	public function setProduct($product)
	{
		$this->product = $product;
	}

	public function getLabel()
	{
		return $this->label;
	}

	public function setLabel($label)
	{
		$this->label = $label;
	}

	public function isPrimary()
	{
		return $this->is_primary;
	}

	/**
	 * @param bool $is_primary
	 */
	public function setIsPrimary($is_primary)
	{
		$this->is_primary = $is_primary;
	}

	public function getSort()
	{
		return $this->sort;
	}

	public function setSort($sort)
	{
		$this->sort = $sort;
	}

	public function toArray()
	{
		return [
			'product' => $this->getProduct(),
			'label' => $this->getLabel(),
			'is_primary' => $this->isPrimary(),
		];
	}
}