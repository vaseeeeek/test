<?php
return array(
	'on' => array(
		'value' => 1,
		'control_type' => 'checkbox',
		'title' => _wp('Plugin is on')
	),
	
	'tabs' => array(
		'plus' => 1,
		'value' => 0,
		'save' => true,
		'control_type' => 'ShevskyV7Tabs',
		'options' => array(
			array(
				'name' => _wp('Integration'),
				'controls' => array(
					'integration', 'place', 'type', 'integrate_your_bundle', 'bundling_helper', 'your_bundle_helper'
				)
			),
			array(
				'name' => _wp('Discounts'),
				'controls' => array(
					'discounts', 'rounding'
				)
			),
			array(
				'name' => _wp('Tables design'),
				'controls' => array(
					'templates',
					'select_show_image', 'template_select', 'configurator_buy_button', 'configurator_quantity', 'template_configurator', 'template_custom'
				)
			),
			array(
				'name' => _wp('"Your bundle" section'),
				'controls' => array(
					'template_your_bundle', 'your_bundle_image_size'
				)
			),
			array(
				'name' => _wp('Script params'),
				'controls' => array(
					'hide_products_if_not_in_stock', 'bundle_groups', 'form_selector', 'quantity_selector', 'quantity_plus_minus_selector', 'sku_selector', 'sku_type_selector', 'services_selector'
				)
			),
			array(
				'name' => _wp('CSS'),
				'controls' => array(
					'css'
				)
			),
			array(
				'name' => '--><a href="?plugin=bundling&action=bundles"><i class="icon16 ss cube-bw"></i> Задать Комплектации',
				'controls' => array(),
				'class' => 'no-tab"><!--'
			)
		),
		'style' => 'min-width: 700px;'
	),
	
	'discounts' => array(
		'value' => 1,
		'control_type' => 'checkbox',
		'title' => _wp('Discounts is on')
	),
	'rounding' => array(
		'value' => '0',
		'control_type' => 'select',
		'title' => _wp('Rounding'),
		'options' => array(
			array(
				'title' => _wp('Round to nearest 100'),
				'value' => 100
			),
			array(
				'title' => _wp('Round to nearest 99'),
				'value' => 99
			),
			array(
				'title' => _wp('Round to nearest 10'),
				'value' => 10
			),
			array(
				'title' => _wp('Round to nearest 1.00'),
				'value' => 1
			),
			array(
				'title' => _wp('Round to nearest .99'),
				'value' => 0.99
			),
			array(
				'title' => _wp('Round to nearest 0.1'),
				'value' => 0.1
			),
			array(
				'title' => _wp('Round to nearest 0.01'),
				'value' => 0.01
			),
			array(
				'title' => _wp('Not round'),
				'value' => 0
			),
		)
	),
	
	'integration' => array(
		'value' => 1,
		'control_type' => 'checkbox',
		'title' => _wp('Auto integration to product cart')
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
	'type' => array(
		'value' => 'select',
		'control_type' => 'radiogroup',
		'title' => _wp('Type of table with bundles'),
		'options' => array(
			array(
				'title' => _wp('Drop-down list'),
				'description' => 'select',
				'value' => 'select'
			),
			array(
				'title' => _wp('Bundles configurator'),
				'description' => 'configurator',
				'value' => 'configurator'
			),
			array(
				'title' => _wp('Custom table'),
				'description' => 'custom',
				'value' => 'custom'
			)
		)
	),
	'integrate_your_bundle' => array(
		'value' => '0',
		'control_type' => 'select',
		'title' => _wp('Integrate "Your bundle" section'),
		'options' => array(
			array(
				'title' => '-',
				'value' => '0'
			),
			array(
				'title' => _wp('Before bundle table'),
				'value' => 'before'
			),
			array(
				'title' => _wp('After bundle table'),
				'value' => 'after'
			),
			array(
				'title' => _wp('Before and after bundle table'),
				'value' => 'after+before'
			),
		)
	),
	'bundling_helper' => array(
		'helper' => 'shopBundling::getBundling($product, &$type:[select,configurator,custom])',
		'control_type' => 'ShevskyV7Helper',
		'string_use_helper' => _wp('Use helper'),
		'string_for' => _wp(' to include the table with bundles manually.')
	),
	'your_bundle_helper' => array(
		'helper' => 'shopBundling::getYourBundle($product)',
		'control_type' => 'ShevskyV7Helper',
		'string_use_helper' => _wp('For including "Your bundle" section use helper'),
		'string_for' => ' '
	),
	
	'templates' => array(
		'plus' => 1,
		'value' => 0,
		'save' => true,
		'control_type' => 'ShevskyV7Menu',
		'options' => array(
			array(
				'name' => _wp('Drop-down list'),
				'controls' => array(
					'select_show_image',
					'template_select'
				)
			),
			array(
				'name' => _wp('Bundles configurator'),
				'controls' => array(
					'configurator_buy_button',
					'configurator_quantity',
					'template_configurator'
				)
			),
			array(
				'name' => _wp('Custom table'),
				'controls' => array(
					'template_custom'
				)
			)
		)
	),
	'select_show_image' => array(
		'value' => 0,
		'control_type' => 'checkbox',
		'title' => _wp('Images is enabled')
	),
	'template_select' => array(
		'title' => _wp('Template of Drop-down list'),
		'value' => '<h3>' . _wp('Bundle') . '</h3>
<table>
	<tbody>
		{foreach $bundles as $bundle_id => $bundle}
		<tr>
			<td colspan="2"><h4>{$bundle[\'title\']|escape}:</h4></td>
		</tr>
		<tr id="bundling-select-{$bundle_id}">
			<td{if $bundle[\'multiple\']} colspan="2"{/if}>
			<select data-id="{$bundle_id}" data-multiple="{$bundle[\'multiple\']}">
				<option></option>
				{foreach $bundle[\'products\'] as $bundled_product}
				<option data-price="{$bundled_product[\'frontend_price\']}" data-url="{$bundled_product[\'frontend_url\']}" data-image="{$bundled_product.image.crop_small}" data-sku-id="{$bundled_product[\'sku_id\']}" value="{$bundled_product[\'id\']}">
					{$bundled_product[\'name\']}
					({wa_currency($bundled_product[\'frontend_price\'], $currency)})
				</option>
				{/foreach}
				</select>
			</td>
			{if !$bundle[\'multiple\']}
			<td class="bundling-product-quantity" align="right">
				<input id="bundling-quantity-{$bundle_id}" data-bundle-id="{$bundle_id}" type="number" min="1" value="1"/> ' . _wp('pc.') . '
			</td>
			{/if}
		</tr>
		<tr class="bundling-about-selected-product">
			{if $bundle[\'multiple\']}
			<td class="bundling-product-title">
				<a class="bundling-product-link" href="#" target="_blank">
					{if $template_show_image}
					<img/>
					{/if}
					<span>' . _wp('About this product (in a new tab)') . '</span>
				</a>
			</td>
			<td class="bundling-product-quantity" align="right"><input type="number" min="0" value="0"/> ' . _wp('pc.') . '</td>
			{else}
			<td colspan="2">
				<a class="bundling-product-link" href="#" target="_blank">
					{if $template_show_image}
					<img/>
					{/if}
					<span>' . _wp('About this product (in a new tab)') . '</span>
				</a>
			</td>
			{/if}
		</tr>
		{/foreach}
	</tbody>
</table>

<div class="bundling-last-price">' . _wp('Total price') . ': <span class="price">{wa_currency_html($product[\'frontend_price\'], $currency)}</span></div>
<div class="bundling-buy-selected"><input class="bundling-add2cart btn btn-default" type="button" value="' . _wp('Buy') . '"> ' . _wp('selected bundle') . '</div>',
		'control_type' => 'ShevskyV7Editor',
		'variables' => array(
			'_locale' => _wp('Available variables'),
			'template_show_image' => _wp('Images is enabled'),
			'bundles' => _wp('An array with available groups of accessories (bundles)'),
			'bundle' => array(
				'description' => _wp('Data of selected group of accessories (bundles)'),
				'values' => array(
					'title' => _wp('Title of type of accessory'),
					'products' => _wp('An array with information about attached products. Instance of the class') . ' <a class="code" href="https://developers.webasyst.ru/apps/shop-script/shopProduct/" target="_blank">shopProduct</a>',
					'image\'][\'thumb' => _wp('Image 200 in width'),
					'image\'][\'square' => _wp('Image 200x200'),
					'image\'][\'crop' => _wp('Image 96x96'),
					'image\'][\'crop_small' => _wp('Image 48x48'),
				)
			)
		),
		'return_default_link' => _wp('Return the default value')
	),
	'configurator_buy_button' => array(
		'value' => 0,
		'control_type' => 'checkbox',
		'title' => _wp('Show "Buy" button on each product')
	),
	'configurator_quantity' => array(
		'value' => 0,
		'control_type' => 'checkbox',
		'title' => _wp('Show "Quantity" field')
	),
	'template_configurator' => array(
		'title' => _wp('Template of Bundles configurator'),
		'value' => '<h3>' . _wp('Bundle') . '</h3>

{foreach $bundles as $bundle_id => $bundle}
<div class="bundling-bundle" id="bundling-bundle-{$bundle_id}" data-bundle-id="{$bundle_id}" data-multiple="{$bundle[\'multiple\']}">
	<div class="bundling-bundle-title">
		<span>{$bundle[\'title\']|escape}</span>
	</div>
	
	<div class="bundling-bundle-products">
		<table>
			<tbody>
				{if !$bundle[\'multiple\']}
				<tr>
					<td>
						<input class="bundling-product-selector" name="bundling_bundle_{$bundle_id}" value="0" type="radio" checked>
					</td>
					<td colspan="3">
						' . _wp('Not selected') . '
					</td>
				</tr>
				{/if}
				{foreach $bundle[\'products\'] as $bundled_product}
				<tr class="bundling-product" data-price="{$bundled_product[\'frontend_price\']}" data-sku-id="{$bundled_product[\'sku_id\']}" data-product-id="{$bundled_product[\'id\']}" data-image="{$bundled_product.image.crop_small}">
					<td width="24">
						<input class="bundling-product-selector" name="bundling_bundle_{$bundle_id}" value="{$bundled_product[\'id\']}" type="{if $bundle[\'multiple\']}checkbox{else}radio{/if}">
					</td>
					<td width="60">
						<img class="bundling-product-image" src="{$bundled_product.image.crop_small|default:"`$plugin_url`img/no-image.png"}" width="48" height="48"/>
					</td>
					<td>
						<div class="bundling-product-title">
							<a href="{$bundled_product[\'frontend_url\']}" target="_blank">{$bundled_product[\'name\']}</a>
						</div>
					</td>
					<td align="right">
						{if $bundled_product[\'default_frontend_price\'] > $bundled_product[\'frontend_price\']}<s class="compare-price">{wa_currency_html($bundled_product[\'default_frontend_price\'] * $bundled_product[\'quantity\'], $currency)}</s>{/if}
						<span class="bundling-product-price price">{wa_currency_html($bundled_product[\'frontend_price\'] * $bundled_product[\'quantity\'], $currency)}</span>
					</td>
					{if $template_quantity}<td class="bundling-product-quantity" align="right">
						<input data-bundle-id="{$bundle_id}" data-product-id="{$bundled_product[\'id\']}" data-sku-id="{$bundled_product[\'sku_id\']}" type="number" min="{$bundled_product[\'min\']}" step="{$bundled_product[\'step\']}" value="{$bundled_product[\'quantity\']}"/> ' . _wp('pc.') . '
					</td>{/if}
					<td class="bundling-buy" align="right"{if $template_buy_button} width="90"{/if}>
						{if $template_buy_button}
						<input data-product-id="{$bundled_product[\'id\']}" data-sku-id="{$bundled_product[\'sku_id\']}" class="bundling-buy-button btn btn-primary gray" type="button" value="' . _wp('Buy') . '">
						<div class="added" style="color: green; display: none;">✔ ' . _wp('Added to cart') . '</div>
						{/if}
					</td>
				</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
</div>
{/foreach}

<div class="bundling-last-price">' . _wp('Total price') . ': <span class="price">{wa_currency_html($product[\'frontend_price\'], $currency)}</span></div>
<div class="bundling-buy-selected"><input class="bundling-add2cart btn btn-default" type="button" value="' . _wp('Buy') . '"> ' . _wp('selected bundle') . '</div>',
		'control_type' => 'ShevskyV7Editor',
		'variables' => array(
			'_locale' => _wp('Available variables'),
			'bundles' => _wp('An array with available groups of accessories (bundles)'),
			'bundle' => array(
				'description' => _wp('Data of selected group of accessories (bundles)'),
				'values' => array(
					'title' => _wp('Title of type of accessory'),
					'products' => _wp('An array with information about attached products. Instance of the class') . ' <a class="code" href="https://developers.webasyst.ru/apps/shop-script/shopProduct/" target="_blank">shopProduct</a>',
					'image\'][\'thumb' => _wp('Image 200 in width'),
					'image\'][\'square' => _wp('Image 200x200'),
					'image\'][\'crop' => _wp('Image 96x96'),
					'image\'][\'crop_small' => _wp('Image 48x48'),
				)
			)
		),
		'return_default_link' => _wp('Return the default value')
	),
	'template_custom' => array(
		'title' => _wp('Template of Custom table with bundles'),
		'control_type' => 'ShevskyV7Editor',
		'variables' => array(
			'_locale' => _wp('Available variables'),
			'bundles' => _wp('An array with available groups of accessories (bundles)'),
			'bundle' => array(
				'description' => _wp('Data of selected group of accessories (bundles)'),
				'values' => array(
					'title' => _wp('Title of type of accessory'),
					'products' => _wp('An array with information about attached products. Instance of the class') . ' <a class="code" href="https://developers.webasyst.ru/apps/shop-script/shopProduct/" target="_blank">shopProduct</a>',
					'image\'][\'thumb' => _wp('Image 200 in width'),
					'image\'][\'square' => _wp('Image 200x200'),
					'image\'][\'crop' => _wp('Image 96x96'),
					'image\'][\'crop_small' => _wp('Image 48x48'),
				)
			)
		),
		'return_default_link' => false
	),
	'css' => array(
		'title' => 'CSS',
		'value' => <<<CSS
.bundling table {
	width: 100%;
}

.bundling table td {
	padding: 0 !important;
	border: 0 !important;
}

.bundling-select h3 {
	font-size: 1.2em;
	margin: 20px 0 0 0;
}

.bundling-select h4 {
	font-size: 1em;
	margin: 5px 0;
}

.bundling-select .bundling-last-price {
	font-size: 1.1em;
}

.bundling .bundling-add2cart, .bundling-your .bundling-add2cart {
	font-size: 1.2em;
	margin-top: 10px;
}

.bundling-select select {
	width: 100%;
}

.bundling-select .bundling-about-selected-product {
	display: none;
}

.bundling-select .bundling-about-selected-product.center td {
	text-align: center;
}

.bundling-select .bundling-about-selected-product td a {
	display: block;
	font-size: 0.9em;
	padding: 5px 10px;
	margin: 5px 0;
	border: 1px solid #ccc;
	overflow: hidden;
}

.bundling-select .bundling-about-selected-product img {
	float: left;
	padding-right: 10px;
}

.bundling-configurator, .bundling-your {
	margin-bottom: 20px;
}

.bundling-configurator .bundling-bundle-title {
	margin: 10px 0;
	z-index: 1;
    background: #fff;
    position: relative;
    margin-top: 5px;
}

.bundling-configurator .bundling-bundle-title span {
	padding: 10px;
	border: 3px solid #f1f1f1;
	border-bottom: 0;
	border-top-left-radius: 5px;
	border-top-right-radius: 5px;
	background: #fff;
}

.bundling-configurator .bundling-bundle-title.selected span {
	background: #eee;
}

.bundling-configurator .bundling-bundle-products {
	border: 3px solid #f1f1f1;
	padding: 5px 0;
	position: relative;
	margin-top: -4px;
	background: #fff;
	margin-bottom: 30px;
}

.bundling-configurator .bundling-bundle-products table {
	margin-bottom: 0;
}

.bundling-configurator .bundling-bundle-products table tr.selected {
	background: #eee;
}

.bundling-configurator .bundling-bundle-products table td {
	padding: 5px 0 !important;
}

.bundling-configurator .bundling-bundle-products table td:first-child {
	padding-left: 10px !important;
}

.bundling-configurator .bundling-bundle-products table td:last-child {
	padding-right: 10px !important;
}

.bundling-configurator .bundling-last-price {
	font-size: 1.1em;
}

.bundling-product-quantity {
	width: 80px;
}

.bundling-product-quantity input {
	width: 40px;
}

.bundling s.compare-price {
	color: gray;
}

.bundling-your-bundle {
	border: 3px solid #f1f1f1;
	border-radius: 5px;
}
.bundling-your-bundle-header {
	background: #f1f1f1;
	padding: 10px;
}
.bundling-your-bundle-items {
	padding: 10px;
}
.bundling-your-bundle-item {
	display: inline;
	padding: 0 5px;
}
.bundling-your-bundle-item .quantity {
	position: absolute;
	background: #f1f1f1;
	padding: 0 3px;
}
CSS
		, 'control_type' => 'ShevskyV7Editor',
		'class' => 'css',
		'return_default_link' => _wp('Return the default value')
	),
	'your_bundle_image_size' => array(
		'value' => 'crop_small',
		'control_type' => 'select',
		'title' => _wp('Image size'),
		'options' => array(
			array(
				'title' => _wp('Image 200 in width'),
				'value' => 'thumb'
			),
			array(
				'title' => _wp('Image 200x200'),
				'value' => 'square'
			),
			array(
				'title' => _wp('Image 96x96'),
				'value' => 'crop'
			),
			array(
				'title' => _wp('Image 48x48'),
				'value' => 'crop_small'
			)
		)
	),
	'template_your_bundle' => array(
		'title' => _wp('Template of "Your bundle" section'),
		'value' => '<div class="bundling-your-bundle">
	<div class="bundling-your-bundle-header">
		' . _wp('Your bundle') . ': <strong class="items">1</strong> <span class="items-text" data-one="' . _wp('item') . '" data-two="' . _wp('items') . '" data-five="' . _wp('_items') . '">' . _wp('item') . '</span> <span class="for">' . _wp('_for') . '</span> <span class="price">{wa_currency_html($product[\'frontend_price\'], $currency)}</span>
	</div>
	<div class="bundling-your-bundle-items">
		<div class="bundling-your-bundle-item default">
			<span class="quantity"></span>
			<img width="48" height="48" src="{$product_image|default:"`$plugin_url`img/no-image.png"}"/>
		</div>
	</div>
</div>
<input class="bundling-add2cart btn btn-default" type="button" value="' . _wp('Buy') . '"> ' . _wp('selected bundle') . '

<style type="text/css">
	/* ' . _wp('Hide buy buttons from table') . ' */
	.bundling-buy-selected, .bundling-last-price { display: none; }
</style>',
		'control_type' => 'ShevskyV7Editor',
	),
	
	'hide_products_if_not_in_stock' => array(
		'value' => 1,
		'control_type' => 'checkbox',
		'title' => _wp('Hide products if not in stock')
	),
	
	'bundle_groups' => array(
		'value' => 'custom',
		'control_type' => 'select',
		'title' => _wp('Bundle groups'),
		'options' => array(
			array(
				'title' => _wp('Create custom bundle groups'),
				'value' => 'custom'
			),
			array(
				'title' => _wp('Use main category of product as bundle group'),
				'value' => 'main_category'
			),
		)
	),
	'form_selector' => array(
		'value' => '#cart-form',
		'control_type' => 'input',
		'title' => _wp('jQuery Form selector'),
	),
	'quantity_selector' => array(
		'value' => 'input[name=quantity]',
		'control_type' => 'input',
		'title' => _wp('jQuery Quantity selector'),
		'description' => _wp('Quantity of primary product will be got from value of this selector (by default 1 if doesn\'t exists or null)')
	),
	'quantity_plus_minus_selector' => array(
		'value' => '.buttons .plus, .buttons .minus',
		'control_type' => 'input',
		'title' => _wp('jQuery Quantity Plus/Minus selector')
	),
	'sku_selector' => array(
		'value' => 'input[name=sku_id]',
		'control_type' => 'input',
		'title' => _wp('jQuery Sku selector'),
		'description' => _wp('Bundle price will change after every "change" event and take product price from "data-price" attribute')
	),
	'sku_type_selector' => array(
		'value' => '.sku-feature',
		'control_type' => 'input',
		'title' => _wp('jQuery Sku "Features Selectable" selector (date-feature-id + value)'),
	),
	'services_selector' => array(
		'value' => 'input[name="services[]"]',
		'control_type' => 'input',
		'title' => _wp('jQuery Services selector'),
		'description' => _wp('Plugin will filter all :checked elements and add their "data-price" attributes to total price')
	)
);