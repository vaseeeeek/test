<?php

class shopEditShippingSelection
{
	const MODE_ALL = 'ALL';
	const MODE_SELECTED = 'SELECTED';

	public $mode = self::MODE_ALL;
	public $selected_shipping_ids = array();

	public function __construct($params = null)
	{
		if (is_array($params))
		{
			$this->mode = $params['mode'];
			$this->selected_shipping_ids = $params['selected_shipping_ids'];
		}
	}

	public function getShippingForRoute()
	{
		if ($this->mode == self::MODE_ALL)
		{
			return '0';
		}

		if ($this->mode == self::MODE_SELECTED)
		{
			return $this->selected_shipping_ids;
		}

		return array();
	}
}