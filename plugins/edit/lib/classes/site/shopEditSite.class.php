<?php

/**
 * Class shopEditSite
 *
 * @property int $id
 * @property string $name
 * @property string $title
 * @property string $robots_txt
 * @property array $domain_config
 * @property array $routing
 *
 * @property string $head_js
 * @property array $apps
 */
class shopEditSite extends shopEditPropertyAccess
{
	private $domain_config_fields = array(
		'apps' => 'apps',
		'head_js' => 'head_js',
	);

	function __get($name)
	{
		if (array_key_exists($name, $this->domain_config_fields))
		{
			$value_is_set = array_key_exists('domain_config', $this->_entity_array)
				&& is_array($this->_entity_array['domain_config'])
				&& array_key_exists($name, $this->_entity_array['domain_config']);

			return $value_is_set
				? $this->_entity_array['domain_config'][$name]
				: null;
		}
		else
		{
			return parent::__get($name);
		}
	}

	public function __set($name, $value)
	{
		if (array_key_exists($name, $this->domain_config_fields))
		{
			if (!array_key_exists('domain_config', $this->_entity_array) || !is_array($this->_entity_array['domain_config']))
			{
				$this->_entity_array['domain_config'] = array();
			}

			if ($value === null)
			{
				unset($this->_entity_array['domain_config'][$name]);
			}
			else
			{
				$this->_entity_array['domain_config'][$name] = $value;
			}
		}
		else
		{
			parent::__set($name, $value);
		}
	}
}