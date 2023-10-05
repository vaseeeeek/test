<?php

class shopEditSiteSaveAppsMenuAction extends shopEditLoggedAction
{
	private $settings;

	public function __construct(shopEditSiteAppsMenuSettings $settings)
	{
		parent::__construct();

		$this->settings = $settings;
	}

	protected function execute()
	{
		$site_storage = new shopEditSiteStorage();
		$apps_menu_settings = $this->settings;

		/** @var shopEditSite[] $sites */
		$sites = array();
		if ($apps_menu_settings->site_selection->mode == shopEditSiteSelection::MODE_ALL)
		{
			$sites = $site_storage->getAll();
		}
		elseif ($apps_menu_settings->site_selection->mode == shopEditSiteSelection::MODE_SELECTED)
		{
			foreach ($apps_menu_settings->site_selection->site_ids as $site_id)
			{
				$site = $site_storage->getById($site_id);
				if ($site)
				{
					$sites[] = $site;
				}
			}
		}

		foreach ($sites as $site)
		{
			if ($apps_menu_settings->apps_menu_mode == shopEditSiteAppsMenuSettings::MODE_AUTO)
			{
				$site->apps = null;
			}
			elseif ($apps_menu_settings->apps_menu_mode == shopEditSiteAppsMenuSettings::MODE_MANUAL)
			{
				$site->apps = $apps_menu_settings->apps_menu_elements;
			}

			$site_storage->store($site);
		}

		return array(
			'settings' => $apps_menu_settings->assoc(),
		);
	}

	protected function getAction()
	{
		return $this->action_options->SITE_SAVE_APPS_MENU;
	}
}