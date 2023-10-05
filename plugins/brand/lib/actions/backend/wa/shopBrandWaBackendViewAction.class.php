<?php

class shopBrandWaBackendViewAction extends waViewAction
{
	protected function preExecute()
	{
		$rights = new shopBrandPluginUserRights();
		if (!$rights->hasRights())
		{
			throw new waException('Нет прав', 403);
		}
	}
}
