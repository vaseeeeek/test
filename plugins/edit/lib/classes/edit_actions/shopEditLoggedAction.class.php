<?php

abstract class shopEditLoggedAction extends shopEditAction
{
	protected $action_options;

	private $log_storage;
	private $log_collection;

	public function __construct()
	{
		parent::__construct();

		$this->action_options = new shopEditLogActionEnumOptions();

		$this->log_storage = new shopEditLogStorage();
		$this->log_collection = new shopEditLogsCollection();
	}

	/**
	 * @return shopEditLogExtended|null
	 */
	public function run()
	{
		$this->preExecute();
		$execute_result = $this->execute();

		$action = $this->getAction();
		$log_id = $this->log_storage->writeToLog($action, wa()->getUser()->getId(), $execute_result);
		$log = $this->log_collection->getById($log_id);

		return $log ? $log : null;
	}

	/**
	 * @return string
	 */
	abstract protected function getAction();
}