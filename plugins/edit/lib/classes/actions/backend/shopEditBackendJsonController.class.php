<?php

abstract class shopEditBackendJsonController extends waJsonController
{
	protected $state = array();

	public function run($params = null)
	{
		$state_json = waRequest::request('state');
		$this->state = json_decode($state_json, true);

		if (!$this->stateIsRequired() || is_array($this->state))
		{
			$this->preExecute();
			$this->execute();
		}
		else
		{
			$this->errors['state'] = 'state must be an array';
		}

		$this->display();
	}

	public function display()
	{
		if (waRequest::isXMLHttpRequest())
		{
			$this->getResponse()->addHeader('Content-Type', 'application/json');
		}

		$this->getResponse()->sendHeaders();

		if (count($this->errors) == 0)
		{
			$response = array(
				'status' => 'ok',
				'data' => $this->response,
				'success' => true,
			);
		}
		else
		{
			$response = array(
				'status' => 'fail',
				'errors' => $this->errors,
				'success' => false,
			);
		}

		echo json_encode($response);
	}

	protected function stateIsRequired()
	{
		return true;
	}
}