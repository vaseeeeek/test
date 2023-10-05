<?php

class shopEditSeofilterPluginHelper extends shopEditAbstractPluginHelper
{
	public function isPluginInstalled()
	{
		$info = $this->getPluginInfoRaw();

		if ($info === array())
		{
			return false;
		}

		if (!class_exists('shopSeofilterBasicSettingsModel'))
		{
			return false;
		}

		return true;
	}

	public function isPluginEnabled()
	{
		return shopSeofilterBasicSettingsModel::getSettings()->is_enabled;
	}

	public function getPluginId()
	{
		return 'seofilter';
	}
}