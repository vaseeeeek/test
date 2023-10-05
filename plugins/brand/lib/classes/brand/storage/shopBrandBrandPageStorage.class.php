<?php

// todo collection
class shopBrandBrandPageStorage extends shopBrandStorage
{
	private $brand_page_model;
	private $brand_page_model_meta;

	public function __construct()
	{
		$this->brand_page_model = new shopBrandBrandPageModel();
		$this->brand_page_model_meta = $this->brand_page_model->getMetadata();

		parent::__construct();
	}

	public function store($brand_id, $page_id, $brand_page_params)
	{
		$brand_page = new shopBrandBrandPage($brand_page_params);

		$existing_brand_page = $this->getPage($brand_id, $page_id);

		$brand_page->brand_id = $brand_id;
		$brand_page->page_id = $page_id;
		$brand_page->update_datetime = time();
		$brand_page->create_datetime = $existing_brand_page
			? $existing_brand_page->create_datetime
			: time();

		$brand_page_raw = $this->prepareAccessibleToStorable($brand_page);
		unset($brand_page_raw['id']);

		return $existing_brand_page
			? $this->brand_page_model->updateByField('id', $existing_brand_page->id, $brand_page_raw)
			: $this->brand_page_model->insert($brand_page_raw, waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
	}

	public function getPage($brand_id, $page_id, $storefront = null)
	{
		$page_raw = $this->model->getByField(array(
			'brand_id' => $brand_id,
			'page_id' => $page_id,
		));

		return $page_raw
			? new shopBrandBrandPage($page_raw)
			: null;
	}


	/**
	 * @param int $brand_id
	 * @return shopBrandBrandPage[]
	 */
	public function getPages($brand_id)
	{
		$pages = array();
		foreach ($this->fetchPagesRaw($brand_id) as $page_id => $page_raw)
		{
			$pages[$page_id] = new shopBrandBrandPage($this->prepareStorableForAccessible($page_raw));
		}

		return $pages;
	}

	protected function fetchPagesRaw($brand_id)
	{
		//		$sql = '
		//SELECT *
		//FROM shop_brand_page AS p
		//	LEFT JOIN shop_brand_brand_page AS bp
		//		ON p.id = bp.page_id
		//WHERE (bp.brand_id = :brand_id OR bp.brand_id IS NULL)
		//ORDER BY p.sort ASC
		//';
		//		$query_params = array('brand_id' => $brand_id);
		//
		//		$pages = $this->model->query($sql, $query_params);

		return $this->model
			->select('*')
			->where('brand_id = :brand_id', array('brand_id' => $brand_id))
			->fetchAll('page_id');
	}

	/**
	 * @return shopBrandIDataFieldSpecification[]
	 */
	protected function accessSpecification()
	{
		$specification = new shopBrandDataFieldSpecificationFactory();

		return array(
			'id' => $specification->integer(),
			'page_id' => $specification->integer(),
			'brand_id' => $specification->integer(),
			'content' => $specification->string(),
			'meta_title' => $specification->string(),
			'meta_description' => $specification->string(),
			'meta_keywords' => $specification->string(),
			'h1' => $specification->string(),
			'description' => $specification->string(),
			'additional_description' => $specification->string(),
			'template' => $specification->string(),
			'create_datetime' => $specification->datetime(),
			'update_datetime' => $specification->datetime(),
			'create_contact_id' => $specification->integer(),
		);
	}

	/**
	 * @return waModel
	 */
	protected function dataModel()
	{
		return new shopBrandBrandPageModel();
	}
}
