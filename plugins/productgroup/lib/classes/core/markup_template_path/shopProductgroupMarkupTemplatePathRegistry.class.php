<?php

interface shopProductgroupMarkupTemplatePathRegistry
{
	/**
	 * @param shopProductgroupMarkupTemplate $template
	 * @return mixed
	 */
	public function getTemplatePath(shopProductgroupMarkupTemplate $template);
}