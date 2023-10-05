<?php

class shopDpPluginFrontendSvgController extends waController
{
	public function execute()
	{
		$this->getResponse()->addHeader('Content-type', 'image/svg+xml');
		$this->getResponse()->addHeader('X-Content-Type-Options', 'nosniff');

		$file = waRequest::get('file');
		$path = wa()->getAppPath("plugins/dp/img/svg/{$file}.svg", 'shop');

		if(!file_exists($path)) {
			return;
		}

		$svg = simplexml_load_file($path);

		$elements = waRequest::get('elements', array(), 'array');
		foreach($elements as $element => $attrs) {
			foreach($svg->$element as $node) {
				$this->changeAttrs($node, $attrs);
			}
		}

		$classes = waRequest::get('classes', array(), 'array');
		foreach($classes as $class => $attrs) {
			$nodes = $svg->xpath("//*[contains(@class, '{$class}')]");

			foreach($nodes as $node) {
				$this->changeAttrs($node, $attrs);
			}
		}

		echo $svg->asXML();
	}

	private function changeAttrs($node, $attrs)
	{
		$prop_attrs = $node->attributes();

		foreach($attrs as $attr => $value) {
			if(property_exists($prop_attrs, $attr))
				$node->attributes()->$attr = $value;
			else
				$node->addAttribute($attr, $value);
		}
	}
}
