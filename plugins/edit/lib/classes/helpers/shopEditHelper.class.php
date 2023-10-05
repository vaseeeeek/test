<?php

class shopEditHelper
{
	private static $info = null;

	public static function getPath($path)
	{
		return wa('shop')->getAppPath('plugins/edit/' . $path, 'shop');
	}

	public static function getDataPath($path, $public = false)
	{
		return wa('shop')->getDataPath('plugins/edit/', $public, 'shop') . $path;
	}

	public static function getStaticUrl($url, $absolute = false)
	{
		return wa('shop')->getAppStaticUrl('shop', $absolute) . 'plugins/edit/' . $url;
	}

	public static function getDataStaticUrl($url, $absolute = false)
	{
		return wa('shop')->getDataUrl('plugins/edit/' . $url, true, 'shop', $absolute);
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

	public static function getAssetVersion()
	{
		if (self::$info === null)
		{
			self::$info = wa('shop')->getConfig()->getPluginInfo('edit');
		}

		return waSystemConfig::isDebug() ? time() : self::$info['version'];
	}

	public static function arraysWithSubArraysAreEqual($a1, $a2)
	{
		if (is_array($a1) !== is_array($a2))
		{
			return false;
		}

		if (!is_array($a1) && !is_array($a2))
		{
			return $a1 === $a2;
		}

		if (count($a1) !== count($a2))
		{
			return false;
		}

		$diff = array_udiff_assoc($a1, $a2, array('shopEditHelper', 'compareSubArrays'));

		return count($diff) == 0;
	}

	public static function compareSubArrays($a1, $a2)
	{
		return count(array_diff_assoc($a1, $a2)) == 0 ? 0 : 1;
	}
}
