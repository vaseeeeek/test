<?php

class shopEditLogStorage extends shopEditStorage
{
	private $param_model;
	//private $action_options;

	public function __construct()
	{
		$this->param_model = new shopEditLogParamModel();
		//$this->action_options = new shopEditLogActionEnumOptions();

		parent::__construct();
	}

	public function getAll()
	{
		$logs = array();
		foreach ($this->model->select('id')->query() as $row)
		{
			$log = $this->getById($row['id']);
			if ($log)
			{
				$logs[] = $log;
			}
		}

		return $logs;
	}

	public function writeToLog($action, $actor_id, $log_params)
	{
		$log_id = $this->model->insert(array(
			'action' => $action,
			'datetime' => date('Y-m-d H:i:s'),
			'actor_id' => $actor_id,
		));

		if (!($log_id > 0))
		{
			return null;
		}

		if (is_array($log_params) && count($log_params) != 0)
		{
			foreach ($log_params as $name => $value)
			{
				$value_is_json = !(is_numeric($value) || is_null($value) || is_string($value));

				$this->param_model->insert(array(
					'log_id' => $log_id,
					'name' => $name,
					'value' => $value_is_json ? json_encode($value) : $value,
					'value_is_json' => $value_is_json ? shopEditLogParamModel::BOOL_TRUE : shopEditLogParamModel::BOOL_FALSE,
				));
			}
		}

		return $log_id;
	}

	public function getParams($log_id)
	{
		$query = $this->param_model
			->select('name,value,value_is_json')
			->where('log_id = :log_id', array('log_id' => $log_id))
			->query();

		$params = array();
		foreach ($query as $row)
		{
			$params[$row['name']] = $row['value_is_json'] == shopEditLogParamModel::BOOL_TRUE
				? json_decode($row['value'], true)
				: $row['value'];
		}

		return $params;
	}

	protected function accessSpecification()
	{
		$spec = new shopEditDataFieldSpecificationFactory();

		return array(
			'id' => $spec->integer(),
			'action' => $spec->string(),
			'datetime' => $spec->datetime(),
			'actor_id' => $spec->integer(),
		);
	}

	protected function dataModelInstance()
	{
		return new shopEditLogModel();
	}

	protected function entityInstance()
	{
		return new shopEditLog();
	}
}