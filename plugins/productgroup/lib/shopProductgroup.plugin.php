<?php

class shopProductgroupPlugin extends shopPlugin
{
	private static $plugin_env;

	public function __construct($info)
	{
		parent::__construct($info);

		if (!isset(self::$plugin_env))
		{
			$plugin_env_factory = shopProductgroupPluginContext::getInstance()->getPluginEnvFactory();

			self::$plugin_env = $plugin_env_factory->createPluginEnv();
		}
	}

	/**
	 * @return shopProductgroupPluginEnv
	 */
	public function getPluginEnv()
	{
		return self::$plugin_env;
	}

	public function routing($route = array())
	{
		$plugin_env_factory = shopProductgroupPluginContext::getInstance()->getPluginEnvFactory();

		self::$plugin_env = $plugin_env_factory->createPluginEnv();

		return parent::routing($route);
	}

	public function handleBackendProduct($product)
	{
		$handler = new shopProductgroupWaBackendProductHandler();

		return $handler->handle($product);
	}

	public function handleBackendProductsList()
	{
		$handler = new shopProductgroupWaBackendProductsListHandler();

		return $handler->handle();
	}

	public function handleProductDelete($params)
	{
		$handler = new shopProductgroupWaProductDeleteHandler();

		$handler->handle($params);
	}

	public function handleFrontendHead()
	{
		$handler = new shopProductgroupWaFrontendHeadHandler($this->getPluginEnv());

		return $handler->handle();
	}

	public function handleFrontendProduct($product, $keys)
	{
		$handler = new shopProductgroupWaFrontendProductHandler($this->getPluginEnv());

		return $handler->handle($product, $keys);
	}
}
