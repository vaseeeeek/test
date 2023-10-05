<?php

class shopBrandPluginBackendSetupAction extends waViewAction
{
	private $group_storefront_service;

	public function __construct($params = null)
	{
		parent::__construct($params);
	}

	public function execute()
	{
		wa()->getView()->assign([
			'encoding' => $this->getEncoding(),
			'delimiters' => $this->getDelimiters(),
			'version' => $this->getPluginVersion(),
		]);
	}

	private function getEncoding()
	{
		$encoding = array_diff(mb_list_encodings(), array(
			'pass',
			'wchar',
			'byte2be',
			'byte2le',
			'byte4be',
			'byte4le',
			'BASE64',
			'UUENCODE',
			'HTML-ENTITIES',
			'Quoted-Printable',
			'7bit',
			'8bit',
			'auto',
		));

		$popular = array_intersect(array('UTF-8', 'Windows-1251', 'ISO-8859-1',), $encoding);

		asort($encoding);

		return array_values(array_unique(array_merge($popular, $encoding)));
	}

	private function getDelimiters()
	{
		return array(
			array('value' => ';', 'name' => 'Точка с запятой (;)'),
			array('value' => ',', 'name' => 'Запятая (,)'),
			array('value' => 'tab', 'name' => 'Табуляция'),
		);
	}

	private function getPluginVersion()
	{
		return wa('shop')->getPlugin('brand')->getVersion();
	}
}