<?php


class shopBuy1clickWaSettingsStorage implements shopBuy1clickSettingsStorage
{
	private $settings_model;
	private $storefront_settings_model;
	private $wa_customer_form;
	private $env;
	
	public function __construct(
		shopBuy1clickSettingsModel $settings_model, shopBuy1clickStorefrontSettingsModel $storefront_settings_model, shopBuy1clickWaCustomerForm $wa_customer_form, shopBuy1clickEnv $env
	)
	{
		$this->settings_model = $settings_model;
		$this->storefront_settings_model = $storefront_settings_model;
		$this->wa_customer_form = $wa_customer_form;
		$this->env = $env;
	}
	
	public function getFillStorefronts()
	{
		$storefronts = array();
		
		foreach ($this->storefront_settings_model->query("select distinct storefront_id from shop_buy1click_storefront_settings where `value` is not null and storefront_id != '*'") as $row)
		{
			$storefronts[] = $row['storefront_id'];
		}
		
		return $storefronts;
	}
	
	/**
	 * @return shopBuy1clickBasicSettings
	 */
	public function getBasicSettings()
	{
		return new shopBuy1clickBasicSettings(new shopBuy1clickSettingsData($this->getData(), false));
	}
	
	public function storeBasicSettings(shopBuy1clickBasicSettings $basic_settings)
	{
		$this->storeData($basic_settings->toArray());
	}
	
	/**
	 * @param $storefront
	 * @return shopBuy1clickStorefrontSettings
	 * @throws waException
	 */
	public function getStorefrontSettings($storefront)
	{
		return new shopBuy1clickStorefrontSettings(new shopBuy1clickSettingsData(
			$this->getStorefrontData($storefront), $storefront != '*'
		));
	}
	
	public function storeStorefrontSettings($storefront, shopBuy1clickStorefrontSettings $storefront_settings)
	{
		$this->storeStorefrontData($storefront, $storefront_settings->toArray());
	}
	
	/**
	 * @param $storefront
	 * @param $type
	 * @return shopBuy1clickFormSettings
	 * @throws waException
	 */
	public function getFormSettings($storefront, $type)
	{
		$is_allow_null = $storefront != '*' || $type != 'product';
		$form_settings = new shopBuy1clickFormSettings(new shopBuy1clickSettingsData(
			$this->getFormData($storefront, $type), $is_allow_null
		), $this->env);
		
		$data = $form_settings->toArray();
		$form_fields = $form_settings->getFormFields();
		
		if (is_array($form_fields))
		{
			$fields = $this->wa_customer_form->getFields();
			$fields_codes = array();
			
			foreach ($fields as $field)
			{
				$fields_codes[] = $field['code'];
			}
			
			foreach ($form_fields as $key => $field)
			{
				if (!in_array($field['code'], $fields_codes))
				{
					unset($form_fields[$key]);
				}
			}
			
			$data['form_selected_fields'] = array_values($form_fields);
		}
		
		return new shopBuy1clickFormSettings(new shopBuy1clickSettingsData(
			$data, $is_allow_null
		), $this->env);
	}
	
	public function storeFormSettings($storefront, $type, shopBuy1clickFormSettings $form_settings)
	{
		$this->storeFormData($storefront, $type, $form_settings->toArray());
	}
	
	private function getData()
	{
		$rows = $this->settings_model->getAll();
		$data = array();
		
		foreach ($rows as $row)
		{
			$data[$row['name']] = $row['value'];
		}
		
		return $data;
	}
	
	private function storeData($data)
	{
		foreach ($data as $name => $value)
		{
			if (is_bool($value))
			{
				$value = $value ? 1 : 0;
			}
			
			$this->settings_model->replace(array(
				'name' => $name,
				'value' => $value,
			));
		}
	}
	
	/**
	 * @param $storefront
	 * @return array
	 * @throws waException
	 */
	private function getStorefrontData($storefront)
	{
		$rows = $this->storefront_settings_model->getByField('storefront_id', $storefront, true);
		$data = array();
		
		foreach ($rows as $row)
		{
			$data[$row['name']] = $row['value'];
		}
		
		return $data;
	}
	
	private function storeStorefrontData($storefront, $data)
	{
		foreach ($data as $name => $value)
		{
			$value = $this->transformValue($value);
			
			$this->storefront_settings_model->replace(array(
				'storefront_id' => $storefront,
				'name' => $name,
				'value' => $value,
			));
		}
	}
	
	/**
	 * @param $storefront
	 * @param $type
	 * @return array
	 * @throws waException
	 */
	private function getFormData($storefront, $type)
	{
		$data = $this->getStorefrontData($storefront);
		$result_data = array();
		
		foreach ($data as $key => $value)
		{
			$name = preg_replace('/^' . preg_quote($type) . '_/', '', $key, -1, $count);
			
			if ($count > 0)
			{
				$result_data[$name] = $value;
			}
		}
		
		return $result_data;
	}
	
	private function storeFormData($storefront, $type, $data)
	{
		$to_store_data = array();
		
		foreach ($data as $key => $value)
		{
			$to_store_data["{$type}_{$key}"] = $value;
		}
		
		foreach ($to_store_data as $name => $value)
		{
			$value = $this->transformValue($value);
			
			$this->storefront_settings_model->replace(array(
				'storefront_id' => $storefront,
				'name' => $name,
				'value' => $value,
			));
		}
	}
	
	private function transformValue($value)
	{
		if (is_bool($value))
		{
			$value = $value ? 1 : 0;
		}
		
		if (is_array($value))
		{
			$value = json_encode($value);
		}
		
		return $value;
	}
}