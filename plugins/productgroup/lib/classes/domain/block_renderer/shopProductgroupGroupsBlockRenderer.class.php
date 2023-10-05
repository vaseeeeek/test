<?php

class shopProductgroupGroupsBlockRenderer
{
	private $view;

	public function __construct(shopProductgroupView $view)
	{
		$this->view = $view;
	}

	public function renderGroupsBlock($product_id, $theme_id)
	{
		$block_template_path = $this->getProductBlockTemplatePath($theme_id);

		$groups_templates_paths = $this->getGroupsTemplatePaths($theme_id);
		$view_groups = $this->getViewGroups($product_id);

		$this->view->assign([
			'productgroup_scope' => shopProductgroupGroupSettingsScope::PRODUCT,
			'groups' => $view_groups,
			'block_templates_path' => $groups_templates_paths,
			'current_product' => new shopProduct($product_id),
		]);

		return $this->view->fetch($block_template_path);
	}

	/**
	 * @param int $product_id
	 * @param string $theme_id
	 * @param int[] $group_ids
	 * @return mixed
	 */
	public function renderSpecificGroupsBlock($product_id, $theme_id, $group_ids)
	{
		$block_template_path = $this->getProductBlockTemplatePath($theme_id);

		$groups_templates_paths = $this->getGroupsTemplatePaths($theme_id);
		$view_groups = $this->getViewGroups($product_id, $group_ids);

		$this->view->assign([
			'productgroup_scope' => shopProductgroupGroupSettingsScope::PRODUCT,
			'groups' => $view_groups,
			'block_templates_path' => $groups_templates_paths,
			'current_product' => new shopProduct($product_id),
		]);

		return $this->view->fetch($block_template_path);
	}

	public function renderCategoryGroupsBlock($product_id, $category_products, $theme_id)
	{
		$category_block_template_path = $this->getProductBlockTemplatePath($theme_id);

		$groups_templates_paths = $this->getGroupsTemplatePaths($theme_id);
		$view_groups = $this->getViewCategoryGroups($product_id, $category_products);

		$this->view->assign([
			'productgroup_scope' => shopProductgroupGroupSettingsScope::CATEGORY,
			'groups' => $view_groups,
			'block_templates_path' => $groups_templates_paths,
			'current_product' => new shopProduct($product_id),
		]);

		return $this->view->fetch($category_block_template_path);
	}

	public function renderSpecificCategoryGroupsBlock($product_id, $category_products, $theme_id, array $group_ids)
	{
		$category_block_template_path = $this->getProductBlockTemplatePath($theme_id);

		$groups_templates_paths = $this->getGroupsTemplatePaths($theme_id);
		$view_groups = $this->getViewCategoryGroups($product_id, $category_products, $group_ids);

		$this->view->assign([
			'productgroup_scope' => shopProductgroupGroupSettingsScope::CATEGORY,
			'groups' => $view_groups,
			'block_templates_path' => $groups_templates_paths,
			'current_product' => new shopProduct($product_id),
		]);

		return $this->view->fetch($category_block_template_path);
	}

	/**
	 * @param int $product_id
	 * @param int[]|null $group_ids
	 * @return array
	 * @throws waDbException
	 * @throws waException
	 */
	private function getViewGroups($product_id, $group_ids = null)
	{
		if (is_array($group_ids))
		{
			if (count($group_ids) === 0)
			{
				return [];
			}
		}

		$collection = new shopProductgroupWaProductsGroupsCollection([$product_id], shopProductgroupGroupSettingsScope::PRODUCT);

		$products_groups = $collection->getProductsGroups();
		$product_groups = is_array($group_ids)
			? $this->filterByGroupIds($products_groups[$product_id], $group_ids)
			: $products_groups[$product_id];

		$product_groups_compiler = new shopProductgroupViewProductGroupsCompiler();

		return $product_groups_compiler->getGroups($product_groups);
	}

	/**
	 * @param int $product_id
	 * @param array[] $category_products
	 * @param int[]|null $group_ids
	 * @return shopProductgroupProductProductsGroup[]
	 * @throws waDbException
	 * @throws waException
	 */
	private function getViewCategoryGroups($product_id, $category_products, $group_ids = null)
	{
		if (is_array($group_ids) && count($group_ids) === 0)
		{
			return [];
		}

		$category_product_ids = [];
		foreach ($category_products as $p)
		{
			$category_product_ids[$p['id']] = $p['id'];
		}
		$category_product_ids = array_keys($category_product_ids);

		$cache = new shopProductgroupProductsGroupsCache();

		$products_groups = $cache->getProductsGroups($category_product_ids);
		if (!array_key_exists($product_id, $products_groups))
		{
			$product_ids_to_load = [$product_id => $product_id];
			foreach ($category_product_ids as $id)
			{
				if (!array_key_exists($id, $products_groups))
				{
					$product_ids_to_load[$id] = $id;
				}
			}

			$collection = new shopProductgroupWaProductsGroupsCollection($product_ids_to_load, shopProductgroupGroupSettingsScope::CATEGORY);

			$new_products_groups = $collection->getProductsGroups();

			$cache->storeProductsGroups($product_ids_to_load, $new_products_groups);

			foreach ($new_products_groups as $id => $groups)
			{
				$products_groups[$id] = $groups;
			}
		}

		$product_groups = is_array($group_ids)
			? $this->filterByGroupIds($products_groups[$product_id], $group_ids)
			: $products_groups[$product_id];

		$product_groups_compiler = new shopProductgroupViewProductGroupsCompiler();

		return $product_groups_compiler->getGroups($product_groups);
	}

	private function getProductBlockTemplatePath($theme_id)
	{
		$context = shopProductgroupPluginContext::getInstance();

		$template_registry = $context->getMarkupTemplateRegistry();
		$template_path_registry = $context->getMarkupTemplatePathRegistry();

		$groups_block_template = $template_registry->getGroupsBlockTemplate($theme_id);

		return $template_path_registry->getTemplatePath($groups_block_template);
	}

	private function getGroupsTemplatePaths($theme_id)
	{
		$context = shopProductgroupPluginContext::getInstance();

		$template_registry = $context->getMarkupTemplateRegistry();
		$template_path_registry = $context->getMarkupTemplatePathRegistry();

		$simple_group_template = $template_registry->getSimpleGroupTemplate($theme_id);
		$photo_group_template = $template_registry->getPhotoGroupTemplate($theme_id);
		$color_group_template = $template_registry->getColorGroupTemplate($theme_id);

		return [
			shopProductgroupMarkupTemplateId::SIMPLE_GROUP => $template_path_registry->getTemplatePath($simple_group_template),
			shopProductgroupMarkupTemplateId::PHOTO_GROUP => $template_path_registry->getTemplatePath($photo_group_template),
			shopProductgroupMarkupTemplateId::COLOR_GROUP => $template_path_registry->getTemplatePath($color_group_template),
		];
	}

	/**
	 * @param shopProductgroupProductProductsGroup[] $product_groups
	 * @param int[] $group_ids
	 * @return shopProductgroupProductProductsGroup[]
	 */
	private function filterByGroupIds($product_groups, array $group_ids)
	{
		$product_groups_filtered = [];
		foreach ($product_groups as $product_group)
		{
			if (in_array($product_group->group->id, $group_ids))
			{
				$product_groups_filtered[] = $product_group;
			}
		}

		return $product_groups_filtered;
	}
}