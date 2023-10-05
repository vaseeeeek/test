<?php

class shopEditStorefrontSelection
{
	const MODE_ALL = 'ALL';
	const MODE_SELECTED = 'SELECTED';

	public $mode = self::MODE_ALL;
	public $storefronts = array();

	public function __construct($params = null)
	{
		if (is_array($params))
		{
			$this->mode = $params['mode'];
			$this->storefronts = array();

			if (is_array($params['storefronts']))
			{
				foreach ($params['storefronts'] as $storefront => $_)
				{
					$this->storefronts[$storefront] = $storefront;
				}
			}
		}
	}

	public function assoc()
	{
		return array(
			'mode' => $this->mode,
			'storefronts' => array_values($this->storefronts),
		);
	}
}