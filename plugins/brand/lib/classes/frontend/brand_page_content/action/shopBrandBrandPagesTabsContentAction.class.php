<?php

class shopBrandBrandPagesTabsContentAction extends shopBrandBrandPageContentAction
{
	protected $with_pages_tabs = false;

	protected function getActionTemplate()
	{
		return new shopBrandBrandPagesTabsTemplate($this->getTheme());
	}

	protected function executeBrandPage(shopBrandFetchedLayout $fetched_layout)
	{
		$this->view->assign(array(
			'brand' => $this->action->getBrand(),
			'page' => $this->action->getPage(),
			'brand_page' => $this->action->getBrandPage(),
			'pages' => $this->workupPages(),
		));
	}

	private function workupPages(){
        $pages = $this->action->getPages();
        $brand = $this->action->getBrand();
        $this->view_buffer->assign('brand', $brand);

        $collector = new shopBrandTemplateCollector();

        foreach ($pages as &$page) {
            $template_layout = $collector->getBrandPageTemplateLayout(
                $brand->id,
                $page->id,
                $this->storefront
            );
            $templates = $template_layout->getTemplates();
            $template = new shopBrandTemplateLayout($templates);
            $template_layout_fetched = $this->view_buffer->fetchTemplateLayout($template);
            if(is_string($template_layout_fetched->name) && trim($template_layout_fetched->name) != '') {
                $page->name = $template_layout_fetched->name;
            }
        }

        return $pages;
    }
}