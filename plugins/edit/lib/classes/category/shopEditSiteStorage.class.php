<?php

class shopEditSiteStorage extends shopEditStorage
{
	//private $auth_config = array();
	private $routing = null;
	private $routing_path;

	public function __construct()
	{
		wa('site');

		//$auth_config_path = wa()->getConfigPath() . '/auth.php';
		//if (file_exists($auth_config_path))
		//{
		//	$auth_config = include($auth_config_path);
		//	if (is_array($auth_config))
		//	{
		//		$this->auth_config = $auth_config;
		//	}
		//}

		$this->routing_path = wa()->getConfigPath() . '/routing.php';
		if (file_exists($this->routing_path))
		{
			$routing = include($this->routing_path);
			if (is_array($routing))
			{
				$this->routing = $routing;
			}
		}

		parent::__construct();
	}

	/**
	 * @return shopEditSite[]
	 */
	public function getAll()
	{
		$sites = array();
		foreach ($this->model->select('id')->query() as $row)
		{
			$site = $this->getById($row['id']);
			if ($site)
			{
				$sites[] = $site;
			}
		}

		return $sites;
	}

	/**
	 * @param $id
	 * @return shopEditSite|null
	 */
	public function getById($id)
	{
		$site = parent::getById($id);

		if (!$site)
		{
			return null;
		}

		$this->extendSite($site);

		return $site;
	}

	/**
	 * @param array|shopEditPropertyAccess $entity
	 * @param null $id
	 * @return int
	 * @throws waException
	 */
	public function store($entity, $id = null)
	{
		$result = parent::store($entity, $id);

		if ($entity instanceof shopEditSite)
		{
			$entity_assoc = $entity->assoc();
		}
		else
		{
			$entity_assoc = $entity;
		}

		$domain = $entity_assoc['name'];

		if (array_key_exists('robots_txt', $entity_assoc))
		{
			$this->storeDomainRobotsTxt($domain, $entity_assoc['robots_txt']);
		}

		if (array_key_exists('domain_config', $entity_assoc))
		{
			$this->storeDomainConfig($domain, $entity_assoc['domain_config']);
		}

		if (array_key_exists('routing', $entity_assoc))
		{
			$this->storeSiteRouting($domain, $entity_assoc['routing']);
		}

		return $result;
	}

	public function routingIsValid()
	{
		return is_array($this->routing);
	}

	/**
	 * @return shopEditIDataFieldSpecification[]
	 */
	protected function accessSpecification()
	{
		$specification = new shopEditDataFieldSpecificationFactory();

		return array(
			'id' => $specification->integer(),
			'name' => $specification->string(),
			'title' => $specification->string(),
		);
	}

	protected function dataModelInstance()
	{
		return new siteDomainModel();
	}

	protected function entityInstance()
	{
		return new shopEditSite();
	}

	private function extendSite(shopEditSite $site)
	{
		$site->domain_config = $this->getDomainConfig($site->name);
		$site->robots_txt = $this->getDomainRobotsTxt($site->name);
		$site->routing = $this->routingIsValid()
			? $this->getSiteRouting($site->name)
			: null;

		//$site->auth_config = $this->getAuthConfig($site->name);
	}


	private function getDomainRobotsTxt($domain)
	{
		$robots_txt = '';

		$robots_txt_path = wa()->getDataPath('data/' . $domain . '/', true, 'site') . 'robots.txt';
		if (file_exists($robots_txt_path))
		{
			$robots_content = file_get_contents($robots_txt_path);
			$robots_txt = $robots_content !== false && is_string($robots_content) && strlen($robots_content) > 0
				? $robots_content
				: '';
		}

		return $robots_txt;
	}

	private function storeDomainRobotsTxt($domain, $new_robots_txt)
	{
		$robots_txt_path = wa()->getDataPath('data/' . $domain . '/', true, 'site') . 'robots.txt';

		waFiles::write($robots_txt_path, $new_robots_txt);
	}

	private function getDomainConfig($domain)
	{
		$domain_config_path = wa('site')->getConfig()->getConfigPath('domains/' . $domain . '.php');
		if (file_exists($domain_config_path))
		{
			$domain_config = include $domain_config_path;

			if (is_array($domain_config))
			{
				return $domain_config;
			}
		}

		return array();
	}

	private function storeDomainConfig($domain, $new_domain_config)
	{
		if (!is_array($new_domain_config))
		{
			return;
		}

		$domain_config_path = wa('site')->getConfig()->getConfigPath('domains/' . $domain . '.php');

		waUtils::varExportToFile($new_domain_config, $domain_config_path);
	}


	private function getSiteRouting($domain)
	{
		return array_key_exists($domain, $this->routing)
			? $this->routing[$domain]
			: null;
	}

	private function storeSiteRouting($domain, $new_routing)
	{
		if (!$this->routingIsValid())
		{
			return;
		}

		if ($new_routing === null)
		{
			unset($this->routing[$domain]);
		}
		else
		{
			$this->routing[$domain] = $new_routing;
		}

		waUtils::varExportToFile($this->routing, $this->routing_path);
	}






	//private function getAuthConfig($domain)
	//{
	//	return array_key_exists($domain, $this->auth_config)
	//		? $this->auth_config[$domain]
	//		: null;
	//}
	//
	//private function storeAuthConfig($domain, $domain_auth_config)
	//{
	//	$auth_config_path = wa()->getConfigPath() . '/auth.php';
	//	if (!file_exists($auth_config_path))
	//	{
	//		return false;
	//	}
	//
	//	$auth_config = include($auth_config_path);
	//	if (!is_array($auth_config))
	//	{
	//		$auth_config = array();
	//	}
	//
	//	$auth_config[$domain] = $domain_auth_config;
	//
	//	waUtils::varExportToFile($auth_config, $auth_config_path); // todo backup
	//
	//	return true;
	//}
}