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

if (isset($settings['window_header']))
{
	return;
}


$settings['window_header'] = 'Укажите свой город';
$settings['window_subheader'] = 'От этого зависит стоимость доставки и варианты оплаты в ваш регион';

$_settings->update($settings);