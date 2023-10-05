<?php


class shopRegionsCityMetaKeywordsOptimizer extends shopRegionsMetaKeywordsOptimizer
{
	protected function getTemplate()
	{
		$settings = new shopRegionsSettings();

		return $settings->meta_keywords;
	}

	protected function getReplacer()
	{
		return new shopRegionsCityReplacesSet($this->getTemplateView());
	}
}