<?php

class shopBuy1clickWaShopConfirmationChannel implements shopBuy1clickConfirmationChannel
{
	/** @var shopConfirmationChannel|null */
	private $wa_confirmation_channel;

	public function __construct($wa_confirmation_channel)
	{
		$this->wa_confirmation_channel = $wa_confirmation_channel;
	}

	public function getCurrentUnconfirmedChannel()
	{
		return $this->wa_confirmation_channel
			? $this->wa_confirmation_channel->getConfirmChannel()
			: '';
	}

	public function hasUnconfirmedChannels()
	{
		return $this->wa_confirmation_channel
			? !!$this->wa_confirmation_channel->getConfirmChannel()
			: false;
	}

	public function finishContactConfirmation($saved_order_contact_id, $new_contact)
	{
		return $this->wa_confirmation_channel
			? $this->wa_confirmation_channel->postConfirm($saved_order_contact_id, $new_contact)
			: true;
	}

	public function getContact()
	{
		$null_contact = [
			'id'         => null,
			'is_user'    => null,
			'password'   => null,
			'is_company' => null
		];

		return $this->wa_confirmation_channel
			? $this->wa_confirmation_channel->getContact()
			: $null_contact;
	}

	public function clearConfirmedStorage()
	{
		if ($this->wa_confirmation_channel)
		{
			$this->wa_confirmation_channel->delStorage('confirmed');
		}
	}

	public function countUnconfirmedChannels()
	{
		if (!$this->wa_confirmation_channel)
		{
			return 0;
		}

		$confirmed = $this->wa_confirmation_channel->getStorage('confirmed');
		$unconfirmed = $this->wa_confirmation_channel->getStorage('unconfirmed');

		return count($unconfirmed) - count($confirmed);
	}
}
