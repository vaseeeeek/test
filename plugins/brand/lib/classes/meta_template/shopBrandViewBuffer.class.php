<?php


class shopBrandViewBuffer
{
	protected $view;
	protected $is_support_old_template = true;

	public function __construct()
	{
		$this->view = new waSmarty3View(wa(), array(
			'compile_id' => 'brand',
		));

		$smarty_plugins_dir = wa()->getAppPath('plugins/brand/lib/smarty-plugins', 'shop');
		$this->view->smarty->addPluginsDir($smarty_plugins_dir);
		$this->view->smarty->caching = false;
	}

	public function assign($name, $value = null)
	{
		$this->view->assign($name, $value);
	}

	public function getVars($name = null)
	{
		return $this->view->getVars($name);
	}

	public function fetch($template)
	{
		if (strpos($template, '{') === false)
		{
			return $template;
		}

		try
		{
			$result = @$this->view->fetch('string:' . $template);

			if (strlen($result) === 0)
			{
				return " ";
			}

			return $result;
		}
		catch (Exception $e)
		{
			return '(!) ' . $template;
		}
	}

	public function fetchAll(array $templates)
	{
		foreach ($templates as $i => $template)
		{
			$templates[$i] = $this->fetch($template);
		}

		return $templates;
	}

	public function fetchTemplateLayout(shopBrandTemplateLayout $template_layout)
	{
		return new shopBrandFetchedLayout($this->fetchAll($template_layout->getTemplates()));
	}

	public function clearAllAssign()
	{
		$this->view->clearAllAssign();
	}
}