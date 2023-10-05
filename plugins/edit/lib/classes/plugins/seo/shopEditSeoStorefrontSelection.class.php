<?php

class shopEditSeoStorefrontSelection
{
	const MODE_ALL = 'ALL';
	const MODE_SELECTED = 'SELECTED';

	const MODE_ALL_GROUPS = 'ALL_GROUPS';
	const MODE_SELECTED_GROUPS = 'SELECTED_GROUPS';

	public $mode = self::MODE_ALL;
	public $storefronts = array();

	public $storefront_group_ids = array();

	public function __construct($params = null)
	{
		if (is_array($params))
		{
			$this->mode = $params['mode'];
			$this->storefronts = array();
			$this->storefront_group_ids = array();

			if (is_array($params['storefronts']))
			{
				foreach ($params['storefronts'] as $storefront => $_)
				{
					$this->storefronts[$storefront] = $storefront;
				}
			}

			if (is_array($params['storefront_group_ids']))
			{
				foreach ($params['storefront_group_ids'] as $group_id => $_)
				{
					$this->storefront_group_ids[$group_id] = $group_id;
				}
			}
		}
	}

	public function assoc()
	{
		return array(
			'mode' => $this->mode,
			'storefronts' => array_values($this->storefronts),
			'storefront_group_ids' => array_values($this->storefront_group_ids),
		);
	}
}