<?php

class shopProductgroupWaView implements shopProductgroupView
{
	private $wa_view;

	public function __construct(waView $wa_view)
	{
		$this->wa_view = $wa_view;
	}

	public function assign(array $variables)
	{
		$this->wa_view->assign($variables);
	}

	public function fetch($markup_template_path)
	{
		return $this->wa_view->fetch($markup_template_path);
	}
}