<?php

class shopRegionsLazyReplacer
{
	private $replacer = null;

	public function __construct(shopRegionsIReplacer $replacer = null)
	{
		$this->replacer = $replacer;
	}

	public function preCompile($source, $template)
	{
		if ($this->replacer !== null)
		{
			return $this->replacer->toSmarty($source);
		}

		return $source;
	}

	public function parseTemplate($string, $template)
	{
		if ($this->replacer !== null)
		{
			return $this->replacer->fetch($string);
		}

		return $string;
	}
}