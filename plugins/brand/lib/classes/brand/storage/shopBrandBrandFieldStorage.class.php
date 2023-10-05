<?php

class shopBrandBrandFieldStorage
{
	private $field_model;
	private $field_meta;

	private $field_value_model;

	private static $field_ids_cache = null;

	public function __construct()
	{
		$this->field_model = new shopBrandBrandFieldModel();
		$this->field_meta = $this->field_model->getMetadata();

		$this->field_value_model = new shopBrandBrandFieldValueModel();
	}

	public function getAllFields()
	{
		$all = $this->field_model
			->select('*')
			->order('sort ASC')
			->fetchAll();

		self::$field_ids_cache = array();
		foreach ($all as $row)
		{
			self::$field_ids_cache[] = $row['id'];
		}

		return $all;
	}

	public function getBrandFieldValue($brand_id, $field_id)
	{
		return $this->field_value_model->select('value')
			->where('brand_id = :brand_id', array('brand_id' => $brand_id))
			->where('field_id = :field_id', array('field_id' => $field_id))
			->fetchField();
	}

	public function getBrandFieldValues($brand_id)
	{
		$field_values = $this->field_value_model->select('field_id,value')
			->where('brand_id = :brand_id', array('brand_id' => $brand_id))
			->fetchAll('field_id', true);

		if (self::$field_ids_cache === null)
		{
			$this->getAllFields();
		}

		foreach (self::$field_ids_cache as $id)
		{
			if (!array_key_exists($id, $field_values))
			{
				$field_values[$id] = '';
			}
		}

		return $field_values;
	}

	public function storeField($field)
	{
		$to_insert = array();

		foreach ($field as $column => $value)
		{
			if (!array_key_exists($column, $this->field_meta))
			{
				continue;
			}
			elseif ($column == 'id' && !(wa_is_int($value) && $value > 0))
			{
				continue;
			}

			$to_insert[$column] = $value;
		}

		$this->field_model->insert($to_insert, waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
	}

	public function deleteById($field_id)
	{
		$this->field_model->deleteById($field_id);
	}

	public function storeFieldValue($brand_id, $field_id, $value)
	{
		$this->field_value_model->insert(array(
			'brand_id' => $brand_id,
			'field_id' => $field_id,
			'value' => $value,
		), waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
	}

	public function storeFieldValues($brand_id, $field_values)
	{
		foreach ($field_values as $field_id => $value)
		{
			$this->storeFieldValue($brand_id, $field_id, $value);
		}
	}
}