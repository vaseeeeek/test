<?php

class shopEditPluginCatalogUpdateParamsController extends shopEditBackendJsonController
{
	public function execute()
	{
		$this->errors = $this->getStateErrors();

		if (count($this->errors) > 0)
		{
			return;
		}

		$form_state = new shopEditCatalogUpdateParamsFormState($this->state);

		$action = new shopEditCatalogUpdateParamsAction($form_state);
		$action_result = $action->run();

		$this->response['log'] = $action_result->assoc();
	}

	protected function getStateErrors()
	{
		$errors = [];

		if (!isset($this->state['category_selection']) || !is_array($this->state['category_selection']))
		{
			$errors['category_selection'] = 'Нет параметра';
		}

		if (!array_key_exists('target_entity_type', $this->state))
		{
			$errors['target_entity_type'] = 'Нет параметра';
		}

		if (!array_key_exists('params_update_mode', $this->state))
		{
			$errors['params_update_mode'] = 'Нет параметра';
		}

		if (!array_key_exists('additional_params_raw', $this->state))
		{
			$errors['additional_params_raw'] = 'Нет параметра';
		}

		return $errors;
	}
}
