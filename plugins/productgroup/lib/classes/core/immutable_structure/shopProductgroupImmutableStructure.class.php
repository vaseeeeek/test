<?php

abstract class shopProductgroupImmutableStructure
{
	/**
	 * @param $name
	 * @return mixed
	 * @throws shopProductgroupInvalidPropertyNameException
	 */
	public function __get($name)
	{
		if (!property_exists($this, $name))
		{
			$class = get_class($this);
			throw new shopProductgroupInvalidPropertyNameException("В структуре [{$class}] нет свойства [{$name}]");
		}

		return $this->$name;
	}
}