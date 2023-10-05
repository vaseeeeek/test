<?php

interface shopProductgroupGroupStorage
{
	/**
	 * @return shopProductgroupGroup[]
	 */
	public function getAll();
	
	/**
	 * @param $id
	 * @return shopProductgroupGroup
	 */
	public function getById($id);

	/**
	 * @param $group_id
	 * @param shopProductgroupGroup $group
	 * @return bool
	 */
	public function updateGroup($group_id, shopProductgroupGroup $group);

	/**
	 * @param shopProductgroupGroup $group
	 * @return shopProductgroupGroup
	 */
	public function addGroup(shopProductgroupGroup $group);

	/**
	 * @param int $group_id
	 * @return boolean
	 */
	public function deleteById($group_id);
}