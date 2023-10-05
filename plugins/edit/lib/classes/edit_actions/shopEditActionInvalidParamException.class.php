<?php

class shopEditActionInvalidParamException extends waException
{
	private $param;
	private $param_error;

	public function __construct($param, $param_error = '')
	{
		parent::__construct();
		$this->param = $param;
		$this->param_error = $param_error;
	}

	public function getParam()
	{
		return $this->param;
	}

	public function getParamError()
	{
		return $this->param_error;
	}
}