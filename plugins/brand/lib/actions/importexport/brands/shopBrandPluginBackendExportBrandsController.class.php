<?php

class shopBrandPluginBackendExportBrandsController extends shopBrandCsvExportController
{
	protected function init()
	{
		parent::init();

		$this->buildBrandsQueue();
		$this->initDisabledBrands();
	}

    private function initDisabledBrands()
    {
        $storage = new shopBrandBackendBrandStorage();

        $all_deleted = $storage->getAllDeleted();
        $ids = [];
        foreach ($all_deleted as $brand) {
            $ids[] = $brand->id;
        }
        $this->data['deleted_ids'] = $ids;
	}

	private function buildBrandsQueue()
	{
		$brand_model = new shopBrandBrandModel();

		$brand_ids = [];
		foreach ($brand_model->select('id')->query() as $row) {
			$brand_ids[] = $row['id'];
		}

		$this->data['brand_ids'] = $brand_ids;
		$this->data['offset'] = 0;
	}

	protected function getMap()
	{
		$map = [
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

		return $map;
	}

	protected function isDone()
	{
		return $this->isBrandsQueueEmpty();
	}

	protected function step()
	{
		$brand_id = $this->getBrandIdFromQueue();
		$exported_brand = $this->getExportedBrand($brand_id);
		if ($exported_brand) {
			$this->write($exported_brand);
		}
		$this->popBrandsQueue();

		return true;
	}

	private function getBrandIdFromQueue()
	{
		return $this->data['brand_ids'][$this->data['offset']];
	}

	private function popBrandsQueue()
	{
		$this->data['offset']++;
	}

	private function isBrandsQueueEmpty()
	{
		return $this->data['offset'] === count($this->data['brand_ids']);
	}

	protected function getInfo()
	{
		return [
			'processId' => $this->processId,
			'offset' => $this->data['offset'],
			'brands_count' => count($this->data['brand_ids']),
		];
	}

	private function getExportedBrand($brand_id)
	{
		$brand_ar = new shopBrandBrandStorage();
		$brand = $brand_ar->getById($brand_id);

		if (!$brand) {
			return null;
		}

        $brand_page_storage = new shopBrandBrandPageStorage();
        $brand_page = $brand_page_storage->getPage($brand->id, 1);

        if (!$brand_page) {
            $brand_page = new shopBrandBrandPage();
        }

		return [
			'id' => $brand->id,
			'status' => $brand->is_shown,
			'enabled' => in_array($brand->id, $this->data['deleted_ids']) ? '0' : '1',
			'name' => $brand->name,
			'url' => $brand->url,
			'product_sort' => $brand->product_sort,
			'sort' => $brand->sort,
            'h1' => ifset($brand_page->h1, ''),
            'meta_title' => ifset($brand_page->meta_title, ''),
            'meta_description' => ifset($brand_page->meta_description, ''),
            'meta_keywords' => ifset($brand_page->meta_keywords, ''),
            'description' => ifset($brand_page->description, ''),
            'additional_description' => ifset($brand_page->additional_description, ''),
		];
	}
}