<?php
class shopDpPayment extends shopDpShippingPaymentPlugin
{
	public function get($payment_id)
	{
		if(is_array($payment_id)) {
			$output_payments = array();
			foreach($this->getAll() as $payment)
				if(!empty($payment_id[$payment['id']]))
					$output_payments[$payment['id']] = $payment;

			return $output_payments;
		} else {
			return $this->getShopPluginModel()->getPlugin($payment_id, 'payment');
		}
	}

	public function getWaAppSettingsModel()
	{
		if(!isset($this->wa_app_settings_model))
			$this->wa_app_settings_model = new waAppSettingsModel();

		return $this->wa_app_settings_model;
	}

	public function getDisabledPayment()
	{
		if(!isset($this->disabled_payment))
			$this->disabled_payment = json_decode($this->getWaAppSettingsModel()->get('shop', 'shipping_payment_disabled'), true);

		return $this->disabled_payment;
	}

	public function getAll()
	{
		$plugins = $this->getShopPluginModel()->listPlugins('payment', array(
			'all' => 1
		));

		return $plugins;
	}

	public function getAvailableForShipping($shipping_id)
	{
		$plugins = $this->getAll();

		$disabled_payment = $this->getDisabledPayment();

		if(empty($disabled_payment))
			return $plugins;

		foreach($plugins as $id => &$plugin) {
			if(!empty($disabled_payment[$id])) {
				if(in_array($shipping_id, $disabled_payment[$id])) {
					//echo 'delete ' . $id . "\r\n";
					unset($plugins[$id]);
				}
			}
		}

		return $plugins;
	}
}