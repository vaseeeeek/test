<?php

try
{
	$path = wa()->getAppPath('plugins/regions/lib/classes/sxgeo', 'shop');
	waFiles::delete($path);
}
catch (Exception $e)
{}