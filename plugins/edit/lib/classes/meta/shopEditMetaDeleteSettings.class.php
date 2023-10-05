<?php

class shopEditMetaDeleteSettings
{
	const SOURCE_TYPE_CATEGORY = 'category';
	const SOURCE_TYPE_MAIN_PAGE = 'main_page';
	const SOURCE_TYPE_PAGE = 'page';
	const SOURCE_TYPE_PRODUCT = 'product';

	const FIELD_META_TITLE = 'meta_title';
	const FIELD_META_DESCRIPTION = 'meta_description';
	const FIELD_META_KEYWORDS = 'meta_keywords';
	const FIELD_DESCRIPTION = 'description';
	const FIELD_PAGE_CONTENT = 'page_content';

	public $fields;
	public $source_type;
	public $storefront_selection;
	public $delete_seo_plugin_data;

	public function __construct($settings_params)
	{
		$possible_source_types = $this->getPossibleSourceTypes();

		$this->fields = $this->filterFields($settings_params['fields']);
		$this->source_type = trim($settings_params['source_type']);
		$this->storefront_selection = new shopEditSeoStorefrontSelection($settings_params['storefront_selection']);
		$this->delete_seo_plugin_data = !!$settings_params['delete_seo_plugin_data'];

		if (!array_key_exists($this->source_type, $possible_source_types))
		{
			throw new shopEditActionInvalidParamException('source_type', 'Некорректный тип');
		}
	}

	public function assoc()
	{
		return array(
			'fields' => $this->fields,
			'source_type' => $this->source_type,
			'storefront_selection' => $this->storefront_selection->assoc(),
			'delete_seo_plugin_data' => $this->delete_seo_plugin_data,
		);
	}

	private function getPossibleFields()
	{
		return array(
			self::FIELD_META_TITLE => self::FIELD_META_TITLE,
			self::FIELD_META_DESCRIPTION => self::FIELD_META_DESCRIPTION,
			self::FIELD_META_KEYWORDS => self::FIELD_META_KEYWORDS,
			self::FIELD_DESCRIPTION => self::FIELD_DESCRIPTION,
			self::FIELD_PAGE_CONTENT => self::FIELD_PAGE_CONTENT,
		);
	}

	private function getPossibleSourceTypes()
	{
		return array(
			self::SOURCE_TYPE_CATEGORY => self::SOURCE_TYPE_CATEGORY,
			self::SOURCE_TYPE_MAIN_PAGE => self::SOURCE_TYPE_MAIN_PAGE,
			self::SOURCE_TYPE_PAGE => self::SOURCE_TYPE_PAGE,
			self::SOURCE_TYPE_PRODUCT => self::SOURCE_TYPE_PRODUCT,
		);
	}

	private function filterFields($fields)
	{
		$possible_fields = $this->getPossibleFields();

		foreach (array_keys($fields) as $index)
		{
			$field = trim($fields[$index]);

			if (!array_key_exists($field, $possible_fields))
			{
				unset($fields[$index]);
			}
		}

		return array_values($fields);
	}
}