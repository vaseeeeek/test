<?php

interface shopProductgroupStorageFactory
{
	/**
	 * @return shopProductgroupGroupStorage
	 */
	public function createGroupStorage();

	/**
	 * @return shopProductgroupGroupSettingsStorage
	 */
	public function createGroupSettingsStorage();

	/**
	 * @return shopProductgroupProductGroupStorage
	 */
	public function createProductGroupStorage();

	/**
	 * @return shopProductgroupSettingsStorage
	 */
	public function createSettingsStorage();
}