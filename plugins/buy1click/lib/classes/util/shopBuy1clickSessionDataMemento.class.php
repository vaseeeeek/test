<?php

class shopBuy1clickSessionDataMemento
{
	private $original_data = [];

	public function replace($data_key, $data)
	{
		if (!array_key_exists($data_key, $this->original_data))
		{
			$this->original_data[$data_key] = wa()->getStorage()->get($data_key);
		}

		wa()->getStorage()->set($data_key, $data);
	}

	public function rollback($data_key)
	{
		if (array_key_exists($data_key, $this->original_data))
		{
			wa()->getStorage()->set($data_key, $this->original_data[$data_key]);
		}
	}
}