<?php

class shopBundlingCategoriesModel extends waModel
{
	protected $table = 'shop_bundling_categories';

	public $products_model;
	
	public function __construct($type = null, $writable = false)
	{
		parent::__construct($type, $writable);
		$this->products_model = new shopBundlingProductsModel();
	}
	
	public function getUndefinedCategory()
	{
		return $this->query("SELECT * FROM {$this->getTableName()} WHERE category_id = 0")->fetchAssoc();
	}
	
	public function getCategories($key = null)
	{
		return $this->query("SELECT b.*, c.name AS name, c.id AS id FROM `shop_category` AS c LEFT JOIN {$this->getTableName()} AS b ON b.category_id = c.id")->fetchAll($key);
	}

	public function getCategoryAsBundle($id)
	{
		if($id == 0) {
			$category = $this->getUndefinedCategory();
		} else {
			$category = $this->query("SELECT b.*, c.name AS name, c.id AS id FROM `shop_category` AS c LEFT JOIN {$this->getTableName()} AS b ON b.category_id = c.id WHERE c.id = ?", $id)->fetchAssoc();
		}

		return array(
			'id' => $id,
			'multiple' => $category['multiple'],
			'sort' => 0,
			'title' => $id == 0 ? ifempty($category, 'title', _wp('Accessories')) : ifempty($category, 'title', $category['name']),
			'products' => array()
		);
	}

	public function getBundlesWithinCategories($product_id, $delete_if_no_products = false, $hide_if_not_in_stock = false, $show_type = null, $discounts = true)
	{
		$all_products = $this->products_model->getProductsForProductAndBundle($product_id, 0, $hide_if_not_in_stock, $show_type, $discounts, true, false);

		$bundles = array();
		foreach($all_products as $product) {
			$category_id = $product['category_id'];

			if($category_id === null)
				$category_id = 0;

			if(!isset($bundles[$category_id])) {
				$bundles[$category_id] = $this->getCategoryAsBundle($category_id);
			}

			array_push($bundles[$category_id]['products'], $product);
		}

		return $bundles;
	}
	
	public function getCategoriesAsBundles($product_id, $delete_if_no_products = false, $hide_if_not_in_stock = false, $show_type = null, $discounts = true)
	{
		$sql = "(SELECT b.*, c.name AS name, c.id AS id, s.sort AS sort FROM `shop_category` AS c LEFT JOIN shop_bundling_categories AS b ON b.category_id = c.id LEFT JOIN `shop_bundling_sort` AS s ON s.bundle_id = CONCAT('-', c.id) AND s.product_id = {$product_id})
    UNION
    (SELECT b.*, '" . _wp('Accessories') . "' AS name, 0 AS id, s.sort AS sort FROM shop_product LEFT JOIN shop_bundling_categories AS b ON b.category_id = 0 LEFT JOIN `shop_bundling_sort` AS s ON s.bundle_id = '0' AND s.product_id = {$product_id} LIMIT 1)
	ORDER BY sort";
		$bundles = $this->query($sql)->fetchAll();
		
		foreach($bundles as $key => &$bundle) {
			$bundle_id = intval($bundle['id']) * -1;

			$bundle['products'] = $this->products_model->getProductsForProductAndBundle($product_id, $bundle_id, $hide_if_not_in_stock, $show_type, $discounts);

			if(empty($bundle['title']))
				$bundle['title'] = $bundle['name'];

			if(!$bundle['products'] && $delete_if_no_products)
				unset($bundles[$key]);
		}

		return $bundles;
	}
	
	public function getCategoriesAsBundleGroups($product_id)
	{
		$bundle_groups = array_keys($this->query("SELECT CONCAT('-', c.id) AS bundle_id FROM `shop_category` AS c LEFT JOIN {$this->getTableName()} AS b ON b.category_id = c.id")->fetchAll('bundle_id'));
		
		array_push($bundle_groups, 0);
		
		return $bundle_groups;
	}

	/**
	 * @deprecated
	 * @param $product_id
	 * @return array
	 */
	public function getBundledProducts($product_id)
	{
		return $this->query("SELECT p.*, CONCAT('-', sp.category_id) AS bundle_id FROM `shop_bundling_products` AS p LEFT JOIN `shop_product` AS sp ON sp.id = p.bundled_product_id WHERE product_id = ?", $product_id)->fetchAll('bundle_id', 2);
	}
}