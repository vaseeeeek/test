<?php

class shopBrandPluginFrontendBrandsAction extends shopBrandFrontendActionWithMeta
{
    private $settings;

    public function __construct($params = null){
        parent::__construct($params);

        $settings_storage = new shopBrandSettingsStorage();
        $this->settings = $settings_storage->getSettings();
    }

	public function executeBrandPage(shopBrandFetchedLayout $fetched_layout)
	{
		$route_params = array(
			'module' => 'frontend',
			'plugin' => 'brand',
			'action' => 'brands',
		);

        $brands_search_html = '';


        if($this->settings['use_brands_alpha'] || $this->settings['use_brands_search']) {
            $brands_search_html = $this->getBrandsSearchHtml();
        }

		$this->view->assign(array(
			'brands' => $this->getBrands(),
			'h1' => $fetched_layout->h1,
			'description' => $fetched_layout->description,
			'brands_page_url' => wa()->getRouteUrl('shop', $route_params),
			'brands_search_html' => $brands_search_html,
		));

		waSystem::popActivePlugin();
	}

	private function getBrandsSearchHtml(){
        $brands_search_html = '';
        $themes = wa()->getThemes('shop');
        $theme_id = waRequest::getTheme();

        if (array_key_exists($theme_id, $themes)) {
            $theme = $themes[$theme_id];
            $template = new shopBrandSearchBrandsTemplate($theme);

            $letters = [];

            if($this->settings['use_brands_alpha']) {
                $letters = $this->getBrandsLetters();
            }

            wa()->getResponse()->addJs($template->getActionJsUrl());
            $view = wa()->getView();
            $view->assign(array(
                'brands_search' => $this->settings['use_brands_search'],
                'brands_alpha' => $this->settings['use_brands_alpha'],
                'letters' => $letters,
            ));


            $template_path = $template->isThemeTemplate()
                ? $theme->getPath() . '/' . $template->getActionThemeTemplate()
                : $template->getActionTemplate();

            $brands_search_html = $view->fetch($template_path);

        }
        return $brands_search_html;
    }

	private function getBrandsLetters(){
        $brands = $this->getBrands();
        $_letters = [];
        foreach ($brands as $b){
            $_letters[] = mb_strtoupper(mb_substr($b['name'], 0, 1));
        }
        $_letters = array_unique($_letters);
        sort($_letters, SORT_LOCALE_STRING);
        $en_letters = [];
        $ru_letters = [];
        $numbers = [];
        foreach ($_letters as $l){
            if(preg_match("/[А-Яа-яёЁ]/u", $l)) {
                $ru_letters[] = $l;
            } elseif(preg_match("/[A-Za-z]/u", $l)) {
                $en_letters[] = $l;
            } else {
                $numbers[] = $l;
            }
        }

        return [
            'ru_letters' => $ru_letters,
            'en_letters' => $en_letters,
            'numbers' => $numbers,
        ];

    }

	protected function getTemplateLayout()
	{
		$template_collector = new shopBrandTemplateCollector();

		$template_layout = $template_collector->getBrandsPageTemplateLayout($this->storefront);
		$templates = $template_layout->getTemplates();

		$fields_to_check = array(
			'h1',
			'meta_title',
		);

		foreach ($fields_to_check as $field)
		{
			if (!is_string($templates[$field]) || mb_strlen(trim($templates[$field])) == 0)
			{
				$templates[$field] = 'Бренды';
			}
		}

		return new shopBrandTemplateLayout($templates);
	}

	protected function getActionTemplate()
	{
		return new shopBrandBrandsActionTemplate($this->getTheme());
	}

	private function getBrands()
	{

		try
		{
			$collection = shopBrandBrandsCollectionFactory::getBrandsCollection($this->settings);
		}
		catch (Exception $e)
		{
			return array();
		}

		$this->setSort($collection);

		return $collection->getBrands();
	}

	private function setSort(shopBrandBrandsCollection $c)
	{
		$sort = waRequest::request('sort');
		$order = waRequest::request('order');

		if ($sort)
		{
			$c->sort($sort, $order);
		}
	}
}
