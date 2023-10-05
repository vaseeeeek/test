<?php

class shopComplexPluginAvailablePrices
{
	public $available = array();
	public $available_prices = array();
	
	public function __construct($product, $hide_if_storefront, $hide_if_user, $hide_if_cart_products, $default_if_null, $settings)
	{
		$this->product = $product;

		if($this->product['complex_plugin_toggle_prices'] !== '0')
			return false;

		$this->skus = !empty($product['skus']) ? $product['skus'] : $product->getSkus();
		$this->hide_if_storefront = $hide_if_storefront;
		$this->hide_if_user = $hide_if_user;
		$this->hide_if_cart_products = $hide_if_cart_products;
		$this->default_if_null = $default_if_null;
		$this->settings = $settings;
		
		$this->price_model = new shopComplexPluginPriceModel();
		$this->rule_model = new shopComplexPluginRuleModel();
		
		$this->prices = $this->price_model->getPrices(true);

		$this->plugin = wa('shop')->getPlugin('complex');
		
		$this->execute();
	}
	
	private function getProductInstance()
	{
		return new shopComplexPluginProduct();
	}
	
	private function getCheckInstance()
	{		
		return new shopComplexPluginCheck($this->product);
	}
	
	private function checkRule(&$rule)
	{
		$check_instance = $this->getCheckInstance();
		
		$check = $check_instance->checkConditions($rule['id'], $rule['condition_mode'], $rule['conditions']);
		
		$rule['check_result'] = $check;
	}
	
	private function workupConditions(&$conditions)
	{
		foreach($conditions as $key => &$condition) {
			if($condition['field'] == 'group') {
				self::workupConditions($condition['value']['conditions'], $this->hide_if_storefront, $this->hide_if_user);
			} else {
				if($this->hide_if_storefront && $condition['field'] == 'storefront') {
				} elseif($this->hide_if_user && substr($condition['field'], 0, '5') == 'user.') {
				} elseif($this->hide_if_cart_products && in_array($condition['field'], array('cart.products.with_type_count', 'cart.products.in_category_count'))) {
				} elseif(substr($condition['field'], 0, 8) == 'product.') {
				} else {
					$condition['field'] = 'yes';
				}
			}
		}
	}
	
	public function execute()
	{
		$rules = array();
		
		foreach($this->prices as $price) {
			if(empty($this->settings[$price['id']]['on'])) {
				continue;
			}
			
			{
				$continue = false;
				
				if(!isset($rules[$price['rule_id']]))
					$rules[$price['rule_id']] = $this->rule_model->getRule($price['rule_id']);
				
				$rule = $rules[$price['rule_id']];
				
				if(empty($rule['conditions_processed'])) {
					$this->workupConditions($rule['conditions']);
					$rule['conditions_processed'] = true;
				}
				
				if(empty($rule['rule_checked'])) {
					$this->checkRule($rule);
					$rule['rule_checked'] = true;
				}
				
				if($rule['check_result'])
					$continue = true;
			}
			
			if($continue) {
				$instance = $this->getProductInstance();

				foreach($this->skus as $sku_id => $sku) {
					$price_value = $instance->getSkuPrice($sku, 'price', $this->product, $price['id']);
					
					if(!$price_value && $this->default_if_null)
						$price_value = $sku['price'];

					if($price_value > $sku['price'] && $this->plugin->getSettings('dont_show_more')) {
						if(isset($this->available[$price['id']]))
							unset($this->available[$price['id']]);
						if(isset($this->available_prices[$price['id']]))
							unset($this->available_prices[$price['id']]);

						break;
					}
					
					if($price_value) {
						if(!isset($this->available[$price['id']]))
							$this->available[$price['id']] = array();
						if(!isset($this->available_prices[$price['id']]))
							$this->available_prices[$price['id']] = array();

						$this->available[$price['id']][$sku_id] = $price_value;
						$this->available_prices[$price['id']][$sku_id] = $price_value;
					}
				}
				
				if(!empty($this->available[$price['id']]))
					$this->available[$price['id']]['source'] = $price;
			}
		}
	}
}