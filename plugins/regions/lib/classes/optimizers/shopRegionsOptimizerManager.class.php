<?php

class shopRegionsOptimizerManager
{
	const TYPE_CITY_TITLE = 'CITY_TITLE';
	const TYPE_CITY_DESCRIPTION = 'CITY_DESCRIPTION';
	const TYPE_CITY_KEYWORDS = 'CITY_KEYWORDS';
	const TYPE_PAGE = 'PAGE';

	private static $optimizers = array();

	private $context;

	public function __construct(shopRegionsPluginContext $context)
	{
		$this->context = $context;
	}

	/**
	 * @return shopRegionsCityMetaTitleOptimizer
	 */
	public function getMetaTitleOptimizer()
	{
		return $this->getOptimizer(self::TYPE_CITY_TITLE);
	}

	/**
	 * @return shopRegionsCityMetaDescriptionOptimizer
	 */
	public function getMetaDescriptionOptimizer()
	{
		return $this->getOptimizer(self::TYPE_CITY_DESCRIPTION);
	}

	/**
	 * @return shopRegionsCityMetaKeywordsOptimizer
	 */
	public function getMetaKeywordsOptimizer()
	{
		return $this->getOptimizer(self::TYPE_CITY_KEYWORDS);
	}

	/**
	 * @return shopRegionsPageOptimizer
	 */
	public function getPageOptimizer()
	{
		return $this->getOptimizer(self::TYPE_PAGE);
	}


	private function getOptimizer($type)
	{
		if (!array_key_exists($type, self::$optimizers))
		{
			self::$optimizers[$type] = $this->createOptimizer($type);
		}

		return self::$optimizers[$type];
	}

	private function createOptimizer($type)
	{
		$view = $this->context->getTemplateView();

		if ($type == self::TYPE_CITY_TITLE)
		{
			return new shopRegionsCityMetaTitleOptimizer($view);
		}

		if ($type == self::TYPE_CITY_DESCRIPTION)
		{
			return new shopRegionsCityMetaDescriptionOptimizer($view);
		}

		if ($type == self::TYPE_CITY_KEYWORDS)
		{
			return new shopRegionsCityMetaKeywordsOptimizer($view);
		}

		if ($type == self::TYPE_PAGE)
		{
			return new shopRegionsPageOptimizer($view);
		}

		throw new waException("Неизвестный тип оптимизатора [{$type}]");
	}
}