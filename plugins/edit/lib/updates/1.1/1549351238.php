<?php

$model = new waModel();

try
{
	$row = $model->query('
SHOW COLUMNS
FROM shop_edit_log
WHERE field = \'action\'
')->fetchAssoc();

	if ($row)
	{
		$type = null;

		if (array_key_exists('type', $row))
		{
			$type = $row['type'];
		}
		elseif (array_key_exists('Type', $row))
		{
			$type = $row['Type'];
		}

		if (is_string($type) && strtolower(substr($type, 0, 4)) == 'enum')
		{
			$model->exec('
ALTER TABLE `shop_edit_log`
	ALTER `action` DROP DEFAULT
');

			$model->exec('
ALTER TABLE `shop_edit_log`
	CHANGE COLUMN `action` `action` VARCHAR(100) NOT NULL AFTER `id`
');
		}
	}
}
catch (Exception $e)
{
}