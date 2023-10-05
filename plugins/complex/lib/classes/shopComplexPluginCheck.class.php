<?php

class shopComplexPluginCheck
{
	private $rule_results = array();
	private static $category_tree = array();
	private static $category_model;
	
	public function __construct($product)
	{
		if(is_int($product))
			$product = new shopProduct($product);
		
		$this->product = $product;
	}
	
	public static function currency($value, $frontend = 0, $format = false)
	{
		$currency = wa('shop')->getConfig()->getGeneralSettings('currency');
		$frontend_currency = wa('shop')->getConfig()->getCurrency(false);

		if($currency != $frontend_currency)
			$value = shop_currency($value, $frontend == 0 ? $frontend_currency : $currency, $frontend == 0 ? $currency : $frontend_currency, false);
		
		if($format)
			return self::format($value);
		else
			return $value;
	}
	
	public static function compare($compare, $a, $b) {
		switch($compare) {
			case '==':
			case '=':
				$result = $a == $b;
				break;
			case '!=':
				$result = $a != $b;
				break;
			case '>=':
				$result = $a >= $b;
				break;
			case '<=':
				$result = $a <= $b;
				break;
			case '>':
				$result = $a > $b;
				break;
			case '<':
				$result = $a < $b;
				break;
		}
				
		return $result;
	}
	
	public static function compareStrings($compare, $a, $b)
	{
		return self::compare($compare == '!=' ? '!=' : '=', strval($a), strval($b));
	}
	
	public function getUser()
	{
		if(!isset($this->user))
			$this->user = wa()->getUser();
		
		return $this->user;
	}
	
	public function getUserCategories()
	{
		if ($this->getUser()->getId() === null) {
			return [];
		}

		if(!isset($this->user_categories))
			$this->user_categories = wao(new waContactCategoriesModel())->getContactCategories($this->getUser()->getId());

		return $this->user_categories;
	}
	
	public function getUserRegion()
	{
		if(!isset($this->user_region)) {
			if(!wa()->getUser()->isAuth()) {
				$storage = wa()->getStorage()->get('shop/checkout');
				
				if(!empty($storage['contact']['address']))
					$this->user_region = $storage['contact']['address'];
				else
					$this->user_region = null;
			} else
				$this->user_region = $this->getUser()->get('address');
		}
		
		return $this->user_region;
	}
	
	public function getStorefront()
	{
		if(!isset($this->storefront)) {
			$routing = wa()->getRouting();
			
			$site = $routing->getDomain();
			$url = $routing->getRoute('url');
			foreach($routing->getRoutes($site) as $id => $route)
				if($route['url'] == $url) {
					$route_id = $id;
					break;
				}
			
			$this->storefront = $site . '/' . ifset($route_id);
		}
		
		return $this->storefront;
	}
	
	public function getCart()
	{
		if(!isset($this->cart))
			$this->cart = new shopComplexPluginCart();
		
		return $this->cart;
	}
	
	public function getCartTotal($discount = true)
	{
		$var = $discount ? 'cart_total_with_discount' : 'cart_total_without_discount';
		
		if(!isset($this->$var)) {
			$this->$var = self::currency($this->getCart()->total($discount));
		}

		return $this->$var;
	}
	
	public function getCartItems()
	{
		if(!isset($this->cart_items)) {
			$this->cart_items = $this->getCart()->items();

			// Если это страница изменения корзины, значит корзина еще старая, надо обновить количества товаров
			if (waRequest::param('module') === 'frontendOrderCart' && waRequest::param('action') === 'save') {
				$passed_items = waRequest::post('items', array(), waRequest::TYPE_ARRAY);
				foreach ($passed_items as $passed_item) {
					$id = ifset($passed_item, 'id', null);
					$quantity = ifset($passed_item, 'quantity', null);
					if (!isset($id) || !isset($quantity) || !isset($this->cart_items[$id])) {
						continue;
					}

					$this->cart_items[$id]['quantity'] = (int)$quantity;
				}
			}
		}
		
		return $this->cart_items;
	}

