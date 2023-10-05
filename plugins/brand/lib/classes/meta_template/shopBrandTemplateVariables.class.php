<?php

class shopBrandTemplateVariables
{
	private $page_type_options;

	public function __construct()
	{
		$this->page_type_options = new shopBrandPageTypeEnumOptions();
	}

	public function getPluginPageVariablesInfo($page_type)
	{
		$variables_info = array(
			'{$page.id}' => 'ID страницы',
			'{$page.name}' => 'название страницы',
			//'{$page.url}' => 'URL страницы',
			'{$page.frontend_url}' => 'URL страницы',
			'{$brand.id}' => 'ID бренда',
			'{$brand.name}' => 'название бренда',
			//'{$brand.url}' => '',
			'{$brand.image_url}' => 'URL изображения бренда',
			'{$brand.frontend_url}' => 'URL страницы бренда',
			//'{$brand.description_short}' => 'краткое описание бренда',
		);

		if ($page_type == $this->page_type_options->CATALOG)
		{
			$variables_info = array_merge_recursive(
				$variables_info,
				array(
					'{$page_number}' => 'номер страницы',
					'{$pages_count}' => 'количество страниц',
					'{$brand.products_count}' => 'количество товаров бренда',
					'{$brand.min_price}' => 'минимальная цена',
					//'{$brand.min_price_without_currency}' => 'минимальная цена ()',
					'{$brand.max_price}' => 'максимальная цена',
					//'{$brand.max_price_without_currency}' => '',
				)
			);
		}
		elseif ($page_type == $this->page_type_options->REVIEWS)
		{
			$variables_info['{$brand.reviews_count}'] = 'количество отзывов';
		}
		elseif ($page_type == $this->page_type_options->PAGE)
		{}

		$variables_info = array_merge_recursive(
			$variables_info,
			array(
				'{$host}' => 'текущий домен',
				'{$store_info.name}' => 'название магазина',
				'{$store_info.phone}' => 'телефон магазина',
			)
		);

		return $variables_info;
	}

	public function getModifiersInfo()
	{
		return array(
			'|lower' => 'преобразует в нижний регистр',
			'|ucfirst' => 'преобразует первый символ в верхний регистр',
			'|lcfirst' => 'преобразует первый символ в нижний регистр',
		);
	}

	public function getBrandFieldVariableTemplate() {
		return '{$brand.field[%FIELD_ID%]}';
	}

	public function getBrandsPageVariables()
	{
		return array(
			'{$host}' => 'текущий домен',
			'{$store_info.name}' => 'название магазина',
			'{$store_info.phone}' => 'телефон магазина',
			'{$brands}' => 'массив брендов',
		);
	}

	public function getViewState()
	{
		$variables = new shopBrandTemplateVariables();
		$page_type_options = new shopBrandPageTypeEnumOptions();

		$view_state = array(
			'page_variables' => array(),
			'modifiers' => $variables->getModifiersInfo(),
			'brand_field_variable_template' => $variables->getBrandFieldVariableTemplate(),
			'brands_page_variables' => $variables->getBrandsPageVariables(),
		);

		foreach ($page_type_options->getOptions() as $page_type)
		{
			$view_state['page_variables'][$page_type] = $variables->getPluginPageVariablesInfo($page_type);
		}

		return $view_state;
	}
}