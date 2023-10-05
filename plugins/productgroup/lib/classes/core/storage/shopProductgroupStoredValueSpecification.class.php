<?php

/**
 * @property-read $stored_type
 * @property-read $default_value
 */
class shopProductgroupStoredValueSpecification extends shopProductgroupImmutableStructure
{
	protected $stored_type;
	protected $default_value;

	public function __construct($stored_type, $default_value)
	{
		$this->stored_type = $stored_type;
		$this->default_value = $default_value;
	}
}