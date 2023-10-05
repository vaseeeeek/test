<?php

class shopEditSeoPluginHelper extends shopEditAbstractSeoPluginHelper
{
	/** @var shopEditAbstractSeoPluginHelper */
	private $version_helper = null;

	public function __construct()
	{
		$this->version_helper = $this->getVersionHelper();
	}

	public function isPluginInstalled()
	{
		return $this->version_helper->isPluginInstalled();
	}

	public function isPluginEnabled()
	{
		return $this->version_helper->isPluginEnabled();
	}

	public function getStorefrontGroups()
	{
		return $this->version_helper->getStorefrontGroups();
	}

	public function getActionFormVersion()
	{
		return $this->version_helper->getActionFormVersion();
	}

	public function deleteCategoryPersonalMeta($meta_fields, shopEditSeoStorefrontSelection $storefront_selection)
	{
		return $this->version_helper->deleteCategoryPersonalMeta($meta_fields, $storefront_selection);
	}

	public function deleteProductPersonalMeta($meta_fields, shopEditSeoStorefrontSelection $storefront_selection)
	{
		return $this->version_helper->deleteProductPersonalMeta($meta_fields, $storefront_selection);
	}

	public function getCategoryMoveMetaTagsAction(shopEditCategoryMoveMetaTagsFormState $settings)
	{
		return $this->version_helper->getCategoryMoveMetaTagsAction($settings);
	}


	private function getVersionHelper()
	{
		$info = self::getPluginInfoRaw();
		if ($info === array())
		{
			return new shopEditSeoPluginHelperNotEnabled();
		}

		$version = ifset($info['version']);

		if (version_compare($version, '2.22', '>=') && version_compare($version, '3', '<'))
		{
			return new shopEditSeoPluginHelperVersion2();
		}

		if (version_compare($version, '3', '>=') && version_compare($version, '4', '<'))
		{
			return new shopEditSeoPluginHelperVersion3();
		}

		return new shopEditSeoPluginHelperNotEnabled();
	}
}