<?php

class shopComplexPluginActions extends waViewActions
{
	protected $json = false;
	protected $return_run = false;
	protected $response = array();
	protected $errors = array();
	
	private $pre_executed = false;
	
	public function preExecute()
	{
		if(!$this->pre_executed) {
			$this->plugin = wa('shop')->getPlugin('complex');

			$this->view->assign('plugin_url', $this->plugin->getPluginStaticUrl());
			
			try {
				$this->view->smarty->registerPlugin('function', 'decl_of_num', array(
					'shopComplexPluginActions',
					'declOfNum'
				));
			} catch(SmartyException $e) {
			}
			
			$this->pre_executed = true;
		}
	}
	
	public function setJson()
	{
		$this->json = true;
	}
	
	public function disableJson()
	{
		$this->json = false;
	}

	public function getPluginRoot()
	{
		return wa()->getAppPath('plugins/complex/', 'shop');
	}

	public function returnRun($params)
	{
		$this->return_run = true;
		$output = $this->run($params);
		$this->return_run = false;
		
		return $output;
	}
	
	public function setParams($params)
	{
		$this->params = $params;
	}
	
	public function run($params = null)
	{
		if($this->return_run) {
			$action = $params;
			if(!$action)
				$action = 'default';
			
			$this->action = $action;
			$this->preExecute();
			$this->execute($this->action);
			$this->postExecute();
			
			return $this->display();
		} else
			parent::run($params);
	}
	
	public function setError($message, $data = array())
	{
		$this->errors[] = array($message, $data);
	}
	
	public function display()
	{
		if($this->return_run) {
			$this->getResponse()->sendHeaders();
			return $this->view->fetch($this->getTemplate());
		} elseif($this->json) {
			if(waRequest::isXMLHttpRequest())
				$this->getResponse()->addHeader('Content-type', 'application/json');
			$this->getResponse()->sendHeaders();
			
			if(!$this->errors) {
				$data = array('status' => 'ok', 'data' => $this->response);
				echo json_encode($data);
			} else
				echo json_encode(array('status' => 'fail', 'errors' => $this->errors));
		} else
			parent::display();
	}
	
	public static function declOfNum($params)
	{
		$number = $params['n'];
		$titles = $params['t'];
		$cases = array(2, 0, 1, 1, 1, 2);
		
		return $titles[ ($number%100>4 && $number%100<20)? 2 : $cases[min($number%10, 5)] ];
	} 
}