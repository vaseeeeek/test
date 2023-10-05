<?php

class shopMassupdatingUpdateTags
{
	public function update($id, $action, $_tags, $update_case = false)
	{
		$tags = explode(',', $_tags);

		if(!in_array($action, array('add', 'replace'))) {
			throw new Exception('Неверные параметры для тегов');
		} else {
			$product_tags_model = new shopProductTagsModel();

			if(count($tags) > 0 || (count($tags) == 0 && $this->update_empty)) {
				if($update_case) {
					$tag_model = new shopTagModel();
					foreach($tags as $tag) {
						$found_tag = $tag_model->query("SELECT * FROM {$tag_model->getTableName()} WHERE LOWER(name) = ?", mb_strtolower($tag))->fetchAssoc();
						if(!empty($found_tag['name']) && $found_tag['name'] != $tag) {
							$tag_model->updateById($found_tag['id'], array(
								'name' => $tag
							));
						}
					}
				}
				
				if($action == 'replace') {
					$product_tags_model->setData(new shopProduct($id), $tags);
				} else {
					$product_tags_model->addTags($id, $tags);
				}
			}
		}
	}
}