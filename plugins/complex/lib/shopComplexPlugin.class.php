<?php

new shevskySettingsControlsV7('complex');
class shopComplexPlugin extends shopPlugin
{
	private static $instance;
	private static $product_instance;

	public function __construct($info)
	{
		parent::__construct($info);

		self::$instance = $this;
	}

	public static function getInstance()
	{
		if (!isset(self::$instance)) {
			self::$instance = wa('shop')->getPlugin('complex');
		}

		return self::$instance;
	}

	private static function getProductInstance()
	{
		if (!isset(self::$product_instance)) {
			self::$product_instance = new shopComplexPluginProduct(array(
				'settings' => self::getInstance()->getSettings()
			));
		}

		return self::$product_instance;
	}

	public function getControls($params = array())
	{		
		waHtmlControl::registerControl('Types', array($this, 'getTypesControl'));
		waHtmlControl::registerControl('Transfer', array($this, 'getTransferControl'));
		waHtmlControl::registerControl('AvailablePrices', array($this, 'getAvailablePricesControl'));
		
		$controls = parent::getControls($params);
		return $controls;
	}
	
	public function getControlsInstance()
	{
		if(!isset($this->controls))
			$this->controls = new shopComplexPluginControls();
		
		return $this->controls;
	}

	public function routing($route = array())
	{
		if(!$this->getSettings('on'))
			return false;

		$routing = array(
			'complex-plugin/availablePricesTableStylesheet/' => 'frontend/availablePricesTableStylesheet'
		);

		return $routing;
	}
	
	public static function getTransferPlugins()
	{
		$plugins = array();
		
		if(shopComplexPluginTransfer::getPricePluginPriceParams(true))
			$plugins[] = 'price';
		
		if(shopComplexPluginTransfer::getOptPluginPriceParams(true))
			$plugins[] = 'opt';
		
		return $plugins;
	}
	
	public function getTypesControl($name, $params = array())
	{
		$rule_model = new shopComplexPluginRuleModel();
		$rule_model->deleteNotUsingRules();
		
		$price_model = new shopComplexPluginPriceModel();
		$prices = $price_model->getPrices();
		$price_model->workupPriceRules($prices);
		
		$view = wa()->getView();
		$view->assign('plugin_url', $this->getPluginStaticUrl());
		$view->assign('name', $name);
		$view->assign('params', $params);
		$view->assign('prices', $prices);
		
		$view->assign('transfer', $this->getSettings('transfer') ? self::getTransferPlugins() : null);

		$control = $view->fetch(wa()->getAppPath('plugins/complex/templates/controls/types.html', 'shop'));
		
		return $control;
	}
	
	public function getTransferControl($name, $params = array())
	{		
		$view = wa()->getView();
		$view->assign('plugin_url', $this->getPluginStaticUrl());
		$view->assign('name', $name);
		
		$view->assign('transfer', self::getTransferPlugins());

		$control = $view->fetch(wa()->getAppPath('plugins/complex/templates/controls/transfer.html', 'shop'));
		
		return $control;
	}
	
	public function getAvailablePricesControl($name, $params = array())
	{
		$view = wa()->getView();
		$view->assign('plugin_url', $this->getPluginStaticUrl());
		$view->assign('name', $name);
		
		$price_model = new shopComplexPluginPriceModel();
		$prices = $price_model->getPrices();
		$price_model->workupPriceRules($prices);
		
		$view->assign('prices', $prices);
		
		$view->assign('value', ifset($params['value']));
		
		$control = $view->fetch(wa()->getAppPath('plugins/complex/templates/controls/availablePrices.html', 'shop'));
		
		return $control;
	}
	
	public function backendOrderEdit($order)
	{
		if($this->getSettings('on') && $this->getSettings('backend_order')) {
			$price_model = new shopComplexPluginPriceModel();
			$prices = $price_model->getPrices(true);
			
			if(!$prices)
				return;
			
			$view = wa()->getView();
			$view->assign('plugin_url', $this->getPluginStaticUrl());
			$view->assign('prices', $prices);
			$view->assign('order', $order);
			
			$control = $view->fetch(wa()->getAppPath('plugins/complex/templates/controls/orderEdit.html', 'shop'));
			
			return $control;
		}
	}
	
