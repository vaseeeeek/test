<?php

class shopComplexPluginLocAction extends waViewAction
{
    public function execute()
    {
        $strings = array();

        foreach(array(
            'cancel',
            'All conditions are met',
            'At least one condition is met',
            'Add condition',
            'Set rules for using this price',
            'or',
            'Control type',
            'is not found',
            'Add conditions',
            'It is not possible to create a condition group inside an existing one',
            'Transfer settings and prices',
            'Transfering of prices and settings...',
            'End of transfering...',
            'Preparing for transfer...',
            'On',
            'Off',
            'Save rules',
            'Helpers',
            'To display "complex" prices together with main one, you can use special helpers',
            'will return price of the main sku, or nothing, if there is no',
            'will return formatted price of the main sku, or nothing, if there is no',
            'will return price for selected sku, or nothing, if there is no',
            'will return formatted price for selected sku, or nothing, if there is no',
            'returns the status of algorithm to use "complex" prices',
            'algorithm is enabled and will change the price in accordance with the established conditions',
            'algorithm is disabled, product will ALWAYS use main price (also returned if the complex price is disabled)',
            'algorithm is disabled, product will ALWAYS use "complex" price',
            'is enabled selected "complex" price',
            'Close',
            'Delete price',
            'If you delete this price, then <strong>ALL</strong> existing prices for products will be deleted at the same time.</p><p>This action can\'t be undone. Are you sure you want to do this?',
            'Permanently delete',
			'are the conditions met for this product',
			'price name',
			'NOT'
			
        ) as $s) {
            $strings[$s] = _wp($s);
        }

        $this->view->assign('strings', $strings ? $strings : new stdClass());

        $this->getResponse()->addHeader('Content-Type', 'text/javascript; charset=utf-8');
    }
}
