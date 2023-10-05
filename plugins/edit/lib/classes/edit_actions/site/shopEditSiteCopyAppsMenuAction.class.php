<?php

class shopEditSiteCopyAppsMenuAction extends shopEditLoggedAction
{
	private $site_storage;

	private $setting;
	private $source_site;

	public function __construct(shopEditCopySiteSettings $settings)
	{
		parent::__construct();

		$this->site_storage = new shopEditSiteStorage();

		$this->setting = $settings;
		$this->source_site = $this->site_storage->getById($settings->source_site_id);
		if (!$this->source_site)
		{
			throw new shopEditActionInvalidParamException('source_site_id', 'Выберите сайт откуда копировать');
		}
	}

	protected function execute()
	{
		$site_storage = $this->site_storage;

		$settings = $this->setting;
		$source_site = $this->source_site;

		/** @var shopEditSite[] $destination_sites */
		$destination_sites = array();
		if ($settings->destination_site_selection->mode == shopEditSiteSelection::MODE_ALL)
		{
			$destination_sites = $site_storage->getAll();
		}
		elseif ($settings->destination_site_selection->mode == shopEditSiteSelection::MODE_SELECTED)
		{
			foreach ($settings->destination_site_selection->site_ids as $site_id)
			{
				$site = $site_storage->getById($site_id);
				if ($site)
				{
					$destination_sites[] = $site;
				}
			}
		}

		$affected_site_ids = array();
		foreach ($destination_sites as $site)
		{
			if (shopEditHelper::arraysWithSubArraysAreEqual($site->apps, $source_site->apps))
			{
				continue;
			}

			$site->apps = $source_site->apps;

			$site_storage->store($site);
			$affected_site_ids[$site->id] = $site->id;
		}

		return array(
			'settings' => $settings->assoc(),
			'source_site_name' => $source_site->name,
			'affected_site_ids' => $affected_site_ids,
			'affected_sites_count' => count($affected_site_ids),
		);
	}

	protected function getAction()
	{
		return $this->action_options->SITE_COPY_APPS_MENU;
	}
}