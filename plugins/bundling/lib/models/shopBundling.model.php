<?php

class shopBundlingModel extends waModel
{
	protected $table = 'shop_bundling_bundles';
	
	public function __construct($type = null, $writable = false)
	{
		parent::__construct($type, $writable);
		$this->products_model = new shopBundlingProductsModel();
	}
	
	public static function getCategoriesForProduct($product_id)
	{
		$category_products_model = new shopCategoryProductsModel();
		if(is_array($product_id)) {
			$categories = array();
			$i = 0;
			foreach($category_products_model->query("SELECT category_id FROM `{$category_products_model->getTableName()}` WHERE product_id IN (s:product_ids)", array(
				'product_ids' => $product_id
			))->fetchAll() as $row) {
				if(!isset($categories[$row['category_id']]))
					$i = 0;
				
				$categories[$row['category_id']] = ++$i;
			}
		} else
			$categories = explode(',', $category_products_model->query("SELECT GROUP_CONCAT(category_id) AS `category_ids` FROM `{$category_products_model->getTableName()}` WHERE product_id = ?", $product_id)->fetchField('category_ids'));
		
		return $categories;
	}
	
	public static function getTypeForProduct($product_id)
	{
		$product_model = new shopProductModel();
		if(is_array($product_id)) {
			$types = array();
			$i = 0;
			foreach($product_model->query("SELECT type_id FROM `{$product_model->getTableName()}` WHERE id IN (s:product_ids)", array(
				'product_ids' => $product_id
			))->fetchAll() as $row) {
				if(!isset($types[$row['type_id']]))
					$i = 0;
				
				$types[$row['type_id']] = ++$i;
			}
			
			return $types;
		} else
			return $product_model->query("SELECT type_id FROM `{$product_model->getTableName()}` WHERE id = {$product_id}")->fetchField('type_id');
	}
	
	public static function getFeaturesForProduct($product_id)
	{
		$product_features_model = new shopProductFeaturesModel();
		if(is_array($product_id)) {
			$features = array();
			$i = 0;
			foreach($product_features_model->query("SELECT CONCAT(feature_id, '-', feature_value_id) AS feature FROM `{$product_features_model->getTableName()}` WHERE product_id IN (s:product_ids)", array(
				'product_ids' => $product_id
			))->fetchAll() as $row) {
				if(!isset($features[$row['feature']]))
					$i = 0;
				
				$features[$row['feature']] = ++$i;
			}
			
			return $features;
		} else
			return explode(',', $product_features_model->query("SELECT GROUP_CONCAT(CONCAT(feature_id, '-', feature_value_id)) as `features` FROM `{$product_features_model->getTableName()}` WHERE product_id = {$product_id}")->fetchField('features'));
	}
	
	public function getCategoriesForProductWithNames($product_id)
	{
		$category_products_model = new shopCategoryProductsModel();
		$category_model = new shopCategoryModel();
		return $category_products_model->query("SELECT cp.category_id AS `id`, c.name AS `name` FROM `{$category_products_model->getTableName()}` AS cp LEFT JOIN `{$category_model->getTableName()}` AS c ON c.id = cp.category_id WHERE cp.product_id = ?", $product_id)->fetchAll('id');
	}
	
	public function getBundleGroups($product_id)
	{
		$product = new shopProduct($product_id);
		
		return array_keys($this->query("SELECT `id` FROM `{$this->getTableName()}` WHERE `product_id` = i:product_id OR `type_id` = i:type_id OR `category_id` IN (s:category_ids) OR CONCAT(feature_id, '-', feature_value) IN (s:features)", array(
			'product_id' => $product_id,
			'type_id' => $product['type_id'],
			'category_ids' => $this->getCategoriesForProduct($product_id),
			'features' => $this->getFeaturesForProduct($product_id)
		))->fetchAll('id'));
	}
	
	public function getAllBundleGroups($group = true)
	{
		$data = $this->query("SELECT b.*, c.name AS `category_name`, t.name AS `type_name`, f.name AS `feature_name`, fv.value AS `feature_title` FROM `{$this->getTableName()}` AS b LEFT JOIN `shop_category` AS c ON b.category_id = c.id LEFT JOIN `shop_type` AS t ON b.type_id = t.id LEFT JOIN `shop_feature_values_varchar` AS fv ON fv.feature_id = b.feature_id AND fv.id = b.feature_value LEFT JOIN `shop_feature` AS f ON f.id = b.feature_id WHERE `product_id` IS NULL ORDER BY b.sort ASC")->fetchAll('id');
		
		if($group) {
			$_data = array();
			foreach($data as $id => &$row) {
				$by = (!is_null($row['feature_id']) && !is_null($row['feature_value'])) ? 'feature' : (is_null($row['category_id']) ? 'type' : 'category');

				if($by == 'feature')
					$key = $row['feature_id'] . '-' . $row['feature_value'];
				else
					$key = $row[$by . '_id'];
				
				if(!isset($_data[$by][$key]))
					$_data[$by][$key] = array(
						'name' => $row[$by . '_name'],
						'title' => ifset($row[$by . '_title']),
						'bundles' => array($id => array(
							'title' => $row['title'],
							'multiple' => $row['multiple']
						))
					);
				else
					$_data[$by][$key]['bundles'][$id] = array(
						'title' => $row['title'],
						'multiple' => $row['multiple']
					);
			}

			$data = (gettype($group) == 'string') ? ifset($_data[$group], null) : $_data;
		}
		
		return $data;
	}
	
