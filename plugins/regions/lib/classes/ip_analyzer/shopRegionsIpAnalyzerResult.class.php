<?php


class shopRegionsIpAnalyzerResult
{
	private $data;
	private $city;

	public function __construct($data)
	{
		$this->data = $data;

        if (isset($this->data['error']) && $this->data['error'])
		{
			return;
		}

		$this->tryFindCity();
	}

	public function getCity()
	{
		return $this->city;
	}

	public function getCityData()
	{
		return $this->data;
	}

	private function tryFindCity()
	{
		if (isset($this->data['city']['name_ru']))
		{
			$city_model = new shopRegionsCityModel();

			$this->city = $city_model->getByField(
				array(
					'name' => $this->data['city']['name_ru'],
					'is_enable' => '1',
				)
			);
		}
	}
}