	public function backendProductEdit($product)
	{
		if($this->getSettings('on') && $this->getSettings('toggle_prices')) {
			$price_model = new shopComplexPluginPriceModel();
			$prices = $price_model->getPrices(true);
			
			$view = wa()->getView();
			$view->assign('prices', $prices);
			$view->assign('product', $product);
			
			$control = $view->fetch(wa()->getAppPath('plugins/complex/templates/controls/productEdit.html', 'shop'));
			
			return array(
				'basics' => $control
			);
		}
	}
	
	public function backendProductSkuSettings($params)
	{
		if($this->getSettings('on')) {
			$price_model = new shopComplexPluginPriceModel();
			$prices = $price_model->getPrices(true);
			
			$view = wa()->getView();
			$view->assign('plugin_url', $this->getPluginStaticUrl());
			$view->assign('prices', $prices);
			$view->assign('params', $params);
			
			$view->assign('toggle_prices', $this->getSettings('toggle_prices'));
			
			$control = $view->fetch(wa()->getAppPath('plugins/complex/templates/controls/productSkuSettings.html', 'shop'));
			
			return $control;
		}
	}

	public static function isSS8Checkout()
	{
		$m = waRequest::param('module');
		$a = waRequest::param('action');

		return $m === 'frontendOrder' || $m === 'frontendOrderConfirmation' || $m === 'frontendOrderCart' || ($m === 'frontend' && $a === 'order');
	}
	
	public static function isInCartOrOrder()
	{
		$m = waRequest::param('module');
		$a = waRequest::param('action');
		$p = waRequest::param('plugin');

		$is_checkout = $m === 'frontend' && ($a === 'checkout' || $a === 'order');
		$is_cart = $m === 'frontendCart' || ($m === 'frontend' && $a === 'cart');
		$is_plugin = ($m === 'frontend' && $a === 'checkoutone') || $p === 'buy1click' || $p === 'buy1step';
		// todo добавить всякие разные плагины заказа в один шаг, но я не знаю какие у них роутинги, поэтому напишите на mail@shevsky.com

		return $is_checkout || $is_cart || $is_plugin || self::isSS8Checkout();
	}
	
