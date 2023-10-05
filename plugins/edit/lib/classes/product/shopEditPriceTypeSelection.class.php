<?php

class shopEditPriceTypeSelection
{
	const MODE_ALL = 'ALL';
	const MODE_SELECTED = 'SELECTED';

	public $mode = self::MODE_ALL;
	public $selected_ids = array();

	public function __construct($params = null)
	{
		if (!is_array($params))
		{
			return;
		}

		$this->mode = $params['mode'];
		$this->selected_ids = $params['selected_ids'];
	}

	public function hasShopPrices()
	{
		return $this->mode == self::MODE_ALL || count(array_intersect($this->getShopPrices(), $this->selected_ids)) > 0;
	}

	public function hasMainPrice()
	{
		return $this->mode == self::MODE_ALL || array_search('price', $this->selected_ids) !== false;
	}

	public function hasPriceOrComparePrice()
	{
		return $this->mode == self::MODE_ALL || array_search('price', $this->selected_ids) !== false || array_search('compare_price', $this->selected_ids) !== false;
	}

	public function getShopPrices()
	{
		return array(
			'price',
			'compare_price',
			'purchase_price',
		);
	}

	public function assoc()
	{
		return array(
			'mode' => $this->mode,
			'selected_ids' => $this->selected_ids,
		);
	}
}