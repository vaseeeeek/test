<?php


class shopRegionsCityMetaTitleOptimizer extends shopRegionsMetaTitleOptimizer
{
	protected function getTemplate()
	{
		$settings = new shopRegionsSettings();

		return $settings->meta_title;
	}

	protected function getReplacer()
	{
		return new shopRegionsCityReplacesSet($this->getTemplateView());
	}
}