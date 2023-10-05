<?php

class shopRegionsUserEnvironmentModel extends waModel
{
	const RECORD_TTL = 1800;

	protected $table = 'shop_regions_user_environment';

	public function saveUser($cookies, $key)
	{
		$model = new self();

		$s_cookies = json_encode($cookies);

		if ($s_cookies === false)
		{
			return null;
		}

		$data = array(
			'key' => $key,
			'cookies' => $s_cookies,
			'time' => time(),
		);

		$row = $model->getByField('key', $key);

		$success = true;
		if ($row)
		{
			if ($s_cookies !== $row['cookies'])
			{
				$success = $model->updateByField('key', $key, $data);
			}
		}
		else
		{
			$this->clearOldRows();
			$success = $model->insert($data);
		}

		return $success ? $key : null;
	}

	public function loadUserEnvironment($key)
	{
		$model = new self();
		$environment = $model->getByField('key', $key);

		if (!$environment)
		{
			return array();
		}

		$cookies = json_decode($environment['cookies'], true);

		if ($cookies === null)
		{
			$cookies = @unserialize($environment['cookies']);

			if ($cookies === false)
			{
				return array();
			}
		}

		return $cookies ? $cookies : array();
	}

	public function generateKey()
	{
		return $key = uniqid("", true);
	}

	private function clearOldRows($ttl = self::RECORD_TTL)
	{
		$model = new self();

		$sql = 'DELETE FROM `%1$s`
WHERE `time` < \'%2$d\'';
		$query = sprintf(
			$sql,
			$model->getTableName(),
			time() - $ttl
		);

		return $model->query($query);
	}
}