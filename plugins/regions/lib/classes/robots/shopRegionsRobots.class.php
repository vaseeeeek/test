<?php

abstract class shopRegionsRobots
{
	const DOMAIN_TEMPLATE_VARIABLE = '{storefront_url}';

	protected $domain;
	protected $template;
	protected $path;

	public function __construct($domain)
	{
		$this->domain = $domain;
		$this->path = $this->getTemplatePath();
		$this->template = $this->loadTemplate();
	}

	abstract public function save($robots_content);

	public function getTemplate()
	{
		return str_replace("\r\n", "\n", $this->template);
	}

	/**
	 * @return bool|string
	 */
	abstract public function getTemplateBackup();

	protected function loadTemplate()
	{
		return implode('', file_exists($this->path) ? file($this->path) : array());
	}

	protected function getTemplatePath()
	{
		return wa()->getDataPath('data/' . $this->domain . '/', true, 'site') . 'robots.txt';
	}

	protected function applyTemplate($domain = null)
	{
		return str_replace(
			self::DOMAIN_TEMPLATE_VARIABLE,
			is_null($domain) ? $this->domain : $domain,
			$this->getTemplate()
		);
	}
}