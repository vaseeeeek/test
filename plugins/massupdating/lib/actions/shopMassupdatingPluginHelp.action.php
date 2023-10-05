<?php

class shopMassupdatingPluginHelpAction extends waViewAction
{
	public function __construct($params = null)
	{
		parent::__construct($params);
		
		$article = waRequest::get('article');
		if(!in_array($article, array('farVariables', 'farAdvancedSearchType')))
			echo _wp('Ошибка');
		
		$this->setTemplate('file:' . $this->getPluginRoot() . 'templates/help/' . $article . '.html');
	}
}