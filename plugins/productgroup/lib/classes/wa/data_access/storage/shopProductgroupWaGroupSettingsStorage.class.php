<?php

class shopProductgroupWaGroupSettingsStorage implements shopProductgroupGroupSettingsStorage
{
	private $settings_model;

	public function __construct()
	{
		$this->settings_model = new shopProductgroupGroupSettingsModel();
	}

	/**
	 * @param int $group_id
	 * @param string $scope
	 * @return shopProductgroupGroupSettings
	 */
	public function getGroupScopeSettings($group_id, $scope)
	{
		$settings_raw = $this->settings_model
			->select('name,value')
			->where('group_id = :group_id', ['group_id' => $group_id])
			->where('scope = :scope', ['scope' => $scope])
			->fetchAll('name', true);

		return $this->createFromRaw($settings_raw, $scope);
	}

	/**
	 * @param int $group_id
	 * @param string $scope
	 * @param shopProductgroupGroupSettings $group_settings
	 * @return bool
	 */
	public function storeGroupScopeSettings($group_id, $scope, $group_settings)
	{
		$group_settings_raw = $this->convertToRaw($group_settings);

		foreach ($group_settings_raw as $name => $value)
		{
			$this->settings_model->insert([
				'group_id' => $group_id,
				'scope' => $scope,
				'name' => $name,
				'value' => $value,
			], waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
		}

		return true;
	}

	private function createFromRaw(array $settings_raw, $scope)
	{
		$default_raw_settings = $this->getDefaultRawSettings($scope);

		return new shopProductgroupGroupSettings(
			ifset($settings_raw, 'is_shown', $default_raw_settings['is_shown']) == '1',
			ifset($settings_raw, 'show_in_stock_only', $default_raw_settings['show_in_stock_only']) == '1',
			ifset($settings_raw, 'show_on_primary_product_only', $default_raw_settings['show_on_primary_product_only']) == '1',
			ifset($settings_raw, 'show_header', $default_raw_settings['show_header']) == '1',
			ifset($settings_raw, 'current_product_first', $default_raw_settings['current_product_first']) == '1',
			ifset($settings_raw, 'image_size', $default_raw_settings['image_size'])
		);
	}

	private function convertToRaw(shopProductgroupGroupSettings $group_settings)
	{
		return [
			'is_shown' => $group_settings->is_shown ? '1' : '0',
			'show_in_stock_only' => $group_settings->show_in_stock_only ? '1' : '0',
			'show_on_primary_product_only' => $group_settings->show_on_primary_product_only ? '1' : '0',
			'show_header' => $group_settings->show_header ? '1' : '0',
			'current_product_first' => $group_settings->current_product_first ? '1' : '0',
			'image_size' => strval($group_settings->image_size),
		];
	}

	private function getDefaultRawSettings($scope)
	{
		static $default_scope_settings = [
			shopProductgroupGroupSettingsScope::PRODUCT => [
				'is_shown' => '1',
				'show_in_stock_only' => '1',
				'show_on_primary_product_only' => '0',
				'show_header' => '1',
				'current_product_first' => '0',
				'image_size' => '',
			],
			shopProductgroupGroupSettingsScope::CATEGORY => [
				'is_shown' => '1',
				'show_in_stock_only' => '1',
				'show_on_primary_product_only' => '0',
				'show_header' => '0',
				'current_product_first' => '1',
				'image_size' => '',
			],
		];

		return $default_scope_settings[$scope];
	}
}