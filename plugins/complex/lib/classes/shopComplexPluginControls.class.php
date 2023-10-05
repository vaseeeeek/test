<?php

class shopComplexPluginControls
{
	protected $controls;
	public static $control_types = array('compare', 'categories', 'types', 'features', 'feature_key', 'feature_value', 'shipping', 'rates', 'payment', 'countries', 'regions', 'storefronts', 'user_categories');
	
	public function __construct()
	{
		$this->plugin_path = wa()->getAppPath('plugins/complex', 'shop');
		
		$controls_path = $this->plugin_path . '/lib/config/data/controls.php';
		$this->controls = include($controls_path);
	}
	
	public function getControl($name)
	{
		$controls = $this->getAllControls();
		return ifset($controls[$name]);
	}
	
	public function getControlTitle($name)
	{
		$control = $this->getControl($name);
		if(is_array($control))
			return _wp($control['title']);
		else
			return _wp($control);
	}
	
	public static function getControlCompareValues($name)
	{
		$controls = array();
		foreach(include(wa()->getAppPath('plugins/complex', 'shop') . '/lib/config/data/controls.php') as $group)
			$controls = array_merge($controls, $group['controls']);
		
		if(isset($controls[$name])) {
			$default = array('!=', '=', '>=', '<=', '>', '<');
			
			$control = $controls[$name];
			if(is_array($control)) {
				$type = explode(':', $control['type']);
				
				foreach($type as $field) {
					if($field == 'compare')
						return $default;
					
					elseif(substr($field, 0, strlen('compare')) == 'compare')
						return explode(';', substr($field, strlen('compare') + 1, -1));
				}
			} else
				return $default;
		}
	}
	
	public function getControlValue($control, $value, $controls = array())
	{
		$output = $value;
		
		switch($control) {
			case 'compare':
				$output = '<b class="compare">' . ($value == '!=' ? _wp('NOT') : ($value == '==' ? _wp('ALL') : $value)) . '</b>';
				break;
			case 'shipping':
			case 'payment':
			case 'countries':
			case 'categories':
			case 'types':
			case 'features':
			case 'storefronts':
			case 'user_categories':
				$output = $this->takeOne($control, $value, 'name');
				break;
			case 'feature_key':
				$id = substr($value, 0, strpos($value, ':'));
				$output = $this->takeOne('features', $id, 'name');
				break;
			case 'feature_value':
				if(isset($controls['feature_key'])) {
					$feature = explode(':', $controls['feature_key']);
					
					if($feature[1]) { // selectable
						$feature_values = shopFeatureModel::getFeatureValues(array(
							'type' => $feature[2],
							'id' => $feature[0]
						));
						
						$output = ifset($feature_values[$value], $value);
					} else
						$output = $value;
				}
				break;
			case 'regions':
				if(isset($controls['countries'])) {
					$country = $controls['countries'];
					
					$output = $this->takeOne('regions', $value, 'name', 'country_iso3 = \'' . $country . '\'');
				}
				break;
			case 'rates':
				if(isset($controls['shipping'])) {
					$rates = $this->takeShippingRates($controls['shipping']);
					
					if($rates) {
						$output = ifset($rates[$value], $value);
					} else
						$output = $value;
				}
				break;
			default:
				if(isset($controls['compare']) && $controls['compare'] == '==')
					$value = '<span style="display: none;">-1</span>';
				
				$output = '<span class="control">' . $value . '</span>';
				break;
		}
		
		return $output;
	}
	
	public function getAllControlGroups()
	{
		return $this->controls;
	}
	
	public function getAllControls()
	{
		$controls = array();
		foreach($this->controls as $group)
			$controls = array_merge($controls, $group['controls']);
		
		return $controls;
	}
	
	public function getFieldValues($type, $additional = null)
	{
		if(!in_array($type, self::$control_types)) {
			return false;
		}

		switch($type) {
			case 'feature_value':
				if($additional) {
					$feature = explode(':', $additional);
					
					if($feature[1]) { // selectable
						$feature_values = shopFeatureModel::getFeatureValues(array(
							'type' => $feature[2],
							'id' => $feature[0]
						));
						
						if(is_array($feature_values)) {
							$output = array();
							
							foreach($feature_values as $id => $name)
								$output[] = array(
									'id' => $id,
									'name' => $name
								);
						} else
							return array();
					} else
						return array();
					
					return $output;
				} else
					return false;
				
				break;
			case 'feature_key':
				$where = '(type = "varchar" OR type = "double") AND parent_id IS NULL';
				break;
			case 'features':
				$where = '(type LIKE "%dimension.%" OR type = "double") AND parent_id IS NULL';
				break;
			case 'compare':
				$output = array('!=', '=', '>=', '<=', '>', '<');
				
				if($additional && $this->getControl($additional)) {
					$control = $this->getControl($additional);
					if(is_array($control)) {
						$type = explode(':', $control['type']);

						foreach($type as $field) {
							if($field != 'compare' && substr($field, 0, strlen('compare')) == 'compare')
								$output = explode(';', substr($field, strlen('compare') + 1, -1));
						}
					}
				}

				$compare = array();
				foreach($output as $id)
					$compare[] = array(
						'id' => $id,
						'name' => $id
					);
				
				return $compare;
				
				break;
			default:
				$where = '';
				break;
		}
		
		$values = $this->take($type == 'feature_key' ? 'features' : $type, $where);
		
		return $values;
	}
	
	public static function workupCondition(&$condition)
	{
		$controls_instance = new shopComplexPluginControls();
		
		$field = $condition['field'];
		$value = $condition['value'];

		foreach($value as $key => $_value) {
			$all_values = $controls_instance->getFieldValues($key, $key == 'feature_value' ? ifset($value['feature_key']) : ($key == 'compare' ? $field : null));
			$condition['control_all_values'][$key] = $all_values;
		}
	}
	
