<?php


class shopRegionsViewBuffer
{
	protected $view;

	public function __construct()
	{
		$this->view = new waSmarty3View(wa('shop'), array(
			'compile_id' => 'regions',
		));

		$smarty_modifiers = new shopRegionsSmartyModifiers;
		$smarty_modifiers->registerModifiers($this->view->smarty);

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
			return $this->view->fetch('string:' . $template);
		}
		catch (SmartyCompilerException  $e)
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

	public function clearAllAssign()
	{
		$this->view->clearAllAssign();
	}

	public function setLegacyReplacer(shopRegionsIReplacer $replacer)
	{
		$lazy_replacer = new shopRegionsLazyReplacer($replacer);
		$this->view->smarty->registerFilter('pre', array($lazy_replacer, 'preCompile'));
		$this->view->smarty->registerFilter('output', array($lazy_replacer, 'parseTemplate'));
	}
}