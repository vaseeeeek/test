<?php

class shopEditProductUpdatePriceActionProductsCollection extends shopProductsCollection
{
	/** @var shopEditProductUpdatePriceFormState */
	private $form_state = null;

	public function __construct($hash = '', $options = array())
	{
		if (is_array($options) && array_key_exists('form_state', $options))
		{
			$this->form_state = $options['form_state'];

			unset($options['form_state']);
		}

		if (!($this->form_state instanceof shopEditProductUpdatePriceFormState))
		{
			throw new waException('В параметре "$options" под ключом "form_state" требуется экземпляр класса "shopEditProductUpdatePriceFormState"!');
		}

		parent::__construct('all', $options);
	}

	public function getProductSQL($fields = '*')
	{
		$split_fields = array_map('trim', explode(',', $fields));
		if (in_array('frontend_url', $split_fields) && !in_array('*', $split_fields))
		{
			if ($dependent_fields = array_diff(array('url', 'category_id',), $split_fields))
			{
				$fields .= ',' . implode(',', $dependent_fields);
			}
		}

		$fields_joined = $this->getFields($fields);

		$sql = $this->getSQL();

		$sql = "SELECT " . ($this->joins && !$this->group_by ? 'DISTINCT ' : '') . $fields_joined . " "
			. $sql;
		$sql .= $this->_getGroupBy();

		if ($this->having)
		{
			$sql .= " HAVING " . implode(' AND ', $this->having);
		}

		return $sql;
	}

	protected function prepare($add = false, $auto_title = true)
	{
		if (!$this->prepared || $add)
		{
			parent::prepare($add, $auto_title);

			$this->prepareByActionForm();
		}
	}

	protected function getExpression($op, $value)
	{
		$values = explode(',', $value);

		if (($op == '=' || $op == '==') && count($values) > 1)
		{
			$model = $this->getModel();
			$values_esc = array();
			foreach ($values as $v)
			{
				$values_esc[] = '\'' . $model->escape($v) . '\'';
			}

			return ' IN (' . implode(',', $values_esc) . ')';
		}
		else
		{
			return parent::getExpression($op, $value);
		}
	}


	private function prepareByActionForm()
	{
		$price_fields = $this->getPriceFields();
		if (count($price_fields) == 0)
		{
			$this->addWhere('1 = 0');

			return;
		}

		if (
			$this->form_state->skip_zero_price
			|| $this->form_state->change_price_mode == shopEditProductUpdatePriceFormState::CHANGE_PRICE_MODE_PERCENT
		)
		{
			$join_where_parts = array();
			foreach ($price_fields as $price_field)
			{
				$join_where_parts[] = ":table.{$price_field} > 0";
			}

			$this->addJoin(
				'shop_product_skus',
				':table.product_id = p.id',
				'(' . implode(' OR ', $join_where_parts) . ')'
			);
		}

		$products_selection = $this->form_state->products_selection;
		if ($products_selection->source_type == shopEditProductsSelection::SOURCE_TYPE_CATEGORY)
		{
			$this->categoriesPrepare($products_selection->category_ids);
		}
		elseif ($products_selection->source_type == shopEditProductsSelection::SOURCE_TYPE_SET)
		{
			$this->setsPrepare($products_selection->set_ids);
		}
	}

	private function setsPrepare($set_ids)
	{
		$set_model = new shopSetModel();

		$set_conditions = array();

		if (!count($set_ids))
		{
			$this->where[] = '0';

			return;
		}

		foreach ($set_ids as $id)
		{
			$set = $set_model->getById($id);

			if (!$set)
			{
				continue;
			}

			if ($set['type'] == shopSetModel::TYPE_STATIC)
			{
				$alias = $this->addJoin('shop_set_products', null);

				$set_conditions[] = '(' . "{$alias}.set_id = '" . $set_model->escape($id) . "'" . ')';
			}
			else
			{
				if (!empty($set['rule']) && ($set['rule'] == 'compare_price DESC'))
				{
					$set_conditions[] = '(compare_price > price)';
				}
			}
		}

		if (count($set_conditions))
		{
			$this->where[] = '(' . implode(' OR ', $set_conditions) . ')';
		}
	}

	private function categoriesPrepare($category_ids)
	{
		$this->searchPrepare('category_id=' . implode(',', $category_ids));
	}

	private function getPriceFields()
	{
		$price_type_storage = new shopEditProductPriceTypeStorage();

		$price_fields = array();
		foreach ($price_type_storage->getSelectedPriceTypes($this->form_state->price_type_selection) as $price_type)
		{
			$price_fields[] = $price_type['id'];
		}

		return $price_fields;
	}
}