	public function getCartItemsWithCategories()
	{
		if(!isset($this->cart_items_with_categories)) {
			$items = $this->getCartItems();
			$model = new shopCategoryProductsModel();

			foreach($items as &$item) {
				$product_id = $item['product_id'] ? $item['product_id'] : $item['id'];

				$item['categories'] = $model->query("SELECT category_id FROM {$model->getTableName()} WHERE product_id = ?", $product_id)->fetchAll('category_id');
				$item['category_ids'] = array_keys($item['categories']);
				$item['category_ids'] = self::workupCategoryTree($item['category_ids']);
			}

			$this->cart_items_with_categories = $items;
		}

		return $this->cart_items_with_categories;
	}

	private static function getCategoryModel()
	{
		if (!isset(self::$category_model)) {
			self::$category_model = new shopCategoryModel();
		}

		return self::$category_model;
	}

	private static function workupCategoryTree($category_id)
	{
		if (is_array($category_id)) {
			$stack = array();

			foreach ($category_id as $id) {
				$stack = array_merge($stack, self::workupCategoryTree($id));
			}

			return $stack;
		}

		if (!array_key_exists($category_id, self::$category_tree)) {
			$tree = self::getCategoryModel()->getTree($category_id, 0);
			$parent_ids = array();
			foreach ($tree as $item){
				if ($item['parent_id'] != 0) {
					$parent_ids = array_merge($parent_ids, self::workupCategoryTree((int)$item['parent_id']));
				}
			}

			self::$category_tree[$category_id] = array_merge(array($category_id), $parent_ids);
		}

		return self::$category_tree[$category_id];
	}

	public function getCartItemsWithFeatures()
	{
		if(!isset($this->cart_items_with_features)) {
			$items = $this->getCartItems();

			foreach($items as &$item) {
				$product_id = $item['product_id'] ? $item['product_id'] : $item['id'];

				$item['features'] = $this->getProductFeatures($product_id);
			}

			$this->cart_items_with_features = $items;
		}

		return $this->cart_items_with_features;
	}
	
	public function getCartPayment()
	{
		if(!isset($this->cart_payment)) {
			$checkout_data = wa()->getStorage()->get('shop/checkout');
			if (shopComplexPlugin::isSS8Checkout()) {
				$this->cart_payment = ifempty($checkout_data['order']['payment']['id'], false);
			} else {
				$this->cart_payment = ifempty($checkout_data['payment'], false);
			}
		}
		return $this->cart_payment;
	}
	
	public function getCartShipping()
	{
		if(!isset($this->cart_shipping)) {
			$checkout_data = wa()->getStorage()->get('shop/checkout');
			if (shopComplexPlugin::isSS8Checkout()) {
				$data = ifempty($checkout_data['order']['shipping'], array());
				$this->cart_shipping = array(
					'id' => ifset($data['variant_id'], null),
					'rate_id' => ifset($data['rate_id'], null)
				);
			} else {
				$this->cart_shipping = ifempty($checkout_data['shipping'], false);
			}
		}
		
		return $this->cart_shipping;
	}
	
	public function getCartShippingId()
	{
		if($shipping = $this->getCartShipping())
			return $shipping['id'];
		else
			return null;
	}
	
	public function getCartShippingRateId()
	{
		if($shipping = $this->getCartShipping())
			return $shipping['rate_id'];
		else
			return null;
	}
	
	public function getCountOfTotalCartItems()
	{
		if(!isset($this->count_of_total_cart_items)) {
			$items = $this->getCartItems();
			
			foreach($items as $key => $item) {
				if($item['quantity'] > 1)
					for($i = 1; $i < $item['quantity']; $i++)
						$items[$key . '_' . $i] = array();
			}
			
			$this->count_of_total_cart_items = count($items);
		}
		
		return $this->count_of_total_cart_items;
	}
	
	private function checkProductCategoriesForParent($category) {
		$add_categories = array();
		
		if($category['parent_id']) {
			$parent = self::getCategoryModel()->getById($category['parent_id']);
					
			if(!empty($parent['include_sub_categories']))
				array_push($add_categories, $parent['id']);

			if(!empty($parent['parent_id'])) {
				$add_more_categories = $this->checkProductCategoriesForParent($parent);
				$add_categories = array_merge($add_categories, $add_more_categories);
			}
		}

		return $add_categories;
	}
	
