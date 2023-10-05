<?php


class shopBuy1clickWaSessionStorage implements shopBuy1clickSessionStorage
{
	private $storage;
	
	public function __construct(waSessionStorage $storage)
	{
		$this->storage = $storage;
	}
	
	/**
	 * @param $code
	 * @return shopBuy1clickSession
	 */
	public function getByCode($code)
	{
		$data = $this->storage->get('shop/buy1click/form/' . $code);
		
		$session = new shopBuy1clickSession($code);
		$session->setIsCheckedPolicy(ifset($data['is_checked_policy'], true));
		$session->setShippingParams(ifset($data['shipping_params'], array()));
		$session->setSelectedShippingId(ifset($data['selected_shipping_id']));
		$session->setSelectedShippingRateId(ifset($data['selected_shipping_rate_id']));
		$session->setSelectedPaymentId(ifset($data['selected_payment_id']));
		$session->setCoupon(ifset($data['coupon'], ''));
		$session->setComment(ifset($data['comment'], ''));
		$session->setConfirmationChannelType(ifset($data['confirmation_step'], ''));
		$session->setConfirmationChannelAddress(ifset($data['confirmation_channel_address'], ''));
		$session->setConfirmationChannelIsLastChannel(ifset($data['confirmation_channel_code'], ''));

		return $session;
	}
	
	public function store(shopBuy1clickSession $session)
	{
		$this->storage->set('shop/buy1click/form/' . $session->getCode(), $session->toArray());
	}
}
