<?php

class shopBrandThumbRequestHandler
{
	public function handle($request_file)
	{
		try
		{
			$this->trySendFileResponse($request_file);
		}
		catch (waException $e)
		{
			$this->exitWithNotFoundResponse();
		}
	}

	/**
	 * @param $request_file
	 * @throws waException
	 */
	private function trySendFileResponse($request_file)
	{
		list($original_image_path, $thumb_image_path, $size) = $this->tryGetImagePaths($request_file);

		if (!file_exists($thumb_image_path))
		{
			$this->tryToCreateThumb($original_image_path, $thumb_image_path, $size);
		}

		waFiles::readFile($thumb_image_path);
	}

	/**
	 * @param $request_file
	 * @return array
	 * @throws waException
	 */
	private function tryGetImagePaths($request_file)
	{
		//$settings_storage = new shopBrandSettingsStorage();
		//$plugin_settings = $settings_storage->getSettings();
		$brand_image_storage = new shopBrandBrandImageStorage();
		$plugin_image_storage = new shopBrandImageStorage();


		list($original_image_name, $size) = $plugin_image_storage->parseOptimizedImageFileName($request_file);

		if (!is_string($size) || !$brand_image_storage->isSizeAvailable($size))
		{
			throw new waException('некорректный размер');
		}

		if (!$plugin_image_storage->isOriginalFileExists($original_image_name))
		{
			throw new waException('нет оригинального файла');
		}


		$original_image_path = $plugin_image_storage->getOriginalImagePath($original_image_name);
		$thumb_image_path = $plugin_image_storage->getOptimizedImagePath($original_image_name, $size);

		if (!is_string($thumb_image_path))
		{
			throw new waException('не получилось получить путь до оптимизированного изображения');
		}

		return array($original_image_path, $thumb_image_path, $size);
	}

	/**
	 * @param $original_image_path
	 * @param $thumb_image_path
	 * @param $size
	 * @throws waException
	 */
	private function tryToCreateThumb($original_image_path, $thumb_image_path, $size)
	{
		$create_error = '';
		try
		{
			$this->removeWatermarkPluginFromEventListeners();

			$thumb_image = shopImage::generateThumb($original_image_path, $size);

			$this->restoreRemovedEventHandlers();
		}
		catch (waException $e)
		{
			$create_error = ' :' . $e->getMessage();
			$thumb_image = null;
		}

		if (!$thumb_image)
		{
			throw new waException("не удалось создать оптимизированное изображение{$create_error}");
		}

		$save_error = '';
		try
		{
			$save_result = $thumb_image->save($thumb_image_path, 90);
		}
		catch (waException $e)
		{
			$save_error = ' :' . $e->getMessage();
			$save_result = false;
		}

		if (!$save_result)
		{
			throw new waException("не удалось сохранить оптимизированное изображение{$save_error}");
		}

		clearstatcache();
	}

	private function exitWithNotFoundResponse()
	{
		header("HTTP/1.0 404 Not Found");

		exit;
	}

	private function removeWatermarkPluginFromEventListeners()
	{
		shopBrandWaEventHandlerAccess::removePluginHandler('shop', 'watermark', 'image_thumb');
		shopBrandWaEventHandlerAccess::removePluginHandler('shop', 'watermark', 'image_generate_thumb');
	}

	private function restoreRemovedEventHandlers()
	{
		shopBrandWaEventHandlerAccess::restoreRemovedHandlers();
	}
}
