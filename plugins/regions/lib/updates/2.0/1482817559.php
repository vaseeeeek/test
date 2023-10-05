<?php

try
{
	$_settings = new shopRegionsSettingsLegacy();
}
catch (waException $exception)
{
	return;
}

$settings = $_settings->get();

if (isset($settings['ip_analyzer_show']))
{
	return;
}

$settings['ip_analyzer_show'] = 1;

$_settings->update($settings);