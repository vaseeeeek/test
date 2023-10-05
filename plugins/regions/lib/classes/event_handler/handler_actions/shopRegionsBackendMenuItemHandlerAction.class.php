<?php

class shopRegionsBackendMenuItemHandlerAction implements shopRegionsIHandlerAction
{
	public function execute($handler_params)
	{
		$html = '';

		if (shopRegionsPlugin::userHasRightsToEditRegions())
		{
			$html = '
<li class="no-tab shop-regions__li">
	<a href="?plugin=regions">Регионы</a>
</li>';
		}

		return array(
			'core_li' => $html,
		);
	}
}