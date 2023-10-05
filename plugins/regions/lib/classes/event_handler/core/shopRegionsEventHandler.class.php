<?php

abstract class shopRegionsEventHandler
{
	private $handler_params;

	public function __construct($handler_params = null)
	{
		$this->handler_params = $handler_params;
	}

	public function handle()
	{
		$handler_result = $this->aggregatorInitialValue();

		foreach ($this->actions() as $handler_action)
		{
			$handler_result = $this->aggregateHandleResults($handler_result, $handler_action->execute($this->handler_params));
		}

		return $handler_result;
	}

	/**
	 * @return shopRegionsIHandlerAction[]
	 */
	protected abstract function actions();

	protected function aggregateHandleResults($accumulator, $handler_result)
	{
		if ($accumulator === null)
		{
			return $handler_result;
		}

		if (is_array($handler_result))
		{
			foreach ($handler_result as $field => $value)
			{
				$accumulator[$field] = $this->aggregateHandleResults(ifset($accumulator[$field]), $value);
			}
		}
		else
		{
			$accumulator .= PHP_EOL . $handler_result;
		}

		return $accumulator;
	}

	protected function aggregatorInitialValue()
	{
		return null;
	}

	protected function getInitialHandlerParams()
	{
		return $this->handler_params;
	}
}