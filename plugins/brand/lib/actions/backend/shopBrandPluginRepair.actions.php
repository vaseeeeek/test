<?php

class shopBrandPluginRepairActions extends waViewActions
{
	protected $response = 'Ok';

	/**
	 * @throws waException
	 */
	protected function preExecute()
	{
		$rights = new shopBrandPluginUserRights();
		if (!$rights->hasRights())
		{
			throw new waException('Нет прав', 403);
		}
	}

	public function defaultAction()
	{
		$this->response = "Доступные действия:\r\n\tproductbrandsImport  -  перенос данных из плагина productbrands\r\n\tthumb  -  восстановление файла thumb.php для генерации изображений \"на лету\"";
	}

	public function cleanerAction()
	{
		$cleaner = new shopBrandCleaner();

		$cleaner->clean();
	}

	/**
	 * @throws waException
	 */
	public function productbrandsImportAction()
	{
		$importer = new shopBrandImportProductbrandsBrands();

		$importer->import();
	}

	public function deletePageDuplicatesAction()
	{
		$model = new shopBrandBrandPageModel();

		$sql = '
select max(id) as id, page_id, brand_id, count(*) as `count`
from shop_brand_brand_page
group by page_id, brand_id
';

		$delete_sql = '
DELETE
FROM shop_brand_brand_page
WHERE page_id = :page_id AND brand_id = :brand_id
	AND id != :id
';

		foreach ($model->query($sql) as $group)
		{
			if ($group['count'] == 1)
			{
				continue;
			}

			$delete_params = array(
				'page_id' => $group['page_id'],
				'brand_id' => $group['brand_id'],
				'id' => $group['id'],
			);

			$model->exec($delete_sql, $delete_params);
		}
	}

	public function thumbAction()
	{
		$plugin_image_storage = new shopBrandImageStorage();

		$plugin_image_storage->createThumbFile();
	}

	public function run($params = null)
	{
		$action = $params;
		if (!$action)
		{
			$action = 'default';
		}
		$this->action = $action;

		try
		{
			$this->preExecute();
			$this->execute($this->action);
			$this->postExecute();
		}
		catch (waException $e)
		{
			$this->response = $e->getMessage();
		}

		if ($this->action == $action)
		{
			if (waRequest::isXMLHttpRequest())
			{
				$this->getResponse()->addHeader('Content-type', 'application/json');
			}
			$this->getResponse()->sendHeaders();
			if (!$this->errors)
			{
				echo '<pre>' . $this->response . '</pre>';
			}
			else
			{
				echo '<pre>' . json_encode(array('status' => 'fail', 'errors' => $this->errors)) . '</pre>';
			}
		}
	}
}
