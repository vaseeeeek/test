<?php

class shopProductgroupWaGroupStorage implements shopProductgroupGroupStorage
{
	private $group_model;

	public function __construct()
	{
		$this->group_model = new shopProductgroupGroupModel();
	}

	/**
	 * @return shopProductgroupGroup[]
	 */
	public function getAll()
	{
		$query = $this->group_model
			->select('*')
			->order('sort')
			->query();

		$result = [];
		foreach ($query as $group_raw)
		{
			$result[] = $this->buildGroupByRaw($group_raw);
		}

		return $result;
	}

	/**
	 * @param $id
	 * @return shopProductgroupGroup
	 */
	public function getById($id)
	{
		$group_raw = $this->group_model->getById($id);

		if (!isset($group_raw))
		{
			return null;
		}

		return $this->buildGroupByRaw($group_raw);
	}

	public function updateGroup($group_id, shopProductgroupGroup $group)
	{
		if (!$group_id)
		{
			return false;
		}

		$group_raw = $this->groupToRaw($group);
		unset($group_raw['id']);

		return $this->group_model->updateById($group->id, $group_raw);
	}

	public function addGroup(shopProductgroupGroup $group)
	{
		$group_raw = $this->groupToRaw($group);
		unset($group_raw['id']);

		$new_id = $this->group_model->insert($group_raw);

		return new shopProductgroupGroup(
			intval($new_id),
			$group->name,
			$group->markup_template_id,
			$group->is_shown,
			intval($group->related_feature_id),
			intval($group->sort)
		);
	}

	public function deleteById($group_id)
	{
		return $this->group_model->deleteById($group_id);
	}

	private function buildGroupByRaw($group_raw)
	{
		return new shopProductgroupGroup(
			intval($group_raw['id']),
			$group_raw['name'],
			$group_raw['markup_template_id'],
			$group_raw['is_shown'] === '1',
			intval($group_raw['related_feature_id']),
			intval($group_raw['sort'])
		);
	}

	private function groupToRaw(shopProductgroupGroup $group)
	{
		return $group_raw = [
			'id' => $group->id,
			'name' => $group->name,
			'markup_template_id' => $group->markup_template_id,
			'is_shown' => $group->is_shown ? '1' : '0',
			'related_feature_id' => $group->related_feature_id,
			'sort' => $group->sort,
		];
	}
}