	public static function getAvailablePricesTable($product, $params = array())
	{
		$plugin = self::getInstance();

		if(!$plugin->getSettings('on'))
			return false;

		$hide_if_storefront = $plugin->getSettings('hide_if_storefront');
		$hide_if_user = $plugin->getSettings('hide_if_user');
		$hide_if_cart_products = $plugin->getSettings('hide_if_cart_products');
		$default_if_null = $plugin->getSettings('default_if_null');
		$settings = $plugin->getSettings('available_prices');
		$template = $plugin->getSettings('product_template');

		$sku_selector = $plugin->getSettings('sku_selector');
		$sku_type_selector = $plugin->getSettings('sku_type_selector');
		$price_format = $plugin->getSettings('price_format');
		
		$view = wa()->getView();
		$view->assign('plugin_url', $plugin->getPluginStaticUrl());
		$view->assign('name', !empty($settings[0]['name']));
		$view->assign('conditions', !empty($settings[0]['conditions']));
		$view->assign('params', $params);

		$product = new shopProduct($product['id'], true);
		$skus = !empty($product['skus']) ? $product['skus'] : $product->getSkus();
		$default_price = $product['price'];

		$view->assign('default_price', $default_price);
		$view->assign('default_price_format', shop_currency($default_price, $product['currency']));
		$view->assign('default_price_format_html', shop_currency_html($default_price, $product['currency']));
		
		$available_prices = new shopComplexPluginAvailablePrices($product, $hide_if_storefront, $hide_if_user, $hide_if_cart_products, $default_if_null, $settings);

		$prices = $available_prices->available;
		$prices_only = $available_prices->available_prices;
		
		$price_model = new shopComplexPluginPriceModel();
		
		$sku_id = $product['sku_id'];

		if($product['sku_count'] > 1) {
			$sku_prices = array();
			foreach($skus as $_sku_id => $sku) {
				$sku_prices[$_sku_id] = array();
				
				foreach($prices_only as $price_id => $price) {
					$value = ifset($price[$_sku_id], 0);
					$sku_prices[$_sku_id][$price_id]['value'] = $value;
					$sku_prices[$_sku_id][$price_id]['value_format'] = shop_currency($value, $product['currency']);
					$sku_prices[$_sku_id][$price_id]['value_format_html'] = shop_currency_html($value, $product['currency']);
				}
			}
			
			if($product->sku_type == shopProductModel::SKU_TYPE_SELECTABLE) {
				$features_selectable = $product->features_selectable;
				
				$product_features_model = new shopProductFeaturesModel();
				$sku_features = $product_features_model->getSkuFeatures($product->id);
				$sku_features_keys = array();
				
				foreach($sku_features as $sku_id => $sf) {
					$sku_f = '';
					foreach ($features_selectable as $f_id => $f) {
						if(isset($sf[$f_id])) {
							$sku_f .= "|".$f_id.":".$sf[$f_id];
						}
					}
					$sku_f = substr($sku_f, 1);
					
					$sku_features_keys[$sku_f] = $sku_id;
				}
				
				$view->assign('sku_features_keys', $sku_features_keys);
			} else
				$view->assign('sku_features_keys', array());

			$view->assign('sku_prices', $sku_prices);
			
			$template .= <<<JS
<script type="text/javascript" src="{\$plugin_url}js/complex.frontend.table.js"></script>
<script type="text/javascript">
(function($) {
	var skuFeaturesKeys = {\$sku_features_keys|json_encode|escape:'js'};
	var complexPrices = {\$sku_prices|json_encode|escape:'js'};
	
	$.complexFrontendTable($('.js-complex-plugin-table'), '{$sku_selector}', '{$sku_type_selector}', '{$price_format}', complexPrices, skuFeaturesKeys);
})(jQuery);
</script>
JS;
		}

		foreach($prices as $id => &$price) {
			if(empty($price)) {
				continue;
			}
			
			$price['id'] = $id;
			$price_model->workupPriceRules($price['source'], true);
			$price['name'] = ifempty($settings[$id]['name'], $price['source']['name']);
			$price['clearly_conditions'] = ifempty($settings[$id]['conditions']);
			$price['conditions'] = nl2br(trim(ifempty($settings[$id]['conditions'], $price['source']['rule_simple'])));
			
			$price['value'] = ifset($price[$sku_id]);
			$price['value_format'] = shop_currency($price['value'], $product['currency']);
			$price['value_format_html'] = shop_currency_html($price['value'], $product['currency']);
		}

		$view->assign('prices', $prices);
		
		if(!empty($prices)) {
			if(empty($params['no_css']))
				wa()->getResponse()->addCss(wa()->getRouteUrl('shop/frontend/availablePricesTableStylesheet/', array(
					'plugin' => 'complex'
				), true));

			return $view->fetch('string:<div id="complex-plugin-table" class="complex-plugin-table js-complex-plugin-table">' . $template . '</div>');
		}
	}
	
	public function frontendProduct($product)
	{
		if($this->getSettings('on') && $this->getSettings('integration')) {
			$place = $this->getSettings('place');
			$table = shopComplexPlugin::getAvailablePricesTable($product);
			
			return array(
				$place => $table
			);
		}
	}
	
	public function frontendProducts($params)
	{
		if(!$this->getSettings('on') || !($this->getSettings('frontend') || (!$this->getSettings('frontend') && $this->isInCartOrOrder())))
			return;

		$product_instance = self::getProductInstance();

		if($product_instance) {
			if(!empty($params['skus'])) {
				$m = waRequest::param('module');
				$a = waRequest::param('action');

				$skus = $params['skus'];
				$sku = array_shift($skus);
			}
			if(!empty($params['products']))
				$product_instance->workupProducts($params['products'], !$this->isInCartOrOrder());

			if(!empty($params['skus']))
				$product_instance->workupSkus($params['skus'], false);
		}

	}

