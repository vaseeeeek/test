<?php

class shopEditMetaDeleteAction extends shopEditLoggedAction
{
	private $storefront_storage;

	private $settings;

	public function __construct(shopEditMetaDeleteSettings $settings)
	{
		parent::__construct();

		$this->storefront_storage = new shopEditStorefrontStorage();

		$this->settings = $settings;
	}

	protected function execute()
	{
		if ($this->settings->source_type == shopEditMetaDeleteSettings::SOURCE_TYPE_MAIN_PAGE)
		{
			$this->deleteMainPageMeta();
		}
		elseif ($this->settings->source_type == shopEditMetaDeleteSettings::SOURCE_TYPE_PAGE)
		{
			$this->deletePageMeta();
		}
		elseif ($this->settings->source_type == shopEditMetaDeleteSettings::SOURCE_TYPE_CATEGORY)
		{
			$this->deleteCategoryMeta();
		}
		elseif ($this->settings->source_type == shopEditMetaDeleteSettings::SOURCE_TYPE_PRODUCT)
		{
			$this->deleteProductMeta();
		}

		return array(
			'settings' => $this->settings->assoc(),
		);
	}

	protected function getAction()
	{
		return $this->action_options->META_DELETE;
	}


	private function deleteMainPageMeta()
	{
		$storefronts = $this->getStorefronts();

		foreach ($storefronts as $storefront)
		{
			foreach ($this->settings->fields as $field)
			{
				if (
					$field == shopEditMetaDeleteSettings::FIELD_META_DESCRIPTION
					|| $field == shopEditMetaDeleteSettings::FIELD_META_KEYWORDS
				)
				{
					$storefront->$field = '';
				}
				elseif ($field == shopEditMetaDeleteSettings::FIELD_META_TITLE)
				{
					$storefront->title = '';
				}
			}
		}

		$this->storefront_storage->updateShopStorefronts($storefronts);
	}

	private function deletePageMeta()
	{
		$page_model = new shopPageModel();

		if ($this->settings->storefront_selection->mode == shopEditSeoStorefrontSelection::MODE_SELECTED)
		{
			$storefronts = $this->getStorefronts();

			$page_conditions = array();
			$query_params = array();
			$index = 1;

			foreach ($storefronts as $storefront)
			{
				$page_conditions[] = "(p.domain = :domain_{$index} AND p.route = :route_{$index})";

				$query_params["domain_{$index}"] = $storefront->domain;
				$query_params["route_{$index}"] = $storefront->url;

				$index++;
			}

			if (count($storefronts) == 0)
			{
				return;
			}

			$statement = new waDbStatement($page_model, implode(' OR ', $page_conditions));
			$statement->bindArray($query_params);

			$storefront_where = $statement->getQuery();
		}
		else
		{
			$storefront_where = '';
		}

		foreach ($this->settings->fields as $field)
		{
			if ($field == shopEditMetaDeleteSettings::FIELD_META_TITLE || $field == shopEditMetaDeleteSettings::FIELD_PAGE_CONTENT)
			{
				$column = $field == shopEditMetaDeleteSettings::FIELD_META_TITLE ? 'title' : 'content';

				$update_sql = "
UPDATE shop_page AS p
SET p.{$column} = ''";

				if ($storefront_where !== '')
				{
					$update_sql .= PHP_EOL . 'WHERE ' . $storefront_where;
				}

				$page_model->exec($update_sql);
			}
			elseif ($field == shopEditMetaDeleteSettings::FIELD_META_DESCRIPTION || $field == shopEditMetaDeleteSettings::FIELD_META_KEYWORDS)
			{
				$update_sql = '
UPDATE shop_page_params AS pp, shop_page AS p
SET pp.value = \'\'
WHERE p.id = pp.page_id AND pp.name = :field';

				if ($field == shopEditMetaDeleteSettings::FIELD_META_DESCRIPTION)
				{
					$db_field_name = 'description';
				}
				elseif ($field == shopEditMetaDeleteSettings::FIELD_META_KEYWORDS)
				{
					$db_field_name = 'keywords';
				}
				else
				{
					continue;
				}

				if ($storefront_where !== '')
				{
					$update_sql .= ' AND (' . $storefront_where . ')';
				}

				$page_model->exec($update_sql, array('field' => $db_field_name));
			}
		}
	}

	private function deleteCategoryMeta()
	{
		$meta_fields = $this->getMetaFields();

		if (count($meta_fields) == 0)
		{
			return;
		}

		$seo_helper = new shopEditSeoPluginHelper();

		if (!$this->settings->delete_seo_plugin_data)
		{
			$this->deleteCatalogWaSeoMeta('shop_category', $meta_fields);
		}
		elseif ($this->settings->delete_seo_plugin_data && $seo_helper->isPluginInstalled())
		{
			$seo_helper->deleteCategoryPersonalMeta($meta_fields, $this->settings->storefront_selection);
		}
	}

	private function deleteProductMeta()
	{
		$meta_fields = $this->getMetaFields();

		if (count($meta_fields) == 0)
		{
			return;
		}

		$seo_helper = new shopEditSeoPluginHelper();

		if (!$this->settings->delete_seo_plugin_data)
		{
			$this->deleteCatalogWaSeoMeta('shop_product', $meta_fields);
		}
		elseif ($this->settings->delete_seo_plugin_data && $seo_helper->isPluginInstalled())
		{
			$seo_helper->deleteProductPersonalMeta($meta_fields, $this->settings->storefront_selection);
		}
	}

	private function getMetaFields()
	{
		$fields_filtered = array();
		foreach ($this->settings->fields as $field)
		{
			if (
				$field == shopEditMetaDeleteSettings::FIELD_META_TITLE
				|| $field == shopEditMetaDeleteSettings::FIELD_META_DESCRIPTION
				|| $field == shopEditMetaDeleteSettings::FIELD_META_KEYWORDS
				|| $field == shopEditMetaDeleteSettings::FIELD_DESCRIPTION
			)
			{
				$fields_filtered[] = $field;
			}
		}

		return $fields_filtered;
	}

	private function deleteCatalogWaSeoMeta($wa_table_name, $meta_fields)
	{
		$model = new waModel();

		$set = array();
		foreach ($meta_fields as $field)
		{
			$set[] = "`{$field}` = ''";
		}

		if (count($set) > 0)
		{
			$set_expression = implode(', ', $set);

			$update_sql = "
UPDATE {$wa_table_name}
SET {$set_expression}
";

			$model->exec($update_sql);
		}
	}

	/**
	 * @return shopEditStorefront[]
	 */
	private function getStorefronts()
	{
		if ($this->settings->storefront_selection->mode == shopEditSeoStorefrontSelection::MODE_ALL)
		{
			return $this->storefront_storage->getAllShopStorefronts();
		}
		elseif ($this->settings->storefront_selection->mode == shopEditSeoStorefrontSelection::MODE_SELECTED)
		{
			return $this->storefront_storage->getShopStorefronts($this->settings->storefront_selection->storefronts);
		}
		else
		{
			return array();
		}
	}
}