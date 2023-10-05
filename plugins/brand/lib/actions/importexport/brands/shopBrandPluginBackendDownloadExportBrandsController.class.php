<?php

class shopBrandPluginBackendDownloadExportBrandsController extends shopBrandCsvDownloadExportController
{
	protected function getFilename()
	{
		$date = date('Ymd_His');

		return "export_brands_{$date}.csv";
	}
}