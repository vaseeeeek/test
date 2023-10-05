<?php

class shopBrandBrandListFilter
{
	public $is_shown = null;
	public $has_image = null;
	public $has_description = null;
	public $has_additional_description = null;
	public $has_sort = null;
	public $has_filters = null;
	public $is_not_deleted = null;

	public function __construct($params)
	{
		if (!is_array($params))
		{
			return;
		}

		$this->is_shown = $params['is_shown'];
		$this->has_image = $params['has_image'];
		$this->has_description = $params['has_description'];
		$this->has_additional_description = $params['has_additional_description'];
		$this->has_sort = $params['has_sort'];
		$this->has_filters = $params['has_filters'];
		$this->is_not_deleted = $params['is_not_deleted'];
	}

	public function assoc()
	{
		return array(
			'is_shown' => $this->is_shown,
			'has_image' => $this->has_image,
			'has_description' => $this->has_description,
			'has_additional_description' => $this->has_additional_description,
			'has_sort' => $this->has_sort,
			'has_filters' => $this->has_filters,
			'is_not_deleted' => $this->is_not_deleted,
		);
	}
}
