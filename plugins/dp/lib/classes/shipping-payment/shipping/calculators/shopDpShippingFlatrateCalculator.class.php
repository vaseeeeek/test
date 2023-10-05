<?php

class shopDpShippingFlatrateCalculator extends shopDpShippingCalculator implements shopDpShippingCalculatorInterface
{
	public function calculateRates($options = array())
	{
		$cost = $this->getPlugin()->cost;
		$this->correct($cost);

		if($this->getPlugin()->delivery === '') {
			$est_delivery = null;
		} else {
			$est_delivery = $this->getPlugin()->delivery;

			$assembly_time = $this->getAssemblyTime();
			if ($assembly_time > 0 && $est_delivery !== '')
			{
				$est_delivery = "{$est_delivery} +{$assembly_time}hour";
			}

			if ($this->view)
			{
				$est_delivery = strtotime($est_delivery);
			}
			else
			{
				$est_delivery = trim($est_delivery);
			}
		}

		return array(
			'cost' => floatval($cost),
			'est_delivery' => $est_delivery
		);
	}
}