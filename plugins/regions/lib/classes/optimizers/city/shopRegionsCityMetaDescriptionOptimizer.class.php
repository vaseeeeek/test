<?php


class shopRegionsCityMetaDescriptionOptimizer extends shopRegionsMetaDescriptionOptimizer
{
	protected function getTemplate()
	{
		$settings = new shopRegionsSettings();

		return $settings->meta_description;
	}

	protected function getReplacer()
	{
		return new shopRegionsCityReplacesSet($this->getTemplateView());
	}
}