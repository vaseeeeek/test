<?php

class shopBrandPluginBackendImportBrandsController extends shopBrandCsvImportController
{
	protected function init()
	{
		parent::init();

		$this->data['offset'] = $this->offset();
		$this->data['size'] = $this->size();
	}

	protected function getMap()
	{
		$source_map = [
            'id' => 'Id',
            'status' => 'Статус',
            'enabled' => 'Существует',
            'name' => 'Наименование',
            'url' => 'URL',
            'product_sort' => 'Сортировка',
            'sort' => 'Порядковый номер',
            'h1' => 'Заголовок H1',
            'meta_title' => 'Title',
            'meta_description' => 'META Description',
            'meta_keywords' => 'META Keywords',
            'description' => 'Описание',
            'additional_description' => 'Дополнительное описание',
        ];

		
		$header = $this->getHeader();
		$map = [];

		foreach ($header as $i => $column) {
			if (preg_match('/Поле №\d+/u', $column, $matches)) {
				$column = $matches[0];
			}

			$key = array_search($column, $source_map);

			if ($key) {
				$map[$key] = $i;
			}
		}

		return $map;
	}

	protected function isDone()
	{
		return $this->data['offset'] == $this->data['size'];
	}

	protected function step()
	{
		$imported_brand = $this->read();

		if (!is_null($imported_brand)) {
			$this->handleBrand($imported_brand);
		}

		$this->data['offset'] = $this->offset();

		return true;
	}

	protected function getInfo()
	{
		return array(
			'processId' => $this->processId,
			'offset' => $this->data['offset'],
			'size' => $this->data['size'],
		);
	}

	private function handleBrand($imported_brand)
	{
		$brand_ar = new shopBrandBrandStorage();

		$brand_id = $imported_brand['id'];
		$brand = $brand_ar->getById($brand_id);
		if (!$brand) {
			return;
		}

		$this->updateStatus($brand, $imported_brand);
		$this->updateName($brand, $imported_brand);
		$this->updateDefaultSort($brand, $imported_brand);
		$this->updateSort($brand, $imported_brand);
		$this->updateUrl($brand, $imported_brand);
		
		$this->updatePage($brand, $imported_brand);

		$brand_ar->store($brand->assoc());
	}

    private function updatePage($brand, $imported_brand)
    {
        $page_assoc = [
            'h1' => ifset($imported_brand['h1'], ''),
            'meta_title' => ifset($imported_brand['meta_title'], ''),
            'meta_description' => ifset($imported_brand['meta_description'], ''),
            'meta_keywords' => ifset($imported_brand['meta_keywords'], ''),
            'description' => ifset($imported_brand['description'], ''),
            'additional_description' => ifset($imported_brand['additional_description'], ''),
        ];

        $brand_page_storage = new shopBrandBrandPageStorage();
        $brand_page_storage->store($brand->id, 1, $page_assoc);
	}

	private function updateStatus(shopBrandBrand $brand, $imported_brand)
	{
		if (!array_key_exists('status', $imported_brand)) {
			return;
		}

		$status = strval($imported_brand['status']);
		if ($status === '1') {
			$brand->is_shown = shopBrandBrand::DB_TRUE;
		} elseif ($status === '0') {
			$brand->is_shown = shopBrandBrand::DB_FALSE;
		}
	}

	private function updateName(shopBrandBrand $brand, $imported_brand)
	{
		if (!array_key_exists('name', $imported_brand) || !is_string($imported_brand['name'])) {
			return;
		}

		$brand->name = trim($imported_brand['name']);
	}

	private function updateDefaultSort(shopBrandBrand $brand, $imported_brand)
	{
		if (!array_key_exists('product_sort', $imported_brand) || !is_string($imported_brand['product_sort'])) {
			return;
		}


        $sort = trim($imported_brand['product_sort']);
        if (in_array($sort, shopBrandImportexportHelper::getProductSortOptions())) {
            $brand->product_sort = $sort;
        }

	}

    private function updateSort(shopBrandBrand $brand, $imported_brand)
    {
        if (!array_key_exists('sort', $imported_brand) || !is_string($imported_brand['sort'])) {
            return;
        }


        $brand->sort = trim($imported_brand['sort']);

    }

	private function updateUrl(shopBrandBrand $brand, $imported_brand)
	{
		if (!array_key_exists('url', $imported_brand) || !is_string($imported_brand['url'])) {
			return;
		}

		$url = trim($imported_brand['url']);
		if ($url === '') {
			return;
		}

		$brand_model = new shopBrandBrandModel();
		$count = $brand_model
			->select('COUNT(*)')
			->where('id != :id', ['id' => $brand->id])
			->where('url = :url', ['url' => $url])
			->fetchField();

		$url_is_unique = intval($count) === 0;
		if ($url_is_unique) {
			$brand->url = $url;
		}
	}
}