<?php

interface shopBuy1clickConfirmationChannel
{
	public function getCurrentUnconfirmedChannel();

	public function hasUnconfirmedChannels();

	/**
	 * @param int $saved_order_contact_id
	 * @param bool $new_contact todo понять за что этот параметр вообще отвечает
	 * @return mixed
	 */
	public function finishContactConfirmation($saved_order_contact_id, $new_contact);

	public function getContact();

	public function clearConfirmedStorage();

	public function countUnconfirmedChannels();
}
