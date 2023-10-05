<?php

class shopBrandImageStorage
{
	const OPTIMIZED_IMAGES_PATH = 'plugins/brand/brand_image_optimized/';
	const ORIGINAL_IMAGES_PATH = 'plugins/brand/brand_image/';

	const SIZE_BACKEND_LIST = '0x100';
	const SIZE_BACKEND_REVIEWS = '48';

	private static $image_extensions = array(
		'bmp' => 'bmp',
		'png' => 'png',
		'jpg' => 'jpg',
		'jpeg' => 'jpeg',
		'gif' => 'gif',
		'svg' => 'svg',
		'tif' => 'tif',
		'tiff' => 'tiff',
		'webp' => 'webp',
	);

	private static $optimizable_image_extensions = array(
		'jpg' => 'jpg',
		'jpeg' => 'jpeg',
		'gif' => 'gif',
		'png' => 'png',
	);

	public function getOriginalImageRootPath()
	{
		return wa()->getDataPath(self::ORIGINAL_IMAGES_PATH, true, 'shop', true);
	}

	public function getOriginalImagePath($image_name)
	{
		return $this->getOriginalImageRootPath() . $image_name;
	}

	public function getOriginalImageUrl($image_name, $absolute = false)
	{
		return wa()->getDataUrl(self::ORIGINAL_IMAGES_PATH, true, 'shop', $absolute) . $image_name;
	}


	public function getOptimizedImagePath($original_image_name, $size)
	{
		if (
			!is_string($original_image_name) || $original_image_name === ''
			|| !is_string($size) || $size === ''
		)
		{
			return null;
		}

		$path_info = pathinfo($original_image_name);
		$ext = isset($path_info['extension']) ? $path_info['extension'] : '';
		if ($ext === '')
		{
			return null;
		}

		$ext = strtolower($ext);

		return wa()->getDataPath(self::OPTIMIZED_IMAGES_PATH, true, 'shop', true) . "{$path_info['filename']}.{$size}.{$ext}";
	}

	public function getOptimizedImageUrl($image_name, $size, $absolute = false)
	{
		if (
			!is_string($image_name) || $image_name === ''
			|| !is_string($size) || $size === ''
		)
		{
			return null;
		}

		$path_info = pathinfo($image_name);
		$ext = isset($path_info['extension']) && is_string($path_info['extension'])
			? $path_info['extension']
			: '';

		if ($ext === '')
		{
			return null;
		}

		$ext = strtolower($ext);

		if (!array_key_exists($ext, self::$optimizable_image_extensions))
		{
			return null;
		}

		$image_path = wa()->getDataPath(self::OPTIMIZED_IMAGES_PATH, true, 'shop', true) . "{$path_info['filename']}.{$size}.{$ext}";

		if (file_exists($image_path))
		{
			$base_path = self::OPTIMIZED_IMAGES_PATH;
		}
		else
		{
			if (waSystemConfig::systemOption('mod_rewrite'))
			{
				$base_path = self::OPTIMIZED_IMAGES_PATH;
			}
			else
			{
				$base_path = self::OPTIMIZED_IMAGES_PATH . 'thumb.php/';
			}
		}

		return wa()->getDataUrl("{$base_path}{$path_info['filename']}.{$size}.{$ext}", true, 'shop', $absolute);
	}

	public function isOriginalFileExists($image_file_name)
	{
		$image_path = $this->getOriginalImagePath($image_file_name);

		//$image_path_info = pathinfo($image_path);
		//$extension = strtolower($image_path_info['extension']);

		// todo зачем тут $image_extensions?
		//return file_exists($image_path) && array_key_exists($extension, self::$image_extensions);
		return file_exists($image_path);
	}

	public function getAllOptimizedImagePaths($image_name)
	{
		if (!is_string($image_name) || $image_name === '')
		{
			return array();
		}

		$path_info = pathinfo($image_name);
		$ext = isset($path_info['extension']) ? $path_info['extension'] : '';
		if ($ext === '')
		{
			return array();
		}

		$ext = strtolower($ext);

		$optimized_images_dir = wa()->getDataPath(self::OPTIMIZED_IMAGES_PATH, true, 'shop', true);

		$all_images = array();

		foreach (waFiles::listdir($optimized_images_dir) as $optimized_file_name)
		{
			if ($optimized_file_name === '.' || $optimized_file_name === '..')
			{
				continue;
			}

			list($original_file_name, $size) = $this->parseOptimizedImageFileName($optimized_file_name);

			if ($original_file_name === $image_name)
			{
				$all_images[] = $optimized_images_dir . $optimized_file_name;
			}
		}

		return $all_images;
	}

	public function parseOptimizedImageFileName($file_name)
	{
		if (
			!preg_match('/(.*)\.([0-9]+x[0-9]+)\.([a-z0-9]+)/i', $file_name, $matches)
			&& !preg_match('/(.*)\.([0-9]+)\.([a-z0-9]+)/i', $file_name, $matches)
		)
		{
			return array(null, null);
		}

		$size = $matches[2];
		$original_image_name = $matches[1] . '.' . $matches[3];

		return array($original_image_name, $size);
	}

	public function isBuiltInImageSize($size)
	{
		return $size === self::SIZE_BACKEND_LIST || $size === self::SIZE_BACKEND_REVIEWS;
	}



	public function handleImageUpload(waRequestFile $file, &$new_file_name)
	{
		$name = preg_replace('/[^a-z_0-9.]/i', '', shopBrandHelper::transliterate($file->name));
		$image_path = $this->getOriginalImagePath($name);

		$path_info = pathinfo($image_path);

		$file_name = $path_info['filename'];
		$file_ext = array_key_exists('extension', $path_info)
			? strtolower($path_info['extension'])
			: '';

		if (!array_key_exists($file_ext, self::$image_extensions))
		{
			return false;
		}

		$new_file_name = file_exists($image_path)
			? uniqid($file_name)
			: $file_name;

		$new_file_name .=  '.' . $file_ext;

		return !!$file->moveTo($this->getOriginalImageRootPath(), $new_file_name);
	}

	public function createThumbFile()
	{
		$target_path = wa()->getDataPath(self::OPTIMIZED_IMAGES_PATH, true, 'shop', true);
		$htaccess_source_path = wa()->getAppPath('lib/config/data/', 'shop');

		$target = $target_path.'thumb.php';
		if (!file_exists($target))
		{
			$php_file = '<?php
$file = dirname(__FILE__)."/../../../../../../"."/wa-apps/shop/plugins/brand/lib/images-magic/thumb.php";

if (file_exists($file)) {
    include($file);
} else {
    header("HTTP/1.0 404 Not Found");
}
';
			waFiles::write($target, $php_file);
		}

		$target = $target_path . '.htaccess';
		if (!file_exists($target))
		{
			waFiles::copy($htaccess_source_path . '.htaccess', $target);
		}
	}
}
