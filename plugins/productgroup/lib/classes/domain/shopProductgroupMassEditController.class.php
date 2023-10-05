<?php

class shopProductgroupMassEditController
{
	private $product_group_model;
	private $product_group_product_model;

	public function __construct()
	{
		$this->product_group_model = new shopProductgroupProductGroupModel();
		$this->product_group_product_model = new shopProductgroupProductGroupProductModel();
	}

	public function getDialogState($products_selection)
	{
		$group_storage = shopProductgroupPluginContext::getInstance()->getGroupStorage();

		$groups = $group_storage->getAll();
		$products = $this->getProducts($products_selection);

		return [
			'groups' => $this->getGroupsAssoc($groups),
			'product_groups' => $this->getProductsGroups($groups, $products),
		];
	}

	/**
	 * @param $group_id
	 * @param $group_products
	 * @throws Exception
	 */
	public function addProductsToGroup($group_id, $group_products)
	{
		list($updates, $main_product_group_id, $product_group_ids_to_merge) = $this->collectProductGroupProductUpdates($group_id, $group_products);

		if (!$main_product_group_id)
		{
			$main_product_group_id = $this->product_group_model->insert([
				'group_id' => $group_id,
			]);
		}

		$this->executeProductGroupProductsUpdates($main_product_group_id, $updates);

		$this->moveProductsToProductGroup($main_product_group_id, $product_group_ids_to_merge, $group_id, $group_products);

		$primary_product_id = null;
		foreach ($updates as $update)
		{
			if ($update['is_primary'])
			{
				$primary_product_id = $update['product_id'];
			}
		}
		$this->updateProductGroupPrimaryProduct($main_product_group_id, $primary_product_id);
	}

	/**
	 * @param shopProductgroupGroup[] $groups
	 * @return array
	 */
	private function getGroupsAssoc(array $groups)
	{
		$group_settings_storage = shopProductgroupPluginContext::getInstance()->getGroupSettingsStorage();

		$groups_assoc = [];
		foreach ($groups as $group)
		{
			$group_assoc = $group->toAssoc();

			$scope_settings = [];
			foreach (shopProductgroupGroupSettingsScope::getScopes() as $scope)
			{
				$settings = $group_settings_storage->getGroupScopeSettings($group->id, $scope);
				$scope_settings[] = [
					'scope' => $scope_settings,
					'settings' => $settings->toAssoc(),
				];
			}

			$group_assoc['scope_settings'] = $scope_settings;

			$groups_assoc[] = $group_assoc;
		}

		return $groups_assoc;
	}

