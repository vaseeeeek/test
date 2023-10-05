<?php

class shopDpPlugin extends shopPlugin
{
	protected static $env;
	protected static $settings_storage;

	/**
	 * @param string|null $initiator
	 * @return shopDpPlugin
	 * @throws waException
	 */
	public static function getInstance($initiator = null)
	{
		return wa('shop')->getPlugin('dp');
	}

	/**
	 * @return shopDpSettingsStorage
	 */
	protected static function getSettingsStorage()
	{
		if(!self::$settings_storage) {
			self::$settings_storage = new shopDpSettingsStorage(self::getEnv());
		}

		return self::$settings_storage;
	}

	/**
	 * @return shopDpEnv
	 */
	public static function getEnv()
	{
		if(!self::$env)
			self::$env = new shopDpEnv();

		return self::$env;
	}

	/**
	 * @param string|null $name
	 * @param string|null $storefront_id
	 * @return mixed
	 */
	public function getSettings($name = null, $storefront_id = null)
	{
		return $this->getSettingsStorage()->getSettings($name, $storefront_id);
	}

	/**
	 * @param string|null $name
	 * @param string|null $storefront_id
	 * @return mixed
	 */
	public static function staticallyGetSettings($name = null, $storefront_id = null)
	{
		return self::getSettingsStorage()->getSettings($name, $storefront_id);
	}

	/**
	 * @param waView|null $view
	 * @return shopDpFrontend
	 * @throws waException
	 */
	public function getFrontend($view = null)
	{
		return new shopDpFrontend($view, $this->getSettings(), self::getEnv(), $this);
	}

	/**
	 * @param array $route
	 * @return array|false
	 */
	public function routing($route = array())
	{
		if(!$this->getSettings('status')) {
			return false;
		}

		$routing = array(
			'dp-plugin/config/' => 'frontend/config',
			'dp-plugin/stylesheet/' => 'frontend/stylesheet',
			'dp-plugin/svg/' => 'frontend/svg',
			'dp-plugin/dialog/' => 'frontend/dialog',
			'dp-plugin/service/' => 'frontend/service',
			'dp-plugin/calculate/' => 'frontend/calculate',
			'dp-plugin/city-search/' => 'frontend/citySearch',
			'dp-plugin/city-save/' => 'frontend/citySave'
		);

		return $routing;
	}

	public function frontendHead()
	{
		if(!$this->getSettings('status') || $this->getSettings('asset_mode') !== 'async') {
			return false;
		}

		$js_config_url = wa()->getRouteUrl('shop/frontend/config/', array(
				'plugin' => 'dp'
			)) . '?v' . $this->getVersion();

		$output = <<<HTML
<script type="text/javascript" src="{$js_config_url}"></script>
HTML;

		return $output;
	}

	/**
	 * @param shopProduct|array|int|string $product
	 * @return array|false
	 * @throws waException
	 */
	public function frontendProduct($product)
	{
		if(!$this->getSettings('status') || !$this->getSettings('product_status')) {
			return false;
		}

		$output = array();

		$frontend = $this->getFrontend();

		if($this->getSettings('product_status')) {
			$hook = $this->getSettings('product_hook');
			$view = $frontend->product($product);

			$output[$hook] = $view;
		}

		return $output;
	}
}
