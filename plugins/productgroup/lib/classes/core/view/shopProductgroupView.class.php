<?php

interface shopProductgroupView
{
	public function assign(array $variables);

	public function fetch($markup_template_path);
}