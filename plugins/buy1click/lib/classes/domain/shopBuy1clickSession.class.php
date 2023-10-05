<?php


class shopBuy1clickSession
{
	private $code;
	private $shipping_params;
	private $selected_shipping_id;
	private $selected_shipping_rate_id;
	private $selected_payment_id;
	private $coupon;
	private $comment;
	private $confirmation_channel_type;
	private $confirmation_channel_address;
	private $confirmation_channel_is_last_channel;
	private $is_checked_policy;

	public function __construct($code)
	{
		$this->code = $code;
	}

	public function getCode()
	{
		return $this->code;
	}

	public function getShippingParams()
	{
		return $this->shipping_params;
	}

	public function setShippingParams($shipping_params)
	{
		$this->shipping_params = $shipping_params;
	}

	public function getSelectedShippingId()
	{
		return $this->selected_shipping_id;
	}

	public function setSelectedShippingId($selected_shipping_id)
	{
		$this->selected_shipping_id = $selected_shipping_id;
	}

	public function getSelectedShippingRateId()
	{
		return $this->selected_shipping_rate_id;
	}

	public function setSelectedShippingRateId($selected_shipping_rate_id)
	{
		$this->selected_shipping_rate_id = $selected_shipping_rate_id;
	}

	public function getSelectedPaymentId()
	{
		return $this->selected_payment_id;
	}

	public function setSelectedPaymentId($selected_payment_id)
	{
		$this->selected_payment_id = $selected_payment_id;
	}

	public function getCoupon()
	{
		return $this->coupon;
	}

	public function setCoupon($coupon)
	{
		$this->coupon = $coupon;
	}

	public function getComment()
	{
		return $this->comment;
	}

	public function setComment($comment)
	{
		$this->comment = $comment;
	}

	public function getConfirmationChannelType()
	{
		return $this->confirmation_channel_type;
	}

	public function setConfirmationChannelType($confirmation_channel_type)
	{
		$this->confirmation_channel_type = $confirmation_channel_type;
	}

	public function getConfirmationChannelAddress()
	{
		return $this->confirmation_channel_address;
	}

	public function setConfirmationChannelAddress($confirmation_channel_address)
	{
		$this->confirmation_channel_address = $confirmation_channel_address;
	}

	public function getConfirmationChannelIsLastChannel()
	{
		return $this->confirmation_channel_is_last_channel;
	}

	public function setConfirmationChannelIsLastChannel($confirmation_channel_is_last_channel)
	{
		$this->confirmation_channel_is_last_channel = $confirmation_channel_is_last_channel;
	}

	public function isCheckedPolicy()
	{
		return $this->is_checked_policy;
	}

	public function setIsCheckedPolicy($is_checked_policy)
	{
		$this->is_checked_policy = $is_checked_policy;
	}
	
	public function toArray()
	{
		return array(
			'code' => $this->getCode(),
			'is_checked_policy' => $this->isCheckedPolicy(),
			'shipping_params' => $this->getShippingParams(),
			'selected_shipping_id' => $this->getSelectedShippingId(),
			'selected_shipping_rate_id' => $this->getSelectedShippingRateId(),
			'selected_payment_id' => $this->getSelectedPaymentId(),
			'coupon' => $this->getCoupon(),
			'comment' => $this->getComment(),
		);
	}
}
