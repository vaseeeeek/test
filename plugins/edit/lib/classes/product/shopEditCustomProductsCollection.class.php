<?php

class shopEditCustomProductsCollection extends shopProductsCollection
{
	private $consider_include_sub_categories = true;

	public function __construct($hash = '', array $options = array())
	{
		if (!is_array($options))
		{
			$options = array();
		}

		$options['frontend'] = false;

		if (array_key_exists('consider_include_sub_categories', $options))
		{
			$this->consider_include_sub_categories = $options['consider_include_sub_categories'];

			unset($options['consider_include_sub_categories']);
		}

		parent::__construct($hash, $options);
	}

	protected function getModel($name = 'product')
	{
		if ($name == 'category')
		{
			$forced_values = array();
			if (!$this->consider_include_sub_categories)
			{
				$forced_values['include_sub_categories'] = 0;
			}

			$this->models[$name] = new shopEditCustomCategoryModelWithForcedFieldValues($forced_values);
		}

		return parent::getModel($name);
	}
}