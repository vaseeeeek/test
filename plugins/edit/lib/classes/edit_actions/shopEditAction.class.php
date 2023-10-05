<?php

abstract class shopEditAction
{
	public function __construct()
	{
	}

	/**
	 * @return array
	 */
	public function run()
	{
		$this->preExecute();

		return $this->execute();
	}

	protected function preExecute()
	{
		return true;
	}

	/**
	 * @return array
	 */
	abstract protected function execute();
}