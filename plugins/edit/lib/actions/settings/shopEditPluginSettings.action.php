<?php

class shopEditPluginSettingsAction extends waViewAction
{
	public function execute()
	{
		$state = array(
			'categories' => $this->getCategories(),
			'sets' => $this->getSets(),

			'apps' => $this->getAllApps(),
			'seo_storefront_groups' => $this->getSeoStorefrontGroups(),
			'sites' => $this->getSites(),
			'currencies' => $this->getCurrencies(),
			'price_types' => $this->getPriceTypes(),

			'brands' => $this->getBrands(),

			'shipping_methods' => $this->getShippingMethods(),
			'payment_methods' => $this->getPaymentMethods(),

			'installed_plugins_info' => $this->getInstalledPluginsInfo(),
			'log_pages_count' => $this->getLogPagesCount(),
			'backend_url' => wa()->getConfig()->getBackendUrl(true),
		);
		$this->view->assign('state', $state);

		$this->view->assign('asset_version', shopEditHelper::getAssetVersion());
	}

	private function getCategories()
	{
		$storage = new shopEditCategoryStorage();

		return $storage->getAllAssocForSettings();
	}

	private function getSets()
	{
		$set_model = new shopSetModel();

		return $set_model->getAll();
	}

	private function getSeoStorefrontGroups()
	{
		$seo_helper = new shopEditSeoPluginHelper();

		return $seo_helper->getStorefrontGroups();
	}

	private function getSites()
	{
		$storage = new shopEditSiteStorage();

		return array_map(array($this, 'toAssoc'), $storage->getAll());
	}

	private function getCurrencies()
	{
		$currencies = array();

		$product_model = new shopProductModel();

		$rows = $product_model
			->select('DISTINCT currency')
			->where('currency IS NOT NULL')
			->fetchAll();

		$currency_model = new shopCurrencyModel();
		foreach ($rows as $row)
		{
			$currency = $currency_model->getById($row['currency']);
			if ($currency)
			{
				$currencies[] = $currency;
			}
		}

		return $currencies;
	}

	private function getPriceTypes()
	{
		$storage = new shopEditProductPriceTypeStorage();

		return $storage->getAllPriceTypes();
	}

	private function getAllApps()
	{
		$app_storage = new shopEditAppStorage();

		$apps_storefronts_assoc = array();
		foreach ($app_storage->getAll() as $app)
		{
			$apps_storefronts_assoc[] = $app->assoc();
		}

		return $apps_storefronts_assoc;
	}

	private function getBrands()
	{
		$brand_helper = new shopEditBrandPluginHelper();
		if (!$brand_helper->isPluginInstalled())
		{
			return array();
		}

		$storage = new shopBrandBrandStorage();
		$brand_converter = new shopEditBrandConverter();

		return $brand_converter->allToAssoc($storage->getAll());
	}

	private function getInstalledPluginsInfo()
	{
		/** @var shopEditAbstractPluginHelper[] $plugin_helpers */
		$plugin_helpers = array(
			new shopEditSeoPluginHelper(),
			new shopEditBrandPluginHelper(),
			new shopEditSeofilterPluginHelper(),
			new shopEditPricePluginHelper(),
		);

		$installed_plugins = array();
		foreach ($plugin_helpers as $plugin_helper)
		{
			if ($plugin_helper->isPluginInstalled())
			{
				$installed_plugins[$plugin_helper->getPluginId()] = $plugin_helper->getPluginInfoExtended();
			}
		}

		return $installed_plugins;
	}

	private function getLogPagesCount()
	{
		$collection = new shopEditLogsCollection();

		return ceil($collection->count() / shopEditPluginLogGetPageController::LIMIT - 1e-6);
	}

	private function getShippingMethods()
	{
		$plugin_model = new shopPluginModel();

		return $plugin_model
			->select('id,name,logo')
			->where('type = :type', array('type' => shopPluginModel::TYPE_SHIPPING))
			->where('status = 1')
			->order('sort ASC')
			->fetchAll();
	}

	private function getPaymentMethods()
	{
		$plugin_model = new shopPluginModel();

		return $plugin_model
			->select('id,name,logo')
			->where('type = :type', array('type' => shopPluginModel::TYPE_PAYMENT))
			->where('status = 1')
			->order('sort ASC')
			->fetchAll();
	}

	private function toAssoc($obj)
	{
		return $obj->assoc();
	}
}