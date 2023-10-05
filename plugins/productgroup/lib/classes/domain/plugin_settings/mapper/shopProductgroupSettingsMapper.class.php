<?php

class shopProductgroupSettingsMapper
{
	public function toAssoc(shopProductgroupSettings $settings)
	{
		$assoc = [];

		foreach (get_class_vars(get_class($settings)) as $field => $_)
		{
			$assoc[$field] = $settings->$field;
		}

		return $assoc;
	}

	public function mapToObject(shopProductgroupSettings $settings, $settings_assoc)
	{
		foreach (get_class_vars(get_class($settings)) as $field => $_)
		{
			if (array_key_exists($field, $settings_assoc))
			{
				$settings->$field = $settings_assoc[$field];
			}
		}
	}
}