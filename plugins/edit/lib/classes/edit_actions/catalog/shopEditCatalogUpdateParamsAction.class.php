<?php

class shopEditCatalogUpdateParamsAction extends shopEditLoggedAction
{
	private $form_state;

	public function __construct(shopEditCatalogUpdateParamsFormState $form_state)
	{
		$this->form_state = $form_state;

		parent::__construct();
	}

	/**
	 * @return array
	 * @throws waException
	 */
	protected function execute()
	{
		$catalog_params_storage = new shopEditCatalogParamsStorage();

		$params = $this->form_state->getParamsParsed();

		if ($this->form_state->params_update_mode === shopEditCatalogParamsUpdateMode::CLEAR)
		{
			$catalog_params_storage->deleteAllParamsForEntities(
				$this->form_state->target_entity_type,
				$this->form_state->category_selection
			);
		}
		elseif (count($params) > 0)
		{
			if ($this->form_state->params_update_mode === shopEditCatalogParamsUpdateMode::OVERWRITE)
			{
				$catalog_params_storage->overwriteParamsForEntities(
					$this->form_state->target_entity_type,
					$this->form_state->category_selection,
					$params
				);
			}
			elseif ($this->form_state->params_update_mode === shopEditCatalogParamsUpdateMode::ADD_UPDATE)
			{
				$catalog_params_storage->addUpdateParamsForEntities(
					$this->form_state->target_entity_type,
					$this->form_state->category_selection,
					$params
				);
			}
			elseif ($this->form_state->params_update_mode === shopEditCatalogParamsUpdateMode::ADD_IGNORE)
			{
				$catalog_params_storage->addIgnoreParamsForEntities(
					$this->form_state->target_entity_type,
					$this->form_state->category_selection,
					$params
				);
			}
		}

		$params_array = [];
		foreach ($params as $key => $value)
		{
			$params_array[] = [
				'name' => $key,
				'value' => $value,
			];
		}

		return [
			'form_state' => $this->form_state->assoc(),
			'params_array' => $params_array,
		];
	}

	protected function getAction()
	{
		return $this->action_options->CATALOG_UPDATE_PARAMS;
	}
}
