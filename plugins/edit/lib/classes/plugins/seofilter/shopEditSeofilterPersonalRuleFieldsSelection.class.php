<?php

class shopEditSeofilterPersonalRuleFieldsSelection
{
	const MODE_ALL = 'ALL';
	const MODE_SELECTED = 'SELECTED';

	const FIELD_DEFAULT_PRODUCT_SORT = 'default_product_sort';
	const FIELD_IS_PAGINATION_TEMPLATES_ENABLED = 'is_pagination_templates_enabled';

	public $mode = self::MODE_ALL;
	public $fields = array();

	public function __construct($params = null)
	{
		if (is_array($params))
		{
			$this->mode = $params['mode'];
			$this->fields = array();

			foreach ($this->getAllMainFields() as $main_field)
			{
				if (array_key_exists($main_field, $params['fields']))
				{
					$this->fields[] = $main_field;
				}
			}

			if (array_key_exists(self::FIELD_IS_PAGINATION_TEMPLATES_ENABLED, $params['fields']))
			{
				foreach ($this->getAllPaginationFields() as $pagination_field)
				{
					if (array_key_exists($pagination_field, $params['fields']))
					{
						$this->fields[] = $pagination_field;
					}
				}
			}
		}
	}

	public function getSelectedFields()
	{
		if ($this->mode == self::MODE_ALL)
		{
			return $this->getAllFields();
		}

		if ($this->mode == self::MODE_SELECTED)
		{
			return $this->fields;
		}

		return array();
	}

	public function getFieldsExceptSelected()
	{
		if ($this->mode == self::MODE_ALL)
		{
			return array();
		}

		$all_fields = array_fill_keys($this->getAllFields(), 1);

		if ($this->mode == self::MODE_SELECTED)
		{
			foreach ($this->fields as $field)
			{
				unset($all_fields[$field]);
			}
		}

		return array_keys($all_fields);
	}

	public function areAllSelected()
	{
		if ($this->mode == self::MODE_ALL)
		{
			return true;
		}

		return count($this->fields) == count($this->getAllFields());
	}

	public function assoc()
	{
		return array(
			'mode' => $this->mode,
			'fields' => $this->fields,
		);
	}

	public function getAllFields()
	{
		return array_merge($this->getAllMainFields(), $this->getAllPaginationFields());
	}

	public function getAllMainFields()
	{
		return array(
			'default_product_sort',
			'seo_h1',
			'meta_title',
			'meta_description',
			'meta_keywords',
			'seo_description',
			'additional_description',
			'is_pagination_templates_enabled',
		);
	}

	public function getAllPaginationFields()
	{
		return array(
			'seo_h1_pagination',
			'meta_title_pagination',
			'meta_description_pagination',
			'meta_keywords_pagination',
			'seo_description_pagination',
			'additional_description_pagination',
		);
	}
}