<?php

class shopEditProductsSelection
{
	const SOURCE_TYPE_ALL = 'ALL';
	const SOURCE_TYPE_CATEGORY = 'CATEGORY';
	const SOURCE_TYPE_SET = 'SET';

	public $source_type = self::SOURCE_TYPE_ALL;
	public $category_ids = array();
	public $set_ids = array();

	public function __construct($params = null)
	{
		if (!is_array($params))
		{
			return;
		}

		$this->source_type = $params['source_type'];

		if (is_array($params['category_ids']))
		{
			foreach ($params['category_ids'] as $category_id => $_)
			{
				$this->category_ids[$category_id] = $category_id;
			}
		}

		if (is_array($params['set_ids']))
		{
			foreach ($params['set_ids'] as $set_id => $_)
			{
				$this->set_ids[$set_id] = $set_id;
			}
		}
	}

	public function assoc()
	{
		return array(
			'source_type' => $this->source_type,
			'category_ids' => array_values($this->category_ids),
			'set_ids' => array_values($this->set_ids),
		);
	}
}