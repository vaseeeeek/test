<?php

class shopEditPluginLogGetPageController extends shopEditBackendJsonController
{
	const LIMIT = 10;

	public function execute()
	{
		$page = $this->state['page'];

		$offset = ($page - 1) * self::LIMIT;

		$collection = new shopEditLogsCollection();
		$logs = $collection
			->sort('id', 'DESC')
			->getLogs($offset, self::LIMIT);

		$count = $collection->count();

		$this->response['action_logs'] = array_map(array($this, 'toAssoc'), $logs);
		$this->response['count'] = $count;
		$this->response['pages_count'] = ceil($count / shopEditPluginLogGetPageController::LIMIT - 1e-6);
	}

	private function toAssoc(shopEditLog $log)
	{
		return $log->assoc();
	}
}