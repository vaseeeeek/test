<?php

class shopRegionsCitySettingsModel extends waModel
{
	private static $settings = array();

	protected $table = 'shop_regions_city_settings';

	/**
	 * @param $city_id
	 * @return bool|array
	 */
	public function loadStorefrontSettings($city_id)
	{
		if (array_key_exists($city_id, self::$settings))
		{
			return self::$settings[$city_id];
		}

		$record = $this
			->where('`city_id` = \'' . $this->escape($city_id) . '\'')
			->fetchField('storefront_settings');

		$settings = $record === false ? false : unserialize($record);

		if (!$settings)
		{
			return $settings;
		}

		$plugin_model = new shopPluginModel();
		$plugins = $plugin_model->select('CONCAT(id, type) id_type')->fetchAll('id_type');

		if (isset($settings['payment_id']) && is_array($settings['payment_id']))
		{
			foreach ($settings['payment_id'] as $i => $payment_id)
			{
				if (!isset($plugins[$payment_id . shopPluginModel::TYPE_PAYMENT]))
				{
					unset($settings['payment_id'][$i]);
				}
			}
			unset($i);
		}

		if (isset($settings['shipping_id']) && is_array($settings['shipping_id']))
		{
			foreach ($settings['shipping_id'] as $i => $shipping_id)
			{
				if (!isset($plugins[$shipping_id . shopPluginModel::TYPE_SHIPPING]))
				{
					unset($settings['shipping_id'][$i]);
				}
			}
			unset($i);
		}

		self::$settings[$city_id] = $settings;

		return self::$settings[$city_id];
	}

	/**
	 * @param $city_id
	 * @param array $settings
	 * @return bool
	 */
	public function saveStorefrontSettings($city_id, $settings)
	{
		$data = array(
			'city_id' => $city_id,
			'storefront_settings' => serialize($settings),
		);

		return $this->countByField('city_id', $city_id) > 0
			? $this->updateByField('city_id', $city_id, $data)
			: $this->insert($data);
	}

	public function resetStorefrontSettings($city_id)
	{
		$this->deleteByField('city_id', $city_id);
	}
}