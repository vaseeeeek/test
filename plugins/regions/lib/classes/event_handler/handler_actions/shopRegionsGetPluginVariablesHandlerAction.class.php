<?php

class shopRegionsGetPluginVariablesHandlerAction implements shopRegionsIHandlerAction
{
	public function execute($view_vars)
	{
		$context = new shopRegionsPluginContext();
		$variables = $context->getPluginViewVariables();

		return is_array($variables)
			? $variables
			: array();
	}
}