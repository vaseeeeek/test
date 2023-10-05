<?php

abstract class shopDpIntegration
{
	protected $params;
	protected $instance;
	protected $plugin_id;

	public function __construct($params = array())
	{
		$this->params = $params;
	}

	final public function getParam($name)
	{
		return ifset($this->params, $name, null);
	}

	final public function setParam($name, $value)
	{
		$this->params[$name] = $value;
	}

	final protected function getPluginInstance()
	{
		if(!$this->plugin_id) {
			return null;
		}

		if(!isset($this->instance)) {
			$this->instance = wa('shop')->getPlugin($this->plugin_id);
		}

		return $this->instance;
	}
}