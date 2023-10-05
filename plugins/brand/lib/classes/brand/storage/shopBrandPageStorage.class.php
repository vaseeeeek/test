<?php

class shopBrandPageStorage extends shopBrandStorage
{
	const MAIN_PAGE_ID = 1;
	const MAIN_PAGE_URL = '';

	private $statuses;
	private $types;

	public function __construct()
	{
		$this->statuses = new shopBrandPageStatusEnumOptions();
		$this->types = new shopBrandPageTypeEnumOptions();

		parent::__construct();
	}

	/**
	 * @return shopBrandPage[]
	 */
	public function getAll()
	{
		$pages = array();
		$query = $this->model
			->select('*')
			->order('sort ASC')
			->fetchAll('id');

		foreach ($query as $id => $page_raw)
		{
			$pages[$id] = $id == self::MAIN_PAGE_ID
				? $this->getMainPage()
				: new shopBrandPage($this->prepareStorableForAccessible($page_raw));
		}

		if (!array_key_exists(self::MAIN_PAGE_ID, $pages))
		{
			$pages[self::MAIN_PAGE_ID] = $this->getDefaultMainPage();
		}

		return $pages;
	}

	public function getById($id)
	{
		$page_raw = $this->fetchRaw($id);

		return $page_raw
			? new shopBrandPage($this->prepareStorableForAccessible($page_raw))
			: null;
	}

	public function savePages($pages_assoc, $ids_to_delete)
	{
		$sort = 1;
		$pages_to_insert_assoc = array();

		$has_main_page = false;

		$_ids_to_delete = array();
		foreach (array_values($ids_to_delete) as $id_to_delete)
		{
			$_ids_to_delete[$id_to_delete] = $id_to_delete;
		}

		$ids_to_delete = $_ids_to_delete;
		unset($_ids_to_delete);

		foreach ($pages_assoc as $page_assoc)
		{
			$page_id = $page_assoc['id'];

			$existing_page = $this->getById($page_id);
			$page_to_insert = null;

			if ($page_id == self::MAIN_PAGE_ID)
			{
				$has_main_page = true;

				$default_main_page = $this->getDefaultMainPage();
				$main_page = new shopBrandPage($page_assoc);

				$main_page->url = $default_main_page->url;
				$main_page->status = $default_main_page->status;
				$main_page->type = $default_main_page->type;

				$page_to_insert = $main_page->assoc();
				unset($default_main_page);
				unset($main_page);
			}
			else
			{
				if (!array_key_exists($page_id, $ids_to_delete))
				{
					$page_to_insert = $page_assoc;
				}
			}

			if (is_array($page_to_insert))
			{
				$page_to_insert['create_datetime'] = $existing_page ? $existing_page->create_datetime : time();

				if ($existing_page)
				{
					$page = new shopBrandPage($page_assoc);

					if (!$existing_page->isEqual($page))
					{
						$page_to_insert['update_datetime'] = time();
					}
				}
				else
				{
					$page_to_insert['update_datetime'] = time();
				}

				$pages_to_insert_assoc[] = $page_to_insert;
			}
		}

		if (!$has_main_page)
		{
			$default_main_page = $this->getDefaultMainPage();

			$pages_to_insert_assoc = array_merge(array($default_main_page->assoc()), $pages_to_insert_assoc);
		}

		$this->model->deleteById($ids_to_delete);

		foreach ($pages_to_insert_assoc as $page_to_insert)
		{
			if ($page_to_insert['id'] < 0)
			{
				$page_to_insert['id'] = null;
			}

			$page_to_insert['sort'] = $sort++;

			if ($page_to_insert['id'] == self::MAIN_PAGE_ID)
			{
				$page_to_insert['url'] = self::MAIN_PAGE_URL;
			}
			else
			{
				$page_to_insert['url'] = strlen($page_to_insert['url'])
					? $this->getUniqueUrl($page_to_insert['url'], $page_to_insert['id'])
					: $this->getUniqueUrl($page_to_insert['name'], $page_to_insert['id']);
			}

			$this->model->insert(
				$this->prepareAccessibleToStorable($page_to_insert),
				waModel::INSERT_ON_DUPLICATE_KEY_UPDATE
			);
		}
	}

	public function getByUrl($page_url)
	{
		if ($page_url == self::MAIN_PAGE_URL)
		{
			return $this->getMainPage();
		}
		else
		{
			$page_raw = $this->model->getByField('url', $page_url);

			return $page_raw
				? new shopBrandPage($this->prepareStorableForAccessible($page_raw))
				: null;
		}
	}

	public function getMainPage()
	{
		$main_page = $this->getById(self::MAIN_PAGE_ID);

		if ($main_page && !$main_page->name)
		{
			$main_page->name = 'Главная';
		}

		return $main_page ? $main_page : $this->getDefaultMainPage();
	}

	public function getUniqueUrl($url, $page_id = null)
	{
		$base_url = $random_url = shopBrandHelper::toCanonicalUrl($url);

		$unique_url_count_sql = "
SELECT COUNT(id)
FROM {$this->model->getTableName()}
WHERE url = :url
";
		$sql_params = array(
			'url' => $random_url,
		);

		if ($page_id > 0)
		{
			$unique_url_count_sql .= ' AND id != :id';
			$sql_params['id'] = $page_id;
		}

		while ($this->model->query($unique_url_count_sql, $sql_params)->fetchField() > 0)
		{
			$random_url = $base_url . '_' . substr(uniqid(), -5, 5);
			$sql_params['url'] = $random_url;
		}

		return $random_url;
	}

	/**
	 * @return shopBrandIDataFieldSpecification[]
	 */
	protected function accessSpecification()
	{
		$specification = new shopBrandDataFieldSpecificationFactory();

		return array(
			'id' => $specification->integer(),
			'name' => $specification->string(),
			'url' => $specification->string(),
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
			'status' => $specification->enum($this->statuses, $this->statuses->PUBLISHED),
			'type' => $specification->enum($this->types, $this->types->PAGE),
			'sort' => $specification->integer(),
		);
	}

	/**
	 * @return waModel
	 */
	protected function dataModel()
	{
		return new shopBrandPageModel();
	}

	/**
	 * @return shopBrandPage
	 */
	private function getDefaultMainPage()
	{
		$page = new shopBrandPage();

		$page->id = self::MAIN_PAGE_ID;
		$page->name = 'Каталог';
		$page->url = '';
		$page->meta_title = '';
		$page->meta_description = '';
		$page->meta_keywords = '';
		$page->h1 = '';
		$page->description = '';
		$page->additional_description = '';
		$page->create_datetime = date('Y-m-d H:i:s');
		$page->update_datetime = date('Y-m-d H:i:s');
		$page->status = $this->statuses->PUBLISHED;
		$page->type = $this->types->CATALOG;
		$page->sort = 1;

		return $page;
	}
}