<?php

class shopBrandWaBackendJsonController extends waJsonController
{
	/**
	 * @throws waException
	 */
	protected function preExecute()
	{
		$rights = new shopBrandPluginUserRights();
		if (!$rights->hasRights())
		{
			throw new waException('Нет прав', 403);
		}
	}
}
