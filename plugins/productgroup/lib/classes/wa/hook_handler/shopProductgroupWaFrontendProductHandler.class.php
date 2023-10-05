<?php

class shopProductgroupWaFrontendProductHandler
{
	private $plugin_env;

	public function __construct(shopProductgroupPluginEnv $plugin_env)
	{
		$this->plugin_env = $plugin_env;
	}

	/**
	 * @param shopProduct $product
	 * @param array|null $keys
	 * @return array
	 * @throws waException
	 */
	public function handle($product, $keys)
	{
		if (
			(is_array($keys) && !in_array('cart', $keys))
			|| !$product
		)
		{
			return [];
		}

		$config = $this->plugin_env->plugin_config;
		if (!$config->is_enabled)
		{
			return [];
		}

		if ($config->output_wa_hook === shopProductgroupOutputHook::FRONTEND_PRODUCT_CART)
		{
			$block_name = 'cart';
		}
		elseif ($config->output_wa_hook === shopProductgroupOutputHook::FRONTEND_PRODUCT_BLOCK_AUX)
		{
			$block_name = 'block_aux';
		}
		else
		{
			return [];
		}

		$cart_html = '';
		if ($this->plugin_env->theme_id)
		{
			$view = new shopProductgroupWaView(wa()->getView());

			$renderer = new shopProductgroupGroupsBlockRenderer($view);

			$cart_html = $renderer->renderGroupsBlock($product['id'], $this->plugin_env->theme_id);
		}

		return [
			$block_name => $cart_html,
		];
	}
}