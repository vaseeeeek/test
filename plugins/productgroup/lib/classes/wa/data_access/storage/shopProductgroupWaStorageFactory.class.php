<?php

class shopProductgroupWaStorageFactory implements shopProductgroupStorageFactory
{
	/**
	 * @return shopProductgroupGroupStorage
	 */
	public function createGroupStorage()
	{
		return new shopProductgroupWaGroupStorage();
	}

	/**
	 * @return shopProductgroupGroupSettingsStorage
	 */
	public function createGroupSettingsStorage()
	{
		return new shopProductgroupWaGroupSettingsStorage();
	}

	/**
	 * @return shopProductgroupProductGroupStorage
	 */
	public function createProductGroupStorage()
	{
		return new shopProductgroupWaProductGroupStorage(
			$this->createGroupStorage()
		);
	}

	public function createSettingsStorage()
	{
		return new shopProductgroupWaSettingsStorage();
	}
}