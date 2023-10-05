<?php

// todo rename to shopEditSiteCopyAppsMenuFormState
class shopEditCopySiteSettings
{
	public $source_site_id = 0;
	public $destination_site_selection;

	public function __construct($params)
	{
		$this->source_site_id = $params['source_site_id'];
		$this->destination_site_selection = new shopEditSiteSelection($params['destination_site_selection']);
	}

	public function assoc()
	{
		return array(
			'source_site_id' => $this->source_site_id,
			'destination_site_selection' => $this->destination_site_selection->assoc(),
		);
	}
}