	public function getBundle($id)
	{
		$id = intval($id);
		return $this->query("SELECT *, CONCAT(feature_id, '-', feature_value) AS feature FROM `{$this->getTableName()}` WHERE id = {$id}")->fetch();
	}
	
	public function getAllBundledProducts($product_id, $where = '')
	{
		if($where)
			$where = 'AND (' . $where . ')';
		
		$products = $this->query("SELECT p.*, CONCAT(p.bundled_product_id, '-', p.sku_id) AS _key FROM `{$this->products_model->getTableName()}` AS p WHERE p.product_id = ? {$where}", intval($product_id))->fetchAll();
		
		return $products;
	}

	public function getBundles($product_id, $delete_if_no_products = false, $category = true, $type = true, $hide_if_not_in_stock = true, $show_type = null, $feature = true, $discounts = true)
	{
		$category_model = new shopCategoryModel();
		$type_model = new shopTypeModel();
		$sort_model = new shopBundlingSortModel();
		
		if(is_array($product_id)) {
			$product_ids = $product_id;
			$type_ids = $this->getTypeForProduct($product_ids);
			$category_ids = $this->getCategoriesForProduct($product_ids);
			$features = $this->getFeaturesForProduct($product_ids);

			$sql = "SELECT b.*, c.name AS `category_name`, t.name AS `type_name`, f.name AS `feature_name`, fv.value AS `feature_title`, CONCAT(b.feature_id, '-', b.feature_value) AS feature FROM `{$this->getTableName()}` AS b LEFT JOIN `{$category_model->getTableName()}` AS c ON b.category_id = c.id LEFT JOIN `{$type_model->getTableName()}` AS t ON b.type_id = t.id LEFT JOIN `shop_feature_values_varchar` AS fv ON fv.feature_id = b.feature_id AND fv.id = b.feature_value LEFT JOIN `shop_feature` AS f ON f.id = b.feature_id";
			
			$data = array();
			if($category_ids || $type_ids || count($product_ids) == 1 || $features) {
				$sql .= " WHERE FALSE";
				
				if($category_ids) {
					$sql .= " OR b.category_id IN (s:category_ids)";
					$data['category_ids'] = array_keys($category_ids);
				}
				
				if($type_ids) {
					$sql .= " OR b.type_id IN (s:type_ids)";
					$data['type_ids'] = array_keys($type_ids);
				}
				
				if(count($product_ids) == 1) {
					$sql .= " OR b.product_id = s:product_id";
					$data['product_id'] = $product_id;
				}
				
				if($features) {
					$sql .= " OR CONCAT(b.feature_id, '-', b.feature_value) IN (s:features)";
					$data['features'] = array_keys($features);
				}
			}
			$bundles = $this->query($sql, $data)->fetchAll('id');

			return array(
				'type_ids' => $type_ids,
				'category_ids' => $category_ids,
				'features' => $features,
				'bundles' => $bundles
			);
		} else {
			if($product_id == 'bundle') {
				$product_id = $delete_if_no_products;
				$delete_if_no_products = false;
				
				$only_bundle = true;
			} else
				$only_bundle = false;
			
			$product_id = intval($product_id);
			
			$product = new shopProduct($product_id);
			$sql = "SELECT b.*, b.sort AS b_sort, c.name AS `category_name`, t.name AS `type_name`, f.name AS `feature_name`, fv.value AS `feature_title`, CONCAT(b.feature_id, '-', b.feature_value) AS feature, s.sort FROM `{$this->getTableName()}` AS b LEFT JOIN `{$category_model->getTableName()}` AS c ON b.category_id = c.id LEFT JOIN `{$type_model->getTableName()}` AS t ON b.type_id = t.id LEFT JOIN `shop_feature_values_varchar` AS fv ON fv.feature_id = b.feature_id AND fv.id = b.feature_value LEFT JOIN `shop_feature` AS f ON f.id = b.feature_id LEFT JOIN `{$sort_model->getTableName()}` AS s ON s.bundle_id = b.id AND s.product_id = {$product_id} WHERE b.product_id = i:product_id";
			
			$data = array(
				'product_id' => $product_id
			);
			
			$category_ids = $this->getCategoriesForProduct($product_id);
			if($category && $category_ids) {
				$sql .= " OR b.category_id IN (s:category_ids)";
				$data['category_ids'] = $category_ids;
			}
			
			if($type && $product['type_id']) {
				$sql .= " OR b.type_id = i:type_id";
				$data['type_id'] = $product['type_id'];
			}
			
			$features = $this->getFeaturesForProduct($product_id);
			if($feature && $features) {
				$sql .= " OR CONCAT(b.feature_id, '-', b.feature_value) IN (s:features)";
				$data['features'] = $features;
			}

			$sql .= " ORDER BY s.sort ASC, b_sort ASC";
			$bundles = $this->query($sql, $data)->fetchAll('id');
			
			if($only_bundle)
				return $bundles;
			
			foreach($bundles as $bundle_id => &$bundle) {
				$bundle['products'] = $this->products_model->getProductsForProductAndBundle($product_id, $bundle_id, $hide_if_not_in_stock, $show_type, $discounts);

				if(!$bundle['products'] && $delete_if_no_products)
					unset($bundles[$bundle_id]);
			}
			
			return $bundles;
		}
	}
}
