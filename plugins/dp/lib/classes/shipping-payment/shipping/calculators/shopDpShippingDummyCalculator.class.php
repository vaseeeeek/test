<?php

class shopDpShippingDummyCalculator extends shopDpShippingCalculator implements shopDpShippingCalculatorInterface
{
	public function calculateRates($options = array())
	{
		return array(
			'cost' => 0,
			'est_delivery' => null
		);
	}
}