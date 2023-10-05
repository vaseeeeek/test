<?php

abstract class shopDpPointsIntegration implements shopDpPointsIntegrationInterface
{
	const UNLIMITED = 'unlimited';
	const LOG_FILE = 'dp.points.log';

	public $id;
	public $service;
	public $return;

	protected $search_params = array();
	protected $query_params = array();
	protected $options = array();
	/**
	 * @var array[string]string $params
	 *
	 * @var int $params.style
	 * Стиль обработки пунктов выдачи для дальнейшего отображения пользователю
	 * 0: Запрашиваются все существующие пункты выдачи, сохраняются в базу, затем происходит выборка необходимых из базы
	 * 1: Запрашивается некий срез из всех существующих пунктов выдачи, сохраняется в базу, затем происходит выборка необходимых из них (логику работы среза и проверки актуальности по конкретному срезу необхдоимо устанавливать индивидуально)
	 * 2: Запрашиваются необходимые для конкретного города пункты выдачи и сохраняются в базу данных
	 * 3: Возвращаются уже необходимые пункты выдачи в соответствии с параметрами поиска (не сохраняются в базу данных)
	 *
	 * @var string|int $params.actuality
	 * Актуальность хранимых в базе данных записей
	 * 'unlimited': Данные сохраняются один раз и больше не обновляются
	 * 0...N: Значение актуальности данных в секундах
	 */
	protected $params = array(
		'style' => 0,
		'actuality' => self::UNLIMITED
	);

	/**
	 * @param int $id
	 * Идентификатор способа доставки (такой же, как и в Shop-Script)
	 *
	 * @param string $service
	 * Сервис службы доставки
	 *
	 * @param array $search_params
	 * Параметры поиска пунктов выдачи
	 *
	 * @param string $return
	 * Тип возвращаемых значений:
	 * 'actual': Вернуть только актуальные пункты выдачи, если нужно их обновить, то они обновятся и только потом будут возвращены
	 * 'existing': Вернуть существующие пункты выдачи, без проверки их актуальности
	 * 'minimal': Вернуть пункты выдачи только для стиля обработки "3", для остальных пустой массив
	 */

	final public function __construct($id, $service, $search_params = array(), $return = 'actual')
	{
		$this->id = $id;
		$this->service = $service;
		$this->return = $return;

		$this->setSearchParams($search_params);
	}

	final public function getSearchParams($param = null)
	{
		if($param === null)
			return $this->search_params;
		else
			return ifset($this->search_params, $param, null);
	}

	final public function setSearchParams($search_params = array(), $clearly = false)
	{
		if($clearly) {
			$this->search_params = $search_params;
		} else {
			$this->search_params = array_merge($this->search_params, $search_params);
		}
	}

	final public function getParams()
	{
		return $this->params;
	}

	final public function getParam($field)
	{
		return ifset($this->params, $field, null);
	}

	final public function log($error)
	{
		shopDpLog::log($error, self::LOG_FILE);
	}

	final public function getShippingModel()
	{
		if(!isset($this->shipping_model))
			$this->shipping_model = new shopDpShippingModel();

		return $this->shipping_model;
	}

	final public function getPointsModel()
	{
		if(!isset($this->points_model))
			$this->points_model = new shopDpPointsModel();

		return $this->points_model;
	}

	final public function isUnlimited()
	{
		return $this->getParam('actuality') === self::UNLIMITED;
	}

	final public function setOption($name, $value)
	{
		$this->options[$name] = $value;
	}

	final public function getOption($name)
	{
		return ifset($this->options, $name, null);
	}

	final protected function getPlugin()
	{
		if($this->getOption('plugin')) {
			$plugin = $this->getOption('plugin');
		} else {
			$plugin = shopDpPlugin::getInstance('store');

			$this->setOption('plugin', $plugin);
		}

		return $plugin;
	}

	final public function getCacheActuality()
	{
		if($this->getOption('actuality')) {
			$actuality = $this->getOption('actuality');
		} else {
			return $this->getParam('actuality');
		}

		return (int) $actuality;
	}

	public function getNet($options = array(), $custom_options = array(), $reload = false)
	{
		if(!isset($this->net) || $reload) {
			$options = array_merge(array(
				'timeout' => 15
			), $options);

			$options = array_merge(array('format' => ifset($options, 'format', waNet::FORMAT_XML), 'request_format' => ifset($options, 'request_format', waNet::FORMAT_RAW)), $options);
			$net = new waNet(
				$options,
				$custom_options
			);

			if($reload)
				return $net;
			else
				$this->net = $net;
		}

		return $this->net;
	}

