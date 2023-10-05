<?php

abstract class shopBrandActionTemplateSettingsUtil extends shopBrandActionThemeTemplate
{
	public static function _getThemeDefaultTemplateFileName(shopBrandActionThemeTemplate $obj)
	{
		return $obj->getThemeDefaultTemplateFileName();
	}
}