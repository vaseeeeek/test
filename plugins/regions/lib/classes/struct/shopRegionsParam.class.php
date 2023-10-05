<?php


class shopRegionsParam
{
	private $m_param;
	private $m_c_param;

	private $row;

	private function __construct()
	{
		$this->m_param = new shopRegionsParamModel();
		$this->m_c_param = new shopRegionsCityParamModel();

		$this->row = array(
			'id' => null,
			'name' => null,
			'sort' => 0,
		);
	}

	public static function create()
	{
		$param = new self();
		$param->row = array(
			'id' => null,
			'name' => null,
			'sort' => 0,
		);

		return $param;
	}

	public static function build($row)
	{
		$param = new self();

		foreach ($param->row as $field => $_)
		{
			if (isset($row[$field]))
			{
				$param->row[$field] = $row[$field];
			}
		}

		if ($param->getID() < 0)
		{
			$param->row['id'] = null;
		}

		return $param;
	}

	public static function load($id)
	{
		$m_param = new shopRegionsParamModel();
		$row = $m_param->getById($id);

		if ($row)
		{
			return self::build($row);
		}

		return null;
	}

	public static function isExists($id)
	{
		$m_param = new shopRegionsParamModel();

		return $m_param->countByField('id', $id) > 0;
	}

	public function isCreated()
	{
		return isset($this->row['id']);
	}

	public function getID() { return $this->row['id']; }

	public function getName() { return $this->row['name']; }
	public function setName($name) { $this->row['name'] = $name; }

	public function getSort() { return $this->row['sort']; }
	public function setSort($sort) { $this->row['sort'] = $sort; }

	public function toArray() { return $this->row; }

	public function save()
	{
		if (!mb_strlen($this->getName()))
		{
			return false;
		}

		if ($this->isCreated())
		{
			return $this->update();
		}

		return $this->insert();
	}

	public function delete()
	{
		$result = $this->m_c_param->deleteByField('param_id', $this->getID());

		if (!$result)
		{
			return false;
		}

		$result = $this->m_param->deleteById($this->getID());

		if (!$result)
		{
			return false;
		}

		$this->row = array();
		return true;
	}

	private function insert()
	{
		$result = $this->m_param->insert($this->row);

		if (!$result)
		{
			return false;
		}

		$id = $result;
		$this->row['id'] = $id;

		return true;
	}

	private function update()
	{
		return $this->m_param->updateById($this->getID(), $this->row);
	}
}