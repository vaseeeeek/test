<?php

class shopBrandBrandPageActionFactory
{
	/**
	 * @param shopBrandPluginFrontendBrandPageAction $brand_page_action
	 * @return shopBrandBrandPageContentAction
	 * @throws waException
	 */
	public function getPageAction(shopBrandPluginFrontendBrandPageAction $brand_page_action)
	{
		$type_options = new shopBrandPageTypeEnumOptions();

		$action_params[shopBrandBrandPageContentAction::BRAND_PAGE_ACTION_PARAM] = $brand_page_action;

		$page = $brand_page_action->getPage();
		$page_type = $page->type;
		if ($page_type == $type_options->CATALOG)
		{
			$action = new shopBrandBrandCatalogPageContentAction($action_params);
		}
		elseif ($page_type == $type_options->REVIEWS)
		{
			$action = new shopBrandBrandReviewsPageContentAction($action_params);
		}
		elseif ($page_type == $type_options->PAGE)
		{
			$action = new shopBrandBrandInfoPageContentAction($action_params);
		}
		else
		{
			throw new waException("unknown page type [{$page_type}], page id [{$page->id}]");
		}

		return $action;
	}
}