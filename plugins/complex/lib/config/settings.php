<?php
return array(
	'on' => array(
		'value' => 1,
		'control_type' => 'checkbox',
		'title' => _wp('Plugin is on')
	),
	
	'tabs' => array(
		'value' => 0,
		'save' => true,
		'control_type' => 'ShevskyV7Tabs',
		'options' => array(
			array(
				'name' => _wp('Price types'),
				'controls' => array(
					'types'
				)
			),
			array(
				'name' => _wp('Basic settings'),
				'controls' => array(
					'update_cart_total', 'toggle_prices', 'compare', 'compare_only_if_discount', 'compare_if_compare_style', 'backend_order', 'export'
				)
			),
			array(
				'name' => _wp('Product page'),
				'controls' => array(
					'frontend', 'integration', 'place', 'hide_if_storefront', 'hide_if_user', 'hide_if_cart_products', 'default_if_null', 'dont_show_more', 'table_helper', 'product_menu', 'available_prices', 'product_template', 'product_css', 'sku_selector', 'sku_type_selector', 'price_format'
				)
			),
			array(
				'name' => '',
				'controls' => array(
					'transfer', 'transfer_block', 'css'
				),
				'class' => 'no-tab'
			),
		),
		'style' => 'min-width: 800px;'
	),
	
	'types' => array(
		'control_type' => 'Types',
	),
	
	'frontend' => array(
		'value' => 1,
		'control_type' => 'checkbox',
		'title' => _wp('Change product prices when viewing'),
		'description' => _wp('If product have at least one "complex" price, main price will be replaced to "complex"')
	),
	
	'update_cart_total' => array(
		'value' => 0,
		'control_type' => 'checkbox',
		'title' => _wp('Reload "Sum" value in cart'),
		'description' => _wp('If you select this parameter, "Sum" value in cart (stored in user session) will be reload every price changes to "complex".<br/>This parameter can be turned on when using complex conditions for the applying a "complex" price, when its applying depends on the specific actions of user')
	),
	'toggle_prices' => array(
		'value' => 0,
		'control_type' => 'checkbox',
		'title' => _wp('Allow to enable/disable price "swap" algorithm'),
		'description' => _wp('It will be possible for some products to switch the operating mode of the plugin algorithm. <br/>You can use, for example, to sell a product by wholesale price, or to disable wholesale prices for a while')
	),
	'export' => array(
		'value' => 1,
		'control_type' => 'checkbox',
		'title' => _wp('Export "Complex" prices'),
		'description' => _wp('When export products with changed price "swap" algorithm to "use complex price", their prices will be set for them')
	),
	'compare' => array(
		'value' => 0,
		'control_type' => 'checkbox',
		'title' => _wp('Show the main price as compare'),
		'description' => _wp('If the algorithm of the plugin calculated that there is some "complex" price for this product, the main will become a "compare"')
	),
	'compare_only_if_discount' => array(
		'value' => 1,
		'control_type' => 'checkbox',
		'title' => _wp('Only if "compare price" > "final price"'),
		'description' => _wp('Don\'t set a "compare price" if it is less than a "final price"')
	),
	'compare_if_compare_style' => array(
		'value' => 'no',
		'control_type' => 'select',
		'title' => _wp('If "compare price" already exists...'),
		'options' => array(
			array(
				'title' => _wp('Leave main "compare price"'),
				'value' => 'no'
			),
			array(
				'title' => _wp('Change to "main price"'),
				'value' => 'compare'
			),
		)
	),
	'backend_order' => array(
		'value' => 1,
		'control_type' => 'checkbox',
		'title' => _wp('Allow to use of "complex" prices in backend'),
		'description' => _wp('If you enable this option, when creating and editing orders in the backend, you can "sell" products by a certain "complex" price')
	),
	
	'transfer_block' => array(
		'control_type' => 'Transfer',
	),
	
	'transfer' => array(
		'value' => '1',
		'control_type' => 'checkbox',
		'title' => _wp('Transfer settings and prices from other plugins'),
	),
	
	'integration' => array(
		'value' => 1,
		'control_type' => 'checkbox',
		'title' => _wp('Integrate table with available prices on product page')
	),
	'place' => array(
		'value' => 'cart',
		'control_type' => 'radiogroup',
		'title' => _wp('Place of integration'),
		'options' => array(
			array(
				'title' => _wp('Content added next to links to custom product pages'),
				'value' => 'menu'
			),
			array(
				'title' => _wp('Content added next to the “Add to cart” button'),
				'value' => 'cart'
			),
			array(
				'title' => _wp('Informational block, usually in a sidebar'),
				'value' => 'block_aux'
			),
			array(
				'title' => _wp('Block of extra product details in main description area'),
				'value' => 'block'
			)
		)
	),
	'hide_if_storefront' => array(
		'value' => 1,
		'control_type' => 'checkbox',
		'title' => _wp('Hide not available for current storefront "complex" prices')
	),
	'hide_if_user' => array(
		'value' => 1,
		'control_type' => 'checkbox',
		'title' => _wp('Hide not available for current user "complex" prices'),
		'description' => _wp('"Complex" prices not available by category or user region will be hidden')
	),
	'hide_if_cart_products' => array(
		'value' => 1,
		'control_type' => 'checkbox',
		'title' => _wp('Hide if product is not suitable for "Products in cart" conditions'),
		'description' => _wp('"Complex" prices not available by conditions "Products in cart" will be hidden')
	),
	'default_if_null' => array(
		'value' => 1,
		'control_type' => 'checkbox',
		'title' => _wp('Show "main" price if "complex" is not filled'),
		'description' => _wp('If the complex price is missing and default value is not filled, the price will be specified by "main" price')
	),
	'dont_show_more' => array(
		'value' => 0,
		'control_type' => 'checkbox',
		'title' => _wp('Don\'t show prices more than main')
	),
	'table_helper' => array(
		'helper' => 'shopComplexPlugin::getAvailablePricesTable($product)',
		'control_type' => 'ShevskyV7Helper',
		'string_use_helper' => _wp('Use helper'),
		'string_for' => _wp(' to include the table with available prices.'),
		'description' => '<div></div>'
	),
	'product_menu' => array(
		'value' => 0,
		'save' => true,
		'control_type' => 'ShevskyV7Menu',
		'options' => array(
			array(
				'name' => _wp('Available prices'),
				'controls' => array(
					'available_prices'
				)
			),
			array(
				'name' => _wp('Template of the table with available prices'),
				'controls' => array(
					'product_template', 'product_css'
				)
			),
			array(
				'name' => _wp('Script params'),
				'controls' => array(
					'sku_selector', 'sku_type_selector', 'price_format'
				)
			),
		)
	),
	'available_prices' => array(
		'control_type' => 'AvailablePrices',
		'value' => array(
			0 => array(
				'name' => 1,
				'conditions' => 1
			)
		)
	),
	'product_template' => array(
		'title' => _wp('Template of the table with available prices'),
		'value' => '<h4>' . _wp('This product is available at special prices') . '</h4>
<table>
	<tbody>
		{foreach $prices as $price}
		<tr class="complex-plugin-price" id="complex-plugin-{$price.id}" data-id="{$price.id}">
			{if $name}
			<td>
				<strong>{$price.name}</strong>
			</td>
			{/if}
			{if $conditions}
			<td>
				{$price.conditions}
			</td>
			{/if}
			<td class="value">
				<span class="price">{$price.value_format_html}</span>
			</td>
		</tr>
		{/foreach}
	</tbody>
</table>',
		'control_type' => 'ShevskyV7Editor',
		'variables' => array(
			'_locale' => _wp('Available variables'),
			'name' => _wp('Price name is enabled'),
			'conditions' => _wp('Conditions is enabled'),
			'default_price' => _wp('Default product price value'),
			'default_price_format' => _wp('Formatted default product price value'),
			'default_price_format_html' => _wp('HTML formatted default product price value'),
			'prices' => _wp('An array with available prices'),
			'price' => array(
				'description' => _wp('Data of selected price'),
				'values' => array(
					'name' => _wp('Name of price'),
					'conditions' => _wp('Conditions for using selected price'),
					'value' => _wp('Value of price'),
					'value_format' => _wp('Formatted value of price'),
					'value_format_html' => _wp('HTML formatted value of price'),
				)
			)
		),
		'return_default_link' => _wp('Return the default value')
	),
	'product_css' => array(
		'title' => _wp('CSS'),
		'value' => '.complex-plugin-table table td {
	border: 1px solid #bbb;
	padding: 15px;
}
.complex-plugin-table table tr {
	transition: 0.3s;
}
.complex-plugin-table table tr:hover {
	box-shadow: 0 0 10px rgba(0,0,0,0.2);
}
.complex-plugin-table table td.value {
	text-align: center;
	width: 100px;
}',
		'control_type' => 'ShevskyV7Editor',
		'class' => 'css',
		'return_default_link' => _wp('Return the default value')
	),
	'sku_selector' => array(
		'value' => 'input[name=sku_id]',
		'control_type' => 'input',
		'title' => _wp('jQuery Sku selector')
	),
	'sku_type_selector' => array(
		'value' => '.sku-feature',
		'control_type' => 'input',
		'title' => _wp('jQuery Sku "Features Selectable" selector (date-feature-id + value)'),
	),
	'price_format' => array(
		'value' => 'value_format_html',
		'control_type' => 'select',
		'title' => _wp('Price format when sku is changing'),
		'description' => _wp('When changing a sku of product, the price in the table (.value) will be displayed in selected format'),
		'options' => array(
			array(
				'title' => _wp('Value of price') . ' ($price[\'value\'])',
				'value' => 'value'
			),
			array(
				'title' => _wp('Formatted value of price') . ' ($price[\'value_format\'])',
				'value' => 'value_format'
			),
			array(
				'title' => _wp('HTML formatted value of price') . ' ($price[\'value_format_html\'])',
				'value' => 'value_format_html'
			),
		)
	),
	
	'css' => array(
		'value' => '<style type="text/css">#plugins-settings-form > .field:last-of-type {
    clear: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #f6f6f6;
    padding: 0 10px;
    box-shadow: 0 -6px 10px -10px #aaa;
    z-index: 1052;
}

#plugins-settings-form > .field:last-of-type .value {
    border-top: 1px solid #ddd;
    margin: 0;
    padding: 10px;
    padding-left: 225px;
    position: static;
}</style>',
		'control_type' => 'ShevskyV7Html'
	)
);