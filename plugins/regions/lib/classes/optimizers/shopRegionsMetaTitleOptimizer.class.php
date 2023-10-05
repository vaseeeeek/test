<?php

abstract class shopRegionsMetaTitleOptimizer extends shopRegionsOptimizer
{
	private $meta_response;

	public function __construct(shopRegionsViewBuffer $view)
	{
		parent::__construct($view);
		$this->meta_response = new shopRegionsMetaResponse();
	}

	protected function optimize()
	{
		$text = $this->getText();

		$this->meta_response->setMetaTitle($text);
	}
}