<?php

class shopDpServicePointsGroup implements ArrayAccess
{
	public $shipping_methods;
	public $params;

	protected $options;
	protected $data = array();
	protected $plugin;
	protected $frontend;

	private $service_config;

	/**
	 * @param array $params
	 * @param array $options
	 */
	public function __construct($params = array(), $options = array())
	{
		$this->options = $options;
		extract($options);

		if(isset($plugin))
			$this->plugin = $plugin;

		if(isset($frontend))
			$this->frontend = $frontend;

		$this->params = array_merge($params, array(
			'group' => true
		));

		if(isset($title))
			$this->data['title'] = $title;
		else
			$this->data['title'] = $this->getPlugin()->getSettings('design_points_group_title');

		if(isset($switch_mode))
			$this->data['switch_mode'] = $switch_mode;
		else
			$this->data['switch_mode'] = $this->getPlugin()->getSettings('design_points_group_switch_mode');

		if(!isset($shipping_methods))
			$shipping_methods = $this->getPlugin()->getSettings('shipping_methods');

		$this->shipping_methods = $shipping_methods;

		$this->process();
	}

	private function getServiceConfig()
	{
		if(!isset($this->service_config)) {
			$this->service_config = new shopDpServiceConfig();
		}

		return $this->service_config;
	}

	protected function getPlugin()
	{
		if(!isset($this->plugin))
			$this->plugin = shopDpPlugin::getInstance('points group');

		return $this->plugin;
	}

	protected function getFrontend()
	{
		if(!isset($this->frontend)) {
			$this->frontend = new shopDpFrontend();
		}

		return $this->frontend;
	}

	private function process()
	{
		$this['id'] = 0;
		$this['config'] = array(
			'type' => 'points'
		);
		$js = array('id' => 0, 'points' => array());

		$groups = array();

		$checked = ifempty($this->params['checked'], 'all');
		$checked_shipping = ifempty($this->params['checked_shipping'], null);
		$available_points_count = 0;

		$this->options['service_config'] = $this->getServiceConfig();

		foreach($this->shipping_methods as $id => $shipping_method) {
			if(!empty($shipping_method['status']) && !empty($shipping_method['service']) && in_array($shipping_method['service'], shopDpPluginHelper::getPointServices())) {
				$s = $shipping_method['service'];

				if(empty($this->data['points'])) {
					$this->data['points'] = array();
				}

				$params = array_merge($this->params, array(
					'unavailable_points' => empty($this->options['all']) ? $checked != $s : false,
					'open_point_if_one' => empty($this->options['all']) ? $checked_shipping == $id : false,
					'sort_points' => false,
					'break_on_unavailable' => true
				));

				$shipping_method['id'] = $id;

				$service = new shopDpService($shipping_method, $params, $this->options);

				if($service['available'] && !empty($service['points']) && is_array($service['points'])) {
					if(!isset($groups[$s])) {
						$groups[$s] = array(
							'count' => count($service['points']),
							'name' => $service['service_name'],
							'image' => $service['service_image'],
						);
					} else {
						$groups[$s]['count'] += count($service['points']);
					}

					$available_points_count += $service['available_points_count'];
					$this['points'] = array_merge($this['points'], $service['points']);

					if(!empty($service['js']['points'])) {
						$js['points'] = array_merge($js['points'], $service['js']['points']);
					}
				}
			}
		}

		if(!empty($this->params['sort_points'])) {
			$this['points'] = wao(new shopDpPointsSort($this->data['points'], $this->params['sort_points']))->execute();
		}

		$this['filter_by_service'] = !empty($groups) && count($groups) > 1 && $this['switch_mode'] === 'filter';

		$this['available_points_count'] = $available_points_count;
		$this['checked'] = $checked;
		$this['groups'] = $groups;
		$this['js'] = $js;
	}

	public function getDialogTitle()
	{
		$title = $this->getTitle();
		$output = $title;

		if($this['switch_mode'] === 'header') {
			$output .= $this->getFrontend()->pointsServiceSwitcher(array(
				'groups' => $this['groups'],
				'checked' => $this['checked']
			));
		}

		return $output;
	}

	public function getTitle()
	{
		if(!empty($this->data['title']))
			return $this->data['title'];
		else
			return null;
	}

	public function getData($name = null)
	{
		if($name) {
			return isset($this->data[$name]) ? $this->data[$name] : null;
		} else {
			return $this->data;
		}
	}

	public function setData($name, $value)
	{
		$this->data[$name] = $value;

		return $value;
	}

	public function __get($name)
	{
		if(isset($this->data[$name])) {
			return $this->data[$name];
		}
	}

	public function offsetGet($offset)
	{
		return $this->__get($offset);
	}

	public function __set($name, $value)
	{
		return $this->setData($name, $value);
	}

	public function offsetSet($offset, $value)
	{
		$this->__set($offset, $value);
	}

	public function offsetUnset($offset)
	{
		$this->__set($offset, null);
	}

	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}
}