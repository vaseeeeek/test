<?php


interface shopBuy1clickPaymentPluginStorage
{
	/**
	 * @param $payment_id
	 * @return shopBuy1clickPaymentPlugin
	 */
	public function getByPaymentId($payment_id);
}