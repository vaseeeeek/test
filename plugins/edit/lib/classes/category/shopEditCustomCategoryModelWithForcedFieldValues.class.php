<?php

class shopEditCustomCategoryModelWithForcedFieldValues extends shopCategoryModel
{
	private $forced_category_field_values;

	public function __construct($forced_category_field_values = array())
	{
		parent::__construct(null, false);

		$this->forced_category_field_values = is_array($forced_category_field_values)
			? $forced_category_field_values
			: array();
	}

	public function getById($value)
	{
		$category = parent::getById($value);

		if (is_array($category))
		{
			foreach ($this->forced_category_field_values as $field => $value)
			{
				$category[$field] = $value;
			}
		}

		return $category;
	}
}