	public function getProductCategories()
	{
		if(!isset($this->product_categories)) {
			$categories = self::getCategoryModel()->query("SELECT p.category_id, c.parent_id FROM shop_category_products AS p LEFT JOIN shop_category AS c ON c.id = p.category_id WHERE p.product_id = ?", $this->product['id'])->fetchAll('category_id');

			$this->product_categories = array_keys($categories);
			
			foreach($categories as $category) {
				if($category['parent_id']) {
					$parent = self::getCategoryModel()->getById($category['parent_id']);
					
					if(!empty($parent['include_sub_categories']))
						array_push($this->product_categories, $parent['id']);
					
					foreach($categories as $_category) {
						$add_categories = $this->checkProductCategoriesForParent($_category);
						$this->product_categories = array_merge($this->product_categories, $add_categories);
					}
					
					
				} else
					continue;
			}
		}

		return $this->product_categories;
	}
	
	public function getProductFeatures($id = null)
	{
		if($id === null)
			$id = $this->product['id'];

		if(!isset($this->product_features[$id])) {
			if(!isset($this->feature_model))
				$this->feature_model = new shopFeatureModel();
			if(!isset($this->product_feature_model))
				$this->product_feature_model = new shopProductFeaturesModel();

			$features = array();
			$_features = $this->product_feature_model->getValues($id);
			foreach($_features as $key => &$feature) {
				$feature_params = $this->feature_model->getByCode($key);
				$feature_id = $feature_params['id'];
				$feature_type = $feature_params['type'];
				$feature_is_selectable = $feature_params['selectable'];
				$feature_is_multiple = $feature_params['multiple'];
				
				$features[$feature_id] = array(
					'type' => $feature_type,
					'selectable' => $feature_is_selectable,
					'multiple' => $feature_is_multiple,
					'value_id' => null,
					'value' => $feature,
				);
				
				if($feature_is_selectable && in_array($feature_type, array('varchar', 'double'))) {
					$values_model = $this->feature_model->getValuesModel($feature_type);
					
					if(!is_array($feature)) {
						$value_params = $values_model->getByField(array(
							'feature_id' => $feature_id,
							'value' => $feature
						));
						$features[$feature_id]['value_id'] = $value_params['id'];
					} else {
						foreach($feature as &$feature_value) {
							if(is_string($feature_value)) {
								$value_params = $values_model->getByField(array(
									'feature_id' => $feature_id,
									'value' => $feature_value
								));
								$feature_value = array(
									'value' => $feature_value,
									'value_id' => $value_params['id']
								);
							}
						}
						
						$features[$feature_id]['value'] = $feature;
					}
				}
			}
			
			$this->product_features[$id] = $features;
		}

		return $this->product_features[$id];
	}
	
