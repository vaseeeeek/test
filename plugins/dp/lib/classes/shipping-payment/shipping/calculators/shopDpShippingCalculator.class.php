<?php

abstract class shopDpShippingCalculator
{
	private static $plugins_assoc = [];

	protected $plugin;

	protected $plugin_id;
	protected $id;
	protected $view;
	protected $shop_plugin_model;

	public $frontend_currency;

	final public function __construct($params = array())
	{
		$this->plugin_id = ifset($params, 'plugin_id', null);
		$this->id = ifset($params['id']);
		$this->view = !empty($params['view']) ? true : false;
		$this->shop_plugin_model = new shopPluginModel();

		$this->frontend_currency = shopDpPluginHelper::getCurrency('frontend');
	}

	final protected function getPlugin()
	{
		if(!isset($this->plugin))
			$this->plugin = shopShipping::getPlugin($this->plugin_id, $this->id);

		return $this->plugin;
	}

	final public function correct(&$cost)
	{
		if($this->frontend_currency != $this->getPlugin()->currency)
			$cost = shop_currency($cost, $this->getPlugin()->currency, $this->frontend_currency, false);
	}

	protected function getPluginAssoc()
	{
		if (!array_key_exists($this->id, self::$plugins_assoc))
		{
			self::$plugins_assoc[$this->id] = $this->shop_plugin_model->getPlugin($this->id, shopPluginModel::TYPE_SHIPPING);
		}

		return self::$plugins_assoc[$this->id];
	}

	protected function getAssemblyTime()
	{
		$plugin_assoc = $this->getPluginAssoc();

		$assembly_time = ifset($plugin_assoc, 'options', 'assembly_time', null);

		return wa_is_int($assembly_time)
			? intval($assembly_time)
			: 0;
	}
}