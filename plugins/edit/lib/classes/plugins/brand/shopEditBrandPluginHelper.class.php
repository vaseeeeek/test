<?php

class shopEditBrandPluginHelper extends shopEditAbstractPluginHelper
{
	public function isPluginInstalled()
	{
		$info = $this->getPluginInfoRaw();

		if ($info === array())
		{
			return false;
		}

		return true;
	}

	public function isPluginEnabled()
	{
		return true;
	}

	public function getPluginId()
	{
		return 'brand';
	}

	public function getBrandFilter($brand_id)
	{
		$brand_model = new shopBrandBrandModel();
		$filter_raw = $brand_model
			->select('filter')
			->where('id = :id', array('id' => $brand_id))
			->fetchField();

		if (!is_string($filter_raw) || $filter_raw == '[]')
		{
			return array();
		}

		$filter = json_decode($filter_raw, true);

		return is_array($filter) ? $filter : array();
	}

	public function clearPluginCache()
	{
		$plugin_cache_path = wa('shop')->getCachePath('cache/plugins/brand/', 'shop');

		if (file_exists($plugin_cache_path) && is_dir($plugin_cache_path))
		{
			waFiles::delete($plugin_cache_path);
		}
	}
}