	public static function camelCase($str)
	{
		$str = str_replace('_', ' ', $str);
		$str = ucwords($str);
		$str = str_replace(' ', '', $str);
		return strtolower(substr($str, 0, 1)) . substr($str, 1);
	}
	
	private function takeOne($type, $id = null, $return = false, $where = '')
	{
		$key = 'id';
		
		if($type == 'countries')
			$key = 'iso3letter';
		if($type == 'regions')
			$key = 'code';

		$where = '';
		if(in_array($type, array('categories', 'user_categories')) && !is_null($id))
			$where = intval($id);
		else {
			$where = ($type == 'storefronts' ? $id : ($key . ' = \'' . addslashes($id) . '\'')) . ($where ? (' and ' . $where) : '');
		}
				
		return $this->take($type, $where, array('all' => false), $return);
	}
	
	private function take($type, $where = '', $params = array(), $return = false) {
		$function = 'take' . ucfirst(self::camelCase($type));

		if(method_exists(get_class(), $function)) {
			$output = call_user_func_array(array(get_class(), $function), array($where));

			if(isset($params['all']) && $params['all'] === false) {
				$output = array_shift($output);
				if($return !== false && isset($output[$return]))
					$output = $output[$return];
			}

			return $output;
		}
	}
	
	private function takeTypes($where = '') {
		$type_model = new shopTypeModel();
		$types = $type_model->select('`id`, `name`')->where($where)->order('id DESC')->fetchAll();
		
		return $types;
	}
	
	private function takeFeatures($where = '')
	{
		$feature_model = new shopFeatureModel();
		$features = $feature_model->select('`id`, `type`, `name`, `code`, `selectable`')->where($where)->order('id ASC')->fetchAll();
		
		return $features;
	}
	
	private function takeCategories($where = '')
	{
		if(is_int($where)) {
			$categories = array(
				wao(new shopCategoryModel())->getById($where)
			);
		} else
			$categories = wao(new shopCategoryModel())->getAll();
		
		return $categories;
	}
	
	private function takeShipping($where = '') {
		$plugin_model = new shopPluginModel();
		$shippings = $plugin_model->select('`id`, `name`, `logo`')->where('type = "shipping" ' . ($where ? ('and ' . $where) : ''))->order('id ASC')->fetchAll('id');
		
		foreach($shippings as $shipping_id => &$shipping)
			if($rates = self::takeShippingRates($shipping_id))
				$shipping['rates'] = $rates;

		return ifempty($shippings, array());
	}
	
	public static function takeShippingRates($shipping_id)
	{
		$shipping = new shopShipping();
		$info = $shipping->getPluginInfo($shipping_id);
		
		switch($info['plugin']) {
			case 'russianpost':
				return array(
					'ground' => 'Наземный транспорт',
					'avia' => 'АВИА',
					'bookpost_declared_ground' => 'Бандероль',
					'bookpost_declared_avia' => 'Бандероль АВИА'
				);
				break;
			default:
				return null;
				break;
		}
	}
	
	private function takePayment($where = '') {
		$plugin_model = new shopPluginModel();
		$payments = $plugin_model->select('`id`, `name`, `logo`')->where('type = "payment" ' . ($where ? ('and ' . $where) : ''))->order('id ASC')->fetchAll('id');

		return ifempty($payments, array());
	}
	
	private function takeStorefronts($storefront_id = null)
	{
		$routes = wa()->getRouting()->getByApp('shop');
		$storefronts = array();

		$storefronts['any'] = array(
			'id' => 'any',
			'name' => _wp('Any storefront')
		);
		
		foreach($routes as $site => $route) {
			foreach($route as $id => $params) {
				$key = $site . '/' . $id;

				if(!empty($params['_name']))
					$name = $params['_name'];
				else
					$name = $site . '/' . $params['url'];

				$storefronts[$key] = array(
					'name' => $name,
					'id' => $key
				);
				
				if(!is_null($storefront_id) && $key == $storefront_id)
					return array(
						$storefronts[$key]
					);
			}
		}
		
		return $storefronts;
	}
	
	private function takeUserCategories($where = '')
	{
		if($where == 'any') {
			$categories = array(
				array(
					'id' => 'any',
					'name' => _wp('Any category')
				)
			);
		} elseif(is_int($where)) {
			$categories = array(
				wao(new waContactCategoryModel())->getById($where)
			);
		} else {
			$categories = wao(new waContactCategoryModel())->getAll();
			$categories['any'] = array(
				'id' => 'any',
				'name' => _wp('Any category')
			);
		}

		return $categories;
	}
	
	private function takeCountries($where = '') {
		$country_model = new waCountryModel();
		$countries = $country_model->select('`iso3letter` AS `id`, `name`')->where($where ? $where : '')->order('id ASC')->fetchAll('id');
		foreach($countries as &$country)
			$country['name'] = waLocale::translate('webasyst', 'ru_RU', $country['name']);

		if(empty($where)) {
			unset($countries['rus']);
			unset($countries['ukr']);
			$first_countries = array('rus' => array('id' => 'rus', 'name' => _wp('Российская Федерация')), 'ukr' => array('id' => 'ukr', 'name' => _wp('Украина')));
			$countries = array_merge($first_countries, $countries);
		}
		
		return ifempty($countries, array());
	}
	
	private function takeRegions($where = '') {
		$region_model = new waRegionModel();
		$regions = $region_model->select('`code` AS `id`, `name`')->where($where ? $where : '')->order('id ASC')->fetchAll('id');

		return ifempty($regions, array());
	}
}