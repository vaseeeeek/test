<?php

class shopMassupdatingUpdateSubpages
{
	public function update($ids)
	{
		$model = new shopProductPagesModel();
		$model->deleteByField('id', $ids);
	}
}