<?php

class shopDpPointsSort
{
	public function __construct($points = array(), $type)
	{
		$this->points = $points;
		$this->type = $type;
	}

	public function correctAddress($address) {
		return preg_replace('/^((ул|пр|проспект|бульвар|пер|переулок|пр-т|б-р|ш)\.? )/u', '', trim($address));
	}

	public function execute()
	{
		$field = 'address';
		if($this->type == 2) {
			$field = 'correct_address';
			foreach($this->points as &$point) {
				$point['correct_address'] = $this->correctAddress($point['address']);
			}
		}

		usort($this->points, wa_lambda('$a, $b', "return strcmp(\$a['$field'], \$b['$field']);"));

		return $this->points;
	}
}