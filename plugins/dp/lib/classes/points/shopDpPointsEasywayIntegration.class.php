<?php


class shopDpPointsEasywayIntegration extends shopDpPointsIntegration implements shopDpPointsIntegrationInterface
{
	protected $params = array(
		'style' => 0,
		'actuality' => 604800
	);

	public $url = 'https://lk.easyway.ru/EasyWay/hs/EWA_API/v2/getPickupPoints';
	private $login;
	private $password;

	protected $classificator;

	protected function getClassificator()
	{
		if(!isset($this->classificator)) {
			$this->classificator = new shopDpClassificatorEasyway();
		}

		return $this->classificator;
	}

	private function getLogin()
	{
		return $this->login;
	}

	private function setLogin($login)
	{
		$this->login = $login;
	}

	private function getPassword()
	{
		return $this->password;
	}

	private function setPassword($password)
	{
		$this->password = $password;
	}

	protected function query()
	{
		$result = $this->getNet(array(
			'timeout' => 25,
			'format' => waNet::FORMAT_JSON,
			'request_format' => waNet::FORMAT_JSON,
			'authorization' => true,
			'login' => $this->getLogin(),
			'password' => $this->getPassword()
		), array(
			'Content-Type' => 'application/json;charset=utf-8'
		))->query($this->url);

		return $result;
	}

	public function takePoints($key = null)
	{
		$settings = $this->getPlugin()->getSettings('shipping_methods');

		$login = ifset($settings, $this->id, 'settings', 'easyway', 'login', null);
		$password = ifset($settings, $this->id, 'settings', 'easyway', 'password', null);

		if(!$login || !$password)
			return false;

		$this->setLogin($login);
		$this->setPassword($password);

		try {
			$result = $this->query();
		} catch(waException $e) {
			$this->log($e->getMessage());

			return false;
		}

		if(!empty($result)) {
			$points = array();

			$point = array(
				'country_code' => 'rus'
			);

			foreach($result as $element) {
				try {
					$point['code'] = $element['guid'];
					$point['region_code'] = $this->getClassificator()->getRegionCode($element);
					$point['address'] = $this->getClassificator()->getAddress($element);
					$point['city_name'] = $element['city'];
					$point['coord_y'] = $element['lat'];
					$point['coord_x'] = $element['lng'];

					$points[] = $point;
				} catch(waException $e) { }
			}

			return $points;
		}

		return false;
	}
}