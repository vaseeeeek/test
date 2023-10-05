<?php

class shopDpPluginBackendUploadDataController extends shopUploadController
{
	protected function save(waRequestFile $file)
	{
		if(!in_array(strtolower($file->extension), array('svg', 'webp', 'jpeg', 'jpg', 'bmp', 'gif', 'png', 'jp2'))) {
			throw new waException('Разрешено загружать только изображения формата JPG/JPEG, GIF, PNG, BMP, JP2, SVG и WEBP');
		}

		if(floatval($file->size / 1024 / 1024) > 5) {
			throw new waException('Разрешено загружать изображения не более 5 мБ!');
		}

		$path = wa()->getDataPath('plugins/dp/data', true, 'shop', true);

		$time = time();

		$filename = "$time.{$file->extension}";

		$file->moveTo("$path/$filename");

		return $filename;
	}
}