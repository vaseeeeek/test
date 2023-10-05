<?php

class shopEditLogsCollection
{
	const LIMIT = 10;

	private $log_model;
	private $log_storage;

	private $where = array();
	private $where_params = array();

	private $sort = 'id';
	private $order = 'ASC';

	public function __construct()
	{
		$this->log_model = new shopEditLogModel();
		$this->log_storage = new shopEditLogStorage();
	}

	/**
	 * @param int $offset
	 * @param int $limit
	 * @return shopEditLogExtended[]
	 */
	public function getLogs($offset = 0, $limit = 20)
	{
		$query = $this->buildQuery();
		$query->select('id');

		if ($limit > 0)
		{
			$query->limit("{$offset}, {$limit}");
		}

		$logs = array();
		foreach ($query->query() as $row)
		{
			$logs[] = $this->log_storage->getById($row['id']);
		}

		$this->extendLogs($logs);

		return $logs;
	}

	/**
	 * @param int $log_id
	 * @return shopEditLogExtended|null
	 */
	public function getById($log_id)
	{
		$log = $this->log_storage->getById($log_id);
		if (!$log)
		{
			return null;
		}

		$logs = array(0 => $log);
		$this->extendLogs($logs);

		return $logs[0];
	}

	public function count()
	{
		return intval($this->buildQuery()->select('COUNT(id)')->fetchField());
	}

	/**
	 * @param string $condition
	 * @param array|null $params
	 * @return shopEditLogsCollection $this
	 */
	public function where($condition, $params = null)
	{
		$this->where[] = $condition;
		$this->where_params[] = $params;

		return $this;
	}

	/**
	 * @param $sort
	 * @param $order
	 * @return shopEditLogsCollection
	 */
	public function sort($sort, $order)
	{
		$this->sort = $sort;
		$this->order = strtoupper(trim($order)) == 'ASC' ? 'ASC' : 'DESC';

		return $this;
	}

	/**
	 * @param shopEditLogExtended[] $logs
	 */
	private function extendLogs(&$logs)
	{
		foreach ($logs as $log)
		{
			$actor_id = $log->actor_id;
			$actor = $this->getContact($actor_id);
			$log->actor = array(
				'id' => $actor_id,
				'name' => $actor ? $actor->getName() : "--Пользователь с id [{$actor_id}] удален--",
			);

			$log->datetime_humandatetime = wa_date('humandatetime', strtotime($log->datetime));

			$log->params = $this->log_storage->getParams($log->id);
		}
	}

	/**
	 * @param int $contact_id
	 * @return waContact|null
	 */
	private function getContact($contact_id)
	{
		try
		{
			$contact = new waContact($contact_id);
		}
		catch (waException $e)
		{
			return null;
		}

		return $contact->exists() ? $contact : null;
	}

	/**
	 * @return waDbQuery
	 */
	private function buildQuery()
	{
		$query = $this->log_model
			->order("{$this->sort} {$this->order}");

		foreach ($this->where as $index => $condition)
		{
			if (is_array($this->where_params[$index]))
			{
				$query->where($condition, $this->where_params[$index]);
			}
			else
			{
				$query->where($condition);
			}
		}

		return $query;
	}
}