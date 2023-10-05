<?php

interface shopProductgroupGroupSettingsStorage
{
	/**
	 * @param int $group_id
	 * @param string $scope
	 * @return shopProductgroupGroupSettings
	 */
	public function getGroupScopeSettings($group_id, $scope);

	/**
	 * @param int $group_id
	 * @param string $scope
	 * @param shopProductgroupGroupSettings $group_settings
	 * @return bool
	 */
	public function storeGroupScopeSettings($group_id, $scope, $group_settings);
}