<?php

class shopProductgroupPluginContext
{
	private static $instance;

	private $storage_factory;
	private $group_storage;
	private $group_settings_storage;
	private $product_group_storage;
	private $settings_storage;
	private $markup_template_settings_storage;
	private $markup_template_path_registry;
	private $markup_template_registry;
	private $markup_template_file_storage;
	private $markup_style_settings_model;
	private $markup_style_settings_storage;
	private $style_settings_assoc_mapper;
	private $style_file_storage;
	private $plugin_env_factory;
	private $product_color_access;

	private function __construct()
	{}

	/** @return shopProductgroupPluginContext */
	public static function getInstance()
	{
		if (!isset(self::$instance))
		{
			self::$instance = new shopProductgroupPluginContext();
		}

		return self::$instance;
	}

	/**
	 * @return shopProductgroupGroupStorage
	 */
	public function getGroupStorage()
	{
		if (!isset($this->group_storage))
		{
			$this->group_storage = $this->getStorageFactory()->createGroupStorage();
		}

		return $this->group_storage;
	}

	/**
	 * @return shopProductgroupGroupSettingsStorage
	 */
	public function getGroupSettingsStorage()
	{
		if (!isset($this->group_settings_storage))
		{
			$this->group_settings_storage = $this->getStorageFactory()->createGroupSettingsStorage();
		}

		return $this->group_settings_storage;
	}

	/**
	 * @return shopProductgroupProductGroupStorage
	 */
	public function getProductGroupStorage()
	{
		if (!isset($this->product_group_storage))
		{
			$this->product_group_storage = $this->getStorageFactory()->createProductGroupStorage();
		}

		return $this->product_group_storage;
	}

	/**
	 * @return shopProductgroupSettingsStorage
	 */
	public function getStorefrontSettingsStorage()
	{
		if (!isset($this->settings_storage))
		{
			$this->settings_storage = $this->getStorageFactory()->createSettingsStorage();
		}

		return $this->settings_storage;
	}

	/** @return shopProductgroupMarkupTemplateSettingsStorage */
	public function getMarkupTemplateSettingsStorage()
	{
		if (!isset($this->markup_template_settings_storage))
		{
			$this->markup_template_settings_storage = new shopProductgroupMarkupTemplateSettingsStorage(
				$this->getMarkupTemplateRegistry(),
				$this->getMarkupTemplatePathRegistry(),
				$this->getMarkupTemplateFileStorage()
			);
		}

		return $this->markup_template_settings_storage;
	}

	/**
	 * @return shopProductgroupMarkupTemplatePathRegistry
	 */
	public function getMarkupTemplatePathRegistry()
	{
		if (!isset($this->markup_template_path_registry))
		{
			$this->markup_template_path_registry = new shopProductgroupWaMarkupTemplatePathRegistry(
				$this->getMarkupTemplateFileStorage()
			);
		}

		return $this->markup_template_path_registry;
	}

	/** @return shopProductgroupMarkupTemplateRegistry */
	public function getMarkupTemplateRegistry()
	{
		if (!isset($this->markup_template_registry))
		{
			$this->markup_template_registry = new shopProductgroupMarkupTemplateRegistry();
		}

		return $this->markup_template_registry;
	}

	/** @return shopProductgroupMarkupTemplateFileStorage */
	public function getMarkupTemplateFileStorage()
	{
		if (!isset($this->markup_template_file_storage))
		{
			$this->markup_template_file_storage = new shopProductgroupWaMarkupTemplateFileStorage();
		}

		return $this->markup_template_file_storage;
	}

	/** @return shopProductgroupMarkupTemplateSettingsAssocMapper */
	public function getMarkupTemplateSettingsAssocMapper()
	{
		if (!isset($this->markup_template_settings_assoc_mapper))
		{
			$this->markup_template_settings_assoc_mapper = new shopProductgroupMarkupTemplateSettingsAssocMapper();
		}

		return $this->markup_template_settings_assoc_mapper;
	}

	/** @return shopProductgroupMarkupStyleSettingsStorage */
	public function getMarkupStyleSettingsStorage()
	{
		if (!isset($this->markup_style_settings_storage))
		{
			$data_source = $this->getMarkupStyleSettingsDataSource();
			$this->markup_style_settings_storage = new shopProductgroupMarkupStyleSettingsStorage($data_source);
		}

		return $this->markup_style_settings_storage;
	}

	/** @return shopProductgroupMarkupStyleSettingsAssocMapper */
	public function getMarkupStyleSettingsAssocMapper()
	{
		if (!isset($this->style_settings_assoc_mapper))
		{
			$this->style_settings_assoc_mapper = new shopProductgroupMarkupStyleSettingsAssocMapper();
		}

		return $this->style_settings_assoc_mapper;
	}

	/** @return shopProductgroupStyleFileStorage */
	public function getStyleFileStorage()
	{
		if (!isset($this->style_file_storage))
		{
			$this->style_file_storage = new shopProductgroupWaStyleFileStorage();
		}

		return $this->style_file_storage;
	}

	/** @return shopProductgroupPluginEnvFactory */
	public function getPluginEnvFactory()
	{
		if (!isset($this->plugin_env_factory))
		{
			$this->plugin_env_factory = new shopProductgroupWaPluginEnvFactory();
		}

		return $this->plugin_env_factory;
	}

	/** @return shopProductgroupProductColorAccess */
	public function getProductColorAccess()
	{
		if (!isset($this->product_color_access))
		{
			$this->product_color_access = new shopProductgroupWaProductColorAccess();
		}

		return $this->product_color_access;
	}

	private function getStorageFactory()
	{
		if (!isset($this->storage_factory))
		{
			$this->storage_factory = shopProductgroupConfig::getStorageFactory();
		}

		return $this->storage_factory;
	}

	/** @return shopProductgroupMarkupStyleSettingsDataSource */
	private function getMarkupStyleSettingsDataSource()
	{
		if (!isset($this->markup_style_settings_model))
		{
			$this->markup_style_settings_model = new shopProductgroupStorefrontThemeMarkupStyleSettingsModel();
		}

		return $this->markup_style_settings_model;
	}
}