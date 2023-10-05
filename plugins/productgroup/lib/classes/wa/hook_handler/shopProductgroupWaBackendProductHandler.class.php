<?php

class shopProductgroupWaBackendProductHandler
{
	/**
	 * @param shopProduct $product
	 * @return array
	 * @throws waException
	 * @throws SmartyException
	 */
	public function handle($product)
	{
		if (!$product->id)
		{
			return [];
		}

		$view = wa()->getView();
		$view->assign('product', $product);

		$template_path = shopProductgroupWaHelper::getPath('templates/hooks/BackendProduct.EditSectionLi.html');

		return [
			'edit_section_li' => $view->fetch($template_path),
		];
	}
}