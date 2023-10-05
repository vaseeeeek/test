<?php

class shopEditCategoryMoveMetaTagsFormState
{
	private static $allowed_fields = array(
		'h1' => 'h1',
		'seo_name' => 'seo_name',
		'meta_title' => 'meta_title',
		'meta_description' => 'meta_description',
		'meta_keywords' => 'meta_keywords',
		'description' => 'description',
		'additional_description' => 'additional_description',
	);

	public $meta_fields = array();

	public $source_is_general = true;
	public $source_storefront = '';
	public $source_storefront_group_id = 0;

	public $destination_is_general = false;
	public $destination_storefront_selection;

	public $category_selection;

	public $drop_source_tags = false;

	public function __construct($settings = null)
	{
		$category_selection_params = null;
		$destination_storefront_selection_params = null;

		if (is_array($settings))
		{
			$this->meta_fields = array_unique(array_filter(array_keys($settings['meta_fields']), array($this, 'isAllowedField')));

			$this->source_is_general = $settings['source_is_general'];
			$this->source_storefront = $settings['source_storefront'];
			$this->source_storefront_group_id = $settings['source_storefront_group_id'];

			$this->destination_is_general = $settings['destination_is_general'];
			$destination_storefront_selection_params = $settings['destination_storefront_selection'];

			$category_selection_params = $settings['category_selection'];

			$this->drop_source_tags = $settings['drop_source_tags'];
		}

		$this->category_selection = new shopEditCategorySelection($category_selection_params);
		$this->destination_storefront_selection = new shopEditSeoStorefrontSelection($destination_storefront_selection_params);
	}

	public function assoc()
	{
		return array(
			'meta_fields' => $this->meta_fields,
			'source_is_general' => $this->source_is_general,
			'source_storefront' => $this->source_storefront,
			'source_storefront_group_id' => $this->source_storefront_group_id,
			'destination_is_general' => $this->destination_is_general,
			'destination_storefront_selection' => $this->destination_storefront_selection->assoc(),
			'category_selection' => $this->category_selection->assoc(),
			'drop_source_tags' => $this->drop_source_tags,
		);
	}

	private function isAllowedField($field)
	{
		return array_key_exists($field, self::$allowed_fields);
	}
}