<?php

class shopBdgPluginFiles
{
	const PLUGIN_ID = 'bdg';
	
	protected $_themes = array();
	protected $_current_theme = '';
	protected $_data_url = '';
	protected $_data_path = '';
	protected $_general_data_url = '';
	protected $_general_data_path = '';
	protected $_app_url = '';
	protected $_app_path = '';
	protected $_paths = array(
		'css' => 'css/shopBdgPlugin.css',
		'js' => 'js/shopBdgPlugin.js',
		'head' => 'templates/head.html',
	);
	
	public function __construct($current_theme = '')
	{
		$this->_themes = array_keys(wa()->getThemes('shop'));
		$this->_current_theme = $current_theme;
		
		if ( wa()->getEnv() == 'frontend' )
		{
			$r = wa('shop')->getRouting();
			$rout = $r->getRoute();
			$this->_current_theme = $rout['theme'];
		}
		
		$this->_app_url = 'plugins/'.self::PLUGIN_ID.'/';
		$this->_app_path = wa()->getAppPath($this->_app_url,'shop');
		
		$path = $this->_app_url.$this->_current_theme.'/';
		$this->_data_url = wa('shop')->getDataUrl($path,true,'shop',true);
		$this->_data_path = wa()->getDataPath($path,true,'shop');
		
		$path = $this->_app_url.'_/';
		$this->_general_data_url = wa('shop')->getDataUrl($path,true,'shop',true);
		$this->_general_data_path = wa()->getDataPath($path,true,'shop');
	}
	
	public function getThemes()
	{
		return $this->_themes;
	}
	
	public function getFileContent($name)
	{
		$path = trim($this->_paths[$name],'/');
		$default = $this->_app_path.$path;
		$custom = $this->_data_path.$path;
		$general = $this->_general_data_path.$path;
		if ( file_exists($custom) )
			$file = $custom;
		elseif ( file_exists($general) )
			$file = $general;
		else
			$file = $default;
		
		$content = '';
		if ( file_exists($file) )
			$content = file_get_contents($file);
		return $content;
	}
	
	
	public function save($content,$path)
	{
		if ( !empty($content) )
		{
			$file = $this->_data_path.trim($path,'/');
			if ( !file_exists($file) )
				waFiles::create($file);
			file_put_contents($file,$content);
		}
	}
	

	public function saveFromPostData()
	{
		foreach ( $this->_paths as $name=>$path )
		{
			$content = waRequest::post($name,'',waRequest::TYPE_STRING_TRIM);
			if ( !empty($content) )
				$this->save($content,$path);
		}
	}
	
	public function addCss($name)
	{
		$response = waSystem::getInstance()->getResponse();
		
		$path = trim($this->_paths[$name],'/');
		$file = $this->_data_path.$path;
		$general = $this->_general_data_path.$path;
		if ( file_exists($file) )
			$response->addCss($this->_data_url.$path);
		elseif ( file_exists($general) )
			$response->addCss($this->_general_data_url.$path);
		else
			$response->addCss($this->_app_url.$path,'shop');
	}
	
	public function addJs($name)
	{
		$response = waSystem::getInstance()->getResponse();
		
		$path = trim($this->_paths[$name],'/');
		$file = $this->_data_path.$path;
		$general = $this->_general_data_path.$path;
		if ( file_exists($file) )
			$response->addJs($this->_data_url.$path);
		elseif ( file_exists($general) )
			$response->addJs($this->_general_data_url.$path);
		else
			$response->addJs($this->_app_url.$path,'shop');
	}
}