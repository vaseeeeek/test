<?php

class shopBundlingPluginBackendActions extends waViewActions
{
	public function preExecute()
	{
		$this->setLayout(new shopBackendLayout());
		$this->layout->assign('page', 'products');
		
		$this->getResponse()->addJs('wa-apps/shop/plugins/bundling/js/bundling.' . $this->action . '.js');
		$this->getResponse()->addCss('wa-apps/shop/plugins/bundling/css/backend.css');
		
		$this->plugin = wa('shop')->getPlugin('bundling');
		$this->settings = $this->plugin->getSettings();
		$this->model = $this->plugin->model;
		$this->view->assign('plugin_url', $this->plugin->getPluginStaticUrl());
	}
	
    public function editProductBundlesAction()
    {
		$this->id = waRequest::get('id', 0, 'int');
		$this->product = new shopProduct($this->id);
		$type_model = new shopTypeModel();
		$this->product->type = $type_model->getById($this->product->type_id);
		
		$this->getResponse()->setTitle($this->product->name);
		
		$this->view->assign('currency', wa('shop')->getConfig()->getCurrency(false));
		$this->view->assign('product', $this->product);
		
		if($this->settings['bundle_groups'] == 'custom') {
			$this->bundles = $this->model->getBundles($this->id, false, true, true, false);
			$this->categories = $this->model->getCategoriesForProductWithNames($this->id);
			$this->view->assign('category_bundle_groups', false);
		} elseif($this->settings['bundle_groups'] == 'main_category') {
			$this->view->assign('category_bundle_groups', true);
			
			$model = new shopBundlingCategoriesModel();
			$this->bundles = $model->getCategoriesAsBundles($this->id);
		}

		$this->view->assign('edit_product_bundles_action', wa()->event('bundling_edit_product_bundles_action', $this->bundles));

		$this->view->assign('bundles', $this->bundles);
    }
	
	public function bundlesAction()
	{
		$this->getResponse()->setTitle(_wp('Bundles'));
		
		if($this->settings['bundle_groups'] == 'custom') {
			$by = waRequest::get('by', 'category');
			$bundles = $this->model->getAllBundleGroups($by);

			$this->view->assign('by', $by);
			$this->view->assign('bundles', $bundles);

			$category_model = new shopCategoryModel();
			$type_model = new shopTypeModel();
			$this->view->assign('options', $by == 'category' ? $category_model->getFullTree() : ($by == 'type' ? $type_model->getTypes() : $this->plugin->getFeatures()));

			$this->view->assign('by', $by);
		} elseif($this->settings['bundle_groups'] == 'main_category') {
			$this->setTemplate(wa()->getAppPath('plugins/bundling/templates/actions/backend/BackendBundlesCategory.html', 'shop'));
			
			$model = new shopBundlingCategoriesModel();
			$categories = $model->getCategories();
			$undefined_category = $model->getUndefinedCategory();
			
			$this->view->assign('categories', $categories);
			$this->view->assign('undefined_category', $undefined_category);
		}
	}
}
