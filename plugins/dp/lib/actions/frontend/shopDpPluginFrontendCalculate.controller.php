<?php

class shopDpPluginFrontendCalculateController extends waJsonController
{
	public function execute()
	{
		$shipping_id = waRequest::post('id', 0, 'int');
		$product_id = waRequest::post('product_id', 0, 'int');

		$plugin = shopDpPlugin::getInstance('frontend_calculate');

		$this->response = array(
			'success' => false,
		);

		$service = $this->getService($plugin, $shipping_id, $product_id);

		$service->prepareShipping();
		$service->calculate();
		$service->postCalculate();
		$service->processFields();

		if ($service['cost_params'] === false) {
			$this->response['is_available'] = false;

			return;
		}

		$frontend = new shopDpFrontend();

		$this->response = array(
			'success' => true,
			'calculate_html' => $frontend->productCalculate($service),
		);
	}

	/**
	 * @param shopDpPlugin $plugin
	 * @param $shipping_id
	 * @param $product_id
	 * @return shopDpService
	 */
	private function getService($plugin, $shipping_id, $product_id)
	{
		$env = $plugin->getEnv();

		$shipping_methods = $plugin->getSettings('shipping_methods');
		$cost_mode = $plugin->getSettings('product_cost_mode');
		$estimated_date_mode = $plugin->getSettings("product_estimated_date_mode");
		$calculate_mode = $plugin->getSettings("product_calculate_mode");

		$calculate_params = $env->getCalculateParams($product_id, in_array($cost_mode, array('cart', 'cart+product')));

		$params = array(
			'no_process' => true,
		);

		$options = array(
			'plugin' => $plugin,
			'env' => $env,
			'caller' => 'calculate',
			'calculate_params' => $calculate_params,
			'calculate_mode' => array(
				'cost' => $cost_mode,
				'estimated_date' => $estimated_date_mode,
				'mode' => $calculate_mode,
			),
			'shipping_methods' => $shipping_methods,
		);

		return shopDpFactory::createService($shipping_id, $params, $options);
	}
}
