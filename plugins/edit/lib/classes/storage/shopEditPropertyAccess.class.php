<?php

abstract class shopEditPropertyAccess implements ArrayAccess
{
	/** @var array */
	protected $_entity_array;

	public function __construct($entity_array = null)
	{
		if (is_array($entity_array))
		{
			$this->_entity_array = $entity_array;
		}
		elseif ($entity_array instanceof $this)
		{
			$this->_entity_array = $entity_array->_entity_array;
		}
		else
		{
			$this->_entity_array = array();
		}
	}

	function __get($name)
	{
		$entity_key = $name;

		return $this->getEntityFieldValue($entity_key);
	}

	public function __set($name, $value)
	{
		$this->_entity_array[$name] = $value;
	}

	public function assoc()
	{
		return $this->_entity_array;
	}

	protected function getEntityFieldValue($name)
	{
		return array_key_exists($name, $this->_entity_array) ? $this->_entity_array[$name] : '';
	}




	public function offsetExists($offset)
	{
		return true;
	}

	public function offsetGet($offset)
	{
		return $this->__get($offset);
	}

	public function offsetSet($offset, $value)
	{
		$this->__set($offset, $value);
	}

	public function offsetUnset($offset)
	{
	}
}