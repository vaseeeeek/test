<?php

abstract class shopBrandCsvExportController extends shopBrandImportexportController
{
	/** @var shopCsvWriter */
	protected $writer;

	abstract protected function getMap();

	protected function init()
	{
		$path = waSystem::getInstance()->getTempPath("brand/csv_export/{$this->processId}") . "/csv_export.csv";
		$this->writer = new shopCsvWriter(
			$path,
			waRequest::request('delimiter'),
			waRequest::request('encoding')
		);
		$this->writer->setMap($this->getMap());
		$this->data['csv_writer'] = serialize($this->writer);
	}

	protected function restore()
	{
		$this->writer = unserialize($this->data['csv_writer']);
	}

	protected function write($exported_filter)
	{
		$this->writer->write($exported_filter);
		$this->data['csv_writer'] = serialize($this->writer);
	}
}