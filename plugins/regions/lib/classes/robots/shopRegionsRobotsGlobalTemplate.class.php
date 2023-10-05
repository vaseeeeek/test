<?php

class shopRegionsRobotsGlobalTemplate extends shopRegionsRobots
{
	private $domains;

	public function __construct($domain)
	{
		parent::__construct($domain);

		$m_domain = new siteDomainModel();
		$this->domains = array();
		foreach ($m_domain->getAll() as $domain)
		{
			$this->domains[] = $domain['name'];
		}
	}

	public function save($template)
	{
		$this->saveForDomains($template, $this->domains);
	}

	public function getTemplateBackup()
	{
		return false;
	}

	public function saveForDomains($template_content, $domains)
	{
		$this->template = $template_content;
		file_put_contents($this->path, $template_content);

		foreach ($domains as $domain)
		{
			$robots = new shopRegionsDomainRobots($domain);
			$robots->save('');
		}
	}

	protected function getTemplatePath()
	{
		return wa()->getDataPath('data/', true, 'site') . 'robots_template.txt';
	}
}