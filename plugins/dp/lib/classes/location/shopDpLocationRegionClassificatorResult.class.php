<?php

class shopDpLocationRegionClassificatorResult
{
	protected $xml;
	protected $is_correct = false;
	protected $attributes;

	public function __construct($xml = null)
	{
		if($xml instanceof SimpleXMLElement) {
			$this->is_correct = true;
		}

		$this->xml = $xml;
	}

	public function isCorrect()
	{
		return $this->is_correct;
	}

	public function getInitialXML()
	{
		return $this->xml;
	}

	public function getAttributes()
	{
		if(!$this->isCorrect()) {
			return null;
		}

		if(!isset($this->attributes)) {
			$this->attributes = $this->xml->attributes();
		}

		return $this->attributes;
	}

	public function getAttribute($attr)
	{
		if(!$this->isCorrect()) {
			return null;
		}

		$attrs = $this->getAttributes();

		$value = ifset($attrs, $attr, null);

		if(!$value) {
			return null;
		}

		return (string) $value;
	}

	public function getFias()
	{
		return $this->getAttribute('aoguid');
	}

	public function getOkato()
	{
		return $this->getAttribute('okato');
	}

	public function getCode()
	{
		if(!$this->isCorrect()) {
			return null;
		}

		return (string) $this->xml;  // todo get nodeValue?
	}
}