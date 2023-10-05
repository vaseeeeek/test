<?php

abstract class shopEditAbstractSeoPluginHelper extends shopEditAbstractPluginHelper
{
	abstract public function getStorefrontGroups();

	abstract public function getActionFormVersion();

	abstract public function deleteCategoryPersonalMeta($meta_fields, shopEditSeoStorefrontSelection $storefront_selection);

	abstract public function deleteProductPersonalMeta($meta_fields, shopEditSeoStorefrontSelection $storefront_selection);

	/**
	 * @param shopEditCategoryMoveMetaTagsFormState $settings
	 * @return shopEditCategoryMoveMetaTagsAction
	 */
	abstract public function getCategoryMoveMetaTagsAction(shopEditCategoryMoveMetaTagsFormState $settings);

	public function getPluginId()
	{
		return 'seo';
	}

	public function getPluginInfoExtended()
	{
		$info = parent::getPluginInfoExtended();

		$info['action_form_version'] = $this->getActionFormVersion();

		return $info;
	}
}