	/**
	 * @param shopProductgroupGroup[] $groups
	 * @param array $products
	 * @return array
	 * @throws Exception
	 */
	private function getProductsGroups($groups, $products)
	{
		$product_groups_assoc = [];
		foreach ($groups as $group)
		{
			$product_group = new shopProductgroupProductGroup();

			$product_group_product_model = new shopProductgroupProductGroupProductModel();
			$existing_group_products_data = $product_group_product_model->query('
SELECT pgp.*
FROM shop_productgroup_product_group_product AS pgp
	JOIN shop_productgroup_product_group AS pg
		ON pgp.product_group_id = pg.id
	JOIN shop_productgroup_group AS g
		ON pg.group_id = g.id
WHERE g.id = :group_id
', ['group_id' => $group->id])
				->fetchAll('product_id');

			$group_products = [];
			foreach ($products as $product)
			{
				$group_product = new shopProductgroupGroupProduct();
				$group_product->setProduct($product);

				$data = ifset($existing_group_products_data, $product['id'], null);

				if (is_array($data))
				{
					$group_product->setLabel($data['label']);
					$group_product->setIsPrimary($data['is_primary'] == '1');
					$group_product->setSort($data['sort']);
				}
				else
				{
					$group_product->setLabel('');
					$group_product->setIsPrimary(false);
					$group_product->setSort(0);
				}

				$group_products[] = $group_product;
			}

			$product_group->setGroup($group);
			$product_group->setProducts($group_products);

			$product_groups_assoc[] = $product_group->toArray();
		}

		return $product_groups_assoc;
	}

	private function getProducts($products_selection)
	{
		$products = [];

		$product_ids = $products_selection['product_ids'];
		$products_hash = $products_selection['hash'];

		if (is_array($product_ids) && count($product_ids) > 0)
		{
			$product_model = new shopProductModel();
			$products = $product_model
				->select('id,name')
				->where('id IN (:ids)', ['ids' => $product_ids])
				->fetchAll();
		}
		elseif (is_string($products_hash))
		{
			$collection = new shopProductsCollection($products_hash);
			$products = $collection->getProducts('id,name', 0, 100500);
		}

		return $products;
	}

	private function collectProductGroupProductUpdates($group_id, $group_products)
	{
		$product_ids = [];

		foreach ($group_products as $group_product)
		{
			$product_ids[$group_product['product_id']] = $group_product['product_id'];
		}

		if (count($product_ids) === 0)
		{
			throw new Exception('Нет ни одного товара');
		}

		$query_params = ['group_id' => $group_id, 'product_ids' => array_keys($product_ids)];
		$existing_product_group_products = $this->product_group_model->query('
SELECT pgp.*
FROM shop_productgroup_product_group_product AS pgp
	JOIN shop_productgroup_product_group AS pg
		ON pgp.product_group_id = pg.id
	JOIN shop_productgroup_group AS g
		ON pg.group_id = g.id
WHERE g.id = :group_id AND pgp.product_id IN (:product_ids)
',
			$query_params
		)
			->fetchAll('product_id');

		$main_product_group_id = null;
		$product_group_ids_to_merge = [];
		$updates = [];


		foreach ($group_products as $group_product)
		{
			if (array_key_exists($group_product['product_id'], $existing_product_group_products))
			{
				$existing_data = $existing_product_group_products[$group_product['product_id']];
				$product_group_id = $existing_data['product_group_id'];

				if (!$main_product_group_id)
				{
					$main_product_group_id = $product_group_id;
				}

				if ($product_group_id != $main_product_group_id)
				{
					$product_group_ids_to_merge[$product_group_id] = $product_group_id;
				}

				$update = $existing_data;
				$update['label'] = $group_product['label'];
				$update['is_primary'] = $group_product['is_primary'];

				$updates[] = $update;
			}
			else
			{
				$updates[] = [
					'product_group_id' => null,
					'product_id' => $group_product['product_id'],
					'label' => $group_product['label'],
					'is_primary' => $group_product['is_primary'],
				];
			}
		}

		return [$updates, $main_product_group_id, $product_group_ids_to_merge];
	}

	private function executeProductGroupProductsUpdates($main_product_group_id, $updates)
	{
		$sort = 0;

		foreach ($updates as $update)
		{
			if ($update['product_group_id'] > 0)
			{
				$this->product_group_product_model->updateByField([
					'product_group_id' => $update['product_group_id'],
					'product_id' => $update['product_id'],
				], [
					'product_group_id' => $main_product_group_id,
					'product_id' => $update['product_id'],
					'label' => $update['label'],
					'is_primary' => $update['is_primary'] ? '1' : '0',
					'sort' => $sort++,
				]);
			}
			else
			{
				$this->product_group_product_model->insert([
					'product_group_id' => $main_product_group_id,
					'product_id' => $update['product_id'],
					'label' => $update['label'],
					'is_primary' => $update['is_primary'] ? '1' : '0',
					'sort' => $sort++,
				]);
			}
		}
	}

	private function moveProductsToProductGroup($main_product_group_id, array $product_group_ids_to_merge, $group_id, $group_products)
	{
		foreach ($product_group_ids_to_merge as $product_group_id)
		{
			try
			{
				$this->product_group_product_model->updateByField([
					'product_group_id' => $product_group_id,
				], [
					'product_group_id' => $main_product_group_id,
				]);

				$this->product_group_model->deleteByField('id', $product_group_id);
			}
			catch (Exception $e)
			{
				waLog::dump([
					'message' => 'Не удалось объединить группы',
					'group_id' => $group_id,
					'group_products' => $group_products,
					'main_product_group_id' => $main_product_group_id,
					'product_group_id' => $product_group_id,
				], 'productgroup_error.log');
			}
		}
	}

	private function updateProductGroupPrimaryProduct($main_product_group_id, $primary_product_id)
	{
		if (!$primary_product_id)
		{
			$primary_product_id = $this->product_group_product_model
				->select('product_id')
				->where('product_group_id = :product_group_id', ['product_group_id' => $main_product_group_id])
				->where('is_primary = \'1\'')
				->order('sort')
				->fetchField();
		}

		if ($primary_product_id)
		{
			$this->product_group_product_model->updateByField(['product_group_id' => $main_product_group_id,], ['is_primary' => '1',]);

			$this->product_group_product_model->updateByField([
				'product_group_id' => $main_product_group_id,
				'product_id' => $primary_product_id,
			], [
				'is_primary' => '1',
			]);
		}
	}
}