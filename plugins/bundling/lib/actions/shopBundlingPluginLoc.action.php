<?php

class shopBundlingPluginLocAction extends waViewAction
{
    public function execute()
    {
        $strings = array();

        foreach(array(
            'New',
            'Type of accessory',
			'Multiple select',
			'delete',
			'save',
			'or',
			'cancel',
			'Cancel',
			'add',
			'edit',
			'Save',
			'Leave',
			'Set to all',
			'Delete',
			'Please select at least one product',
			'close',
			'Set up the title of type of accessory',
			'Select option to attach the accessory',
			'Min length of title is 3 symbols!',
			'Delete this bundle?',
			'Delete selected bundle?',
			'Delete selected product from list?',
			'Start typing product or SKU name',
			'pc.',
			'Discount',
			'for chosing any product from this bundle',
			'Customer have to chose product from this bundle to get fixed discount',
			'Default quantity',
			'Product Bundles',
			'Select bundles for this products.',
			'Select for what products set up those as bundles.',
			'Setup as bundles',
			'Setup previously chosen products as bundles',
			'Waiting products',
			'Selected products',
			'No selected products. Maybe you have leave the page where you had selected them.'
			
        ) as $s) {
            $strings[$s] = _wp($s);
        }

        $this->view->assign('strings', $strings ? $strings : new stdClass());

        $this->getResponse()->addHeader('Content-Type', 'text/javascript; charset=utf-8');
    }
}
