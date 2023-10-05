<?php

class shopArrivedPluginExportCsvController extends waJsonController
{

	private function getMapFields()
	{
		if(waRequest::get('rating')) {
			$fields = array(
				"name" => _wp('Product name'),
				"sku_name" => _wp('SKU name'),
				"sku" => _wp('SKU code'),
				"count_active" => _wp('Active'),
				"count_total" => _wp('Total')
			);
		} elseif(waRequest::get('sended')) {
			$fields = array(
				"name" => _wp('Product name'),
				"sku_name" => _wp('SKU name'),
				"sku" => _wp('SKU code'),
				"email" => _wp('Email'),
				"phone" => _wp('Phone'),
				"expiration" => _wp('Actual'),
				"created" => _wp('Created'),
				"date_sended" => _wp('Sended')
			);
		} else {
			$fields = array(
				"name" => _wp('Product name'),
				"sku_name" => _wp('SKU name'),
				"sku" => _wp('SKU code'),
				"email" => _wp('Email'),
				"phone" => _wp('Phone'),
				"expiration" => _wp('Actual'),
				"created" => _wp('Created')
			);
		}
		return $fields;
	}

    public function execute()
    {
        $helper = new shopArrivedPluginReport();
		$items = $helper->getItems();
		$encoding = waRequest::post('encoding', 'UTF-8', waRequest::TYPE_STRING_TRIM);
		$delimiter = waRequest::post('delimiter', ';', waRequest::TYPE_STRING_TRIM);
		// save export params for future
		$settings = include shopArrivedPlugin::path('config.php');
		$settings['encoding'] = $encoding;
		$settings['delimiter'] = $delimiter;
		$config_settings_file = shopArrivedPlugin::path('config.php');
		waUtils::varExportToFile($settings, $config_settings_file);
		// quickfix of rewriting issue
		$file = wa()->getTempPath('plugins/arrived/export.csv');
		waFiles::delete($file);
		// switch Tab delimiter to correct
		if($delimiter == 'tab')
			$delimiter = '\t';
		// write file via shopCsvWriter
		$writer = new shopCsvWriter($file,$delimiter,$encoding);
		$writer->setMap($this->getMapFields());
		foreach($items as $row)
		{
			$row['name'] = ifset($row['product']['name']);
			$row['sku'] = ifset($row['product']['sku']['sku']);
			$row['sku_name'] = ifset($row['product']['sku']['name']);
			$writer->write($row);
		}
		waFiles::readFile($file, "arrived-".date('d.m.Y').".csv");
    }

}