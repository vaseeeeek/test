<?php

class shopDpPluginHelper
{
	protected static $location;

	public static $months = array(
		'01' => array('январь', 'января'),
		'02' => array('февраль', 'февраля'),
		'03' => array('март', 'марта'),
		'04' => array('апрель', 'апреля'),
		'05' => array('май', 'мая'),
		'06' => array('июнь', 'июня'),
		'07' => array('июль', 'июля'),
		'08' => array('август', 'августа'),
		'09' => array('сентябрь', 'сентября'),
		'10' => array('октябрь', 'октября'),
		'11' => array('ноябрь', 'ноября'),
		'12' => array('декабрь', 'декабря'),
	);

	protected static $estimated_date_entity_priorities = array(
		'hour' => 1,
		'day' => 10,
		'week' => 100,
		'month' => 1000
	);

	public static function getLocation()
	{
		if(self::$location === null)
			self::$location = new shopDpLocation('helper');

		return self::$location;
	}

	private static function getFrontend($params)
	{
		$frontend = new shopDpFrontend(null, shopDpPlugin::staticallyGetSettings(), shopDpPlugin::getEnv());

		if(!empty($params)) {
			$frontend->setParams($params);
		}

		return $frontend;
	}

	public static function citySelect($location = null, $params = array())
	{
		self::setLocation($location);

		$frontend = self::getFrontend($params);
		$output = $frontend->citySelect();

		return $output;
	}

	public static function head()
	{
		$plugin = shopDpPlugin::getInstance();

		return $plugin->frontendHead();
	}

	public static function page($location = null, $params = array())
	{
		self::setLocation($location);

		$frontend = self::getFrontend($params);
		$output = $frontend->page();

		return $output;
	}

	public static function product($product, $location = null, $params = array())
	{
		self::setLocation($location);

		$frontend = self::getFrontend($params);
		$output = $frontend->product($product);

		return $output;
	}

	public static function getPluralForm($n, $values)
	{
		if(is_string($values)) {
			switch($values) {
				case 'points':
					$values = array(
						'пункт', 'пункта', 'пунктов'
					);
					break;
				case 'shop':
					$values = array(
						'магазине', 'магазинах', 'магазинах'
					);
					break;
			}
		}

		$cases = array(2, 0, 1, 1, 1, 2);
		return $values[($n % 100 > 4 && $n % 100 < 20) ? 2: $cases[min($n % 10, 5)]];
	}

	public static function currencyCost($cost, $currency)
	{
		if($cost == 0)
			return '<span class="dp-price__value-free">Бесплатно</span>';
		else
			return shop_currency_html($cost, $currency);
	}

	public static function setLocation($country, $region = null, $city = null)
	{
		if(empty($country)) {
			return false;
		}

		$l = self::getLocation();

		$is_location_is_array = is_array($country) && array_key_exists('country', $country) && array_key_exists('region', $country) && array_key_exists('city', $country) && $region === null && $city === null;
		if($is_location_is_array) {
			$location = $country;

			$l->setCountry($location['country']);
			$l->setRegion($location['region']);
			$l->setCity($location['city']);
		} else {
			$l->setCountry($country);
			$l->setRegion($region);
			$l->setCity($city);
		}
	}

	public static function getPointServices($store = true)
	{
		$services = array('cdek', 'kit', 'boxberry', 'iml', 'yandexdelivery', 'dpd', 'pickpoint', 'pek', 'easyway', 'dellin');

		if($store)
			array_push($services, 'store');

		return $services;
	}

