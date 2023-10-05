<?php

class shopProductgroupWaProductGroupStorage implements shopProductgroupProductGroupStorage
{
	private $group_storage;

	private $product_model;

	private $product_group_model;
	private $product_group_product_model;

	public function __construct(shopProductgroupGroupStorage $group_storage)
	{
		$this->group_storage = $group_storage;

		$this->product_model = new shopProductModel();

		$this->product_group_model = new shopProductgroupProductGroupModel();
		$this->product_group_product_model = new shopProductgroupProductGroupProductModel();
	}

	/**
	 * @param $id
	 * @return shopProductgroupProductGroup
	 */
	public function getById($id)
	{
		$row = $this->product_group_model->getById($id);

		if (!isset($row))
		{
			return null;
		}

		return $this->toProductGroup($row);
	}
	
	/**
	 * @param $product_id
	 * @return shopProductgroupProductGroup[]
	 */
	public function getByProductId($product_id)
	{
		$query = $this->product_group_product_model
			->select('product_group_id')
			->where('product_id = :product_id', ['product_id' => $product_id])
			->query();
		$result = [];

		foreach ($query as $row)
		{
			$group_row = $this->product_group_model->getById($row['product_group_id']);
			if (!isset($group_row))
			{
				continue;
			}

			$product_group = $this->toProductGroup($group_row);
			if (!isset($product_group))
			{
				continue;
			}

			$result[] = $product_group;
		}

		return $result;
	}

	public function store(shopProductgroupProductGroup $product_group)
	{
		if ($product_group->getId())
		{
			$this->product_group_model->updateById($product_group->getId(), array(
				'group_id' => $product_group->getGroup()->id,
			));
		}
		else
		{
			$id = $this->product_group_model->insert(array(
				'group_id' => $product_group->getGroup()->id,
			));
			$product_group->setId($id);
		}

		$products = $product_group->getProducts();

		if (!isset($products))
		{
			return;
		}

		$this->product_group_product_model->deleteByField('product_group_id', $product_group->getId());

		foreach ($products as $product)
		{
			$array_product = $product->getProduct();
			$product_id = $array_product['id'];

			$this->product_group_product_model->insert([
				'product_group_id' => $product_group->getId(),
				'product_id' => $product_id,
				'label' => $product->getLabel(),
				'is_primary' => $product->isPrimary() ? '1' : '0',
				'sort' => $product->getSort(),
			], waModel::INSERT_IGNORE);
		}
	}

	public function delete(shopProductgroupProductGroup $product_group)
	{
		if ($product_group->getId())
		{
			$this->product_group_model->deleteById($product_group->getId());
		}

		$products = $product_group->getProducts();

		if (!isset($products))
		{
			return;
		}

		$this->product_group_product_model->deleteByField('product_group_id', $product_group->getId());
	}

	public function loadProducts(shopProductgroupProductGroup $product_group)
	{
		$query = $this->product_group_product_model
			->select('*')
			->where('product_group_id = :product_group_id', ['product_group_id' => $product_group->getId()])
			->order('sort')
			->query();

		$products = [];
		foreach ($query as $row)
		{
			$product = $this->toProduct($row);

			if (!isset($product))
			{
				continue;
			}

			$products[] = $product;
		}

		$product_group->setProducts($products);
	}

	public function handleProductsDelete($product_ids_to_delete)
	{
		if (count($product_ids_to_delete) === 0)
		{
			return;
		}

		$affected_product_group_ids = $this->product_group_product_model->getProductGroupIdsByProduct($product_ids_to_delete);

		$this->product_group_product_model->deleteByField([
			'product_id' => $product_ids_to_delete,
		]);

		foreach ($affected_product_group_ids as $product_group_id)
		{
			$remaining_products_count = $this->product_group_product_model->countProductsInProductGroup($product_group_id);

			if ($remaining_products_count === 0)
			{
				$this->product_group_model->deleteByField([
					'id' => $product_group_id,
				]);
			}
		}
	}

	private function toProductGroup($row)
	{
		$group = $this->group_storage->getById($row['group_id']);

		if (!isset($group))
		{
			return null;
		}

		$product_group = new shopProductgroupProductGroup();
		$product_group->setId($row['id']);
		$product_group->setGroup($group);

		return $product_group;
	}
	
	private function toProduct($row)
	{
		$array_product = $this->product_model->getById($row['product_id']);
		if (!isset($array_product))
		{
			return null;
		}

		$product = new shopProductgroupGroupProduct();
		$product->setProduct($array_product);
		$product->setLabel($row['label']);
		$product->setIsPrimary($row['is_primary'] === '1');

		return $product;
	}
}