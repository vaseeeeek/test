<?php

class shopDpIntegrationFreedeliveryPlugin extends shopDpIntegration
{
	protected $is_inited = false;
	protected $plugin_id = 'freedelivery';

	private $rules;
	private $shipping_methods;

	public function init()
	{
		if(!$this->is_inited) {
			$this->initLocation();
			$this->initFakeProduct();

			$this->shipping_methods = $this->getShippingMethods();
			$this->is_inited = true;
		}
	}

	public function getLocation()
	{
		if(!$this->getParam('location')) {
			$this->setParam('location', new shopDpLocation('freedelivery_plugin'));
		}

		return $this->getParam('location');
	}

	public function initLocation()
	{
		$this->getPluginInstance()->user_region = array(array(
			'data' => array(
				'country' => $this->getLocation()->getCountry(),
				'region' => $this->getLocation()->getRegion(),
				'city' => $this->getLocation()->getCity()
			),
			'ext' => 'shipping'
		));
	}

	public function initFakeProduct()
	{
		$this->getPluginInstance()->cart_total_with_discount = -1;
		$this->getPluginInstance()->cart_total_without_discount = -1;

		if ($this->getParam('calculate_params')) {
			$calc_params = $this->getParam('calculate_params');
			if (isset($calc_params['items'])) {
				$items = $calc_params['items'];
				$product_items = array();
				foreach ($items as $item)
				{
					if (!empty($item['product'])) {
						$item['product']['quantity'] = $item['quantity'];
						$item['product']['type'] = !empty($item['product']['type']) ? $item['product']['type'] : '';
						$item['product']['product_id'] = $item['product']['id'];
						$product_items[] = $item['product'];
					}
				}
				$this->getPluginInstance()->cart_items = $product_items;
			}
		}
	}

	protected function getShippingMethods()
	{
		return $this->getPluginInstance()->getShippingMethods();
	}

	protected function getCorrectRules()
	{
		if(!isset($this->rules)) {
			$this->rules = array();

			foreach($this->getPluginInstance()->getRules() as $rule) {
				if(!empty($rule['deny_reasons'])) {
					foreach($rule['deny_reasons'] as $condition_id => $deny_reason) {
						if(empty($deny_reason['status']))
							return false;

						$save_rule = null;

						if($deny_reason['status'] == 'not-that-value') {
							if(!empty($rule['conditions'][$condition_id])) {
								$condition = $rule['conditions'][$condition_id];

								if(in_array($condition['field'], array('cart.total', 'cart.total_without_discount'))) {
									if(!empty($deny_reason['compare']) && in_array($deny_reason['compare'], array('>', '>=')) && !empty($deny_reason['need'])) {
										switch($deny_reason['compare']) {
											case '>':
												$value = intval($deny_reason['need']) - 1;
												break;
											case '>=':
												$value = intval($deny_reason['need']);
												break;
										}

										if($value > 0)
											$save_rule = array(
												'total' => $value
											);
									}
								}
							}
						}

						if(isset($save_rule)) {
							if(empty($this->rules[$rule['id']]))
								$this->rules[$rule['id']] = array(
									'shippings' => $rule['shippings'],
									'conditions' => array()
								);

							array_push($this->rules[$rule['id']]['conditions'], $save_rule);
						} else {
							if(array_key_exists($rule['id'], $this->rules)) {
								unset($this->rules[$rule['id']]);
							}
							break;
						}
					}
				}
			}
		}

		return $this->rules;
	}

	public function getRules($shipping_id)
	{
		$this->init();

		$rules = array();

		foreach($this->getCorrectRules() as $id => $rule) {
			if(empty($rule['shippings']) || in_array($shipping_id, $rule['shippings'])) {
				$rules[$id] = $rule;
			} else {
				continue;
			}
		}

		if(!empty($rules)) {
			$min_for_total = null;

			foreach($rules as $rule) {
				foreach($rule['conditions'] as $condition)
					if(!empty($condition['total']) && (($condition['total'] < $min_for_total) || $min_for_total === null))
						$min_for_total = $condition['total'];
			}

			$output_rules = array();
			if($min_for_total !== 0 && $min_for_total !== null)
				$output_rules['total'] = $min_for_total;

			return $output_rules;
		}
	}

	public function isFree($shipping_id)
	{
		$this->init();

		return $this->getPluginInstance()->isFreeShipping($shipping_id);
	}
}
