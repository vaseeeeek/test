<?php

/*
 * mail@shevsky.com
 *
 */

class shopMassupdatingPlugin extends shopPlugin
{
	public $inputs = array(
		'name' => array(
			'icon' => 'view-table',
			'name' => 'Наименование',
			'params' => array(
				'type' => 'input',
				'class' => 'long bold'
			)
		), 'url' => array(
			'icon' => 'script',
			'name' => 'URL',
			'params' => array(
				'type' => 'input',
				'class' => 'long bold'
			)
		), 'photo' => array(
			'icon' => 'user photo',
			'name' => 'Фото',
			'params' => array(
				'type' => 'user'
			)
		), 'prices' => array(
			'icon' => 'user',
			'name' => 'Цены',
			'params' => array(
				'type' => 'user'
			)
		), 'currencies' => array(
			'icon' => 'user',
			'name' => 'Валюты',
			'params' => array(
				'type' => 'user'
			)
		), 'tax_id' => array(
			'icon' => 'user',
			'name' => 'Налог',
			'params' => array(
				'type' => 'user'
			)
		), 'tags' => array(
			'icon' => 'user tags',
			'name' => 'Теги',
			'params' => array(
				'type' => 'user'
			)
		), 'badge' => array(
			'icon' => 'user badge-icon',
			'name' => 'Наклейка',
			'params' => array(
				'type' => 'user'
			)
		), 'features' => array(
			'icon' => 'star',
			'name' => 'Характеристики',
			'params' => array(
				'type' => 'user'
			)
		), 'summary' => array(
			'icon' => 'view-table',
			'name' => 'Summary',
			'params' => array(
				'type' => 'textarea',
				'style' => 'height: 37px;'
			)
		), 'meta_title' => array(
			'icon' => 'view-table',
			'name' => 'Заголовок страницы',
			'name_description' => '&lt;title>',
			'params' => array(
				'type' => 'input',
				'class' => 'long bold'
			),
			'description' => 'Здесь можно назначить <b>&lt;title></b> страницы, например, <b>"Купить {$name} в магазине {$shop} по цене {$price}"</b>'
		), 'meta_keywords' => array(
			'icon' => 'view-table',
			'name' => 'META Keywords',
			'params' => array(
				'type' => 'textarea'
			)
		), 'meta_description' => array(
			'icon' => 'view-table',
			'name' => 'META Description',
			'params' => array(
				'type' => 'textarea'
			)
		), 'description' => array(
			'icon' => 'view-table',
			'name' => 'Description',
			'params' => array(
				'type' => 'textarea',
				'wysiwyg' => true
			)
		), 'subpages' => array(
			'icon' => 'info',
			'name' => 'Подстраницы',
			'params' => array(
				'type' => 'user',
			)
		), 'video_url' => array(
			'icon' => 'user video',
			'name' => 'Видео',
			'params' => array(
				'type' => 'input',
				'style' => 'width: 220px;',
				'class' => 'massupdating-video long'
			),
			'description' => 'Video URL on YouTube or Vimeo'
		), 'skus' => array(
			'icon' => 'user sku',
			'name' => 'Артикулы и остатки',
			'params' => array(
				'type' => 'use'
			),
		), 'params' => array(
			'icon' => 'settings',
			'name' => 'Дополнительные параметры',
			'params' => array(
				'type' => 'textarea'
			)
		)
	);
	
	public static function getOne($name)
	{
		return self::getSettingsModel()->get(array('shop', 'massupdating'), $name);
	}
	
	public static function setOne($name, $value)
	{
		return self::getSettingsModel()->set(array('shop', 'massupdating'), $name, $value);
	}
	
	public function walk(&$value, $key)
	{
		$value['name'] = _wp($value['name']);
		if(isset($value['description']))
			$value['description'] = _wp($value['description']);
	}
	
	public function __construct($info)
	{		
		array_walk($this->inputs, array($this, 'walk'));
		parent::__construct($info);
	}
	
	public function iniGet($varname)
	{
		$ini = ini_get($varname);
		
		if($varname == 'upload_max_filesize' || $varname == 'post_max_size') {
			if(preg_match('/^\d+$/', $ini) == 0) {
				$format = substr($ini, -1);
				$value = (int) $ini;
				switch($format) {
					case 'G':
						$value *= 1024;
					case 'M':
						$value *= 1024;
					case 'K':
						$value *= 1024;
				}
				return $value;
			} else
				return $ini;
		} else
			return $ini;
	}
	
