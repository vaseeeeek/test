<?php


class shopBundlingPluginDialogRemoveAllController extends waJsonController
{
	/**
	 * @var shopBundlingModel
	 */
	private $model;

	public function preExecute()
	{
		$plugin = wa('shop')->getPlugin('bundling');
		$this->model = $plugin->model;

		$product_ids = waRequest::request('product_id', array(), 'array_int');
		$hash = waRequest::request('hash', '');
		if(count($product_ids) == 0 && !$hash)
			return $this->setError('zero');

		if($hash)
			$product_ids = $plugin->getProductIdsByHash($hash);

		$this->hash = $hash;
		$this->product_ids = $product_ids;
	}

	public function execute()
	{
		$products_model = new shopBundlingProductsModel();
		foreach ($this->product_ids as $product_id)
		{
			$bundles = $this->model->getByField('product_id', $product_id, true);
			if (!$bundles) {
				continue;
			}

			foreach ($bundles as $bundle)
			{
				$products_model->deleteByField(array(
					'product_id' => $product_id,
					'bundle_id' => $bundle['id']
				));
			}

			$this->model->deleteByField('product_id', $product_id);
		}
	}
}
