<?php

abstract class shopBrandFrontendAction extends shopFrontendAction
{
	public function execute()
	{
		$this->prepareActionTemplate();
	}

	protected function prepareActionTemplate()
	{
		$t = $this->getActionTemplate();
		if ($t->isThemeTemplate())
		{
			$this->setThemeTemplate($t->getActionThemeTemplate());
		}
		else
		{
			$this->setTemplate($t->getActionTemplate());
		}

		$css_url = $t->getActionCssUrl();
		if ($css_url)
		{
			$this->getResponse()->addCss($css_url . '?v=' . shopBrandHelper::getAssetVersion());
		}

		$js_url = $t->getActionJSUrl();
		if ($js_url)
		{
			$this->getResponse()->addJS($js_url . '?v=' . shopBrandHelper::getAssetVersion());
		}
	}

	/** @return shopBrandActionTemplate */
	protected abstract function getActionTemplate();
}