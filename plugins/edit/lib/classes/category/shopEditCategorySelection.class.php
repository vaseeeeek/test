<?php

class shopEditCategorySelection
{
	const MODE_ALL = 'ALL';
	const MODE_SELECTED = 'SELECTED';

	public $mode = self::MODE_ALL;
	public $category_ids = array();

	public function __construct($params = null)
	{
		if (is_array($params))
		{
			$this->mode = $params['mode'];
			$this->category_ids = array();

			if (is_array($params['category_ids']))
			{
				foreach ($params['category_ids'] as $category_id => $_)
				{
					$this->category_ids[$category_id] = $category_id;
				}
			}
		}
	}

	public function assoc()
	{
		return array(
			'mode' => $this->mode,
			'category_ids' => array_values($this->category_ids),
		);
	}
}