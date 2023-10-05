<?php

abstract class shopRegionsReplacesSet implements shopRegionsIReplacer
{
	protected $view;

	public function __construct(shopRegionsViewBuffer $view)
	{
		$this->view = $view;
	}

	public function fetch($template)
	{
		$result = $template;

		foreach ($this->getReplaces() as $replacer)
		{
			if ($replacer instanceof shopRegionsIReplacer)
			{
				$result = $replacer->fetch($result);
			}
		}

		return $result;
	}

	public function toSmarty($template)
	{
		$result = $template;

		foreach ($this->getReplaces() as $replacer)
		{
			if ($replacer instanceof shopRegionsIReplacer)
			{
				$result = $replacer->toSmarty($result);
			}
		}

		return $result;
	}

	abstract public function getReplaces();
}