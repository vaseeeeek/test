<?php


class shopRegionsCityOptimizerSet extends shopRegionsOptimizerSet
{
	protected function getOptimizers()
	{
		$context = new shopRegionsPluginContext();

		$optimizer_manager = new shopRegionsOptimizerManager($context);

		return array(
			$optimizer_manager->getMetaTitleOptimizer(),
			$optimizer_manager->getMetaKeywordsOptimizer(),
			$optimizer_manager->getMetaDescriptionOptimizer(),
		);
	}
}