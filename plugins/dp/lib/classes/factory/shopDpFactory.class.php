<?php

class shopDpFactory
{
	public static function createService($id, $params, $options = array())
	{
		extract($options);

		if(!isset($plugin) && (!isset($shipping_methods) || !isset($fields)))
			$plugin = shopDpPlugin::getInstance('factory:create_service');

		if(!isset($shipping_methods))
			$shipping_methods = $plugin->getSettings('shipping_methods');
		if(!isset($fields))
			$fields = $plugin->getSettings('shipping_fields');

		$service = ifset($shipping_methods, $id, null);

		$service_options = array_merge($options, array(
			'shipping_methods' => $shipping_methods,
			'fields' => $fields
		));

		if($id == -1 || (!empty($group['status']) && !empty($service['service']) && in_array($service['service'], shopDpPluginHelper::getPointServices()))) {
			$params['checked'] = $service['service'];
			$params['checked_shipping'] = $id;
			$service_options['title'] = $group['title'];
			$service_options['switch_mode'] = ifset($group, 'switch_mode', 'header');
			$service_options['all'] = $id == -1;

			return new shopDpServicePointsGroup($params, $service_options);
		}

		return new shopDpService($id, $params, $service_options);
	}

	public static function getCalculatorClass($plugin_id)
	{
		if(!empty($plugin_id)) {
			$plugin_id = ucfirst($plugin_id);
			return "shopDpShipping{$plugin_id}Calculator";
		} else
			return null;
	}

	public static function isCalculatorExists($plugin_id)
	{
		if(!empty($plugin_id)) {
			$class = self::getCalculatorClass($plugin_id);

			return class_exists($class);
		} else
			return false;
	}

	public static function createCalculator($id, $plugin_id, $view = true, $plugin = null)
	{
		if(self::isCalculatorExists($plugin_id) && $class = self::getCalculatorClass($plugin_id))
			return new $class(array(
				'id' => $id,
				'plugin_id' => $plugin_id,
				'view' => $view,
				'plugin' => $plugin
			));
	}
}