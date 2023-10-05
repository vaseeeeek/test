<?php

abstract class shopBrandEnumOptions
{
	private static $_enums_options = array();

	private $_class;

	public function __construct()
	{
		$this->_class = get_class($this);

		if (!array_key_exists($this->_class, self::$_enums_options))
		{
			self::$_enums_options[$this->_class] = array();

			foreach ($this->getOptionValues() as $option_value)
			{
				self::$_enums_options[$this->_class][$option_value] = $option_value;
			}
		}
	}

	function __get($name)
	{
		$options = $this->getOptions();

		if (!array_key_exists($name, $options))
		{
			throw new waException("unknown enum value [{$name}] of enum [{$this->_class}]");
		}

		return $options[$name];
	}

	public function getOptions()
	{
		return self::$_enums_options[$this->_class];
	}

	public function isEnumValue($value)
	{
		return array_key_exists($value, self::$_enums_options[$this->_class]);
	}

	/**
	 * @return string[]
	 */
	protected abstract function getOptionValues();
}