	private function checkCondition($condition)
	{
		$result = 0;
		
		$field = $condition['field'];
		$value = $condition['value'];
		$compare = ifset($value['compare']);
		$key = ifset($value['key']);
		$condition_value = ifset($value['input']);
		$additional_key = null;

		if(!preg_match('/^-?[0-9]+$/', $condition_value)) {
			$temp_view = wa()->getView();
			try {
				$condition_value = @$temp_view->fetch('string:' . $condition_value);
				$condition_value = intval($condition_value);
			} catch(SmartyCompilerException $e) {
			}
		}

		switch($field) {
			// product start
			case 'product.type':
				$user_value = $this->product['type_id'];
				$condition_value = $value['types'];
				$style = 'compare';
				break;
			case 'product.main_category':
				$user_value = $this->product['category_id'];
				$condition_value = $value['categories'];
				$style = 'compare';
				break;
			case 'product.in_category':
				$user_value = $this->getProductCategories();
				$condition_value = $value['categories'];
				$style = 'in_array';
				break;
			case 'product.feature':
				$features = $this->getProductFeatures();

				$user_value = false;
				$condition_value = $value['feature_value'];
				
				foreach($features as $id => $feature) {
					if($id == $value['feature_key']) {
						$user_value = 0;
						
						if($feature['multiple']) {
							$user_value = array_keys($feature['value']);
							$style = 'in_array';
						} else {
							$user_value = $feature['value_id'];
							$style = 'compare';
						}
					}
				}

				if($user_value === false)
					$style = 'no';
				
				break;
			case 'product.feature_value':
				$features = $this->getProductFeatures();
				
				$user_value = false;
				
				foreach($features as $id => $feature)
					if($id == $value['features']) {
						$type = preg_replace('/\..*$/', '', $feature['type']);
						
						$user_value = 0;
						
						if($feature['multiple']) {
							if($type == 'double')
								foreach($feature['value'] as $_feature_value)
									$user_value += $_feature_value;
							else {
								foreach($feature['value'] as $_feature_value) {
									$user_value += $_feature_value['value_base_unit'];
								}
							}
						} else {
							if($type == 'double')
								$user_value += $feature['value'];
							else {
								$user_value += $feature['value']['value_base_unit'];
							}
						}
						
						$user_value = intval($user_value);
					}

				if($user_value !== false)
					$style = 'compare';
				else
					$style = 'no';
				
				break;
			case 'product.status':
				$user_value = $this->product['status'];
				$style = 'compare';
				break;
			case 'product.create_datetime':
				$user_value = $this->product['create_datetime'];
				$style = 'compare_strings';
				break;
			case 'product.edit_datetime':
				$user_value = $this->product['create_datetime'];
				$style = 'compare_strings';
				break;
			case 'product.rating':
				$user_value = $this->product['rating'];
				$style = 'compare';
				break;
			case 'product.skus_count':
				$user_value = count($this->product['skus']);
				$style = 'compare';
				break;
			case 'product.tags_count':
				$user_value = count($this->product['tags']);
				$style = 'compare';
				break;
			case 'product.any':
				$style = 'yes';
				break;
			// product end
			// user start
			case 'user.category':
				$user_value = array_keys($this->getUserCategories());
				$condition_value = $value['user_categories'];
				
				if($condition_value !== 'any')
					$style = 'in_array';
				else
					$style = 'yes';
				break;
			case 'user.region':
			case 'user.shipping_region':
			case 'user.billing_region':
				$user_value = $this->getUserRegion();
				$condition_value = $value;
				$style = 'region';
				break;
			case 'global.cookie':
			case 'global.session':
			case 'global.server':
			case 'global.get':
			case 'global.post':
				$style = 'no';

				if($key) {
					$style = 'compare';

					if($field === 'global.cookie') {
						$user_value = ifset($_COOKIE, $key, null);
					} elseif($field === 'global.session') {
						$user_value = ifset($_SESSION, $key, null);
					} elseif($field === 'global.server') {
						$user_value = ifset($_SERVER, $key, null);
					} elseif($field === 'global.get') {
						$user_value = ifset($_GET, $key, null);
					} elseif($field === 'global.post') {
						$user_value = ifset($_POST, $key, null);
					} else {
						$style = 'no';
					}

					$condition_value = $value['value'];
				}
				break;
			case 'cart.total':
				$user_value = $this->getCartTotal();
				
				$style = 'compare';
				break;
			case 'cart.total_without_discount':
				$user_value = $this->getCartTotal(false);
				
				$style = 'compare';
				break;
			case 'cart.products.count_of_units':
				$user_value = count($this->getCartItems());
				
				$style = 'compare';
				break;
			case 'cart.products.count':
				$user_value = $this->getCountOfTotalCartItems();
				
				$style = 'compare';
				break;
			case 'cart.products.product_count_of_units':
				$user_value = $this->getCartItems();
				$user_value_field = 'quantity';

				$style = 'compare_with_product';
				break;
			case 'cart.products.count_compares_with_feature':
				$user_value = $this->getCartItemsWithFeatures();
				$condition_value = $value['features'];

				$style = 'count_compares_with_feature';
				break;
			case 'cart.products.in_category_count':
				$user_value = $this->getCartItemsWithCategories();
				$condition_value = $value['categories'];

				$user_value_field = 'category_ids';
				$style = 'count_with_field.in_array';
				break;
			case 'cart.products.with_feature_count':
				$user_value = $this->getCartItemsWithFeatures();
				$condition_value = $value['features'];

				$user_value_field = 'features';
				$style = 'count_with_field.features';
				break;
			case 'cart.products.total_in_category':
				$user_value = $this->getCartItemsWithCategories();
				$condition_value = $value['categories'];
				$user_value_field = 'category_ids';
				$additional_key = 'price';

				$style = 'count_with_field.in_array';
				break;
			case 'cart.products.total_with_type':
				$user_value = $this->getCartItems();
				$condition_value = $value['types'];
				$user_value_field = 'product:type_id';
				$additional_key = 'price';

				$style = 'count_with_field.compare';
				break;
			case 'cart.products.with_type_count':
				$user_value = $this->getCartItems();
				$condition_value = $value['types'];

				$user_value_field = 'product:type_id';
				$style = 'count_with_field.compare';
				break;
			// cart end
			case 'storefront':
				$user_value = $this->getStorefront();
				$condition_value = $value['storefronts'];

				if($condition_value !== 'any')
					$style = 'compare';
				else
					$style = 'yes';
				break;
			case 'shipping':
				$user_value = $this->getCartShippingId();
				
				$condition_value = $value['shipping'];
				if(!empty($value['rates'])) {
					$condition_value .= '_' . $value['rates'];
					$user_value = strval($user_value) . '_' . $this->getCartShippingRateId();
				}
				
				$style = 'compare';
				break;
			case 'payment':
				$user_value = $this->getCartPayment();
				$condition_value = $value['payment'];
				$style = 'compare';
				break;
			case 'yes':				
				$style = 'yes';
				break;
			default:
				$style = 'no';
				break;
		}

		switch($style) {
			case 'yes':
				$result = 1;
				break;
			case 'no':
				$result = 0;
				break;
			case 'compare':
				$a = $user_value;
				$b = $condition_value;
				$result = self::compare($compare, $a, $b);
				
				break;
			case 'in_array':
				$result = in_array($condition_value, $user_value) == ($compare == '!=' ? 0 : 1);
				break;
			case 'feature':
				$sum = 0;
				break;
			case 'region':
				if(is_array($user_value)) {
					$user_values = $user_value;
					foreach($user_values as $user_value) {
						if($field == 'user.region' || ($field == 'user.shipping_region' && $user_value['ext'] == 'shipping') || ($field == 'user.billing_region' && $user_value['ext'] == 'billing')) {
							if(!empty($value['regions'])) {
								$result = $user_value['data']['region'] == $value['regions'] && $user_value['data']['country'] == $value['countries'];
							} else {
								$result = $user_value['data']['country'] == $value['countries'];
							}
						} else
							$result = false;
						
						if($result)
							break;
					}
				} else {
					if($field == 'user.region' || ($field == 'user.shipping_region' && $user_value['ext'] == 'shipping') || ($field == 'user.billing_region' && $user_value['ext'] == 'billing')) {
						if(!empty($value['regions'])) {
							$result = $user_value['data']['region'] == $value['regions'] && $user_value['data']['country'] == $value['countries'];
						} else {
							$result = $user_value['data']['country'] == $value['countries'];
						}
					} else
						$result = false;
				}
				
				if($compare == '!=')
					$result = $result ? false : true;
				
				break;
			case 'compare_with_product':
				$result = 0;

				if(!empty($user_value)) {
					foreach($user_value as $user_value_fields) {
						if(ifset($user_value_fields['product_id']) === $this->product['id']) {
							if(isset($user_value_fields[$user_value_field])) {
								$user_field = (int) $user_value_fields[$user_value_field];

								$result = self::compare($compare, $user_field, intval($value['input']));
							}
						}
					}
				}
				break;
			case 'count_compares_with_feature':
				$result = 0;

				if(!empty($user_value)) {
					foreach($user_value as $user_value_fields) {
						if(ifset($user_value_fields['product_id']) === $this->product['id'] && !empty($user_value_fields['features'])) {
							$q = intval($user_value_fields['quantity']);

							foreach($user_value_fields['features'] as $id => $feature) {
								if($id == $condition_value) {
									$type = preg_replace('/\..*$/', '', $feature['type']);

									$user_feature_value = 0;

									if($feature['multiple']) {
										if($type == 'double')
											foreach($feature['value'] as $_feature_value)
												$user_feature_value += $_feature_value;
										else {
											foreach($feature['value'] as $_feature_value) {
												$user_feature_value += $_feature_value['value_base_unit'];
											}
										}
									} else {
										if($type == 'double')
											$user_feature_value += $feature['value'];
										else {
											$user_feature_value += $feature['value']['value_base_unit'];
										}
									}

									$result = self::compare($compare, $q, intval($user_feature_value));
								}
							}

							break;
						}
					}
				}
				break;
			case 'count_with_field.compare':
			case 'count_with_field.in_array':
			case 'count_with_field.features':
				$additional_key = isset($additional_key) ? $additional_key : 'quantity';

				if(!empty($user_value)) {
					$result = 0;
					$count = 0;

					foreach($user_value as $user_value_fields) {
						$q = (int)$user_value_fields['quantity'];
						$v = $additional_key === 'quantity' ? $q : ((float)$user_value_fields[$additional_key] * $q);

						if(strpos($user_value_field, 'product:') !== false) {
							if(!empty($user_value_fields['product']))
								$user_value_fields_product = $user_value_fields['product'];
							else
								$user_value_fields_product = $user_value_fields;

							$find_in = $user_value_fields_product[substr($user_value_field, strlen('product:'))];
						} else
							$find_in = $user_value_fields[$user_value_field];

						if($style == 'count_with_field.features') {
							foreach($find_in as $id => $feature)
								if($id == $condition_value) {
									$type = preg_replace('/\..*$/', '', $feature['type']);

									$user_feature_value = 0;

									if($feature['multiple']) {
										if($type == 'double')
											foreach($feature['value'] as $_feature_value)
												$user_feature_value += $_feature_value;
										else {
											foreach($feature['value'] as $_feature_value) {
												$user_feature_value += $_feature_value['value_base_unit'];
											}
										}
									} else {
										if($type == 'double')
											$user_feature_value += $feature['value'];
										else {
											$user_feature_value += $feature['value']['value_base_unit'];
										}
									}

									$user_feature_value = intval($user_feature_value);

									$count += $value['value'] == $user_feature_value ? $v : 0;
								}
						} elseif($style == 'count_with_field.in_array' || is_array($find_in)) {
							$count += in_array($condition_value, $find_in) ? $v : 0;
						} else {
							$count += $condition_value == $find_in ? $v : 0;
						}
					}

					$a = intval($count);
					$b = $compare == '==' ? $this->getCountOfTotalCartItems() : intval($value['input']);

					$result = $result || self::compare($compare, $a, $b);
				} else
					$result = 0;
				
				break;
		}

		return $result ? 1 : 0;
	}
	
