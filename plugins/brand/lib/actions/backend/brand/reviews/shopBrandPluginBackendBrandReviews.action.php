<?php

class shopBrandPluginBackendBrandReviewsAction extends shopBrandBackendAction
{
	public function execute()
	{
		$this->view->assign('reviews', $this->getReviews());
		$this->view->assign('brands', $this->getBrands());
		$this->view->assign('current_user', $this->getCurrentUser());
	}

	private function getReviews()
	{
		$reviews_storage = new shopBrandBrandReviewStorage();

		return $reviews_storage->getAllAssoc();
	}

	private function getBrands()
	{
		$brand_storage = new shopBrandBrandStorage();

		$brands = array();
		$all = $brand_storage->getAll();
		foreach ($all as $brand)
		{
			$id = $brand->id;
			$brands[$id] = array(
				'id' => $id,
				'name' => $brand->name,
				'image_url' => $brand->getImageUrl(shopBrandImageStorage::SIZE_BACKEND_REVIEWS),
			);
		}
		unset($brand);

		return $brands;
	}

	private function getCurrentUser()
	{
		$user = wa()->getUser();

		return array(
			'id' => $user->getId(),
			'name' => $user->getName(),
			'image' => $user->getPhoto(),
		);
	}
}
