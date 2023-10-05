<?php

class shopEditSeoPluginHelperNotEnabled extends shopEditAbstractSeoPluginHelper
{
	public function isPluginInstalled()
	{
		return false;
	}

	public function isPluginEnabled()
	{
		return false;
	}

	public function getStorefrontGroups()
	{
		return array();
	}

	public function getActionFormVersion()
	{
		return null;
	}

	public function deleteCategoryPersonalMeta($meta_fields, shopEditSeoStorefrontSelection $storefront_selection)
	{
	}

	public function deleteProductPersonalMeta($meta_fields, shopEditSeoStorefrontSelection $storefront_selection)
	{
	}

	public function getCategoryMoveMetaTagsAction(shopEditCategoryMoveMetaTagsFormState $settings)
	{
		throw new Exception('Плагин не установлен, либо для текущей версии это действие не реализовано');
	}
}