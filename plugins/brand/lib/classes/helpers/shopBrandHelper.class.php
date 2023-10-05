<?php

class shopBrandHelper
{
	private static $info = null;

	public static function getBrandFeature()
	{
		$settings_storage = new shopBrandSettingsStorage();
		$settings = $settings_storage->getSettings();

		$brand_feature = $settings->brand_feature;
		if (!$brand_feature)
		{
			throw new waException('brand feature is null');
		}

		return $brand_feature;
	}

	public static function transliterate($string)
	{
		static $transliteration = array(
			'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
			'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's',
			'т' => 't', 'у' => 'u', 'ф' => 'f', 'ы' => 'y', 'э' => 'e', 'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G',
			'Д' => 'D', 'Е' => 'E', 'Ж' => 'ZH', 'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M',
			'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Ы' => 'Y',
			'Э' => 'E', 'ё' => 'yo', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ъ' => '',
			'ь' => '', 'ю' => 'yu', 'я' => 'ya', 'Ё' => 'YO', 'Х' => 'H', 'Ц' => 'TS', 'Ч' => 'CH', 'Ш' => 'SH',
			'Щ' => 'SHCH', 'Ъ' => '', 'Ь' => '', 'Ю' => 'YU', 'Я' => 'YA', ' ' => '-', '+' => '-',
		);

		return strtr($string, $transliteration);
	}

	public static function toCanonicalUrl($url)
	{
		return strtolower(preg_replace('/[^a-z_0-9\-]/i', '', self::transliterate($url)));
	}

	public static function getPath($path)
	{
		return wa('shop')->getAppPath('plugins/brand/' . $path, 'shop');
	}

	public static function getDataPath($path, $public = false)
	{
		return wa('shop')->getDataPath('plugins/brand/', $public, 'shop') . $path;
	}

	public static function getStaticUrl($url, $absolute = false)
	{
		return wa('shop')->getAppStaticUrl('shop', $absolute) . 'plugins/brand/' . $url;
	}

	public static function getDataStaticUrl($url, $absolute = false)
	{
		return wa('shop')->getDataUrl('plugins/brand/' . $url, true, 'shop', $absolute);
	}


	/**
	 * @param string $file_name
	 * @param waResponse|null $response
	 */
	public function addPluginJs($file_name, $response = null)
	{
		if (!$response)
		{
			$response = wa()->getResponse();
		}

		$response->addJs('plugins/brand/js/' . $file_name, 'shop');
	}

	/**
	 * @param string $file_name
	 * @param waResponse|null $response
	 */
	public function addPluginCss($file_name, $response = null)
	{
		if (!$response)
		{
			$response = wa()->getResponse();
		}

		$response->addCss('plugins/brand/css/' . $file_name, 'shop');
	}

	public static function getStorefront()
	{
		return shopBrandStorefront::getCurrent();
	}

	public static function getAssetVersion()
	{
		if (self::$info === null)
		{
			self::$info = wa('shop')->getConfig()->getPluginInfo('brand');
		}

		return waSystemConfig::isDebug()
			? time()
			: ifset(self::$info, 'version', '1');
	}

	public static function isBrandInstalled()
	{
		if (self::$info === null)
		{
			self::$info = wa('shop')->getConfig()->getPluginInfo('brand');
		}

		return is_array(self::$info) && count(self::$info) > 0;
	}

	public static function mergeViewVarArrays($vars1, $vars2)
	{
		if (!is_array($vars1) || !is_array($vars2))
		{
			return $vars2;
		}

		foreach ($vars2 as $var => $value)
		{
			if (array_key_exists($var, $vars1))
			{
				$vars1[$var] = self::mergeViewVarArrays($vars1[$var], $vars2[$var]);
			}
			else
			{
				$vars1[$var] = $value;
			}
		}

		return $vars1;
	}

	public static function isServerNginx()
	{
		$server = '';

		if (array_key_exists('SERVER_SOFTWARE', $_SERVER))
		{
			$server = $_SERVER['SERVER_SOFTWARE'];
		}
		elseif (array_key_exists('SERVER_SIGNATURE', $_SERVER))
		{
			$server = $_SERVER['SERVER_SIGNATURE'];
		}

		if (!is_string($server) || trim($server) === '')
		{
			return false;
		}

		$server = strtolower(trim($server));

		return strpos($server, 'nginx') !== false;
	}
}
