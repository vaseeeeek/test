<?php

/*
 * mail@shevsky.com
 */

class shopMassupdatingPluginVideoCheckController extends waJsonController
{
	public function execute()
	{
		$value = waRequest::get('value', '', 'string');
		
		$app_info = wa()->getAppInfo('shop');
		if($app_info['version'] >= 7) {
			$checked = shopVideo::checkVideo($value);
			if($checked)
				$this->response = 'yes';
			else
				$this->response = 'no';
		} else $this->setError(_wp('Функция "Видео" есть только в версиях Shop-Script 7 и выше'));
	}
}