	public function frontendOrder()
	{
		if (!$this->getSettings('on')) {
			return;
		}

		return "<script type='text/javascript' src='" . $this->getPluginStaticUrl() . "js/complex.order.js'></script>";
	}

	public function frontendOrderCartVars(&$vars)
	{
		if (!$this->getSettings('on')) {
			return;
		}

		if (waRequest::param('module') === 'frontendOrderCart' && waRequest::param('action') === 'save') {
			$this->handleCartSaveVars($vars);
		}
	}

	private function handleCartSaveVars(&$vars)
	{
		if (!isset($vars['cart']['items'])) {
			return;
		}

		$product_instance = self::getProductInstance();

		$items = &$vars['cart']['items'];

		foreach ($items as &$item)
		{
			$sku_id = ifset($item, 'sku_id', null);
			if (!$sku_id)
			{
				continue;
			}

			if (isset($product_instance->processed_skus[$sku_id]))
			{
				$processed_sku = $product_instance->processed_skus[$sku_id];

				$item['price'] = $processed_sku['price'];
			}
		}
	}
	
	public function productCustomFields()
	{
		if($this->getSettings('on')) {
			$price_model = new shopComplexPluginPriceModel();
			$prices = $price_model->getPrices(true);
			
			$fields = array();
			
			foreach($prices as $price_id => $price) {
				$price_field = 'complex_plugin_price_' . $price_id;
				$type_field = 'complex_plugin_type_' . $price_id;
				
				$fields[$price_field] = $price['name'] . ' @' . _wp('Complex prices');
				$fields[$type_field] = $price['name'] . ' @' . _wp('Format of complex price');
			}
			
			return array(
				'sku' => $fields
			);
		}
	}
	
	public function productSave($params)
	{
		if($this->getSettings('on') && !empty($params['data']['skus'])) {
			$price_model = new shopComplexPluginPriceModel();
			$prices = $price_model->getPrices(true);
			
			$model = new shopProductSkusModel();
			
			if(!empty($params['data']['skus'])) {
				$is_exists_skipper = !empty($params['skip_complex_plugin']);

				foreach($params['data']['skus'] as $sku) {
					if($is_exists_skipper) {
						if(in_array($sku['id'], $params['skip_complex_plugin'])) {
							continue;
						}
					}

					if(!empty($sku['complex_plugin'])) {
						$complex = $sku['complex_plugin'];

						foreach($prices as $price_id => $price) {
							$price_field = 'complex_plugin_price_' . $price_id;
							$type_field = 'complex_plugin_type_' . $price_id;

							if(isset($complex[$price_field])) {
								$price = floatval(str_replace(',', '.', $complex[$price_field]));
								$type = ifset($complex[$type_field], '');

								$data = array(
									$price_field => $price
								);

								if(!empty($type) && in_array($type, array('+', '-', '%', '-%'))) {
									$data[$type_field] = $type;
								} else {
									$data[$type_field] = '';
								}

								if($data[$price_field] < 0) {
									switch($data[$type_field]) {
										case '+':
											$data[$type_field] = '-';
											break;
										case '%':
											$data[$type_field] = '-%';
											break;
									}

									$data[$price_field] = $data[$price_field] * -1;
								}

								$model->updateById($sku['id'], $data);
							}
						}
					}
				}
			}
		}
	}
	
	public function productsCollection($params)
	{
		$collection = $params['collection'];
		$hash = $collection->getHash();

		if(count($hash) == 2 && $hash[0] == 'complex') {
			$price_id = intval($hash[1]);

			$price_model = new shopComplexPluginPriceModel();
			$price = $price_model->getById($price_id);
			
			$collection->addJoin('shop_product_skus', ':table.product_id = p.id', ':table.complex_plugin_price_' . $price_id . ' != 0.0000');
			$collection->groupBy('p.id');
			
			$collection->addTitle(sprintf('%s "%s"', _wp('Products with filled price'), $price['name']));
			
			return true;
		}
	}
	
