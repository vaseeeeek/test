<?php

abstract class shopRegionsOptimizer
{
	private $text;
	/** @var shopRegionsViewBuffer */
	private $view;

	public function __construct(shopRegionsViewBuffer $view)
	{
		$this->text = null;
		$this->view = $view;
	}

	final public function execute()
	{
		if ($this->preCheck())
		{
			$this->applyReplacer();

			if ($this->checkText())
			{
				$this->optimize();
			}
		}
	}

	protected function preCheck()
	{
		return true;
	}

	protected function getTemplate()
	{
		return '';
	}

	protected function getReplacer()
	{
		return null;
	}

	abstract protected function optimize();

	final protected function getText()
	{
		return $this->text;
	}

	final protected function getTemplateView()
	{
		return $this->view;
	}

	private function applyReplacer()
	{
		$legacy_replacer = $this->getReplacer();

		if ($legacy_replacer instanceof shopRegionsIReplacer)
		{
			$this->view->setLegacyReplacer($legacy_replacer);
		}

		$template = $this->getTemplate();

		if ($this->text === null && $template !== null)
		{
			$this->text = $this->view->fetch($template);
		}
	}

	private function checkText()
	{
		return !empty($this->text);
	}
}