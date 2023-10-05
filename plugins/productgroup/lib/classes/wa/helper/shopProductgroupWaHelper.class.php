<?php

class shopProductgroupWaHelper
{
	const PLUGIN_ID = 'productgroup';

	private static $info = null;

	public static function getPath($path)
	{
		$app_path = wa('shop')->getAppPath('plugins/' . self::PLUGIN_ID . '/' . $path, 'shop');

		return $app_path;
	}

	public static function getDataPath($path, $public = false)
	{
		return wa('shop')->getDataPath('plugins/' . self::PLUGIN_ID . '/', $public, 'shop') . $path;
	}

	public static function getStaticUrl($url, $absolute = false)
	{
		return wa('shop')->getAppStaticUrl('shop', $absolute) . 'plugins/' . self::PLUGIN_ID . '/' . $url;
	}

	public static function getDataStaticUrl($url, $absolute = false)
	{
		return wa('shop')->getDataUrl('plugins/' . self::PLUGIN_ID . '/' . $url, true, 'shop', $absolute);
	}

	public static function getAssetVersion()
	{
		if (self::$info === null)
		{
			self::$info = wa('shop')->getConfig()->getPluginInfo(self::PLUGIN_ID);
		}

		return waSystemConfig::isDebug() ? time() : self::$info['version'];
	}

	public static function transliterate($string)
	{
		static $transliteration = [
			'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
			'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's',
			'т' => 't', 'у' => 'u', 'ф' => 'f', 'ы' => 'y', 'э' => 'e', 'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G',
			'Д' => 'D', 'Е' => 'E', 'Ж' => 'ZH', 'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M',
			'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Ы' => 'Y',
			'Э' => 'E', 'ё' => 'yo', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ъ' => '',
			'ь' => '', 'ю' => 'yu', 'я' => 'ya', 'Ё' => 'YO', 'Х' => 'H', 'Ц' => 'TS', 'Ч' => 'CH', 'Ш' => 'SH',
			'Щ' => 'SHCH', 'Ъ' => '', 'Ь' => '', 'Ю' => 'YU', 'Я' => 'YA', ' ' => '-', '+' => '-',
		];

		return strtr($string, $transliteration);
	}
}