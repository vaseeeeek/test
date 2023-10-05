<?php

class shopEditBrandSelection
{
	const MODE_ALL = 'ALL';
	const MODE_SELECTED = 'SELECTED';

	public $mode = self::MODE_ALL;
	public $brand_ids = array();

	public function __construct($params = null)
	{
		if (is_array($params))
		{
			$this->mode = $params['mode'];
			$this->brand_ids = array();

			if (is_array($params['brand_ids']))
			{
				foreach ($params['brand_ids'] as $brand_id => $_)
				{
					$this->brand_ids[$brand_id] = $brand_id;
				}
			}
		}
	}

	public function assoc()
	{
		return array(
			'mode' => $this->mode,
			'brand_ids' => array_values($this->brand_ids),
		);
	}
}