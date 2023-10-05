<?php


class shopBuy1clickViewHelper
{
	private static $products = [];

	private static $product_settings;
	private static $cart_settings;

	public static function getButton($product_or_id, $sku_id = null)
	{
		$settings = self::getProductSettings();

		$product = self::getProduct($product_or_id);

		return $product && self::isProductButtonShown($settings, $product, $sku_id)
			? self::renderProductButton($settings, $product, $sku_id)
			: '';
	}
	
	public static function getCartButton()
	{
		$settings = self::getCartSettings();

		return self::isCartButtonShown($settings)
			? self::renderCartButton($settings)
			: '';
	}
	
	public static function getOrderTitleSuffix($order)
	{
		$sales_channel_parser = new shopBuy1clickSalesChannelParser();
		$channel = $sales_channel_parser->parse($order['params']['sales_channel']);
		
		if (!isset($channel))
		{
			return '';
		}
		
		$view = new waSmarty3View(wa());
		
		return $view->fetch(shopBuy1clickPlugin::getPath('/templates/BackendOrderTitleSuffix.html'));
	}
	
	public static function getSkuAvailable($product_or_id)
	{
		$product = self::getProduct($product_or_id);
		if (!$product)
		{
			return array();
		}

		/** @var shopConfig $config */
		$config = wa(shopBuy1clickPlugin::SHOP_ID)->getConfig();

		$sku_available = array();

		if ($product->sku_type == shopProductModel::SKU_TYPE_SELECTABLE)
		{
			$features_selectable = $product->features_selectable;
			$product_features_model = new shopProductFeaturesModel();
			$sku_features = $product_features_model->getSkuFeatures($product->id);

			foreach ($sku_features as $sku_id => $sf)
			{
				if (!isset($product->skus[$sku_id]))
				{
					continue;
				}

				$sku_f = "";

				foreach ($features_selectable as $f_id => $f)
				{
					if (isset($sf[$f_id]))
					{
						$sku_f .= $f_id . ":" . $sf[$f_id] . ";";
					}
				}
				
				$sku = $product->skus[$sku_id];
				$sku_is_in_stock = $config->getGeneralSettings('ignore_stock_count') || $sku['count'] === null || $sku['count'] > 0;
				$sku_available[$sku_f] = $sku_available[$sku_id] = $product->status && $sku['available'] && $sku_is_in_stock;
			}
		}

		// Обычные артикулы тоже учитываем, даже если у товара артикулы созданы через "Выбор параметров".
		foreach ($product->skus as $sku_id => $sku)
		{
			if ($sku['virtual'])
			{
				continue;
			}

			$sku_is_in_stock = $config->getGeneralSettings('ignore_stock_count') || $sku['count'] === null || $sku['count'] > 0;
			$sku_available[$sku['id']] = $product->status && $sku['available'] && $sku_is_in_stock;
		}

		return $sku_available;
	}
	
	public static function initAssets($force = false)
	{
		if (shopBuy1clickWaInstallerHelper::isBuy1clickInstalled())
		{
			return;
		}

		$settings = shopBuy1clickPlugin::getContext()->getSettingsService()->getBasicSettings();
		
		if (
			!$settings->isEnabled()
			|| (waRequest::param('app') == shopBuy1clickPlugin::SHOP_ID && !$force)
		)
		{
			return;
		}
		
		$plugin_id = shopBuy1clickPlugin::PLUGIN_ID;
		/** @var shopBuy1clickPlugin $plugin */
		$plugin = wa(shopBuy1clickPlugin::SHOP_ID)->getPlugin($plugin_id);
		
		wa(shopBuy1clickPlugin::SHOP_ID)->getResponse()
			->addCss("plugins/{$plugin_id}/assets/frontend.bundle.css?v={$plugin->getVersion()}", shopBuy1clickPlugin::SHOP_ID)
			->addCss("plugins/{$plugin_id}/css/buy1click.css?v={$plugin->getVersion()}", shopBuy1clickPlugin::SHOP_ID)
			->addCss(self::getStylePath() . "?v={$plugin->getVersion()}", shopBuy1clickPlugin::SHOP_ID)
			->addJs("plugins/{$plugin_id}/assets/frontend.bundle.js?v={$plugin->getVersion()}", shopBuy1clickPlugin::SHOP_ID)
			->addJs("plugins/{$plugin_id}/js/buy1click.js?v={$plugin->getVersion()}", shopBuy1clickPlugin::SHOP_ID);
	}

	private static function getStylePath() {
        $env = shopBuy1clickPlugin::getContext()->getEnv();
        $storefront_id = $env->getCurrentStorefront();
        if (self::isStorefrontSettingsFilled($storefront_id)) {
            return self::generateStorefrontIfFileNotExistingAndGetPath($storefront_id);
        }

	    return self::generateStorefrontIfFileNotExistingAndGetPath('*');
    }

    private static function generateStorefrontIfFileNotExistingAndGetPath($storefront_id) {
        $cssForStorefront = new shopBuy1clickGenerateCSSFile($storefront_id);
        if (!$cssForStorefront->isExisting()) {
            $cssForStorefront->compileAndSave();
        }
        return $cssForStorefront->getPath();
    }
    private static function isStorefrontSettingsFilled($storefront) {
        $settings_storage = shopBuy1clickPlugin::getContext()->getSettingsStorage();
        $fillStorefronts = $settings_storage->getFillStorefronts();
        return in_array($storefront, $fillStorefronts);
    }

