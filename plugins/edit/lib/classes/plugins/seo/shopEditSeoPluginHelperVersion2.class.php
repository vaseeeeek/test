<?php

class shopEditSeoPluginHelperVersion2 extends shopEditAbstractSeoPluginHelper
{
	const ACTION_FORM_VERSION = '2.22';

	public function isPluginInstalled()
	{
		return true;
	}

	public function isPluginEnabled()
	{
		return shopSeoSettings::isEnablePlugin();
	}

	public function getStorefrontGroups()
	{
		return array();
	}

	public function getActionFormVersion()
	{
		return self::ACTION_FORM_VERSION;
	}

	public function deleteCategoryPersonalMeta($meta_fields, shopEditSeoStorefrontSelection $storefront_selection)
	{
		$this->deleteCatalogPersonalMeta($meta_fields, $storefront_selection, 'shop_seo_template_category');
	}

	public function deleteProductPersonalMeta($meta_fields, shopEditSeoStorefrontSelection $storefront_selection)
	{
		$this->deleteCatalogPersonalMeta($meta_fields, $storefront_selection, 'shop_seo_template_product');
	}

	public function getCategoryMoveMetaTagsAction(shopEditCategoryMoveMetaTagsFormState $settings)
	{
		return new shopEditCategoryMoveMetaTagsActionVersion2($settings);
	}

	private function deleteCatalogPersonalMeta($meta_fields, shopEditSeoStorefrontSelection $storefront_selection, $seo_table_name)
	{
		$model = new waModel();

		$update_query_params = array(
			'fields' => $meta_fields,
		);

		if ($storefront_selection->mode == shopEditSeoStorefrontSelection::MODE_ALL)
		{
			$storefront_where = '';
		}
		elseif ($storefront_selection->mode == shopEditStorefrontSelection::MODE_SELECTED)
		{
			if (count($storefront_selection->storefronts) == 0)
			{
				return;
			}

			$storefront_where = 'AND storefront_id IN (:storefronts)';
			$update_query_params['storefronts'] = $storefront_selection->storefronts;
		}
		else
		{
			return;
		}

		$update_sql = "
UPDATE `{$seo_table_name}`
SET value = ''
WHERE `group_id` = 'data' AND `name` IN (:fields) {$storefront_where}
";
		$model->exec($update_sql, $update_query_params);
	}
}