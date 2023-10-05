<?php


class shopRegionsMetaResponse
{
	private $response;

	public function __construct()
	{
		$this->response = wa()->getResponse();
	}

	public function getMetaTitle()
	{
		return $this->response->getTitle();
	}

	public function setMetaTitle($title)
	{
		$this->response->setTitle($title);
		$this->response->setOGMeta('og:title', $title);
	}

	public function getMetaKeywords()
	{
		return $this->response->getMeta('keywords');
	}

	public function setMetaKeywords($keywords)
	{
		$this->response->setMeta('keywords', $keywords);
	}

	public function getMetaDescription()
	{
		return $this->response->getMeta('description');
	}

	public function setMetaDescription($description)
	{
		$this->response->setMeta('description', $description);
		$this->response->setOGMeta('og:description', $description);
	}
}