<?php

class shopDpShippingCourierCalculator extends shopDpShippingCalculator implements shopDpShippingCalculatorInterface
{
	public function calculateRates($options = array())
	{
		$rate_by = $this->getPlugin()->rate_by;
		$weight_dimension = $this->getPlugin()->weight_dimension;
		switch($weight_dimension) {
			case 'kg':
				$weight_dimension = 'кг';
				break;
			case 'lbs':
				$weight_dimension = 'фунт';
				break;
		}

		$rates = $this->getPlugin()->rate;

		foreach($rates as &$rate) {
			if(strpos($rate['cost'], '%') === false && strpos($rate['cost'], '+') === false)
				$this->correct($rate['cost']);
			if($rate_by == 'price')
				$this->correct($rate['limit']);
		}

		usort($rates, wa_lambda('$a, $b', 'return $a["limit"] > $b["limit"] ? 1 : -1;'));
		$rates['settings'] = compact('rate_by', 'weight_dimension');

		$assembly_time = $this->getAssemblyTime();

		$delivery_time = $this->getPlugin()->delivery_time;
        $exact_delivery_time = $this->getPlugin()->exact_delivery_time;

        if ($delivery_time)
		{
            if($delivery_time === "exact_delivery_time")
                $delivery_time = "+" . $exact_delivery_time . " hour";

			$est_delivery = explode(',', $delivery_time, 2);

			foreach (array_keys($est_delivery) as $index)
			{
				if ($est_delivery[$index] === 'exact_delivery_time')
				{
					continue;
				}

				$delivery_val = $est_delivery[$index];
				if (
					$assembly_time > 0
					&& $delivery_val !== '' && substr($delivery_val, 0, 1) === '+'
				)
				{
					$delivery_val = "{$delivery_val} +{$assembly_time}hour";
				}

				if ($this->view)
				{
					$est_delivery[$index] = strtotime($delivery_val);
				}
				else
				{
					$est_delivery[$index] = trim($delivery_val);
				}
			}
		}
		elseif ($assembly_time > 0)
		{
			$delivery_val = "+{$assembly_time}hour";

			if ($this->view)
			{
				$est_delivery = strtotime($delivery_val);
			}
			else
			{
				$est_delivery = trim($delivery_val);
			}
		}
		else
		{
			$est_delivery = null;
		}

		return array(
			'cost' => $rates,
			'est_delivery' => $est_delivery
		);
	}
}