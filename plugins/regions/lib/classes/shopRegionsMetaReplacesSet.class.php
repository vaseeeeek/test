<?php


class shopRegionsMetaReplacesSet extends shopRegionsReplacesSet
{
	public function getReplaces()
	{
		$meta_response = new shopRegionsMetaResponse();

		return array(
			new shopRegionsVariable('title', $meta_response->getMetaTitle()),
			new shopRegionsVariable('meta_keywords', $meta_response->getMetaKeywords()),
			new shopRegionsVariable('meta_description', $meta_response->getMetaDescription()),
		);
	}
}