<?php

class shopBrandAddBrandReviewActionTemplate extends shopBrandActionTemplate
{
	/** @return string|false */
	protected function getPluginTemplateFileName()
	{
		return 'AddBrandReview.html';
	}

	/** @return string */
	protected function getThemeTemplateFileName()
	{
		return 'brand_plugin_add_brand_review.html';
	}

	protected function getPluginCssFileName()
	{
		return false;
	}

	protected function getPluginJsFileName()
	{
		return false;
	}

	protected function getThemeJsFileName()
	{
		return 'brand_plugin_add_brand_review.js';
	}
}