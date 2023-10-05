<?php

class shopEditSeoPluginHelperVersion3 extends shopEditAbstractSeoPluginHelper
{
	const ACTION_FORM_VERSION = '3.0';

	public function isPluginInstalled()
	{
		return true;
	}

	public function isPluginEnabled()
	{
		return shopSeoPlugin::isEnabled();
	}

	public function getStorefrontGroups()
	{
		$storefront_groups = shopSeoContext::getInstance()->getGroupStorefrontService()->getAll();

		return shopSeoContext::getInstance()
			->getGroupStorefrontArrayMapper()
			->mapGroupsStorefronts($storefront_groups);
	}

	public function getActionFormVersion()
	{
		return self::ACTION_FORM_VERSION;
	}

	public function deleteCategoryPersonalMeta($meta_fields, shopEditSeoStorefrontSelection $storefront_selection)
	{
		$this->deleteCatalogPersonalMeta($meta_fields, $storefront_selection, 'shop_seo_category_settings');
	}

	public function deleteProductPersonalMeta($meta_fields, shopEditSeoStorefrontSelection $storefront_selection)
	{
		$this->deleteCatalogPersonalMeta($meta_fields, $storefront_selection, 'shop_seo_product_settings');
	}

	public function getCategoryMoveMetaTagsAction(shopEditCategoryMoveMetaTagsFormState $settings)
	{
		return new shopEditCategoryMoveMetaTagsActionVersion3($settings);
	}

	private function deleteCatalogPersonalMeta($meta_fields, shopEditSeoStorefrontSelection $storefront_selection, $seo_table_name)
	{
		$model = new waModel();

		$query_params = array(
			'fields' => $meta_fields,
		);

		if ($storefront_selection->mode == shopEditSeoStorefrontSelection::MODE_ALL_GROUPS)
		{
			$storefront_where = '';
		}
		elseif ($storefront_selection->mode == shopEditSeoStorefrontSelection::MODE_SELECTED_GROUPS)
		{
			if (count($storefront_selection->storefront_group_ids) == 0)
			{
				return;
			}

			$storefront_where = 'AND group_storefront_id IN (:group_storefront_ids)';
			$query_params['group_storefront_ids'] = $storefront_selection->storefront_group_ids;
		}
		else
		{
			return;
		}

		$sql = "
DELETE FROM `{$seo_table_name}`
WHERE `name` IN (:fields) {$storefront_where}
";

		$model->exec($sql, $query_params);
	}
}