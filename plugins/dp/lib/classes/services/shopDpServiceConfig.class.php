<?php

class shopDpServiceConfig
{
	public function __construct()
	{
		$path = wa()->getAppPath('plugins/dp', 'shop') . '/lib/config/data/services/';
		$this->config = include($path . 'services.php');
	}

	public function get($service_id)
	{
		$service_config = ifset($this->config, $service_id, array());
		$service_config['id'] = $service_id;

		return $service_config;
	}
}