<?php

class shopRegionsDomainRobots extends shopRegionsRobots
{
	private $is_null = false;
	/** @var  shopRegionsRobotsOptionModel */
	private $robots_option_model;
	private $robots_options;
	private $backup_path;

	public function __construct($domain)
	{
		parent::__construct($domain);

		$this->is_null = empty($domain);
		$this->backup_path = $this->path . '_backup.txt';

		$model = new shopRegionsRobotsOptionModel();
		$options = $model->getByField('domain', $domain);

		$this->robots_option_model = $model;

		$this->robots_options = array(
			'domain' => is_array($options) && array_key_exists('domain', $options) ? $options['domain'] : $domain,
			'is_custom' => false,
		);
	}

	public function save($template_content)
	{
		$this->template = $template_content;
		$content = $this->applyTemplate();

		$is_custom = true;
		if (empty($template_content))
		{
			$global_template = shopRegionsRobotsFactory::globalTemplate();
			$this->template = $global_template->getTemplate();
			$content = $global_template->applyTemplate($this->domain);

			$is_custom = false;
		}

		$this->robots_options['is_custom'] = $is_custom;

		if (!isset($this->robots_options['robots_last_modified_time']))
		{
			$this->backupOriginalRobots();
		}

		file_put_contents($this->path, $content);
		$this->updateOptions();
	}

	public function getTemplateBackup()
	{
		if (!file_exists($this->backup_path))
		{
			return false;
		}

		return file_get_contents($this->backup_path);
	}

	/**
	 * @return bool
	 */
	public function isCustom()
	{
		if ($this->robots_options['is_custom'])
		{
			return true;
		}

		$global_template = shopRegionsRobotsFactory::globalTemplate();
		if ($this->getTemplate() === $global_template->getTemplate())
		{
			return false;
		}

		clearstatcache();

		if (isset($this->robots_options['robots_last_modified_time'])
			&& filemtime($this->path) > $this->robots_options['robots_last_modified_time'])
		{
			$this->robots_options['is_custom'] = true;
		}

		return $this->robots_options['is_custom'];
	}

	public function isNull()
	{
		return $this->is_null;
	}

	protected function loadTemplate()
	{
		$robots = parent::loadTemplate();

		return str_replace($this->domain, shopRegionsRobots::DOMAIN_TEMPLATE_VARIABLE, $robots);
	}

	private function updateOptions()
	{
		clearstatcache();
		$this->robots_options['robots_last_modified_time'] = filemtime($this->path);

		if ($this->robots_option_model->countByField('domain', $this->domain) > 0)
		{
			$this->robots_option_model->updateByField('domain', $this->domain, $this->robots_options);
		}
		else
		{
			$this->robots_option_model->insert($this->robots_options, waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
		}
	}

	private function backupOriginalRobots()
	{
		$original_content = file_get_contents($this->path);

		if ($original_content !== false)
		{
			file_put_contents($this->backup_path, $original_content);
		}
	}
}