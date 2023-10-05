<?php

class shopDpMapGoogle extends shopDpMap
{
	public function getCoords($params)
	{
		$string = $this->getSearchCoordsString($params);

		$net = $this->getNet(array(
			'format' => waNet::FORMAT_JSON
		));
		$result = $net->query('https://maps.googleapis.com/maps/api/geocode/json', array(
			'address' => $string,
			'sensor' => 'false',
			'key' => $this->getParam('google_key')
		));

		$response_header = $net->getResponseHeader();
		if($response_header['http_code'] !== 200) {
			return null;
		}

		$is_ok_response = !empty($result['results'][0]['geometry']);
		if(!$is_ok_response) {
			return null;
		}

		$location = $result['results'][0]['geometry']['location'];

		return array(
			$location['lat'],
			$location['lng']
		);
	}
}