	public static function getFeatures($fetch_by_code = false, $parent = false)
	{
		$feature_model = new shopFeatureModel();
		$features = $feature_model->select('`id` as `value`, `name` as `title`, `code`, `type`, `selectable`, `multiple`')->where((!$parent ? '`parent_id` IS NULL and ' : '') . '(`type` = \'text\' or `type` = \'varchar\' or `type` = \'double\' or `type` = \'color\' or `type` = \'boolean\' or `type` LIKE \'range%\' or `type` LIKE \'dimension%\' or `type` LIKE \'2d%\' or `type` LIKE \'3d%\')')->order('id ASC')->fetchAll($fetch_by_code ? 'code' : 'value');

		return $features;
	}
	
	public function getFeaturesForAllTypes($features = array(), $product_ids = false)
	{
		if($features == 'all')
			$features = self::getFeatures();
		
		$type_features_model = new shopTypeFeaturesModel();
		$for_all_types = $type_features_model->getByField(array(
			'type_id' => 0,
			'feature_id' => array_keys($features)
		), true);
		
		$feature_ids = array();
		$result = array();
		foreach($for_all_types as $value) {
			$feature_ids[] = $value['feature_id'];
			$result[$value['feature_id']] = $features[$value['feature_id']];
		}

		return $product_ids == 'only_ids' ? $feature_ids : $this->getFeaturesControls($feature_ids, $product_ids);
	}
	
	public function getFeaturesWithoutDefault($next = array())
	{
		$features = self::getFeatures();

		return array_diff_key($features, array_flip($this->getSettings('features')), $next, array());
	}
	
	public function getFeatureValue($feature_id, $feature, $product_id = false)
	{
		if(!$product_id) {
			$code = $feature_id;
			$product_id = $feature;
		} else {
			$code = $feature['code'];
		}
		
		$product_features_model = new shopProductFeaturesModel();

		$default_value_array = $product_features_model->getValues($product_id);
		$default_value = ifset($default_value_array[$code]);
		
		return $default_value;
	}
	
	public function findFeatureValues($product_ids, $features)
	{
		$feature_values = array();
		$result = array();
		$types = array();
		
		foreach($product_ids as $product_id) {
			foreach($features as $feature_id => $feature) {
				$value = $this->getFeatureValue($feature_id, $feature, $product_id);
				$feature_values[$feature_id][] = $value;
				$types[$feature_id][] = gettype($value);
			}
		}
		
		foreach($feature_values as $key => $value) {
			if(count($product_ids) == count($value)) {
				foreach($value as $_key => $_value) {
					if(gettype($_value) == 'array') {
						ksort($_value);
						$value[$_key] = json_encode($_value);
					}
				};
				$unique = array_unique($value);
				if(count($unique) == 1) {
					if($features[$key]['multiple'])
						$result[$key]['value'] = json_decode($unique[0], true);
					elseif($features[$key]['type'] == 'boolean' && $unique[0] instanceof shopBooleanValue)
						$result[$key]['value'] = $unique[0]->__get('value');
					else
						$result[$key]['value'] = $unique[0];
				} else
					$result[$key]['differ_values'] = true;
			} else
				$result[$key]['differ_values'] = true;
		}

		return $result;
	}
	
	public function getFeaturesControls($ids, $product_ids = array())
	{
		$feature_model = new shopFeatureModel();
		$control_builder = new shopMassupdatingControlBuilder();
				
		$features = $feature_model->getFeatures('id', $ids, 'id', true);
		$feature_values = $this->findFeatureValues($product_ids, $features);
		foreach($features as $id => $feature) {
			$features[$id]['control'] = $control_builder->build($id, $feature, ifset($feature_values[$id]['value']), ifset($feature_values[$id]['differ_values']));
		};
		
		return $features;
	}
	
	public function filterAllowedProducts(array $product_ids)
	{
		if(wa('shop')->getUser()->getRights('shop', 'type.all') > 1) {
			return $product_ids;
		}
		
		$type_model = new shopTypeModel();
		$types = $type_model->getTypes();
		
        if(empty($product_ids) || empty($types))
            return array();
		
		$full_types = array();
		foreach($types as $type_id => $t)
			$full_types[] = $type_id;

		$where = array();
		$where[] = '(type_id IN (' . implode(',', $full_types) . '))';
		$where = implode(' OR ', $where);
		
		$product_model = new shopProductModel();
		
		return array_keys($product_model->query("
			SELECT id FROM `shop_product`
			WHERE id IN(".implode(',', $product_ids).")
				AND (".$where.")"
		)->fetchAll('id'));
	}
	
