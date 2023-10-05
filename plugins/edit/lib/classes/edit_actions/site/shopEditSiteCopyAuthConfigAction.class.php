<?php

class shopEditSiteCopyAuthConfigAction extends shopEditLoggedAction
{
	private $site_storage;

	private $source_site;
	private $destination_site_selection;

	public function __construct($source_site_id, shopEditSiteSelection $destination_site_selection)
	{
		parent::__construct();

		$this->site_storage = new shopEditSiteStorage();

		$this->source_site = $this->site_storage->getById($source_site_id);
		$this->destination_site_selection = $destination_site_selection;

		if (!$this->source_site)
		{
			throw new shopEditActionInvalidParamException('source_site_id', 'Нет такого сайта');
		}
	}

	protected function execute()
	{
		$destination_site_selection = $this->destination_site_selection;

		$auth_config = array();
		$auth_config_path = wa()->getConfigPath() . '/auth.php';
		if (file_exists($auth_config_path))
		{
			$auth_config = include($auth_config_path);
			if (!is_array($auth_config))
			{
				$auth_config = array();
			}
		}

		$source_config = array_key_exists($this->source_site->name, $auth_config)
			? $auth_config[$this->source_site->name]
			: null;

		$sites = array();
		if ($destination_site_selection->mode == shopEditSiteSelection::MODE_ALL)
		{
			$sites = $this->site_storage->getAll();
		}
		elseif ($destination_site_selection->mode == shopEditSiteSelection::MODE_SELECTED)
		{
			foreach ($destination_site_selection->site_ids as $site_id)
			{
				$site = $this->site_storage->getById($site_id);
				if ($site)
				{
					$sites[] = $site;
				}
			}
		}

		$affected_site_ids = array();
		foreach ($sites as $site)
		{
			$current_site_config = array_key_exists($site->name, $auth_config)
				? $auth_config[$site->name]
				: null;

			if (shopEditHelper::arraysWithSubArraysAreEqual($current_site_config, $source_config))
			{
				continue;
			}

			$auth_config[$site->name] = $source_config;
			$affected_site_ids[$site->id] = $site->id;
		}

		waUtils::varExportToFile($auth_config, $auth_config_path); // todo backup

		return array(
			'source_site_id' => $this->source_site->id,
			'source_site_name' => $this->source_site->name,
			'destination_site_selection' => $destination_site_selection->assoc(),
			'affected_site_ids' => $affected_site_ids,
			'affected_sites_count' => count($affected_site_ids),
		);
	}

	protected function getAction()
	{
		return $this->action_options->SITE_COPY_AUTH_CONFIG;
	}
}