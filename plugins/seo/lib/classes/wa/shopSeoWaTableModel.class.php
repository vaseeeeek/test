<?php


class shopSeoWaTableModel extends waModel
{
	public function __construct($table, $type = null, $writable = false)
	{
		$this->table = $table;
		parent::__construct($type, $writable);
		$this->clearMetadataCache();
	}
}