	public function getQueryParams()
	{
		return $this->query_params;
	}

	public function setQueryParams($query_params = array())
	{
		$this->query_params = array_merge($this->query_params, $query_params);
	}

	/**
	 * Функции для работы с пунктами выдачи
	 *
	 * Алгоритм работы:
	 * 1. Пользователем происходит обращение к findPoints
	 * 2. findPoints передает запрос на getPoints
	 * getPoints определяет, можно ли взять уже существующие в базе значения и если нет, то переходит к takePoints, затем сохраняет его значения с помощью savePoints
	 * 3. takePoints возвращает список пунктов выдачи, метод в обязательном порядке реализуется наследователями этого класса
	 */

	/**
	 * Построение хеша для идентификации актуальности хранимых в базе данных для конкретных поисковых параметров
	 */
	protected function getHash()
	{
		$search_params = $this->getSearchParams();

		$hash = null;

		if(!empty($search_params['country_code']) && !empty($search_params['region_code'])) {
			switch($this->getParam('style')) {
				case 1:
					$hash = "{$search_params['country_code']}:{$search_params['region_code']}";
					break;
				case 2:
					if(!empty($search_params['city_name']))
						$hash = "{$search_params['country_code']}:{$search_params['region_code']}:{$search_params['city_name']}";
					break;
			}
		}

		return $hash;
	}

	protected function savePoints($points = array())
	{
		$this->getPointsModel()->deletePoints($this->id, $this->service, $this->getHash());
		$this->getShippingModel()->updateShipping($this->id, $this->service, $this->getHash());

		foreach($points as &$point) {
			$point['shipping_id'] = $this->id;
			$point['service'] = $this->service;
			$point['search_hash'] = $this->getHash();

			$this->getPointsModel()->savePoint($point);
		}
	}

	protected function getPointsFromModel()
	{
		$actuality = $this->getCacheActuality();
		if($actuality === 0) {
			return false;
		}

		$result = false;
		$data = $this->getPointsModel()->getPoints($this->id, $this->service, $this->getSearchParams());

		switch($this->return) {
			case 'actual':
				$shipping = $this->getShippingModel()->getShipping($this->id, $this->service, $this->getHash());

				if($shipping) {
					if($this->isUnlimited()) {
						$result = true;
					} else {
						$shipping_update_datetime = new DateTime($shipping['update_datetime']);

						if($shipping_update_datetime->getTimestamp() + $this->getCacheActuality() > time()) {
							$result = true;
						} else {
							$result = false;
						}
					}
				} else
					return false;
				break;
			case 'existing':
				$result = true;
				break;
		}

		return array(
			'result' => $result,
			'data' => $data
		);
	}

	final public function findPoints($key = 'hash')
	{
		return $this->getPoints($key);
	}

	public function getPoints($key = null)
	{
		$style = $this->getParam('style');
		if($this->return === 'minimal') {
			if($style === 3) {
				return $this->takePoints($key);
			} else {
				return array();
			}
		}

		switch($style) {
			case 0: // Запрашиваются все существующие пункты выдачи, сохраняются в базу, затем происходит выборка необходимых из базы
			case 1: // Запрашивается некий срез из всех существующих пунктов выдачи, сохраняется в базу, затем происходит выборка необходимых из них
			case 2: // Запрашиваются необходимые для конкретного города пункты выдачи
				$points_from_model = $this->getPointsFromModel();

				$points_result = is_array($points_from_model) ? $points_from_model['result'] : $points_from_model;
				$points_data = is_array($points_from_model) ? $points_from_model['data'] : $points_from_model;

				if($points_result !== false) {
					return $points_data;
				} else {
					$taken_points = $this->takePoints($key);

					if($taken_points !== false) {
						if($this->return === 'actual') { // Если производился поиск только актуальных
							$this->savePoints($taken_points); // Сохраняем все полученные пункты выдачи

							return $this->getPoints();
						} else
							return $taken_points;
					} else {
						return $points_data;
					}
				}
				break;
			case 3: // Возвращаются уже необходимые пункты выдачи в соответствии с параметрами поиска
				return $this->takePoints($key);
				break;
		}

		return false;
	}

	public function takePoints($key = null) {
		return false;
	}
}
