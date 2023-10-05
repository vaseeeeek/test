<?php

class shopEditSiteClearHeadJsAction extends shopEditLoggedAction
{
	private $site_selection;

	public function __construct(shopEditSiteSelection $site_selection)
	{
		parent::__construct();
		$this->site_selection = $site_selection;
	}

	protected function execute()
	{
		$site_selection = $this->site_selection;

		$site_storage = new shopEditSiteStorage();

		/** @var shopEditSite[] $sites */
		$sites = array();

		if ($site_selection->mode == shopEditSiteSelection::MODE_ALL)
		{
			$sites = $site_storage->getAll();
		}
		elseif ($site_selection->mode == shopEditSiteSelection::MODE_SELECTED)
		{
			foreach ($site_selection->site_ids as $site_id)
			{
				$site = $site_storage->getById($site_id);
				if ($site)
				{
					$sites[] = $site;
				}
			}
		}


		$affected_site_ids = array();
		foreach ($sites as $site)
		{
			$current_head_js = $site->head_js;
			if ($current_head_js === null || strlen(trim($current_head_js)) == 0)
			{
				continue;
			}

			$site->head_js = '';
			$site_storage->store($site);

			$affected_site_ids[$site->id] = $site->id;
		}

		return array(
			'site_selection' => $this->site_selection->assoc(),
			'affected_site_ids' => $affected_site_ids,
			'affected_sites_count' => count($affected_site_ids),
		);
	}

	protected function getAction()
	{
		return $this->action_options->SITE_CLEAR_HEAD_JS;
	}
}