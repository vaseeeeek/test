<?php

class shopBundlingPluginBackendSaveBundlesSortController extends waJsonController
{
	public function execute()
	{
		$sort = waRequest::post('sort', array(), waRequest::TYPE_ARRAY_INT);

		$model = new shopBundlingModel();
		foreach ($sort as $bundle_id => $sort_pos)
		{
			$model->updateById($bundle_id, array(
				'sort' => $sort_pos
			));
		}
	}
}
