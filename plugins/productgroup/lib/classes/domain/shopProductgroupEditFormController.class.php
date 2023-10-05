<?php

class shopProductgroupEditFormController
{
	public function runSaveAction($product_id, $product_groups)
	{
		$group_storage = shopProductgroupPluginContext::getInstance()->getGroupStorage();
		$product_group_storage = shopProductgroupPluginContext::getInstance()->getProductGroupStorage();
		$group_ids = [];

		$affected_product_ids = [
			$product_id => $product_id,
		];

		foreach ($product_groups as $product_group_assoc)
		{
			$group_id = $product_group_assoc['group_id'];
			if (!isset($group_id))
			{
				continue;
			}

			$group = $group_storage->getById($group_id);

			if (!isset($group))
			{
				continue;
			}

			if ($product_group_assoc['id'] > 0)
			{
				$product_group = $product_group_storage->getById($product_group_assoc['id']);
			}
			else
			{
				$product_group = new shopProductgroupProductGroup();
			}

			if (!isset($product_group))
			{
				continue;
			}

			$product_group_storage->loadProducts($product_group);

			$product_group->setGroup($group);
			$products = array();

			$product_sort = 0;

			foreach ($product_group_assoc['products'] as $group_product_assoc)
			{
				$_product = new shopProduct($group_product_assoc['product_id']);

				if (!$_product['id'])
				{
					continue;
				}

				$other_product_groups = $product_group_storage->getByProductId($_product['id']);
				foreach ($other_product_groups as $other_product_group)
				{
					if ($other_product_group->getGroup()->id != $product_group->getGroup()->id)
					{
						continue;
					}

					if ($other_product_group->getId() == $product_group->getId())
					{
						continue;
					}

					$product_group_storage->loadProducts($other_product_group);
					$products = array_merge($products, $other_product_group->getProducts());
					$product_group_storage->delete($other_product_group);
				}

				$product = new shopProductgroupGroupProduct();
				$product->setLabel($group_product_assoc['label']);
				$product->setIsPrimary($group_product_assoc['is_primary']);
				$product->setProduct($_product->getData());
				$product->setSort($product_sort++);

				$products[] = $product;
			}

			$product_group->setProducts($products);

			$product_group_storage->store($product_group);
			$group_ids[] = $product_group->getId();

			foreach ($products as $product)
			{
				$id = $product->getProduct()['id'];
				$affected_product_ids[$id] = $id;
			}
		}

		foreach ($product_group_storage->getByProductId($product_id) as $product_group)
		{
			if (!in_array($product_group->getId(), $group_ids))
			{
				$product_group_storage->delete($product_group);
			}
		}

		$this->clearCache($affected_product_ids);

		return [
			'product_groups' => $this->getProductGroups($product_id),
		];
	}

	public function getState($product_id)
	{
		$product = new shopProduct($product_id);
		if (!$product->id)
		{
			throw new waException();
		}

		/** @var waSystemConfig $wa_app_config */
		$wa_app_config = wa()->getConfig();
		$backend_url = $wa_app_config->getBackendUrl(true);

		return [
			'product_groups' => $this->getProductGroups($product_id),

			'product' => $product->getData(),
			'groups' => $this->getGroups(),

			'backend_url' => $backend_url,
		];
	}

	private function getProductGroups($product_id)
	{
		$product_group_storage = shopProductgroupPluginContext::getInstance()->getProductGroupStorage();
		$product_groups = $product_group_storage->getByProductId($product_id);
		$product_groups_assoc = [];

		foreach ($product_groups as $i => $product_group)
		{
			$product_group_storage->loadProducts($product_group);
			$product_groups_assoc[$i] = $product_group->toArray();
		}

		return $product_groups_assoc;
	}

	private function clearCache(array $product_ids)
	{
		$products_groups_cache = new shopProductgroupProductsGroupsCache();

		$products_groups_cache->clearProductsGroups($product_ids);
	}

	private function getGroups()
	{
		$group_storage = shopProductgroupPluginContext::getInstance()->getGroupStorage();
		$group_settings_storage = shopProductgroupPluginContext::getInstance()->getGroupSettingsStorage();

		$groups_assoc = [];
		foreach ($group_storage->getAll() as $group)
		{
			$group_assoc = $group->toAssoc();

			$group_assoc['scope_settings'] = [];
			foreach (shopProductgroupGroupSettingsScope::getScopes() as $scope)
			{
				$group_assoc['scope_settings'][] = [
					'scope' => $scope,
					'settings' => $group_settings_storage->getGroupScopeSettings($group->id, $scope)->toAssoc()
				];
			}

			$groups_assoc[] = $group_assoc;
		}

		return $groups_assoc;
	}
}
