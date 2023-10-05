<?php

class shopEditSiteSelection
{
	const MODE_ALL = 'mode_all';
	const MODE_SELECTED = 'mode_selected';

	public $mode = self::MODE_ALL;
	public $site_ids = array();

	public function __construct($selection_params = null)
	{
		if (!is_array($selection_params))
		{
			return;
		}

		$this->mode = $selection_params['mode'];
		foreach ($selection_params['site_ids'] as $id => $_)
		{
			$this->site_ids[$id] = $id;
		}
	}

	public function assoc()
	{
		return array(
			'mode' => $this->mode,
			'site_ids' => array_values($this->site_ids),
		);
	}
}