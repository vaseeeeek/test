<?php

abstract class shopDpShippingPaymentPlugin
{
	protected $params;
	protected $options;

	final public function __construct($params = array(), $options = array())
	{
		$this->params = $params;
		$this->options = $options;
	}

	final public function getShopPluginModel()
	{
		if(!isset($this->shop_plugin_model))
			$this->shop_plugin_model = new shopPluginModel();

		return $this->shop_plugin_model;
	}
}