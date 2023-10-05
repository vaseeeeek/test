<?php

interface shopEditIDataFieldSpecification
{
	public function toAccessible($raw_value);

	public function toStorable($value);

	public function defaultValue();
}