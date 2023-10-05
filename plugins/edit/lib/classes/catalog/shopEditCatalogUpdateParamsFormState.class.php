<?php

class shopEditCatalogUpdateParamsFormState
{
	public $target_entity_type;
	public $category_selection;
	public $params_update_mode;
	public $additional_params_raw;

	public function __construct($state_assoc)
	{
		$this->target_entity_type = $state_assoc['target_entity_type'];
		$this->category_selection = new shopEditCategorySelection($state_assoc['category_selection']);
		$this->params_update_mode = $state_assoc['params_update_mode'];
		$this->additional_params_raw = $state_assoc['additional_params_raw'];
	}

	public function getParamsParsed()
	{
		if (!is_string($this->additional_params_raw) || trim($this->additional_params_raw) === '')
		{
			return [];
		}

		$params = [];
		foreach (explode("\n", trim($this->additional_params_raw)) as $param_row)
		{
			$param_parts = explode('=', $param_row, 2);
			if (count($param_parts) > 1)
			{
				$params[$param_parts[0]] = trim($param_parts[1]);
			}
		}

		return $params;
	}

	public function assoc()
	{
		return [
			'target_entity_type' => $this->target_entity_type,
			'category_selection' => $this->category_selection->assoc(),
			'params_update_mode' => $this->params_update_mode,
			'additional_params_raw' => $this->additional_params_raw,
		];
	}
}
