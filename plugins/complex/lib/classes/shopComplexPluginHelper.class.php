<?php

class shopComplexPluginHelper
{
	public static function getNameOfPlugin($plugin)
	{
		$plugin_names = array(
			'opt' => _wp('Wholesale prices'),
			'price' => _wp('Multi prices'),
			'productprices' => 'Разные цены для разных витрин'
		);
		
		return ifset($plugin_names[$plugin]);
	}
}