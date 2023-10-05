<?php

abstract class shopSeofilterBackendViewAction extends waViewAction
{
	protected $left_sidebar = array(
		'pages' => array(),
	);

	protected function preExecute()
	{
		$user_rights = new shopSeofilterUserRights();
		if (!$user_rights->hasRights())
		{
			throw new waException('Доступ запрещен', 403);
		}

		$layout = new shopSeofilterBackendLayout();
		$layout->assign('no_level2', true);
		$this->prepareLeftSidebar();
		$this->setLayout($layout);
		$this->addJs(array(
			'tab.js',
		));

		$this->getResponse()->addJs('wa-content/js/ace/ace.js');

		$plugin = wa('shop')->getPlugin('seofilter');

		$version_asset_version = waSystemConfig::isDebug()
			? time()
			: $plugin->getVersion();

		$this->view->assign(array(
			'plugin_url' => $plugin->getPluginStaticUrl(),
			'plugin_version' => $version_asset_version,
			'wa_plugin_version' => $version_asset_version,
			'template_helper' => $this->getTemplateHelperData(),
		));
	}

	public function display($clear_assign = true)
	{
		$this->view->cache($this->cache_time);
		if ($this->cache_time && $this->isCached())
		{
			return $this->view->fetch($this->getTemplate(), $this->cache_id);
		}
		else
		{
			if (!$this->cache_time && $this->cache_id)
			{
				$this->view->clearCache($this->getTemplate(), $this->cache_id);
			}
			$this->preExecute();
			$this->execute();
			$this->postExecute();
			$result = $this->view->fetch($this->getTemplate(), $this->cache_id);
			if ($clear_assign)
			{
				$this->view->clearAllAssign();
			}

			return $result;
		}
	}

	public function postExecute()
	{
		$left_sidebar = $this->left_sidebar;
		$left_sidebar['pages'] = array_values($this->left_sidebar['pages']);
		$left_sidebar['pages'][] = array(
			'text' => 'Настройки плагина',
			'href' => '?action=plugins#/seofilter',
			'current' => false,
			'icon_class' => 'settings',
			'target' => '_blank',
		);
		$this->view->assign('left_sidebar', $left_sidebar);
	}

	protected function addCss(array $local_css)
	{
		foreach ($local_css as $_css)
		{
			wa()->getResponse()->addCss('plugins/seofilter/css/'.$_css, 'shop');
		}
	}

	protected function addJs(array $local_js)
	{
		foreach ($local_js as $_js)
		{
			wa()->getResponse()->addJs('plugins/seofilter/js/'.$_js, 'shop');
		}
	}

	private function prepareLeftSidebar()
	{
		$this->left_sidebar['pages'] = array(
			'add' => array(
				'text' => 'Добавить фильтр',
				'href' => '?plugin=seofilter&action=create',
				'current' => false,
				'icon_class' => 'add',
			),
			'all' => array(
				'text' => 'Все фильтры',
				'href' => '?plugin=seofilter',
				'current' => false,
				'icon_class' => 'folders',
			),
		);
	}

	private function getTemplateHelperData()
	{
		$helper_data = array(
			'variables' => array(
				'{$seo_name}' => 'seo название значения характеристики',
				'{$value_name}' => 'название значения характеристики',
				'{$value_names[n]}' => 'название n-го значения характеристики',
				'{$feature_name}' => 'название характеристики',
				'{$category.name}' => 'название категории',
				'{$category.seo_name}' => 'seo название категории',
				'{$root_category.name}' => 'название корневой категории',
				'{$parent_category.name}' => 'название родительской категории',
				'{$parent_categories_names|sep:\' \'}' => 'путь к странице через пробел',
				'{$filter.products_count}' => 'количество товаров',
				'{$filter.max_price}' => 'максимальная цена товара на странице',
				'{$filter.min_price}' => 'минимальная цена товара на странице',
				'{$filter.max_price_without_currency}' => 'максимальная цена товара на странице (без HTML валюты)',
				'{$filter.min_price_without_currency}' => 'минимальная цена товара на странице (без HTML валюты)',
				//'{$filter.features}' => 'массив характеристик фильтра',
				'{$store_info.name}' => 'название магазина',
				'{$store_info.phone}' => 'телефон магазина',
				'{$storefront.name}' => 'название витрины',
				'{$page_number}' => 'номер страницы',
				'{$pages_count}' => 'количество страниц',
				'{$host}' => 'текущий домен',
			),
			'modifiers' => array(
				'|lower' => 'преобразует в нижний регистр',
				'|ucfirst' => 'преобразует первый символ в верхний регистр',
				'|lcfirst' => 'преобразует первый символ в нижний регистр',
			),
			'custom_variables' => array(),
		);

		foreach (shopSeofilterStorefrontFieldsModel::getAllFields() as $storefront_field_id => $storefront_field_name)
		{
			$helper_data['variables']['{$storefront_field[' . $storefront_field_id . '].value}'] = $storefront_field_name;
		}

		foreach (shopSeofilterFilterFieldModel::getAllFields() as $filter_field_id => $filter_field_name)
		{
			$helper_data['variables']['{$filter.field[' . $filter_field_id . '].value}'] = $filter_field_name;
		}

		$meta = new shopSeofilterCustomTemplateVariablesMeta();

		$custom_template_meta = $meta->getCustomTemplateVariablesMeta();
		if (count($custom_template_meta))
		{
			$helper_data['custom_variables'] = $custom_template_meta;
		}

		return $helper_data;
	}
}