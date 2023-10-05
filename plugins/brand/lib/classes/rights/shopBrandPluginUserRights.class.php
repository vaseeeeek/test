<?php

class shopBrandPluginUserRights
{
	const EDIT_RIGHT_NAME = 'brand.brand_edit';

	public function updateRightsConfig(waRightConfig $config)
	{
		$config->addItem('brand_header', 'Бренды PRO', 'header', array('cssclass' => 'c-access-subcontrol-header', 'tag' => 'div'));
		$config->addItem(self::EDIT_RIGHT_NAME, 'Редактирование брендов', 'checkbox', array('cssclass' => 'c-access-subcontrol-item'));
	}

	/**
	 * @param null|waAuthUser $user
	 * @return bool
	 */
	public function hasRights($user = null)
	{
		/** @var waAuthUser $user */
		if (!$user)
		{
			$user = wa()->getUser();
		}

		return $user
			? $user->getRights('shop', self::EDIT_RIGHT_NAME) != 0
			: false;
	}
}
