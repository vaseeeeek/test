<?php

class shopProductgroupWaSettingsStorage extends shopProductgroupKeyValueStorage implements shopProductgroupSettingsStorage
{
	private $settings_model;

	public function __construct()
	{
		$this->settings_model = new shopProductgroupSettingsModel();
	}

	/**
	 * @param string $storefront
	 * @return shopProductgroupSettings
	 * @throws shopProductgroupIncompleteStorageSpecification
	 */
	public function getSettings($storefront)
	{
		$settings_raw = $this->settings_model
			->select('name,value')
			->where('storefront = :storefront', ['storefront' => $storefront])
			->fetchAll('name', true);

		return $this->buildSettingsByRaw($settings_raw);
	}

	public function haveSettingsForStorefront($storefront)
	{
		$count = $this->settings_model
			->select('COUNT(*)')
			->where('storefront = :storefront', ['storefront' => $storefront])
			->fetchField();

		$count = intval($count);

		return $count > 0;
	}

	/**
	 * @param string $storefront
	 * @param shopProductgroupSettings $settings
	 * @return bool
	 * @throws shopProductgroupIncompleteStorageSpecification
	 */
	public function saveSettings($storefront, shopProductgroupSettings $settings)
	{
		$settings_raw = [];

		foreach (get_class_vars(get_class($settings)) as $field => $_)
		{
			$settings_raw[$field] = $this->valueToRaw($field, $settings->$field);
		}

		return $this->storeRawSettings($storefront, $settings_raw);
	}

	public function deleteSettings($storefront)
	{
		return $this->settings_model->deleteByField('storefront', $storefront);
	}

	public function getStorefrontsWithPersonalSettings()
	{
		$all = $this->settings_model->select('DISTINCT storefront')->fetchAll('storefront');
		unset($all[shopProductgroupGeneralStorefront::NAME]);

		return array_keys($all);
	}

	protected function getFieldSpecifications()
	{
		return [
			'is_enabled' => new shopProductgroupStoredValueSpecification(shopProductgroupStoredValueType::BOOL, false),
			'output_wa_hook' => new shopProductgroupStoredValueSpecification(shopProductgroupStoredValueType::STRING, shopProductgroupOutputHook::NONE),
		];
	}

	/**
	 * @param $settings_raw
	 * @return shopProductgroupSettings
	 * @throws shopProductgroupIncompleteStorageSpecification
	 */
	private function buildSettingsByRaw($settings_raw)
	{
		$settings = new shopProductgroupSettings();

		foreach (get_class_vars(get_class($settings)) as $field => $_)
		{
			$settings->$field = $this->getValueFromRaw($settings_raw, $field);
		}

		return $settings;
	}

	private function storeRawSettings($storefront, array $settings_raw)
	{
		$success = true;

		foreach ($settings_raw as $field => $value)
		{
			$success = $this->settings_model->replace([
				'storefront' => $storefront,
				'name' => $field,
				'value' => $value,
			]) && $success;
		}

		return $success;
	}
}