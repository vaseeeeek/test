<?php

class shopBrandBrandReviewsActionTemplate extends shopBrandActionTemplate
{
	/** @return string */
	protected function getPluginTemplateFileName()
	{
		return 'BrandReviews.html';
	}

	/** @return string */
	protected function getThemeTemplateFileName()
	{
		return 'brand_plugin_brand_page_reviews.html';
	}

	/** @return string|false */
	protected function getPluginCssFileName()
	{
		return 'reviews.css';
	}

	/** @return string|false */
	protected function getPluginJsFileName()
	{
		return 'reviews.js';
	}
}