	private static function getProductSettings()
	{
		if (!isset(self::$product_settings))
		{
			self::$product_settings = self::getSettings('product');
		}

		return self::$product_settings;
	}

	private static function getCartSettings()
	{
		if (!isset(self::$cart_settings))
		{
			self::$cart_settings = self::getSettings('cart');
		}

		return self::$cart_settings;
	}

	private static function getSettings($type)
	{
		$env = shopBuy1clickPlugin::getContext()->getEnv();
		$settings_service = shopBuy1clickPlugin::getContext()->getSettingsService();
		$storefront_id = $env->getCurrentStorefront();

		return $settings_service->getSettings($storefront_id, $type);
	}

	private static function isProductButtonShown(shopBuy1clickSettings $settings, shopProduct $product, $sku_id)
	{
		if (shopBuy1clickWaInstallerHelper::isBuy1clickInstalled())
		{
			return false;
		}

		if (!$settings->isEnabled())
		{
			return false;
		}

		if (!$settings->isEnabledButton())
		{
			return false;
		}

		if ($settings->getHideButtonIfOutOfStock())
		{
			$sku_available = self::getSkuAvailable($product);

			$is_in_of_stock = wa_is_int($sku_id) && $sku_id > 0
				? array_key_exists($sku_id, $sku_available) && $sku_available[$sku_id]
				: count(array_filter($sku_available)) > 0;

			if (!$is_in_of_stock)
			{
				return false;
			}
		}

		return true;
	}

	private static function renderProductButton(shopBuy1clickSettings $settings, shopProduct $product, $sku_id)
	{
		$view = new waSmarty3View(wa());

		$view->assign('type', 'item');
		$view->assign('settings_type', 'item');
		$view->assign('product_id', $product->id);
		$view->assign('product_obj', $product);
		$view->assign('sku_id', $sku_id);
		$view->assign('default_sku_id', $product->sku_id);
		$view->assign('settings', $settings->toArray());
		$view->assign('config_json', self::getConfigJs());

		return $view->fetch(shopBuy1clickPlugin::getPath('/templates/Button.html'));
	}
    private static function getConfigJs () {
        $plugin_id = shopBuy1clickPlugin::PLUGIN_ID;
        $wa = wa();
        $form_url = $wa->getRouteUrl('shop/frontend/form', array(
            'plugin' => $plugin_id,
        ));

        $update_form_url = $wa->getRouteUrl('shop/frontend/formUpdateState', array(
            'plugin' => $plugin_id,
        ));

        $send_form_url = $wa->getRouteUrl('shop/frontend/formSend', array(
            'plugin' => $plugin_id,
        ));

        $ping_form_url = $wa->getRouteUrl('shop/frontend/formPing', array(
            'plugin' => $plugin_id,
        ));

        $close_form_url = $wa->getRouteUrl('shop/frontend/formClose', array(
            'plugin' => $plugin_id,
        ));

        $send_channel_address_url = $wa->getRouteUrl('shop/frontend/requestChannelCode', array(
            'plugin' => $plugin_id,
        ));

        $send_channel_code_url = $wa->getRouteUrl('shop/frontend/validateChannelCode', array(
            'plugin' => $plugin_id,
        ));

        $data = array(
            'wa_url' => $wa->getUrl(),
            'form_url' => $form_url,
            'update_form_url' => $update_form_url,
            'send_form_url' => $send_form_url,
            'ping_form_url' => $ping_form_url,
            'close_form_url' => $close_form_url,
            'send_channel_address_url' => $send_channel_address_url,
            'send_channel_code_url' => $send_channel_code_url,
            'is_increase_plugin_enabled' => shopBuy1clickPlugin::getContext()->getEnv()->isIncreasePluginEnabled(),
        );
        return json_encode($data);
    }
	private static function isCartButtonShown(shopBuy1clickSettings $settings)
	{
		if (shopBuy1clickWaInstallerHelper::isBuy1clickInstalled())
		{
			return false;
		}

		if (!$settings->isEnabled())
		{
			return false;
		}

		if (!$settings->isEnabledButton())
		{
			return false;
		}

		$current_cart = new shopBuy1clickWaCart();
		$cart_is_empty = count($current_cart->getItems()) === 0;
		if ($cart_is_empty)
		{
			return false;
		}

		return true;
	}

	private static function renderCartButton(shopBuy1clickSettings $settings)
	{
		$view = new waSmarty3View(wa());

		$settings_type = $settings->isEqualFormSettings()
			? 'item'
			: 'cart';

		$view->assign('type', 'cart');
		$view->assign('settings', $settings->toArray());
		$view->assign('settings_type', $settings_type);
        $view->assign('config_json', self::getConfigJs());

		return $view->fetch(shopBuy1clickPlugin::getPath('/templates/Button.html'));
	}

	/**
	 * @param shopProduct|int|array $product_or_id
	 * @return shopProduct|null
	 */
	private static function getProduct($product_or_id)
	{
		if ($product_or_id instanceof shopProduct)
		{
			if (!array_key_exists($product_or_id->id, self::$products))
			{
				self::$products[$product_or_id->id] = $product_or_id;
			}

			return $product_or_id;
		}


		$product_id = null;
		if (is_array($product_or_id))
		{
			if (empty($product_or_id['id']))
			{
				return null;
			}

			$product_id = $product_or_id['id'];
		}
		else
		{
			$product_id = $product_or_id;
		}

		if (!array_key_exists($product_id, self::$products))
		{
			self::$products[$product_id] = new shopProduct($product_id);
		}

		return self::$products[$product_id];
	}
}
