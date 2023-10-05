<?php

class shopDpMapYandex extends shopDpMap
{
	public function getCoords($params)
	{
		$string = $this->getSearchCoordsString($params);

		$net = $this->getNet();
		$query_params = array(
			'geocode' => $string,
		);
		$key = $this->getParam('yandex_key');
		if ($key) {
			$query_params['apikey'] = $key;
		}
		$result = $net->query('https://geocode-maps.yandex.ru/1.x/', $query_params);

		$response_header = $net->getResponseHeader();

		if($response_header['http_code'] !== 200) {
			return null;
		}

		if($result instanceof SimpleXMLElement) {
			$result->registerXPathNamespace('ymaps', 'http://maps.yandex.ru/ymaps/1.x');
			$result->registerXPathNamespace('gml', 'http://www.opengis.net/gml');

			$member = $result->xpath('/ymaps:ymaps/ymaps:GeoObjectCollection/gml:featureMember/ymaps:GeoObject/gml:Point/gml:pos');

			if(isset($member[0]))
				return explode(' ', $member[0]);
		}
	}
}
