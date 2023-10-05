<?php

class shopBrandBrandInfoPageContentAction extends shopBrandBrandPageContentAction
{
	public function executeBrandPage(shopBrandFetchedLayout $fetched_layout)
	{
		$h1 = $fetched_layout->h1;
		$content = @$this->view->fetch('string:' . $this->brand_page->content);

		$this->view->assign('page', array(
			'id' => $this->page->id,
			'name' => is_string($h1) && strlen($h1) ? $h1 : $this->page->name,
			'content' => $content,
		));

		$this->view->assign(array(
			'h1' => $h1,
			'content' => $content,
			'brand_plugin_page' => $this->page,
		));
	}

	protected function getMainViewVarNamesToReplace()
	{
		return array(
			'h1' => 'h1',
			'page' => 'page',
		);
	}

	protected function isEmptyPage()
	{
		return !$this->brand_page || $this->brand_page->isEmpty();
	}

	protected function getActionTemplate()
	{
		return new shopBrandBrandInfoActionTemplate($this->getTheme());
	}

	protected function getTemplateLayout()
	{
		$template_layout = parent::getTemplateLayout();
		$templates = $template_layout->getTemplates();

		$fields_to_check = array(
			'h1',
			'meta_title',
		);

		foreach ($fields_to_check as $field)
		{
			if (!is_string($templates[$field]) || mb_strlen(trim($templates[$field])) == 0)
			{
				$templates[$field] = $this->page->name;
			}
		}

		return new shopBrandTemplateLayout($templates);
	}
}