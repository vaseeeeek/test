<?php

/**
 * @property-read bool $is_enabled
 * @property-read $output_wa_hook
 */
class shopProductgroupPluginConfig extends shopProductgroupImmutableStructure
{
	protected $is_enabled;
	protected $output_wa_hook;

	public function __construct($is_enabled, $output_wa_hook)
	{
		$this->is_enabled = $is_enabled;
		$this->output_wa_hook = $output_wa_hook;
	}
}