<?php

new shevskySettingsControlsV7('bundling');
class shopBundlingPlugin extends shopPlugin
{
	public function __construct($info)
	{
		parent::__construct($info);
		$this->model = new shopBundlingModel();
	}
	
	public static function getFeatures()
	{
		$feature_model = new shopFeatureModel();
		$features = $feature_model->select('id, name')->where('selectable = 1 AND type="varchar"')->order('id ASC')->fetchAll();

		return $features;
	}
	
	public function getTypes() {
		$type_model = new shopTypeModel();
		$types = $type_model->getTypes(false);
		if(is_array($types)) {
			if(empty($types))
				return false;
			else
				return array_keys($types);
		} elseif(is_bool($types))
			return $types;
		
		return false;
	}
	
	public function getProductIdsByHash($hash) {
		$product_ids = array();
		
		$offset = 0;
		$count = 100;
		
		$types = $this->getTypes();
		
		if($types === false)
			return array( );
		
		$collection = new shopProductsCollection(urldecode($hash));
		if(is_array($types))
			$collection->addWhere('p.type_id IN (' . implode(',', $types) . ')');
		
		$total_count = $collection->count();
		$collection->orderBy('id');
		
		while($offset < $total_count) {
			$products = $collection->getProducts('id', $offset, $count);
			$product_ids = array_merge($product_ids, array_keys($products));
			$offset += count($products);
		}
		
		return $product_ids;
	}
	
	public function getControls($params = array())
	{
		waHtmlControl::registerControl('BundlingBundles', array($this, 'getBundlesControl'));
		$controls = parent::getControls($params);
		
		return shopBundlingDeveloperInformation::init('bundling', ifset($this->info), $controls, array(
			'#ffed63 0%',
			'#fff 160px'
		));
	}
	
	public function backendProduct($product)
	{
		if($this->getSettings('on')) {
			return array(
				'edit_section_li' => '<li><a style="background: #fff28e;" href="?plugin=bundling&action=editProductBundles&id=' . $product->getId() . '">' . _wp('Product Bundle') . '</a></li>',
			);
		}
	}
	
	public function backendProducts()
	{
		if($this->getSettings('on')) {		
			$js = wa()->getAppStaticUrl('shop/plugins/bundling/js') . 'backend.js';
			
			return array(
				'sidebar_top_li' => '<li><a href="?plugin=bundling&action=bundles"><i class="icon16 bundling"></i>' . _wp('Bundles') . '</a></li>',
				'toolbar_organize_li' => '<li><a href="#" onclick="$.bundling.dialog();event.preventDefault();"><i class="icon16 bundling"></i> ' . _wp('Set up Product Bundles') . '</a></li><li><a href="#" onclick="$.bundling.removeAll(\'' . _wp('Are you sure? This action will REMOVE ALL bundles from selected products!') . '\');event.preventDefault()"><i class="icon16 bundling"></i> ' . _wp('Remove all products bundles') . '</a></li> <script type="text/javascript" src="?plugin=bundling&module=loc"></script><script type="text/javascript" src="' . $js . '"></script> <style type="text/css">i.icon16.bundling { background-image: url(\'' . $this->getPluginStaticUrl() . 'img/bundling.png\'); background-size: 16px 16px; } .button.loading { background-image: url(\'' . $this->getPluginStaticUrl() . 'img/loading.gif\'), linear-gradient(#fff, #dedede); }</style>',
			);
		}
	}
	
