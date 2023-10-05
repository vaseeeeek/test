<?php

class shopMassupdatingUpdatePhotos
{
	public function update($product_ids, $files, $action = 'addtoend')
	{
		$action = ifset($action, 'addtoend');

			if($files->count() > 10) {
				throw new Exception('Разрешается загружать не более 10 изображений за раз');
			}
			
			if(!in_array($action, array('addtostart', 'addtoend', 'replace', 'delete'))) {
				throw new Exception('Неверные параметры для редактирования изображений');
			} else {
				$images = new shopMassupdatingImages();
				if($action == 'delete')
					$images->deleteAll($product_ids);
				else {
					$save = $images->upload($product_ids, $files, $action, shopMassupdatingPlugin::getOne('generate_thumbs'));
					if($save['status'] == 'error') {
						throw new Exception(_wp($save['message']) . '. ' . _wp('Код ошибки #') . $save['code']);
					} else {
						// return $save;
					}
				}
			}
	}
}