	public function productsExport(&$params)
	{
		if($this->getSettings('on') && $this->getSettings('export') && !empty($params['products'])) {
			foreach($params['products'] as &$product) {
				$toggle_prices = intval($product['complex_plugin_toggle_prices']);
				
				if($toggle_prices > 0) {
					foreach($product['skus'] as $id => &$sku) {
						$price = self::skuPrice($toggle_prices, $sku, null, $product);
						
						if($price)
							$sku['price'] = $price;
					}
					
					$price = self::price($toggle_prices, $product);
					
					if($price)
						$product['price'] = $price;
				}
			}
		}
	}
	
	private function updateCartTotal()
	{
		$cart = new shopCart();
			
		$data = wa()->getStorage()->get('shop/cart', array());
		$data['total'] = null;
		wa()->getStorage()->set('shop/cart', $data);
			
		$cart->total();
	}
	
	public function frontendHead()
	{
		if($this->getSettings('on') && $this->getSettings('update_cart_total')) {
			$this->updateCartTotal();
		}
	}
	
	public function cartAdd($params)
	{
		if($this->getSettings('on')) {
			$this->updateCartTotal();
		}
	}
	
	public function cartDelete($params)
	{
		if($this->getSettings('on')) {
			$this->updateCartTotal();
		}
	}
	
	public static function getPriceName($id)
	{
		$price_model = new shopComplexPluginPriceModel();
		
		return $price_model->getName($id);
	}
	
	public static function isEnabled($id)
	{
		$price_model = new shopComplexPluginPriceModel();
		
		return $price_model->isEnabled($id);
	}
	
	public static function price($id, $product, $format = null)
	{
		if(empty($product['id']))
			$product = new shopProduct($product);
		
		$sku = $product['sku_id'];
		
		if($product instanceof shopProduct || isset($product['skus'][$sku]))
			$sku = $product['skus'][$sku];
		
		return self::skuPrice($id, $sku, $format, $product);
	}
	
	public static function skuPrice($id, $sku, $format = null, $product = null)
	{
		if(self::isEnabled($id)) {
			if(empty($sku['id']) || !isset($sku['complex_plugin_price_' . $id])) {
				$product_skus_model = new shopProductSkusModel();

				if(!empty($sku['id']))
					$sku = $sku['id'];

				$sku = $product_skus_model->getById($sku);
			}
			
			$price_model = new shopComplexPluginPriceModel();
			$price = $price_model->getById($id);
			
			if((!isset($sku['complex_plugin_price_' . $id]) || (isset($sku['complex_plugin_price_' . $id]) && floatval($sku['complex_plugin_price_' . $id]) == 0)) && empty($price['default_style']))
				return null;
			
			if($instance = new shopComplexPluginProduct()) {
				$final_price = $instance->getSkuPrice($sku, 'price', $product, $id);
			} else
				return null;

			if(!is_null($format)) {
				if(is_null($product))
					$product = new shopProduct($sku['product_id']);
				
				$currency = $product['currency'];
									
				switch($format) {
					case 'format':
						return shop_currency($final_price, $currency);
						break;
					case 'format_html':
						return shop_currency_html($final_price, $currency);
						break;
				}
			} else
				return $final_price;
		}
		
		return null;
	}
	
	public static function algorithmStatus($id, $product)
	{
		if(self::isEnabled($id)) {
			if(!isset($product['complex_plugin_toggle_prices'])) {
				$product = new shopProduct($product);
			}
			
			$toggle_prices = intval($product['complex_plugin_toggle_prices']);
			
			if($toggle_prices > 0) {
				if($toggle_prices == $id)
					return 1;
				else
					return null;
			} else
				return $toggle_prices;
		} else
			return -1;
	}
	
	public static function checkConditions($id, $product)
	{
		$price_model = new shopComplexPluginPriceModel();
		$price = $price_model->getById($price_model);
		
		if($price) {
			$rule_model = new shopComplexPluginRuleModel();
			$rule = $rule_model->getRule($price['rule_id']);
			
			$check_instance = new shopComplexPluginCheck($product);
			$check = $check_instance->checkConditions($rule['id'], $rule['condition_mode'], $rule['conditions']);
			
			return $check;
		} else
			return false;
	}
}