	public function orderCalculateDiscount($params)
	{
		if($this->getSettings('on') && $this->getSettings('discounts')) {
			$currency = $params['order']['currency'];
			
			$product_keys = array();
			foreach($params['order']['items'] as $id => $item) {
				if($item['type'] == 'product' && !empty($item['product'])) {
					$product_keys[$id] = $item['product_id'];
					$product_keys[$id] .= '-';
					if($item['product']['sku_id'] == $item['sku_id']) {
						$product_keys[$id . '_'] = $product_keys[$id] . '0';
					}
					$product_keys[$id] .= $item['sku_id'];
				}
			}

			$item_discounts = array();
			foreach($product_keys as $item_id => $product_id) {
				if(strpos($product_id, '-') > 0)
					$product_id = substr($product_id, 0, strpos($product_id, '-'));

				$products = $this->model->getAllBundledProducts($product_id, 'p.discount > 0');

				if($products && strpos($item_id, '_') === false) {
					$item_id = intval($item_id);
					
					foreach($products as $bundled_product_data) {
						if(false !== $item_key = array_search($bundled_product_data['_key'], $product_keys)) {
							$item_key = intval($item_key);
							$discount = intval($bundled_product_data['discount']);
							
							if(!empty($params['order']['items'][$item_key]) && $params['order']['items'][$item_key]['type'] == 'product') {
								$q = intval($params['order']['items'][$item_key]['quantity']);
								if($q >= 1) {
									$price = $params['order']['items'][$item_key]['price'];
									$new_price = $price * (100 - $discount) / 100;
									
									$round_style = wa('shop')->getPlugin('bundling')->getSettings('rounding');
									if($round_style)
										$new_price = shopRounding::round($new_price, $round_style, false);

									$item_discounts[$item_key] = array(
										'discount' => ($price - $new_price) * $q,
										'description' => _wp('Discount') . ' ' . $discount . _wp('% for bundle') . ' <a href="?plugin=bundling&action=editProductBundles&id=' . $product_id . '">' . ifset($params['order']['items'][$item_id]['product']['name']) .'</a>'
									);
								}
							}
						}
					}
				}
			}
			
			if($item_discounts)
				return array(
					'items' => $item_discounts
				);
		}
	}
	
	public function getBundlesControl($name, $params = array())
	{
		$by = $name == 'shop_bundling[bundles_category]' ? 'category' : 'type';
		$bundles = $this->model->getAllBundleGroups($by);
		
		$view = wa()->getView();
		$view->assign('by', $by);
		$view->assign('bundles', $bundles);
		
		$category_model = new shopCategoryModel();
		$type_model = new shopTypeModel();
		$view->assign('options', $by == 'category' ? $category_model->getFullTree() : $type_model->getTypes());
		
		$checked = $params['value'] == 1 ? ' checked' : '';
		return '<label><input name="' . $name . '" type="checkbox" value="1"' . $checked . '/> </label><div class="bundling-bundle ' . $by . '" style="margin-top: 10px;display: ' . ($params['value'] == 1 ? 'block' : 'none') . ';">' . $view->fetch('wa-apps/shop/plugins/bundling/templates/bundles.html') . '</div>';
	}
	
	public function frontendProduct($params)
	{
		if($this->getSettings('on') && $this->getSettings('integration')) {
			$place = $this->getSettings('place');
			$type = $this->getSettings('type');
			$bundling = shopBundling::getBundling($params, $type, false);
			
			$output = $bundling;
			
			if($this->getSettings('integrate_your_bundle')) {
				$your_bundle = shopBundling::getYourBundle($params);
				
				switch($this->getSettings('integrate_your_bundle')) {
					case 'before':
						$output = $your_bundle . $output;
						break;
					case 'after':
						$output = $output . $your_bundle;
						break;
					case 'after+before':
						$output = $your_bundle . $output . $your_bundle;
						break;
				}
			}
			
			$output .= $this->includeCss();
			
			return array(
				$place => $output
			);
		}
	}
	
	public function getBundles($product_id, $hide_if_not_in_stock = true, $type = null, $discounts = true, $bundle_groups = 'custom')
	{
		if($bundle_groups == 'custom')
			$bundles = $this->model->getBundles($product_id, true, true, true, $hide_if_not_in_stock, $type, true, $discounts);
		else {
			$model = new shopBundlingCategoriesModel();
			$bundles = $model->getBundlesWithinCategories($product_id, true, $hide_if_not_in_stock, $type, $discounts);
		}
		
		return $bundles;
	}
	
	public function includeCss()
	{
		$view = wa()->getView();
		$view->assign('plugin_url', $this->getPluginStaticUrl());
		
		return $view->fetch('string:<style type="text/css">' . $this->getSettings('css') . '</style>');
	}
}
