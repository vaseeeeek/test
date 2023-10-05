<?php


class shopRegionsPageOptimizer extends shopRegionsOptimizer
{
	protected function preCheck()
	{
		$template = $this->getTemplate();

		return !empty($template);
	}

	protected function getTemplate()
	{
		$page_id = waRequest::param('page_id');
		$m_page = new shopPageModel();
		$page = $m_page->get($page_id);

		if (!$page)
		{
			return '';
		}

		$full_url = $page['full_url'];

		$ignored_pages = waRequest::param('regions_ignore_default_pages', array());

		if (array_key_exists($full_url, $ignored_pages) || $page['content'] == '')
		{
			$settings = new shopRegionsSettings();
			$plugin_template = $settings->getPageTemplate($full_url);

			$routing = new shopRegionsRouting();
			$city = $routing->getCurrentCity();

			return $city === null || $plugin_template === null
				? null
				: $plugin_template;
		}
		else
		{
			return null;
		}
	}

	protected function optimize()
	{
		$text = $this->getText();

		if ($text === null)
		{
			return;
		}

		$page = $this->getTemplateView()->getVars('page');
		$page['content'] = $text;

		wa()->getView()->assign('page', $page);
	}

	protected function getReplacer()
	{
		return new shopRegionsCityReplacesSet($this->getTemplateView());
	}
}