	public function checkConditions($rule_id, $mode, $conditions)
	{
		if(!isset($this->rule_results[$rule_id])) {
			$result = null;

			if(is_array($conditions)) {
				foreach($conditions as $condition) {
					if($condition['field'] != 'group') {
						$check = $this->checkCondition($condition);

						if(is_null($result))
							$result = $check;
						else {
							if($mode == 'and')
								$result = $result * $check;
							elseif($mode == 'or')
								$result = $result + $check;
						}
					} else {
						$group_result = null;

						if(is_array($condition['value'])) {
							$group_id = $condition['value']['id'];
							$group_conditions = $condition['value']['conditions'];
							$group_mode = $condition['value']['mode'];
						} else {
							$group_id = $condition['value'];
							$group_conditions = $condition['conditions'];
							$group_mode = $condition['mode'];
						}

						$check = $this->checkConditions($rule_id . '/' . $group_id, $group_mode, $group_conditions);

						if(is_null($group_result))
							$group_result = $check;
						else {
							if($group_mode == 'and')
								$group_result = $group_result * $check;
							elseif($group_mode == 'or')
								$group_result = $group_result + $check;
						}

						if(is_null($result))
							$result = $group_result;
						else {
							if($mode == 'and')
								$result = $result * $group_result;
							elseif($mode == 'or')
								$result = $result + $group_result;
						}
					}
				}
			}
			
			$this->rule_results[$rule_id] = $result ? 1 : 0;
		}
		
		return $this->rule_results[$rule_id];
	}
}
