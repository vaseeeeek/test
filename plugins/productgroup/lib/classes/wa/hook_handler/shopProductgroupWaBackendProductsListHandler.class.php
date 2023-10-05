<?php

class shopProductgroupWaBackendProductsListHandler
{
	public function handle()
	{
		$template_path = shopProductgroupWaHelper::getPath('templates/hooks/BackendProducts.ToolbarOrganizeLi.html');
		$html = wa()->getView()->fetch($template_path);

		return [
			'toolbar_organize_li' => $html,
		];
	}
}