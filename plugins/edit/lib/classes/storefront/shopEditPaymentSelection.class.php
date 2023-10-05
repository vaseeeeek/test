<?php

class shopEditPaymentSelection
{
	const MODE_ALL = 'ALL';
	const MODE_SELECTED = 'SELECTED';

	public $mode = self::MODE_ALL;
	public $selected_payment_ids = array();

	public function __construct($params = null)
	{
		if (is_array($params))
		{
			$this->mode = $params['mode'];
			$this->selected_payment_ids = $params['selected_payment_ids'];
		}
	}

	public function getPaymentForRoute()
	{
		if ($this->mode == self::MODE_ALL)
		{
			return '0';
		}

		if ($this->mode == self::MODE_SELECTED)
		{
			return $this->selected_payment_ids;
		}

		return array();
	}
}