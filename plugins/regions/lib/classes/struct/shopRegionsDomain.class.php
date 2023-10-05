<?php


class shopRegionsDomain
{
	private static $domains = array();
	private static $all_domains = false;

	/** @var string */
	private $name;
	/** @var int */
	private $id;

	public function __construct($name)
	{
		$name = strtolower($name);

		if (!array_key_exists($name, self::$domains))
		{
			wa('site');
			$model = new siteDomainModel();
			self::$domains[$name] = $model->getByField('name', $name);
		}

		$this->name = $name;
		$this->id = self::$domains[$name]
			? self::$domains[$name]['id']
			: 0;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getRoutes()
	{
		return wa()->getRouting()->getRoutes($this->name);
	}

	public function updateRoutes($routes)
	{
		$path = $this->getConfigRoutingPath();
		$domains = array();

		if (file_exists($path))
		{
			$domains = include($path);
		}

		if (isset($domains[$this->name]))
		{
			$exists_routes = array();

			foreach ($domains[$this->name] as $idx => $route)
			{
				if (!isset($exists_routes[$route['url']]))
				{
					$exists_routes[$route['url']] = $idx;
				}
			}

			foreach ($routes as $route)
			{
				if (isset($exists_routes[$route['url']]))
				{
					$idx = $exists_routes[$route['url']];
					$domains[$this->name][$idx] = $route;
				}
				else
				{
					$domains[$this->name][] = $route;
				}
			}
		}
		else
		{
			$domains[$this->name] = $routes;
		}

		wa()->getRouting()->setRoutes($domains);
		waUtils::varExportToFile($domains, $path);
	}

	public function getConfigRoute($app, $route)
	{
		$routes = $this->getRoutes();

		foreach ($routes as $_config)
		{
			if (ifset($_config['app']) == $app and ifset($_config['url']) == $route)
			{
				return $_config;
			}
		}

		return null;
	}

	public function updateConfigRoute($app, $route, $config)
	{
		$routes = $this->getRoutes();
		$route_is_found = false;

		foreach ($routes as $i => $_config)
		{
			if (ifset($_config['app']) == $app and ifset($_config['url']) == $route)
			{
				$route_is_found = true;
				$routes[$i] = $config;
				break;
			}
		}

		if (!$route_is_found)
		{
			$routes[] = $config;
		}

		$this->updateRoutes($routes);
	}

	public function getHead()
	{
		$config = $this->getConfig();

		return ifset($config['head_js']);
	}

	public function updateHead($head)
	{
		$config = $this->getConfig();
		$config['head_js'] = $head;
		$this->updateConfig($config);
	}

	public function getRobotsTxt()
	{
		$path = $this->getRobotsTxtPath();

		if (!file_exists($path))
		{
			file_put_contents($path, '');
		}

		return file_get_contents($path);
	}

	public function updateRobotsTxt($robots_txt)
	{
		$path = $this->getRobotsTxtPath();

		file_put_contents($path, $robots_txt);
	}

	public function createClone($new_domain_name, $selected_apps)
	{
		$new_domain = new self($new_domain_name);
		$new_domain->create();

		$new_domain->updateConfig($this->getConfig());
		$new_domain->updateAuthConfig($this->name, $this->getAuthConfig());

		$routes_filtered = array();
		foreach ($this->getRoutes() as $idx => $route)
		{
			if (in_array(ifset($route['app']), $selected_apps))
			{
				$routes_filtered[$idx] = $route;
			}
		}

		$new_domain->updateRoutes($routes_filtered);

		$new_domain->cloneDataDir($this);
		$new_domain->generateRobots($this);

		return $new_domain;
	}

	public function getIndexRouteByUrl($url)
	{
		$routes = $this->getRoutes();

		foreach ($routes as $idx => $route)
		{
			if (ifset($route['url']) == $url)
			{
				return $idx;
			}
		}

		return null;
	}

	public function create($title = '', $style = '')
	{
		wa('site');

		$model = new siteDomainModel();
		$domain = $model->getByField('name', $this->name);
		if (!$domain)
		{
			$id = $model->insert(array(
				'name' => strtolower($this->name),
				'title' => $title,
				'style' => $style,
			));

			if ($id)
			{
				$this->id = $id;
			}
		}
		else
		{
			$this->id = $domain['id'];
		}
	}

	private function getConfig()
	{
		$path = $this->getDomainConfigPath();

		if (file_exists($path))
		{
			return include($path);
		}

		return array();
	}

	private function updateConfig(array $config)
	{
		$domain_config_path = $this->getDomainConfigPath();
		waUtils::varExportToFile($config, $domain_config_path);
	}

	private function getAuthConfig()
	{
		$auth_config_path = $this->getAuthConfigPath();

		if (file_exists($auth_config_path))
		{
			$config = include($auth_config_path);

			return $config;
		}

		return array();
	}

	private function updateAuthConfig($old_domain, $config)
	{
		$auth_config_path = $this->getAuthConfigPath();

		if (!isset($config[$old_domain]))
		{
			return;
		}

		$config[$this->name] = $config[$old_domain];
		waUtils::varExportToFile($config, $auth_config_path);
	}

	private function cloneDataDir(shopRegionsDomain $source_domain)
	{
		$files = new shopRegionsWaFiles();

		$files->copy($source_domain->getDataPath(), $this->getDataPath());
	}

	private function generateRobots(shopRegionsDomain $source_domain)
	{
		$source_robots = new shopRegionsDomainRobots($source_domain->getName());
		$robots = new shopRegionsDomainRobots($this->getName());

		$template = '';
		if ($source_robots->isCustom())
		{
			$template = $source_robots->getTemplate();
		}

		$robots->save($template);
	}

	private function getDomainConfigPath()
	{
		return wa('site')->getConfig()->getConfigPath('domains/' . $this->name . '.php');
	}

	private function getAuthConfigPath()
	{
		return wa()->getConfigPath() . '/auth.php';
	}

	private function getDataPath()
	{
		return wa('site')->getDataPath('data/' . $this->name . '/', true, 'site');
	}

	private function getRobotsTxtPath()
	{
		return $this->getDataPath(). 'robots.txt';
	}

	private function getConfigRoutingPath()
	{
		return wa('site')->getConfig()->getPath('config', 'routing');
	}

	public static function getDomainRow($name)
	{
		if (array_key_exists($name, self::$domains))
		{
			return self::$domains[$name];
		}

		self::loadAllDomains();

		return ifset(self::$all_domains[$name]);
	}

	public static function getAllDomains()
	{
		self::loadAllDomains();

		return self::$all_domains;
	}

	private static function loadAllDomains()
	{
		if (self::$all_domains === false)
		{
			wa('site');
			$domain_model = new siteDomainModel();
			self::$all_domains = $domain_model->select('*')->fetchAll('name');
		}
	}
}