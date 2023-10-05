<?php

abstract class shopEditStorage
{
	/** @var shopEditIDataFieldSpecification[][] */
	private static $_access_specifications = array();

	/** @var shopEditPropertyAccess[] */
	private static $_entity_instances = array();

	protected $_class;

	protected $model;
	protected $entity_instance;

	public function __construct()
	{
		$this->_class = get_class($this);
		$this->model = $this->dataModelInstance();

		if (!array_key_exists($this->_class, self::$_access_specifications))
		{
			self::$_access_specifications[$this->_class] = $this->accessSpecification();
			self::$_entity_instances[$this->_class] = $this->entityInstance();
		}

		$this->entity_instance = self::$_entity_instances[$this->_class];
	}

	public function getById($id)
	{
		$data_raw = $this->model->getById($id);

		return $data_raw
			? new $this->entity_instance($this->prepareStorableForAccessible($data_raw))
			: null;
	}

	/**
	 * @param array|shopEditPropertyAccess $entity
	 * @param null $id
	 * @return int
	 * @throws waException
	 */
	public function store($entity, $id = null)
	{
		$entity_assoc['id'] = $id;

		$insert_result = $this->model->insert($this->prepareAccessibleToStorable($entity), waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);

		return $id > 0 ? $id : $insert_result;
	}

	/**
	 * @return shopEditIDataFieldSpecification[]
	 */
	abstract protected function accessSpecification();

	/**
	 * @return waModel
	 */
	abstract protected function dataModelInstance();

	/**
	 * @return shopEditPropertyAccess
	 */
	abstract protected function entityInstance();

	/**
	 * @param string $name
	 * @return sitemapIDataFieldSpecification|null
	 */
	protected function getFieldSpecification($name)
	{
		if (!array_key_exists($name, self::$_access_specifications[$this->_class]))
		{
			return null;
		}

		return self::$_access_specifications[$this->_class][$name];
	}

	protected function fetchRaw($id)
	{
		return $this->model->getById($id);
	}

	protected function fetchPreparedForAccessible($id)
	{
		$stored_settings = $this->fetchRaw($id);

		return $this->prepareStorableForAccessible($stored_settings);
	}

	protected function prepareStorableForAccessible($data_raw)
	{
		$data_for_accessible = array();

		foreach (self::$_access_specifications[$this->_class] as $field => $specification)
		{
			$data_for_accessible[$field] = array_key_exists($field, $data_raw)
				? $specification->toAccessible($data_raw[$field])
				: $specification->defaultValue();
		}

		return $data_for_accessible;
	}

	/**
	 * @param array|shopEditPropertyAccess $accessible
	 * @return array
	 * @throws waException
	 */
	protected function prepareAccessibleToStorable($accessible)
	{
		if (is_array($accessible))
		{
			$accessible_assoc = $accessible;
		}
		elseif ($accessible instanceof shopEditPropertyAccess)
		{
			$accessible_assoc = $accessible->assoc();
		}
		else
		{
			throw new waException('must be accessible or assoc');
		}

		$storable = array();

		foreach (self::$_access_specifications[$this->_class] as $field => $specification)
		{
			$storable[$field] = array_key_exists($field, $accessible_assoc)
				? $specification->toStorable($accessible_assoc[$field])
				: $specification->defaultValue();
		}

		return $storable;
	}

	protected function getAvailableFields()
	{
		return array_keys(self::$_access_specifications[$this->_class]);
	}
}