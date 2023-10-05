<?php

class shopBrandSettingsStorage extends shopBrandStorage
{
	/** @var shopBrandSettings */
	private static $settings_instance = null;

	private $category_link_mode_options;
	private $review_status_options;
	private $brands_sort_options;
	private $empty_page_response_mode_options;

	public function __construct()
	{
		$this->category_link_mode_options = new shopBrandCategoryLinkModeEnumOptions();
		$this->review_status_options = new shopBrandReviewStatusEnumOptions();
		$this->brands_sort_options = new shopBrandBrandsSortEnumOptions();
		$this->empty_page_response_mode_options = new shopBrandEmptyPageResponseModeEnumOptions();

		parent::__construct();
	}

	/**
	 * @return shopBrandSettings
	 */
	public function getSettings()
	{
		if (self::$settings_instance === null)
		{
			$settings_raw = $this->model
				->select('name,setting')
				->where('storefront = :storefront', array('storefront' => shopBrandStorefront::GENERAL))
				->where('name IN (s:names)', array('names' => $this->getAvailableFields()))
				->fetchAll('name', true);

			$settings_assoc = $this->prepareStorableForAccessible($settings_raw);

			$settings_assoc['thumbnail_sizes_array'] = trim($settings_assoc['thumbnail_sizes']) === ''
				? array()
				: explode(';', trim($settings_assoc['thumbnail_sizes']));

			self::$settings_instance = new shopBrandSettings(array_merge($settings_assoc, $this->getHiddenConfig()));
		}

		return self::$settings_instance;
	}

	public function store($settings_assoc) {
		self::$settings_instance = null;

		if (isset($settings_assoc['feature_id']) && $settings_assoc['feature_id'] > 0)
		{
			$feature_model = new shopFeatureModel();
			$feature_id = $settings_assoc['feature_id'];

			$feature = $feature_model->getById($feature_id);

			if (!$feature || $feature['type'] != shopFeatureModel::TYPE_VARCHAR)
			{
				throw new waException($feature ? 'Invalid feature id: type must be varchar.' : "Invalid feature id: no feature with id [{$feature_id}]");
			}
		}
		else
		{
			unset($settings_assoc['feature_id']);
		}

		$this->saveHiddenConfig($settings_assoc);

		$settings_raw = $this->prepareAccessibleToStorable($settings_assoc);

		foreach ($settings_raw as $name => $setting)
		{
			$this->model->insert(array(
				'storefront' => shopBrandStorefront::GENERAL,
				'name' => $name,
				'setting' => $setting,
			), waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
		}
	}

	/**
	 * @return shopBrandIDataFieldSpecification[]
	 */
	protected function accessSpecification()
	{
		$specification = new shopBrandDataFieldSpecificationFactory();

		return array(
			'is_enabled' => $specification->boolean(true),
			'root_url' => $specification->string('brands'),
			'base_url' => $specification->string('brand'),
			'category_link_mode' => $specification->enum($this->category_link_mode_options, $this->category_link_mode_options->RAW),
			'brand_feature_id' => $specification->integer(),
			'new_review_status' => $specification->enum($this->review_status_options, $this->review_status_options->PUBLISHED),
			'use_additional_description' => $specification->boolean(false),
			'add_product_reviews' => $specification->boolean(false),
			'display_brands_to_frontend_nav' => $specification->boolean(true),
			'with_images_only' => $specification->boolean(false),
			'hide_reviews_tab_if_empty' => $specification->boolean(false),
			'disable_add_review_captcha' => $specification->boolean(false),
			'brands_default_sort' => $specification->enum($this->brands_sort_options, $this->brands_sort_options->NAME),
			'use_optimized_images' => $specification->boolean(false),
			'thumbnail_sizes' => $specification->string('0x100;150x0;0x50'),
			'empty_page_response_mode' => $specification->enum($this->empty_page_response_mode_options, $this->empty_page_response_mode_options->ERROR_404),
            'cache_lifetime' => $specification->integer(2100000),
            'use_brands_alpha' => $specification->boolean(false),
            'use_brands_search' => $specification->boolean(false),
        );
	}

	protected function dataModel()
	{
		return new shopBrandSettingsModel();
	}

	private function getHiddenConfig()
	{
		$hidden_config_file = wa('shop')->getConfigPath('shop/plugins/brand') . '/config.php';

		if (!file_exists($hidden_config_file))
		{
			return array();
		}

		$hidden_config_content = include($hidden_config_file);
		if (!is_array($hidden_config_content))
		{
			return array();
		}

		$hidden_config = array();

		foreach ($this->getDefaultHiddenConfig() as $field => $default_value)
		{
			$hidden_config[$field] = array_key_exists($field, $hidden_config_content)
				? $hidden_config_content[$field]
				: $default_value;
		}

		return $hidden_config;
	}

	/**
	 * @param array $settings_assoc
	 * @throws Exception
	 */
	private function saveHiddenConfig($settings_assoc)
	{
		$hidden_config_file = wa('shop')->getConfigPath('shop/plugins/brand') . '/config.php';

		$hidden_config = array();
		foreach ($this->getDefaultHiddenConfig() as $field => $_)
		{
			if (array_key_exists($field, $settings_assoc))
			{
				$hidden_config[$field] = $settings_assoc[$field];
			}
		}

		if (count($hidden_config) > 0)
		{
			waUtils::varExportToFile($hidden_config, $hidden_config_file);
		}
		else
		{
			waFiles::delete($hidden_config_file);
		}
	}

	private function getDefaultHiddenConfig()
	{
		return array(
			'routing_is_extended' => false,
		);
	}
}
