<?php

class shopEditPluginProductUpdatePriceController extends shopEditBackendJsonController
{
	public function execute()
	{
		$this->preValidateStateParams();

		$form_state = $this->tryCreateFormState();

		if (!$form_state || count($this->errors) > 0)
		{
			return;
		}


		$action = new shopEditProductUpdatePriceAction($form_state);
		try
		{
			$action_result = $action->run();
			$this->response['log'] = $action_result->assoc();
		}
		catch (Exception $e)
		{
			$this->errors['action_run_error'] = $e->getMessage();
		}
	}

	private function preValidateStateParams()
	{
		if (!array_key_exists('products_selection', $this->state))
		{
			$this->errors['products_selection'] = 'Обязательное поле';
		}

		if (!array_key_exists('skip_zero_price', $this->state))
		{
			$this->errors['skip_zero_price'] = 'Обязательное поле';
		}

		if (!array_key_exists('only_with_currency', $this->state))
		{
			$this->errors['only_with_currency'] = 'Обязательное поле';
		}

		if (!array_key_exists('price_type_selection', $this->state))
		{
			$this->errors['price_type_selection'] = 'Обязательное поле';
		}

		if (!array_key_exists('change_price_mode', $this->state))
		{
			$this->errors['change_price_mode'] = 'Обязательное поле';
		}

		if (!array_key_exists('change_price_amount', $this->state))
		{
			$this->errors['change_price_amount'] = 'Обязательное поле';
		}

		$change_price_amount = $this->state['change_price_amount'];
		if (is_string($change_price_amount))
		{
			$change_price_amount = str_replace(',', '.', $change_price_amount);
		}

		if (!is_numeric($change_price_amount) || preg_match('/[^-0-9.,]/', $change_price_amount))
		{
			$this->errors['change_price_amount'] = 'Введите число';
		}
		elseif (abs($change_price_amount) < 1e-6)
		{
			$this->errors['change_price_amount'] = 'Должно быть не ноль';
		}

		if (!array_key_exists('round_mode', $this->state))
		{
			$this->errors['round_mode'] = 'Обязательное поле';
		}

		if (!array_key_exists('round_up_only', $this->state))
		{
			$this->errors['round_up_only'] = 'Обязательное поле';
		}
	}

	private function tryCreateFormState()
	{
		try
		{
			$form_state = new shopEditProductUpdatePriceFormState($this->state);
		}
		catch (Exception $e)
		{
			$this->errors['form_state'] = 'error';

			return null;
		}

		$price_type_storage = new shopEditProductPriceTypeStorage();
		$price_types = $price_type_storage->getSelectedPriceTypes($form_state->price_type_selection);

		if (count($price_types) == 0)
		{
			$this->errors['price_type_selection'] = 'Не выбрано ни одного типа цены';
		}

		return $form_state;
	}
}