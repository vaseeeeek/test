<?php

abstract class shopEditCategoryMoveMetaTagsAction extends shopEditLoggedAction
{
	protected function getAction()
	{
		return $this->action_options->CATEGORY_MOVE_META_TAGS;
	}
}