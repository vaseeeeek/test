<?php

class shopEditPluginLogGetAllController extends shopEditBackendJsonController
{
	public function execute()
	{
		$collection = new shopEditLogsCollection();

		$logs = $collection->sort('id', 'DESC')->getLogs(0, 0);

		$this->response['action_logs'] = $this->response['action_logs'] = array_map(array($this, 'toAssoc'), $logs);
	}

	private function toAssoc(shopEditLog $log)
	{
		return $log->assoc();
	}

	protected function stateIsRequired()
	{
		return false;
	}
}