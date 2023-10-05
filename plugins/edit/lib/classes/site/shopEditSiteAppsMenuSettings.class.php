<?php

class shopEditSiteAppsMenuSettings
{
	const MODE_AUTO = 'mode_auto';
	const MODE_MANUAL = 'mode_manual';

	public $site_selection;
	public $apps_menu_mode = self::MODE_AUTO;
	public $apps_menu_elements = array();

	public function __construct($params = null)
	{
		if (!is_array($params))
		{
			$this->site_selection = new shopEditSiteSelection();

			return;
		}

		$this->site_selection = new shopEditSiteSelection($params['site_selection']);
		$this->apps_menu_mode = $params['apps_menu_mode'];

		$this->apps_menu_elements = $params['apps_menu_elements'];
	}

	public function assoc()
	{
		return array(
			'site_selection' => $this->site_selection->assoc(),
			'apps_menu_mode' => $this->apps_menu_mode,
			'apps_menu_elements' => $this->apps_menu_elements,
		);
	}
}