	public static function getAllCurrencies()
	{
		$model = new shopCurrencyModel();
		return $model->getCurrencies();
	}
	
	public static function getDefaultCurrency($param = false)
	{
		$currency = wa('shop')->getConfig()->getGeneralSettings('currency');
		if($param) {
			$currency_info = waCurrency::getInfo($currency);
			return ifset($currency_info[$param], '');
		} else
			return $currency;
	}
	
	public static function getCurrencySign($currency)
	{
		$currency_info = waCurrency::getInfo($currency);
		return $currency_info['sign'];
	}
	
	public function getTypes()
	{
		$type_model = new shopTypeModel();
		$types = $type_model->getTypes(false);
		if(is_array($types)) {
			if(empty($types))
				return false;
			else
				return array_keys($types);
		} elseif(is_bool($types))
			return $types;

		return false;
	}
	
	public function getProductIdsByHash($hash)
	{
		$product_ids = array();
			
		$offset = 0;
		$count = 100;
			
		$types = $this->getTypes();
			
		if($types === false)
			return array();
			
		$collection = new shopProductsCollection(urldecode($hash));
		if(is_array($types))
			$collection->addWhere('p.type_id IN ('.implode(',', $types).')');
		
		$total_count = $collection->count();
		$collection->orderBy('id');
			
		while($offset < $total_count) {
			$products = $collection->getProducts('id', $offset, $count);
			$product_ids = array_merge($product_ids, array_keys($products));
			$offset += count($products);
        }
		
		return $product_ids;
	}
	
	public function backendProducts($params)
	{
		$types = $this->getTypes();
		if($types === false || !$this->getSettings('on'))
			return;
		
		$vars = array(
			'generator' => _wp('Генератор артикулов'),
			'cross' => _wp('Перекрестные и схожие товары'),
			'far' => _wp('Найти и заменить'),
			'massupdating' => _wp('Массовое редактирование'),
			'or' => _wp('редактировать по отдельности...')
		);
		$vars = array_merge($vars, $this->inputs);
		
		$app_info = wa()->getAppInfo('shop');
		if($app_info['version'] < 7)
			unset($vars['video_url']);
		
		$js = wa()->getAppStaticUrl('shop/plugins/massupdating/js') . 'backend.js?4.0';
		wa()->getResponse()->addCss('plugins/massupdating/css/backend.css?7', 'shop');
		
		$vars_links = '';
		foreach(array_slice($vars, 5) as $key => $value) {
			if(isset($value['icon'])) $value['name'] = '<i class="icon10 ' . $value['icon'] . '"></i>' . $value['name'];
			if(isset($value['name_description'])) $value['name'] .= '<br/><span class="hint">' . $value['name_description'] . '</span>';
			$vars_links .= <<<HTML
		<li class="small">
			<a href="javascript: $.massupdating.edit('{$key}');">{$value['name']}</a>
		</li>
HTML;
		}
		
		return array(
			'toolbar_organize_li' => <<<HTML
<div class="block massupdating">
	<ul class="menu-v with-icons">
		<li>
			<a href="javascript: $.massupdating.generator();"><i class="icon16 palette"></i>{$vars['generator']}</a>
		</li>
		<li>
			<a href="javascript: $.massupdating.cross();"><i class="icon16 routing"></i>{$vars['cross']}</a>
		</li>
		<li>
			<a href="javascript: $.massupdating.edit();"><i class="icon16 edit"></i>{$vars['massupdating']}</a>
		</li>
		<li class="small hint">
			{$vars['or']}
		</li>
		{$vars_links}
	</ul>
</div>
<script type="text/javascript" src="{$js}"></script>
HTML
		);
	}
	
	public function memoryErrorCatcher($echo_string = '')
	{
		ini_set('display_errors', false);
		error_reporting(-1);
		
		function shutdown_function($echo_string) {
			$error = error_get_last();
			if(null !== $error && $error['type'] == 1) {
				http_response_code(200);
				echo $echo_string;
			}
		}
		register_shutdown_function('shutdown_function', $echo_string);
	}
}