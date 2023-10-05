<?php

class shopDpShippingPickupCalculator extends shopDpShippingCalculator implements shopDpShippingCalculatorInterface
{
	public function calculateRates($options = array())
	{
		$source_rates = $this->getPlugin()->rate;

		$free_count = 0;
		$rates = array();
		$count_rates = array();
		foreach($source_rates as &$rate) {
			$cost = floatval($rate['cost']);
			if(abs($cost) < 1e-6) {
				$free_count++;
			} else {
				$this->correct($cost);
			}
			$rates[] = $cost;

			if(!isset($count_rates[$cost]))
				$count_rates[strval($cost)] = 1;
			else
				$count_rates[strval($cost)]++;
		}

		if($free_count == count($source_rates))
			$cost = 0;
		else {
			foreach($count_rates as $with => $count)
				if($count == count($source_rates)) {
					return array(
						'cost' => floatval($with),
						'est_delivery' => null
					);
					break;
				}

			sort($rates);
			$rates = array_unique($rates);

			$cost = $rates;
		}

		return array(
			'cost' => $cost,
			'est_delivery' => null
		);
	}
}