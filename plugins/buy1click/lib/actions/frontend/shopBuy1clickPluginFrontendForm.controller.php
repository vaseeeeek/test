<?php


class shopBuy1clickPluginFrontendFormController extends waJsonController
{
	private $env;
	private $settings_service;
	private $form_service;
	private $contact_info_service;

	public function __construct()
	{
		$this->env = shopBuy1clickPlugin::getContext()->getEnv();
		$this->settings_service = shopBuy1clickPlugin::getContext()->getSettingsService();
		$this->form_service = shopBuy1clickPlugin::getContext()->getFormService();
		$this->contact_info_service = shopBuy1clickPlugin::getContext()->getContactInfoService();
	}


	public function execute()
	{
		$type = waRequest::post('type');
		$storefront_id = $this->env->getCurrentStorefront();
		$settings = $this->settings_service->getSettings($storefront_id, $type);

		if ($settings->isEnabledGeoIp())
		{
			$this->applyGeoIpToContact();
		}

		if ($type == shopBuy1clickForm::TYPE_CART)
		{
			$form = $this->form_service->createByCart($storefront_id);
		}
		else
		{
			$form = $this->form_service->createByItem($this->getItem(), $storefront_id);
		}

		$this->form_service->loadShipping($form);
		$this->form_service->loadShippingRates($form);
		$this->form_service->loadShippingCustomFields($form);
		$this->form_service->updateSelectedShipping($form);

		$this->form_service->loadPayments($form);
		$this->form_service->loadOrder($form);
		$this->form_service->loadConfirmationChannel($form);

		$this->form_service->resetConfirmation($form);

		$form->validate(new shopBuy1clickWaFormUpdateValidator());

		$form_view = new shopBuy1clickFormView($form);

		$this->response = array(
			'state' => $form_view->getState(),
			'html' => $form_view->render(),
		);
	}

	private function getItem()
	{
		$item = waRequest::post('item');

		return $this->handleItem($item);
	}

	private function handleItem($item)
	{
		if (!isset($item['sku_id']))
		{
			if (isset($item['features']) && is_array($item['features']))
			{
				$product_features_model = new shopProductFeaturesModel();
				$features = array();

				foreach ($item['features'] as $feature) {
					if (!isset($feature['feature_id']) || !isset($feature['feature_value_id']))
					{
						continue;
					}

					$features[$feature['feature_id']] = $feature['feature_value_id'];
				}

				$item['sku_id'] = $product_features_model->getSkuByFeatures($item['product_id'], $features);
			}
			elseif (isset($item['product_id']))
			{
				$product = new shopProduct($item['product_id']);
				$item['sku_id'] = $product->sku_id;
			}
		}

		$sku_model = new shopProductSkusModel();
		$row = $sku_model->getById($item['sku_id']);

		if (!isset($row))
		{
			return null;
		}

		$item['product_id'] = $row['product_id'];

		if (!isset($item['quantity']))
		{
			$item['quantity'] = 1;
		}

		$services = array();

		if (isset($item['services']) && is_array($item['services']))
		{
			foreach ($item['services'] as $service)
			{
				$services[] = array(
					'service_id' => $service['service_id'],
					'service_variant_id' => $service['service_variant_id'],
				);
			}
		}

		return array(
			'type' => 'product',
			'product_id' => $item['product_id'],
			'sku_id' => $item['sku_id'],
			'quantity' => $item['quantity'],
			'services' => $services
		);
	}

	private function applyGeoIpToContact()
	{
		$contact_info = $this->contact_info_service->getCurrent();

		if ($contact_info->getShippingAddress()->getCity() == '')
		{
			$result = shopIpPlugin::getGeoIpApi()->getForCurrentIp();

			if (isset($result)
				&& ($contact_info->getShippingAddress()->getCountry() == '' || $contact_info->getShippingAddress()->getCountry() == $result->getCountry())
				&& ($contact_info->getShippingAddress()->getRegion() == '' || $contact_info->getShippingAddress()->getCountry() == $result->getRegion()))
			{
				$contact_info->getShippingAddress()->setCountry($result->getCountry());
				$contact_info->getShippingAddress()->setRegion($result->getRegion());
				$contact_info->getShippingAddress()->setCity($result->getCity());
				$this->contact_info_service->store($contact_info);
			}
		}
	}
}
