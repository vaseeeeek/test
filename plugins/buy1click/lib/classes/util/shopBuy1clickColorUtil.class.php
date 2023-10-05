<?php


class shopBuy1clickColorUtil
{
	public static function getDarknessColor($hex)
	{
		$hex = substr($hex, 1, 6);
		$hex = str_split($hex, 2);
		$new_hex = array();
		
		foreach ($hex as $i => $hex_one)
		{
			$hex_dec = hexdec($hex_one);
			$hex_dec = max(0, $hex_dec - 25);
			$new_hex[] = str_pad(dechex($hex_dec), 2, '0', STR_PAD_LEFT);
		}
		
		return '#' . implode('', $new_hex);
	}
}