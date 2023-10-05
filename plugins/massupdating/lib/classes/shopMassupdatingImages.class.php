<?php

class shopMassupdatingImages extends shopProductImageUploadController
{
	public function deleteAll($product_ids)
	{
		if(empty($this->model)) {
			$this->model = new shopProductImagesModel();
		}
		
		$finded = $this->model->getByField('product_id', $product_ids, true);
		foreach($finded as $image)
			$this->model->delete($image['id']);
	}
	
	private function moveToStart($image)
	{
		if(!empty($image['product_id'])) {
			$first = $this->model->query("SELECT * FROM `shop_product_images` WHERE `product_id` = '{$image['product_id']}' ORDER BY `sort` ASC LIMIT 1")->fetchAssoc();
			if(!empty($first));
				$this->model->move($image['id'], $first['id']);
		}
	}
	
	protected $messages = array(
		2 => array(
			'status' => 'error',
			'message' => 'Неверные параметры для загрузки изображений',
			'code' => 2
		),
		3 => array(
			'status' => 'error',
			'message' => 'Некорректное изображение',
			'code' => 3
		),
		4 => array(
			'status' => 'error',
			'message' => 'Ошибка базы данных',
			'code' => 4
		),
		5 => array(
			'status' => 'error',
			'message' => 'Ошибка базы данных',
			'code' => 5
		),
		6 => array(
			'status' => 'error',
			'message' => 'Недостаточно прав доступа для записи файлов',
			'code' => 6
		)
	);
	
	public function upload($product_ids, $loaded_files, $action, $generate_thumbs = false)
	{
		if(empty($this->model)) {
			$this->model = new shopProductImagesModel();
		}
		
		if($action == 'replace')
			$this->deleteAll($product_ids);
		
		/*
		 * Загружаем все файлы в первый продукт
		 */
		
		$product_id = array_shift($product_ids);
		
		$file_ids = 0;
		$data = array();
		$config = $this->getConfig();
		
		$files = array();
		foreach($loaded_files as $file) {
			$files[$file->name] = $file;
		};
		
		if($action == 'addtostart')
			krsort($files);
		else
			sort($files);
		
		foreach($files as $file) {
			if($file instanceof waRequestFile) {
				if(!($image = $file->waImage())) {
					return $this->messages[3];
					break;
				} else {
					if($this->getConfig()->getOption('image_filename')) {
						$filename = basename($file->name, '.' . $file->extension);
						if(!preg_match('//u', $filename)) {
							$tmp_name = @iconv('windows-1251', 'utf-8//ignore', $filename);
							if ($tmp_name) {
								$filename = $tmp_name;
							}
						}
						$filename = preg_replace('/\s+/u', '_', $filename);
						if($filename) {
							foreach (waLocale::getAll() as $l) {
								$filename = waLocale::transliterate($filename, $l);
							}
						}
						$filename = preg_replace('/[^a-zA-Z0-9_\.-]+/', '', $filename);
						if(!strlen(str_replace('_', '', $filename))) {
							$filename = '';
						}
					} else {
						$filename = '';
					}
					
					$data[$file_ids] = array(
						'product_id' => $product_id,
						'upload_datetime' => date('Y-m-d H:i:s'),
						'width' => $image->width,
						'height' => $image->height,
						'size' => $file->size,
						'filename' => $filename,
						'original_filename' => basename($file->name),
						'ext' => $file->extension,
					);

					$image_id = $data[$file_ids]['id'] = $this->model->add($data[$file_ids]);
					if(!$image_id){
						return $this->messages[5];
					}
					if($action == 'addtostart')
						$this->moveToStart($data[$file_ids]);

					$image_path = shopImage::getPath($data[$file_ids]);
					if(!waFiles::create($image_path)) {
						return $this->messages[6];
						break;
					}

					$data[$file_ids]['file'] = $file;
					$file->copyTo($image_path);
					
					if($generate_thumbs)
						shopImage::generateThumbs($data[$file_ids], $config->getImageSizes());
					
					unset($data[$file_ids]['id']);
					$file_ids++;
				}
			} else
				return $this->messages[2];
		};
		 
		/*
		 * Копируем все загруженные файлы из первого продукта во все остальные
		 */
		
		foreach($product_ids as $product_id) {
			foreach($data as $image) {
				$image['product_id'] = $product_id;
				$image_id = $image['id'] = $this->model->add($image);
				if($action == 'addtostart')
					$this->moveToStart($image);
				if(!$image_id){
					return $this->messages[4];
				}
				
				$image_path = shopImage::getPath($image);
				if(!waFiles::create($image_path)) {
					return $this->messages[6];
					break;
				}
				
				$image['file']->copyTo($image_path);
				
				if($generate_thumbs)
					shopImage::generateThumbs($image, $config->getImageSizes());
			}
		}; 
	}
}