	public static function camelToUnderscore($str)
	{
		return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $str));
	}

	public static function getMonthByString($month) {
		$month = mb_strtolower($month);

		foreach(self::$months as $m => $strings) {
			if(in_array($month, $strings)) {
				return $m;
				break;
			}
		}
	}

	public static function getPluginUrl()
	{
		return wa()->getAppStaticUrl('shop') . 'plugins/dp/';
	}

	public static function getCurrency($type = 'frontend')
	{
		return wa('shop')->getConfig()->getCurrency($type === 'shop' ? true : ($type === 'frontend' ? false : null));
	}

	public static function getCurrencyInfo($type = 'frontend')
	{
		$currency = self::getCurrency($type);

		$info = waCurrency::getInfo($currency);

		if($info) {
			if(empty($info['sign_html']))
				$info['sign_html'] = $info['sign'];

			return $info;
		}
	}

	public static function getCountries($group = false)
	{
		return wao(new waCountryModel())->allWithFav();
	}

	public static function getCountryName($country)
	{
		return wao(new waCountryModel())->name($country);
	}

	public static function getRegions($country, $group = false)
	{
		if($group) {
			$method = 'getByCountry';
		} else {
			$method = 'getByCountryWithFav';
		}

		return wao(new waRegionModel())->$method($country);
	}

	public static function getRegionName($country, $region)
	{
		$region = wao(new waRegionModel())->get($country, $region);

		return ifset($region, 'name', null);
	}

	public static function isWorktimeCorrect(&$value)
	{
		if(is_array($value))
			$period = $value['period'];
		else
			$period = $value;

		return preg_match('/^[0-9]{2}:[0-9]{2}\/[0-9]{2}:[0-9]{2}$/', $period);;
	}

	public static function worktimeString($data)
	{
		$day_titles = array(
			1 => _wp('Пн'),
			2 => _wp('Вт'),
			3 => _wp('Ср'),
			4 => _wp('Чт'),
			5 => _wp('Пт'),
			6 => _wp('Сб'),
			7 => _wp('Вс')
		);

		$grouped = array();
		foreach($data as $day => $value) {
			if(!empty($value)) {
				$period = is_array($value) ? $value['period'] : $value;

				if(preg_match('/^[0-9]{2}:[0-9]{2}\/[0-9]{2}:[0-9]{2}$/', $period))
					$grouped[$period][] = $day;
			}
		}

		$output = '';

		foreach($grouped as $period => $days) {
			if(!$period)
				continue;

			sort($days);

			if(count($days) > 1) {
				foreach($days as $day) {
					if(isset($prev)) {
						if($prev + 1 !== $day) {
							$not_correct = true;
							break;
						} else {
							$prev = $day;
						}
					} else {
						$prev = $day;
					}
				}

				if(!empty($not_correct)) {
					$days_str = strtr(implode(', ', $days), $day_titles);
				} else {
					$first = reset($days);
					$first_title = $day_titles[$first];
					$last = end($days);
					$last_title = $day_titles[$last];

					$days_str = "$first_title-$last_title";
				}
			} else {
				$first = reset($days);
				$days_str = $day_titles[$first];
			}

			if($period == '00:00/00:00') {
				$output .= "$days_str — Круглосуточно\n";
				continue;
			}

			$period = str_replace('/', '-', $period);

			$output .= "$days_str — $period\n";
		}

		return trim($output);
	}

	private static function htmlEstimatedDateValue($value, $params)
	{
		$format = ifset($params, 'format', 'human');

		switch($format) {
			case 'human':
				return wa_date('humandate', $value);
				break;
			default:
				return date($format, $value);
				break;
		}
	}

	public static function htmlEstimatedDate($date, $params = array('format' => 'human', 'range' => 'range'), $is_return_short = true)
	{
		$output = null;
		switch($date['type']) {
			case 'today':
				$output = '<span class="dp-estimated-date__value dp-estimated-date__value_today">';
				$output .= $is_return_short ? 'Сегодня' : 'Доставка в день заказа';
				$output .= '</span>';
				break;
			case 'next-day':
				$output = '<span class="dp-estimated-date__value dp-estimated-date__value_next-day">';
				$output .= $is_return_short ? 'Завтра' : 'Доставка на следующий день';
				$output .= '</span>';
				break;
			case 'date':
				$value = self::htmlEstimatedDateValue($date['value'], $params);

				$output = "<span class=\"dp-estimated-date__value dp-estimated-date__value_date\">{$value}</span>";
				break;
			case 'date-interval':
				$range_format = ifset($params, 'range', 'range');

				if(in_array($range_format, array('min', 'max'))) {
					$_date = array(
						'type' => 'date',
						'value' => $date[$range_format]
					);

					return self::htmlEstimatedDate($_date, $params, $is_return_short);
				}

				$min = self::htmlEstimatedDateValue($date['min'], $params);
				$max = self::htmlEstimatedDateValue($date['max'], $params);

				if($min === $max) {
					$output = "<span class=\"dp-estimated-date__value dp-estimated-date__value_date\">{$min}</span>";
				} else {
					$output = "<span class=\"dp-estimated-date__value dp-estimated-date__value_min-date\">{$min}</span>";
					$output .= ' <span class="dp-estimated-date__caption dp-dp-estimated-date__caption_dash">&ndash;</span> ';
					$output .= "<span class=\"dp-estimated-date__value dp-estimated-date__value_max-date\">{$max}</span>";
				}
				break;
			case 'entity':
				$form = shopDpPluginHelper::getPluralForm($date['value'], self::parseEstimatedDateForm($date['entity']));
				$output = '<span class="dp-estimated-date__caption dp-estimated-date__caption_during">В течение</span> ';
				$output .= "<span class=\"dp-estimated-date__value dp-estimated-date__value_entity\">{$date['value']}</span>";
				$output .= " <span class=\"dp-estimated-date__caption dp-estimated-date__caption_entity\">{$form}</span>";
				break;
			case 'entities-interval':
				$max_form = shopDpPluginHelper::getPluralForm($date['max_value'], self::parseEstimatedDateForm($date['max_entity']));
				$min_form = shopDpPluginHelper::getPluralForm($date['min_value'], self::parseEstimatedDateForm($date['min_entity']));

				if (!$date['min_value'])
				{
					$output = '<span class="dp-estimated-date__caption dp-estimated-date__caption_during">';
					$output .= $is_return_short ? 'В течение' : 'Доставка в течение';
					$output .= '</span> ';

					$output .= "<span class=\"dp-estimated-date__value dp-estimated-date__value_entity-interval\">{$date['max_value']}</span>";
					$output .= " <span class=\"dp-estimated-date__caption dp-estimated-date__caption_entity\">{$max_form}</span>";
				}
				elseif (!$date['max_value'])
				{
					$output = '<span class="dp-estimated-date__caption dp-estimated-date__caption_during">';
					$output .= $is_return_short ? 'От' : 'Доставка от';
					$output .= '</span> ';

					$output .= "<span class=\"dp-estimated-date__value dp-estimated-date__value_entity-interval\">{$date['min_value']}</span>";
					$output .= " <span class=\"dp-estimated-date__caption dp-estimated-date__caption_entity\">{$min_form}</span>";
				}
				else
				{
					$output = '<span class="dp-estimated-date__caption dp-estimated-date__caption_during">';
					$output .= $is_return_short ? 'В течение' : 'Доставка в течение';
					$output .= '</span> ';

					if ($date['min_entity'] === $date['max_entity'])
					{
						$output .= "<span class=\"dp-estimated-date__value dp-estimated-date__value_entity-interval\">{$date['min_value']}-{$date['max_value']}</span>";
						$output .= " <span class=\"dp-estimated-date__caption dp-estimated-date__caption_entity\">{$max_form}</span>";
					}
					else
					{
						$output .= "<span class=\"dp-estimated-date__value dp-estimated-date__value_entity-interval\">{$date['min_value']}</span>";
						$output .= " <span class=\"dp-estimated-date__caption dp-estimated-date__caption_entity\">{$min_form}</span>";
						$output .= ' <span class="dp-estimated-date__caption dp-dp-estimated-date__caption_dash">&ndash;</span> ';
						$output .= "<span class=\"dp-estimated-date__value dp-estimated-date__value_entity-interval\">{$date['max_value']}</span>";
						$output .= " <span class=\"dp-estimated-date__caption dp-estimated-date__caption_entity\">{$max_form}</span>";
					}
				}
				break;
			case 'order-time-variable':
				$output = '<div class="dp-estimated-date__values">';
				foreach($date['values'] as $value) {
					$output .= '<div class="dp-estimated-date__values-item">';

					$output .= '<div class="dp-estimated-date__values-item-condition">';
					switch($value['hour_state']) {
						case 'before':
							$output .= '<span class="dp-estimated-date__caption dp-estimated-date__caption_before">Заказ до</span>';
							break;
						case 'after':
							$output .= '<span class="dp-estimated-date__caption dp-estimated-date__caption_after">Заказ после</span>';
							break;
					}
					$output .= " <span class=\"dp-estimated-date__caption dp-estimated-date__caption_hour-value\">{$value['hour_value']}</span>";
					$output .= ' <span class="dp-estimated-date__caption dp-estimated-date__caption_hour-string">часов</span>';
					$output .= '</div> ';

					$output .= '<div class="dp-estimated-date__values-item-value">';
					$output .= self::htmlEstimatedDate($value['value'], $params, false);
					$output .= '</div>';

					$output .= '</div> ';
				}
				$output .= '</div>';
				break;
		}

		if($output) {
			return "<div class=\"dp-estimated-date dp-estimated-date_{$date['type']}\">{$output}</div>";
		} else {
			return '';
		}
	}

	/**
	 * Обрабатывает сроки доставки и приводит их к единому формату
	 *
	 * @param $date
	 * @param $options
	 * @param $schedule
	 *
	 * @return array[string] $params
	 *
	 * @return string $params.type
	 * Формат отдаваемого значения
	 *
	 ** 'date'
	 ** Одно единственное значение даты
	 ** @return string $params.value
	 **
	 ** 'date-interval'
	 ** Интервал дат
	 ** @return string $params.min
	 ** @return string $params.max
	 **
	 ** 'today'
	 ** Сегодня
	 **
	 ** 'next-day'
	 ** Завтра
	 **
	 ** 'entity'
	 ** Количество дней/недель/месяцев
	 ** @return 'day'|'week'|'month' $params.entity
	 ** @return string $params.value
	 **
	 ** 'entities-interval'
	 ** Интервал дней/недель/месяцев
	 ** @return 'day'|'week'|'month' $params.min_entity
	 ** @return string $params.min_value
	 ** @return 'day'|'week'|'month' $params.max_entity
	 ** @return string $params.max_value
	 **
	 ** 'order-time-variable'
	 ** Вариативное значение, зависящее от времени заказа
	 ** @return array[int]array $params.values
	 ** @return 'before'|'after' $params.values[int].hour_state
	 ** @return int $params.values[int].hour_value
	 ** @return array $params.values[int].value
	 */
	public static function parseEstimatedDate($date, $options, $schedule = null)
	{
		$params = array(
			'type' => 'undefined'
		);

		if($date === null || !isset($date['format'])) {
			return $params;
		}

		$mode = ifset($options, 'mode', 'estimated_date', 'inline');

		if($mode === 'no') {
			return $params;
		}

		switch($date['format']) {
			case 'calculated':
				$params = self::parseEstimatedDateInitial(ifset($date, 'value', null));
				break;
			case 'fixed':
				$params = self::parseEstimatedDateEntities(ifset($date, 'value', 'fixed', null));
				break;
			case 'order_time':
				$params = self::parseEstimatedDateOrderTime(ifset($date, 'value', 'order_time', null), $options);
				break;
		}

		$schedule = is_array($schedule) && count($schedule) > 0
			? $schedule
			: self::buildShopSchedule();

		if(!empty($schedule)) {
			$params = self::workupEstimatedDate($params, $schedule);
		}

		return $params;
	}

	private static function createDatetime($timestamp)
	{
		$datetime = new DateTime();
		$datetime->setTimestamp($timestamp);

		return $datetime;
	}

	private static function getDayOfWeek($timestamp)
	{
		$datetime = self::createDatetime($timestamp);

		return $datetime->format('N');
	}

	private static function isTimeInSchedule($hour, $minutes, $schedule)
	{
		if($schedule === '00:00/00:00') {
			return true;
		}

		$is_match = preg_match('/^([0-9]{2}):([0-9]{2})\/([0-9]{2}):([0-9]{2})$/', $schedule, $matches);
		if(!$is_match) {
			return false;
		}

		list (, $from_h, $from_m, $to_h, $to_m) = $matches;

		if ($hour < $from_h)
		{
			return true;
		}

		if($hour > $from_h) {
			if($to_h > $hour) {
				return true;
			} elseif($to_h === $hour) {
				return $to_m > $minutes;
			}

			return false;
		} elseif($hour === $from_h) {
			if($to_h > $hour) {
				return $minutes > $from_m;
			} elseif($to_h === $hour) {
				return $to_m > $minutes;
			}

			return false;
		}

		return false;
	}

	private static function isCurrentTimeInSchedule($schedule)
	{
		$hour = date('H');
		$minutes = date('i');

		return self::isTimeInSchedule($hour, $minutes, $schedule);
	}

	private static function isDayInSchedule($timestamp, $schedule, $is_check_time = false)
	{
		$day_of_week = self::getDayOfWeek($timestamp);

		$is_in = array_key_exists($day_of_week, $schedule) && !empty($schedule[$day_of_week]) && $schedule[$day_of_week] !== ':/:';
		$additional_check = true;

		if($is_check_time && $is_in) {
			$additional_check = self::isCurrentTimeInSchedule($schedule[$day_of_week]);
		}

		return $is_in && $additional_check;
	}

	private static function modifyDayForSchedule(&$day, $schedule)
	{
		$current_timestamp = time();

		$timestamp = strtotime("+{$day} day");
		$previous_timestamp = $timestamp;

		self::modifyTimestampForSchedule($timestamp, $schedule);
		if($previous_timestamp !== $timestamp) {
			$diff_timestamp = $timestamp - $current_timestamp;
			$days = round($diff_timestamp / 86400);
			$day = $days;
		}
	}

	private static function modifyTimestampForSchedule(&$timestamp, $schedule, $is_check_time = false)
	{
		if(self::isDayInSchedule($timestamp, $schedule, $is_check_time)) {
			return;
		}

		$i = 0;

		do {
			$timestamp += 86400;
			$i++;
		} while (!self::isDayInSchedule($timestamp, $schedule) && $i < 7);
	}

	protected static function workupEstimatedDate($params, $schedule)
	{
		switch($params['type']) {
			case 'date':
				self::modifyTimestampForSchedule($params['value'], $schedule);
				break;
			case 'date-interval':
				self::modifyTimestampForSchedule($params['min'], $schedule);
				self::modifyTimestampForSchedule($params['max'], $schedule);
				break;
			case 'today':
			case 'next-day':
				$last_value = $params['value'];
				self::modifyTimestampForSchedule($params['value'], $schedule, $params['type'] === 'today');
				if($last_value !== $params['value']) {
					$params['type'] = 'date';
				}

				break;
			case 'entity':
				if($params['entity'] === 'day') {
					self::modifyDayForSchedule($params['value'], $schedule);
				}
				break;
			case 'entities-interval':
				if($params['min_entity'] === $params['max_entity'] && $params['min_entity'] === 'day') {
					$diff_days = intval($params['max_value']) - intval($params['min_value']);

					self::modifyDayForSchedule($params['min_value'], $schedule);
					$params['max_value'] = intval($params['min_value']) + $diff_days;
				}
				break;
		}

		return $params;
	}

	protected static function parseEstimatedDateOrderTime($date, $options)
	{
		$params = array(
			'type' => 'undefined'
		);

		$mode = ifset($options, 'mode', 'estimated_date', 'inline');

		switch($mode) {
			case 'inline':
				usort($date, wa_lambda('$a, $b', 'return $a["hour"] < $b["hour"] ? -1 : 1;'));

				/**
				 * @param shopDpPluginHelper::parseEstimatedDate['type'] => 'order-time-variable'
				 */
				$params['type'] = 'order-time-variable';
				$params['values'] = array();

				foreach($date as $data) {
					if (!$data) {
						continue;
					}

					if($data['hour'] == 0) {
						$min = null;
						foreach($date as $_data) {
							if($_data['hour'] != 0 && ($min === null || intval($_data['hour']) < $min)) {
								$min = intval($_data['hour']);
							}
						}

						if($min)
							$hour_string = "до <span class=\"dp-estimated-date__time\">{$min}:00</span>";

						$value = array(
							'hour_state' => 'before',
							'hour_value' => (int) $min
						);
					} else {
						$value = array(
							'hour_state' => 'after',
							'hour_value' => (int) $data['hour']
						);
					}

					$value['value'] = self::parseEstimatedDateInitial($data['value']);
					array_push($params['values'], $value);
				}
				break;
			case 'current':
				$hour = date('G');
				usort($date, wa_lambda('$a, $b', 'return $a["hour"] < $b["hour"] ? 1 : -1;'));

				foreach($date as $data) {
					if($hour >= $data['hour']) {
						$params = self::parseEstimatedDateInitial($data['value']);
						break;
					}
				}
				break;
		}

		return $params;
	}

	protected static function parseEstimatedDateMinMax($values)
	{
		$min = min($values);
		$max = max($values);

		if(abs($min - $max) < 86400) {
			/**
			 * @param shopDpPluginHelper::parseEstimatedDate['type'] => 'date'
			 */

			return array(
				'type' => 'date',
				'value' => $min
			);
		}

		/**
		 * @param shopDpPluginHelper::parseEstimatedDate['type'] => 'date-interval'
		 */
		return array(
			'type' => 'date-interval',
			'min' => $min,
			'max' => $max
		);
	}

	protected static function parseEstimatedDateInitial($date)
	{
		if (is_array($date) && !empty($date))
		{
			$calculated_key = array_search('calculated', $date);
			if ($calculated_key !== false)
			{
				unset($date[$calculated_key]);
			}

			$min_from_timestamp = null;
			$max_to_timestamp = null;
			foreach ($date as $__value)
			{
				$from = null;
				$to = null;

				if (is_array($__value) && count($__value) === 2)
				{
					$from = $__value[0];
					$to = $__value[1];
				}
				elseif (!is_array($__value))
				{
					$from = $to = $__value;
				}
				else
				{
					continue;
				}

				if (is_string($from) && substr($from, 0, 1) === '+')
				{
					$date_item_matches = self::parseEstimatedDateMatch($from);
					$from = strtotime("+{$date_item_matches['number']} {$date_item_matches['entity']}");
				}
				if (is_string($to) && substr($to, 0, 1) === '+')
				{
					$date_item_matches = self::parseEstimatedDateMatch($to);
					$to = strtotime("+{$date_item_matches['number']} {$date_item_matches['entity']}");
				}

				if ($min_from_timestamp === null || $min_from_timestamp > $from)
				{
					$min_from_timestamp = $from;
				}

				if ($max_to_timestamp === null || $max_to_timestamp < $to)
				{
					$max_to_timestamp = $to;
				}
			}

			if ($min_from_timestamp === $max_to_timestamp)
			{
				return self::parseEstimatedDateValue($min_from_timestamp);
			}
			else
			{
				return self::parseEstimatedDateMinMax(array(
					$min_from_timestamp,
					$max_to_timestamp
				));
			}
		} elseif(!is_array($date) && !empty($date)) {
			if(substr($date, 0, 1) === '+') {
				return self::parseEstimatedDateEntities($date);
			} else {
				return self::parseEstimatedDateValue($date);
			}
		} else {
			return array(
				'type' => 'undefined'
			);
		}
	}

	protected static function parseEstimatedDateValue($date)
	{
		if(date('Y-m-d', $date) === date('Y-m-d')) {
			/**
			 * @param shopDpPluginHelper::parseEstimatedDate['type'] => 'today'
			 */
			$params = array(
				'type' => 'today',
				'value' => $date
			);
		} elseif(date('Y-m-d', $date) === date('Y-m-d', strtotime('tomorrow'))) {
			/**
			 * @param shopDpPluginHelper::parseEstimatedDate['type'] => 'next-day'
			 */
			$params = array(
				'type' => 'next-day',
				'value' => $date
			);
		} else {
			/**
			 * @param shopDpPluginHelper::parseEstimatedDate['type'] => 'date'
			 */
			$params = array(
				'type' => 'date',
				'value' => $date
			);
		}

		return $params;
	}

	protected static function parseEstimatedDateEntities($date, $options = array())
	{
		$params = array(
			'type' => 'undefined'
		);

		switch($date) {
			case '+3 hour':
				/**
				 * @param shopDpPluginHelper::parseEstimatedDate['type'] => 'today'
				 */
				$params = array(
					'type' => 'today',
					'value' => strtotime('today')
				);
				break;
			case '+1 day':
				/**
				 * @param shopDpPluginHelper::parseEstimatedDate['type'] => 'next-day'
				 */
				$params = array(
					'type' => 'next-day',
					'value' => strtotime('tomorrow')
				);
				break;
			default:
				if(is_array($date)) {
					$min = null;
					$max = null;

					foreach($date as $date_item) {
						$date_item_matches = self::parseEstimatedDateMatch($date_item);

						if(!$date_item_matches)
							continue;

						$compare_date_item_value = intval($date_item_matches['number']) * intval($date_item_matches['priority']);

						if($min === null || $compare_date_item_value < $min['compare_value'])
							$min = array(
								'entity' => $date_item_matches['entity'],
								'value' => $date_item_matches['number'],
								'compare_value' => $compare_date_item_value
							);

						if($max === null || $compare_date_item_value > $max['compare_value'])
							$max = array(
								'entity' => $date_item_matches['entity'],
								'value' => $date_item_matches['number'],
								'compare_value' => $compare_date_item_value
							);
					}

					if($min !== null && $max !== null) {
						if($min['entity'] === $max['entity'] && $min['value'] === $max['value']) {
							$params = self::parseEstimatedDateEntities("+{$min['value']} {$min['entity']}");
						} else {
							/**
							 * @param shopDpPluginHelper::parseEstimatedDate['type'] => 'entities-interval'
							 */
							$params = array(
								'type' => 'entities-interval',
								'min_entity' => $min['entity'],
								'min_value' => $min['value'],
								'max_entity' => $max['entity'],
								'max_value' => $max['value'],
							);
						}
					}
				} else {
					$date_matches = self::parseEstimatedDateMatch($date);

					if($date_matches) {
						/**
						 * @param shopDpPluginHelper::parseEstimatedDate['type'] => 'entity'
						 */
						$params = array(
							'type' => 'entity',
							'entity' => $date_matches['entity'],
							'value' => $date_matches['number']
						);
					}
				}
				break;
		}

		return $params;
	}

	public static function parseEstimatedDateForm($entity, $style = 0)
	{
		switch($style) {
			case 0:
				$forms = array(
					'day' => array('дня', 'дней', 'дней'),
					'week' => array('недели', 'недель', 'недель'),
					'month' => array('месяца', 'месяцев', 'месяцев')
				);
				break;
			case 1:
				$forms = array(
					'day' => array('день', 'дня', 'дней'),
					'week' => array('неделя', 'недели', 'недель'),
					'month' => array('месяц', 'месяцев', 'месяцев')
				);
				break;
		}

		return $forms[$entity];
	}

	protected static function parseEstimatedDateMatch($date)
	{
		preg_match('/^\+([0-9]+) (hour|days?|week|month)$/', $date, $matches);

		if($matches) {
			$number = $matches[1] ? $matches[1] : null;
			$entity = $matches[2] ? str_replace('days', 'day', $matches[2]) : null;
			$priority = self::$estimated_date_entity_priorities[$entity];
			return compact('number', 'entity', 'priority');
		}
	}

	protected static function workupFormattedCurrencyValue(&$value)
	{
		$html_tag_pos = mb_strpos($value, '<');
		$starts_with_tag = $html_tag_pos === 0;

		if(!$starts_with_tag) {
			$untagged_value = mb_substr($value, 0, $html_tag_pos);
			$tagged_value = mb_substr($value, $html_tag_pos);
		} else {
			$html_tag_end_pos = mb_strrpos($value, '>') + 1;
			$untagged_value = mb_substr($value, $html_tag_end_pos);
			$tagged_value = mb_substr($value, 0, $html_tag_end_pos);
		}

		$untagged_value = trim($untagged_value);
		$untagged_value = str_replace(' ', '&nbsp;', $untagged_value);

		if(!$starts_with_tag) {
			$value = $untagged_value
				? "{$untagged_value}&nbsp;{$tagged_value}"
				: "{$tagged_value}";
		} else {
			$value = $tagged_value
				? "{$tagged_value}&nbsp;{$untagged_value}"
				: "{$untagged_value}";
		}
	}

	protected static function workupFormattedCurrencyValues(array &$values)
	{
		foreach($values as &$value) {
			self::workupFormattedCurrencyValue($value);
		}
	}

	public static function htmlCost($cost)
	{
		$frontend_currency = self::getCurrency();

		$output = null;
		switch($cost['type']) {
			case 'value':
				if($cost['value'] === 0.0) {
					$output = '<span class="dp-cost__value dp-cost__value_free">Бесплатно</span>';
				} else {
					$output = shop_currency_html($cost['value'], $frontend_currency);
					self::workupFormattedCurrencyValue($output);
				}
				break;
			case 'free-or-value':
				$value = shop_currency_html($cost['value'], $frontend_currency);
				self::workupFormattedCurrencyValue($value);

				$output = '<div class="dp-cost__caption dp-cost__caption_free">Бесплатно</div> ';
				$output .= '<div class="dp-cost__else">';
				$output .= '<span class="dp-cost__caption dp-cost__caption_or">или</span>';
				$output .= " <span class=\"dp-cost__value\">{$value}</span>";
				$output .= '</div>';
				break;
			case 'range-value':
				if($cost['min'] !== null && $cost['max'] !== null) {
					$is_to = true;

					$_min = $cost['min'];
					if($cost['min'] == -1) {
						$_min = $cost['max'];
						$is_to = false;
					} elseif($cost['max'] == -1) {
						$_min = $cost['min'];
						$is_to = false;
					}

					$min = shop_currency_html($_min, $frontend_currency);
					$max = shop_currency_html($cost['max'], $frontend_currency);

					self::workupFormattedCurrencyValues(ref(array(&$min, &$max)));

					$output = '<span class="dp-cost__caption dp-cost__caption_from">От</span> ';
					$output .= " <span class=\"dp-cost__value\">{$min}</span>";

					if($is_to) {
						$output .= ' <span class="dp-cost__caption dp-cost__caption_to">до</span>';
						$output .= " <span class=\"dp-cost__value\">{$max}</span>";
					}
				} elseif($cost['min'] !== null) {
					$min = shop_currency_html($cost['min'], $frontend_currency);
					self::workupFormattedCurrencyValue($min);

					$output = '<span class="dp-cost__caption dp-cost__caption_from">От</span> ';
					$output .= " <span class=\"dp-cost__value\">{$min}</span>";
				} elseif($cost['max'] !== null) {
					$max = shop_currency_html($cost['max'], $frontend_currency);
					self::workupFormattedCurrencyValue($max);

					$output = '<span class="dp-cost__caption dp-cost__caption_to">До</span>';
					$output .= " <span class=\"dp-cost__value\">{$max}</span>";
				}
				break;
			case 'free-or-range-value':
				if($cost['min'] !== null) {
					$min = shop_currency_html($cost['min'], $frontend_currency);
					self::workupFormattedCurrencyValue($min);

					$output = '<div class="dp-cost__caption dp-cost__caption_free">Бесплатно</div>';
					$output .= '<div class="dp-cost__else">';
					$output .= ' <span class="dp-cost__caption dp-cost__caption_or">или</span>';
					$output .= ' <span class="dp-cost__caption dp-cost__caption_from">от</span> ';
					$output .= " <span class=\"dp-cost__value\">{$min}</span>";
					$output .= '</div>';
				}
				break;
			case 'variable-value':
			case 'variable-percents-value':
				$output = '<div class="dp-cost__values">';

				if(!is_array($cost['values'])) {
					return '';
				}

				foreach($cost['values'] as $value) {
					$output .= '<div class="dp-cost__values-item">';
					if($value['limit'] !== 0.0) {
						switch($cost['variable_by']) {
							case 'price':
								$value_limit = shop_currency_html($value['limit'], $frontend_currency);
								break;
							case 'weight':
								$value_limit = "{$value['limit']} {$cost['weight_dimension']}";
								break;
						}

						if(isset($value_limit)) {
							self::workupFormattedCurrencyValue($value_limit);

							$output .= ' <span class="dp-cost__caption dp-cost__caption_from">От</span> ';
							$output .= "<span class=\"dp-cost__value dp-cost__value_limit\">{$value_limit}</span>";
							$output .= ' <span class="dp-cost__caption dp-cost__caption_dash">&ndash;</span> ';
						}
					}

					if(array_key_exists('value', $value)) {
						$value_value = shop_currency_html($value['value'], $frontend_currency);
					} elseif(array_key_exists('min', $value) && array_key_exists('max', $value)) {
						$value_min = shop_currency_html($value['min'], $frontend_currency);
						$value_max = shop_currency_html($value['max'], $frontend_currency);

						self::workupFormattedCurrencyValues(ref(array($value_min, $value_max)));
					}

					$is_percents = $cost['type'] === 'variable-percents-value' && !empty($value['percents']);
					$is_only_percents = $is_percents && empty($value['value']);

					if(isset($value_value) && $value['value'] === 0.0 && !$is_only_percents) {
						$output .= '<span class="dp-cost__value dp-cost__value_free">Бесплатно</span>';
					} else {
						if(isset($value_value) && !$is_only_percents) {
							$output .= "<span class=\"dp-cost__value\">{$value_value}</span>";
						} elseif(isset($value_min) && isset($value_max)) {
							$output .= '<span class="dp-cost__caption dp-cost__caption_from">От</span> ';
							$output .= " <span class=\"dp-cost__value\">{$value_min}</span>";
							$output .= ' <span class="dp-cost__caption dp-cost__caption_to">до</span>';
							$output .= " <span class=\"dp-cost__value\">{$value_max}</span>";
						}

						if($is_percents) {
							if(!$is_only_percents) {
								$output .= ' <span class="dp-cost__caption dp-cost__caption-plus">+</span> ';
							}

							$output .= "<span class=\"dp-cost__value dp-cost__value_percents\">{$value['percents']}%</span>";
							$output .= ' <span class="dp-cost__caption dp-cost__caption-percents">от заказа</span>';
						}
					}

					$output .= '</div> ';
				}
				$output .= '</div>';
				break;
			case 'percents':
				$output = "<span class=\"dp-cost__value dp-cost__value_percents\">{$cost['percents']}%</span>";
				$output .= ' <span class="dp-cost__caption dp-cost__caption-percents">от заказа</span>';
				break;
		}

		if($output) {
			return "<div class=\"dp-cost dp-cost_{$cost['type']}\">{$output}</div>";
		} else {
			return '';
		}
	}

	protected static function parseFloat($value)
	{
		$value = str_replace(',', '.', $value);

		return (float) $value;
	}

	/**
	 * Обрабатывает стоимость доставки и приводит ее к единому формату
	 *
	 * @param $cost
	 * @param $options
	 *
	 * @return array[string] $params
	 *
	 * @return boolean $params.is_estimated
	 * Является ли результат расчета приблизительным
	 *
	 * @return string $params.type
	 * Формат отдаваемого значения
	 *
	 ** 'value'
	 ** Одно единственное значение
	 ** @return float $params.value
	 *
	 ** 'free-or-value'
	 ** Бесплатно или значение
	 ** @return float $params.value
	 **
	 ** 'range-value'
	 ** Диапазон значений
	 ** @return float $params.min
	 ** @return float $params.max
	 **
	 ** 'free-or-range-value'
	 ** Бесплатно или диапазон значений
	 ** @return float $params.min
	 ** @return float $params.max
	 **
	 ** 'variable-value'
	 ** Вариативное значение, зависит от суммы или веса заказа
	 ** @return 'price'|'weight' $params.variable_by
	 ** @return string $params.weight_dimension
	 ** @return array[int]array $params.values
	 ** @return float $params.values[int].limit
	 ** @return float|null $params.values[int].value
	 ** @return float|null $params.values[int].min
	 ** @return float|null $params.values[int].max
	 **
	 ** 'percents'
	 ** Значение "процент" от суммы заказа
	 ** @return float $params.values
	 **
	 ** 'variable-percents-value'
	 ** Вариативное значение, зависит от суммы или веса заказа, а так же может быть "процентом" от суммы заказа
	 ** @return 'price'|'weight' $params.variable_by
	 ** @return string $params.weight_dimension
	 ** @return array[int]array $params.values
	 ** @return float $params.values[int].limit
	 ** @return float|null $params.values[int].percents
	 ** @return float|null $params.values[int].value
	 */
	public static function parseCost($cost, $options = array())
	{
		$params = array(
			'type' => 'undefined',
			'is_estimated' => false
		);

		if($cost === null || (is_array($cost) && empty($cost))) {
			return $params;
		}

		$default_options = array(
			'mode' => array(
				'cost' => 'inline'
			),
			'params' => array()
		);

		$options = array_merge($default_options, $options);

		$mode = ifset($options, 'mode', 'cost', 'inline');
		$_mode = ifset($options, 'mode', 'mode', 'no');

		if($_mode === 'no') {
			return $params;
		}

		$shop_currency = self::getCurrency('shop');
		$frontend_currency = self::getCurrency();

		if(is_array($cost) && count($cost) == 1) {
			$cost = ifset($cost[0]);
		}

		if(is_array($cost) && !empty($cost['calculated'])) {
			// Определенно высчитываемое значение, только число

			if(count($cost) == 2) {
				/**
				 * @param shopDpPluginHelper::parseCost['type'] => 'value'
				 */
				$params['type'] = 'value';
				$params['value'] = self::parseFloat($cost[0]);
				$params['is_estimated'] = true;
			} else {
				unset($cost['calculated']);
				$min = self::parseFloat(min($cost));
				$filtered_cost = array_filter($cost);
				if(empty($filtered_cost)) {
					$filtered_min = $min;
				} else {
					$filtered_min = self::parseFloat(min($filtered_cost));
				}

				$max = self::parseFloat(max($cost));

				if($min === 0.0 && $filtered_min !== $min) {
					if($filtered_min === $max) {
						/**
						 * @param shopDpPluginHelper::parseCost['type'] => 'free-or-value'
						 */
						$params['type'] = 'free-or-value';
						$params['value'] = $filtered_min;
					} else {
						/**
						 * @param shopDpPluginHelper::parseCost['type'] => 'free-or-range-value'
						 */
						$params['type'] = 'free-or-range-value';
						$params['min'] = $filtered_min;
						$params['max'] = $max;
					}
				} else {
					if($min === $max) {
						/**
						 * @param shopDpPluginHelper::parseCost['type'] => 'value'
						 */
						$params['type'] = 'value';
						$params['value'] = $min;
					} else {
						/**
						 * @param shopDpPluginHelper::parseCost['type'] => 'range-value'
						 */
						$params['type'] = 'range-value';
						$params['min'] = $min;
						$params['max'] = $max;
					}
				}

				$params['is_estimated'] = true;
			}

			// Проверка на бесплатность доставки
			if(!empty($options['free']['total']) && !($params['type'] === 'value' && $params['value'] === 0.0)) {
				$compare_items = null;

				switch($mode) {
					case 'inline':
						if(!in_array($params['type'], array('free-or-value', 'free-or-range-value'))) {
							$default_value = array_key_exists('value', $params) ? array(
								'limit' => 0.0,
								'value' => $params['value'],
								'is_estimated' => true
							) : array(
								'limit' => 0.0,
								'min' => $params['min'],
								'max' => $params['max']
							);

							/**
							 * @param shopDpPluginHelper::parseCost['type'] => 'variable-value'
							 */
							$params = array(
								'type' => 'variable-value',
								'variable_by' => 'price',
								'values' => array(
									$default_value,
									array(
										'limit' => (float) shop_currency($options['free']['total'], $shop_currency, $frontend_currency, null),
										'value' => 0.0
									)
								)
							);
						}
						break;
					case 'cart':
						if(isset($options['params']['cart_items'])) {
							$compare_items = $options['params']['cart_items'];
						}
						break;
					case 'product':
						if(isset($options['params']['items'])) {
							$compare_items = $options['params']['items'];
						}
						break;
					case 'cart+product':
						if(isset($options['params']['items']) || isset($options['params']['cart_items'])) {
							$compare_items = array_merge(ifset($options, 'params', 'items', array()), ifset($options, 'params', 'cart_items', array()));
						}
						break;
				}

				if($compare_items && in_array($options['mode']['cost'], array('cart', 'product', 'cart+product'))) {
					$compare_value = self::parseCostCompareValue($compare_items);

					if($compare_value['price'] >= $options['free']['total']) {
						/**
						 * @param shopDpPluginHelper::parseCost['type'] => 'value'
						 */
						$params = array(
							'type' => 'value',
							'value' => 0.0
						);
					}
				}
			}
		} elseif(is_array($cost)) {
			if(array_key_exists('settings', $cost)) {
				$rate_settings = $cost['settings'];

				if(count($cost) === 2) {
					// Вариативное значение, но указан только один вариант

					$params = self::parseCostValue(shop_currency($cost[0]['cost'], $shop_currency, $frontend_currency, null), $options, true);
				} else {
					/**
					 * @param shopDpPluginHelper::parseCost['type'] => 'variable-value'
					 */
					$params['type'] = 'variable-value';

					if($rate_settings['rate_by'] == 'price' && !empty($options['free']['total'])) {
						array_push($cost, array(
							'limit' => (float) $options['free']['total'],
							'cost' => 0.0
						));
					}

					// Удаляем из массива одинаковые варианты
					$existing_costs = array();
					foreach($cost as $key => $rate) {
						if($key !== 'settings') {
							// Заодно проверяем, а нет ли у нас процентов в стоимости доставки
							if($fixed_plus_percents_matches = self::matchCostValuePercents($rate['cost'])) {
								/**
								 * @param shopDpPluginHelper::parseCost['type'] => 'variable-percents-value'
								 */
								$params['type'] = 'variable-percents-value';
								$compare_value = $rate['cost'];
							} else {
								$compare_value = (float) $rate['cost'];
							}

							if(!in_array($compare_value, $existing_costs)) {
								$existing_costs[$key] = $compare_value;
							} else {
								$existing_cost_key = array_search($compare_value, $existing_costs);

								if($cost[$existing_cost_key]['limit'] > floatval($rate['limit'])) {
									unset($cost[$existing_cost_key]);
									unset($existing_costs[$existing_cost_key]);
									$existing_costs[$key] = $compare_value;
								} else {
									unset($cost[$key]);
								}
							}
						}
					}

					// Сортируем массив по возрастанию условий (лимитов)
					uasort($cost, wa_lambda('$a, $b', "if(isset(\$a['limit']) && isset(\$b['limit'])) return (\$a['limit'] < \$b['limit']) ? -1 : 1; else return 0;"));

					$compare_items = null;
					switch($mode) {
						case 'inline':
							$params['variable_by'] = $rate_settings['rate_by'];
							if($params['variable_by'] === 'weight')
								$params['weight_dimension'] = $rate_settings['weight_dimension'];
							$params['values'] = array();

							foreach($cost as $key => $rate) {
								if($key !== 'settings') {
									$value = array(
										'limit' => (float)shop_currency($rate['limit'], $shop_currency, $frontend_currency, null)
									);

									if($params['type'] === 'variable-percents-value') {
										if($fixed_plus_percents_matches = self::matchCostValuePercents($rate['cost'])) {
											$value['percents'] = $fixed_plus_percents_matches['percents'];
											$value['value'] = $fixed_plus_percents_matches['fixed'];
										} else {
											$value['value'] = (float) shop_currency($rate['cost'], $shop_currency, $frontend_currency, null);
										}
									} else {
										$value['value'] = (float) shop_currency($rate['cost'], $shop_currency, $frontend_currency, null);
									}

									array_push($params['values'], $value);
								}
							}
							break;
						case 'cart':
							if(isset($options['params']['cart_items'])) {
								$compare_items = $options['params']['cart_items'];
							}
							break;
						case 'product':
							if(isset($options['params']['items'])) {
								$compare_items = $options['params']['items'];
							}
							break;
						case 'cart+product':
							if(isset($options['params']['items']) || isset($options['params']['cart_items'])) {
								$compare_items = array_merge(ifset($options, 'params', 'items', array()), ifset($options, 'params', 'cart_items', array()));
							}
							break;
					}

					if(in_array($mode, array('cart', 'product', 'cart+product'))) {
						if(isset($compare_items)) {
							$compare_value = self::parseCostCompareValue($compare_items);
							arsort($cost);

							foreach($cost as $key => $rate) {
								if($key !== 'settings') {
									if($compare_value[$rate_settings['rate_by']] >= $rate['limit']) {
										/**
										 * @param shopDpPluginHelper::parseCost['type'] => 'value'
										 */
										$params = array(
											'type' => 'value',
											'value' => self::parseFloat($rate['cost'])
										);
										break;
									}
								}
							}
						} else {
							/**
							 * @param shopDpPluginHelper::parseCost['type'] => 'undefined'
							 */
							$params = array(
								'type' => 'undefined',
								'is_estimated' => false
							);
						}
					}
				}
			} else { // Настроек нет, простой разброс ВСЕГДА из двух значений
				$free = false;
				$min = null;
				$max = null;

				if(!empty($options['free'])) {
					$free = true;
				}

				$compare_items = array();
				switch($options['mode']['cost']) {
					case 'cart':
						if(isset($options['params']['cart_items'])) {
							$compare_items = $options['params']['cart_items'];
						}
						break;
					case 'product':
						if(isset($options['params']['items'])) {
							$compare_items = $options['params']['items'];
						}
						break;
					case 'cart+product':
						if(isset($options['params']['items']) || isset($options['params']['cart_items'])) {
							$compare_items = array_merge(ifset($options, 'params', 'items', array()), ifset($options, 'params', 'cart_items', array()));
						}
						break;
				}

				$compare_value = null;
				if(isset($compare_items) && in_array($options['mode']['cost'], array('cart', 'product', 'cart+product'))) {
					$compare_value = self::parseCostCompareValue($compare_items);
				}

				foreach($cost as $rate) {
					if(is_array($rate) && !empty($rate['settings'])) { // Пока что такое может присутствовать только при стоимостях, зависящих от зоны доставки
						foreach($rate as $next_rate) {
							if($compare_value && isset($next_rate['limit'])) {
								if($compare_value[$rate['settings']['rate_by']] < $next_rate['limit']) {
									continue;
								}
							}

							if(isset($next_rate['cost'])) {
								$next_rate_cost = (float) $next_rate['cost'];

								if($next_rate_cost !== 0.0) {
									if($min === null || $next_rate_cost < $min)
										$min = $next_rate_cost;

									if($max === null || $next_rate_cost > $max)
										$max = $next_rate_cost;
								} else
									$free = true;
							}
						}
					} elseif(is_array($rate)) {
						$_min = (float) min($rate);

						if($_min !== 0.0) {
							if($min === null || $_min < $min)
								$min = $_min;
						} else
							$free = true;

						$_max = (float) max($rate);
						if($max === null || $_max > $max)
							$max = $_max;
					} else {
						$rate = (float) $rate;

						if($rate !== 0.0) {
							if($min === null || $rate < $min)
								$min = $rate;

							if($max === null || $rate > $max)
								$max = $rate;
						} else
							$free = true;
					}

					if(!$free) {
						if ($min === $max && $min !== null) {
							/**
							 * @param shopDpPluginHelper::parseCost['type'] => 'value'
							 */
							$params['type'] = 'value';
							$params['value'] = (float)shop_currency($min, $shop_currency, $frontend_currency, null);
						} elseif ($min !== $max && ($min !== null || $max !== null)) {
							/**
							 * @param shopDpPluginHelper::parseCost['type'] => 'range-value'
							 */
							$params['type'] = 'range-value';
							$params['min'] = (float)shop_currency($min, $shop_currency, $frontend_currency, null);
							$params['max'] = (float)shop_currency($max, $shop_currency, $frontend_currency, null);

							if ($min === null)
							{
								$params['min'] = $params['max'];
							}
							elseif ($max === null)
							{
								$params['max'] = $params['min'];
							}
						}
					} else {
						if ($min === $max && $min !== null) {
							if ($min) {
								/**
								 * @param shopDpPluginHelper::parseCost['type'] => 'free-or-value'
								 */
								$params['type'] = 'free-or-value';
								$params['value'] = (float)shop_currency($max, $shop_currency, $frontend_currency, null);
							} else {
								/**
								 * @param shopDpPluginHelper::parseCost['type'] => 'value'
								 */
								$params['type'] = 'value';
								$params['value'] = (float) shop_currency(0, $shop_currency, $frontend_currency, null);
							}
						} elseif ($min !== $max && ($min !== null || $max !== null)) {
							/**
							 * @param shopDpPluginHelper::parseCost['type'] => 'free-or-range-value'
							 */
							$params['type'] = 'free-or-range-value';
							$params['min'] = (float) shop_currency($min, $shop_currency, $frontend_currency, null);
							$params['max'] = (float) shop_currency($max, $shop_currency, $frontend_currency, null);

							if ($min === null)
							{
								$params['min'] = $params['max'];
							}
							elseif ($max === null)
							{
								$params['max'] = $params['min'];
							}
						}
					}
				}
			}
		} else { // Не массив
			if(!empty($options['free'])) {
				$params = self::parseCost(array(
					'settings' => array(
						'rate_by' => 'price'
					),
					0 => array(
						'limit' => 0.0,
						'cost' => $cost
					),
				), $options);
			} else {
				/**
				 * @param shopDpPluginHelper::parseCost['type'] => 'value'
				 */
				$params['type'] = 'value';
				$params['value'] = (float) shop_currency($cost, $shop_currency, $frontend_currency, null);
			}
		}

		return $params;
	}

	protected static function matchCostValuePercents($value)
	{
		if(strpos($value, '%') !== false || strpos($value, '+') !== false) {
			if(preg_match('/^(([0-9\.]+)\+)?([0-9\.]+)%$/', $value, $fixed_plus_percents_matches)) {
				return array(
					'fixed' => floatval($fixed_plus_percents_matches[2]),
					'percents' => floatval($fixed_plus_percents_matches[3])
				);
			}
		}

		return null;
	}

	protected static function parseCostValue($value, $options = array(), $is_check_for_free = false)
	{
		$params = array(
			'type' => 'value',
			'value' => (float) $value
		);

		if($fixed_plus_percents_matches = self::matchCostValuePercents($value)) {
			$fixed = $fixed_plus_percents_matches['fixed'];
			$percents = $fixed_plus_percents_matches['percents'];

			if($options && isset($options['params']['items'][0]['price'])) {
				$price = $options['params']['items'][0]['price'];

				$params['value'] = $fixed + ($price * $percents / 100);
			} else {
				if($fixed) {
					/**
					 * @param shopDpPluginHelper::parseCost['type'] => 'range-value'
					 */
					$params['type'] = 'range-value';
					$params['min'] = (float)$fixed;
					$params['max'] = null;
				} else {
					/**
					 * @param shopDpPluginHelper::parseCost['type'] => 'percents'
					 */
					$params['type'] = 'percents';
					$params['value'] = null;
					$params['percents'] = (float)$percents;
				}
			}
		}

		if($is_check_for_free) {
			$shop_currency = self::getCurrency('shop');
			$frontend_currency = self::getCurrency();

			if(!empty($options['free']['total'])) {
				$compare_items = null;

				switch($options['mode']['cost']) {
					case 'inline':
						/**
						 * @param shopDpPluginHelper::parseCost['type'] => 'variable-value'
						 */
						$new_params_type = 'variable-value';

						switch($params['type']) {
							case 'value':
							case 'range-value':
								$default_value = array(
									'limit' => 0.0,
									'value' => (float) ($params['type'] === 'value' ? $params['value'] : $params['min'])
								);
								break;
							case 'percents':
								/**
								 * @param shopDpPluginHelper::parseCost['type'] => 'variable-percents-value'
								 */
								$new_params_type = 'variable-percents-value';
								$default_value = array(
									'limit' => 0.0,
									'value' => null,
									'percents' => $params['percents']
								);
								break;
							default:
								$default_value = array();
								break;
						}

						$params = array(
							'type' => $new_params_type,
							'variable_by' => 'price',
							'values' => array(
								$default_value,
								array(
									'limit' => (float) shop_currency($options['free']['total'], $shop_currency, $frontend_currency, null),
									'value' => 0.0,
									'percents' => null
								)
							)
						);
						break;
					case 'cart':
						if(isset($options['params']['cart_items'])) {
							$compare_items = $options['params']['cart_items'];
						}
						break;
					case 'product':
						if(isset($options['params']['items'])) {
							$compare_items = $options['params']['items'];
						}
						break;
					case 'cart+product':
						if(isset($options['params']['items']) || isset($options['params']['cart_items'])) {
							$compare_items = array_merge(ifset($options, 'params', 'items', array()), ifset($options, 'params', 'cart_items', array()));
						}
						break;
				}

				if(isset($compare_items) && in_array($options['mode']['cost'], array('cart', 'product', 'cart+product'))) {
					$compare_value = self::parseCostCompareValue($compare_items);

					if($compare_value['price'] >= $options['free']['total']) {
						$params = array(
							'type' => 'value',
							'value' => 0.0
						);
					}
				}
			}
		}

		return $params;
	}

	protected static function parseCostCompareValue($items)
	{
		$shop_currency = self::getCurrency('shop');

		$compare_value = array(
			'price' => 0,
			'currency' => $shop_currency,
			'weight' => 0
		);

		foreach($items as $item) {
			if($item['currency'] != $shop_currency) {
				$item['price'] = shop_currency($item['price'], $item['currency'], $shop_currency, null);
			}

			$compare_value['price'] += intval($item['price']) * $item['quantity'];
			$compare_value['weight'] += intval($item['weight']) * $item['quantity'];
		}

		return $compare_value;
	}

	public static function getMapAdapter()
	{
		$settings_storage = new shopDpSettingsStorage();

		$map_service = $settings_storage->getBasicSettings('map_service');
		$map_params = $settings_storage->getBasicSettings('map_params');

		if(!($map_service === 'google' && !empty($map_params['google_key']))) {
			$map_service = 'yandex';
		}

		if(!$map_params) {
			$map_params = array();
		}

		$class = 'shopDpMap' . ucfirst($map_service);
		if(!class_exists($class)) {
			return null;
		}

		return new $class($map_params);
	}

	private static function buildShopSchedule()
	{
		/** @var shopDpPlugin $plugin */
		$plugin = shopDpPlugin::getInstance();
		if (!$plugin->getEnv()->isAvailableShopSchedule())
		{
			return [];
		}

		/** @var shopConfig $config */
		$config = $plugin->getEnv()->getConfig();
		$shop_schedule = $config->getStorefrontSchedule();

		$new_schedule = [];

		foreach ($shop_schedule['week'] as $day_of_week => $day_schedule)
		{
			$is_work_day = $day_schedule['work'];
			$start_work = $day_schedule['start_work'];
			$end_work = $day_schedule['end_work'];
			$day_schedule['end_processing'];

			if (!$is_work_day)
			{
				continue;
			}

			$new_schedule[$day_of_week] = "{$start_work}/{$end_work}";
		}

		return $new_schedule;
	}
}
