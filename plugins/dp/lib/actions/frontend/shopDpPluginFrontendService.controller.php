<?php

class shopDpPluginFrontendServiceController extends waJsonController
{
	public function execute()
	{
		$id = waRequest::get('id', 0, 'int');
		$product_id = waRequest::get('product_id', 0, 'int');

		$plugin = shopDpPlugin::getInstance('frontend_service');
		$env = $plugin->getEnv();
		$group_points = $plugin->getSettings('design_points_group');
		$group_title = $plugin->getSettings('design_points_group_title');
		$group_switch_mode = $plugin->getSettings('design_points_group_switch_mode');
		$shipping_methods = $plugin->getSettings('shipping_methods');
		$payment_methods = $plugin->getSettings('payment_methods');
		$sort_points = $plugin->getSettings('design_points_sort');
		$fields = $plugin->getSettings('shipping_fields');

		$type = $product_id ? 'product' : 'page';
		$cost_mode = $plugin->getSettings("{$type}_cost_mode");
		$estimated_date_mode = $plugin->getSettings("{$type}_estimated_date_mode");
		$calculate_mode = $plugin->getSettings("product_calculate_mode");

		$calculate_params = $env->getCalculateParams($type == 'product' ? $product_id : null, in_array($cost_mode, array('cart', 'cart+product')));

		$frontend = new shopDpFrontend(null, $plugin->getSettings());

		$params = array(
			'integration' => 'actual',
			'process_points' => true,
			'sort_points' => $sort_points
		);
		$options = array(
			'plugin' => $plugin,
			'env' => $env,
			'frontend' => $frontend,
			'calculate_params' => $calculate_params,
			'calculate_mode' => array(
				'cost' => $cost_mode,
				'estimated_date' => $estimated_date_mode,
				'mode' => $calculate_mode,
			),
			'payment_methods' => $payment_methods,
			'shipping_methods' => $shipping_methods,
			'group' => array(
				'status' => $group_points ? true : false,
				'title' => $group_title,
				'switch_mode' => $group_switch_mode
			),
			'fields' => $fields
		);

		$service = shopDpFactory::createService($id, $params, $options);

		$this->response = array(
			'title' => $frontend->serviceDialogTitle($service),
			'body' => $frontend->serviceDialog($service),
			'footer' => ''
		);
	}
}