<?php

class shopBundling
{
	public static function getSkuFeaturesSelectable($product)
	{
		if($product->sku_type == shopProductModel::SKU_TYPE_SELECTABLE) {
            $features_selectable = $product->features_selectable;

            $product_features_model = new shopProductFeaturesModel();
            $sku_features = $product_features_model->getSkuFeatures($product->id);

            $sku_selectable = array();
            foreach ($sku_features as $sku_id => $sf) {
                if (!isset($product->skus[$sku_id])) {
                    continue;
                }
                $sku_f = "";
                foreach ($features_selectable as $f_id => $f) {
                    if (isset($sf[$f_id])) {
                        $sku_f .= "|".$f_id.":".$sf[$f_id];
                    }
                }
				$sku_f = substr($sku_f, 1);
                $sku = $product->skus[$sku_id];
                $sku_selectable[$sku_f] = array(
                    'id'        => $sku_id,
                    'price'     => (float)shop_currency($sku['price'], $product['currency'], null, false),
					'available' => $product->status && $sku['available'] && (wa()->getConfig()->getGeneralSettings('ignore_stock_count') || $sku['count'] === null || $sku['count'] > 0),
					'image_id'  => (int)$sku['image_id']
                );
                if ($sku['compare_price']) {
                    $sku_selectable[$sku_f]['compare_price'] = (float)shop_currency($sku['compare_price'], $product['currency'], null, false);
                }
            }

            return ifset($sku_selectable);
        } else
			return null;
	}
	
	public static function getBundling($p, $type, $inline = true)
	{
		$plugin = wa('shop')->getPlugin('bundling');
		
		if($plugin->getSettings('on')) {
			if(gettype($p) == 'int')
				$p = new shopProduct($p);
					
			$view = wa()->getView();

			$bundles = $plugin->getBundles($p['id'], $plugin->getSettings('hide_products_if_not_in_stock'), $type, $plugin->getSettings('discounts'), $plugin->getSettings('bundle_groups'));
			
			if($bundles) {
				$form_selector = $plugin->getSettings('form_selector');
				$quantity_selector = $plugin->getSettings('quantity_selector');
				$quantity_plus_minus_selector = $plugin->getSettings('quantity_plus_minus_selector');
				$sku_selector = $plugin->getSettings('sku_selector');
				$sku_type_selector = $plugin->getSettings('sku_type_selector');
				$services_selector = $plugin->getSettings('services_selector');
				$product_selector = $plugin->getSettings('product_selector');
				$product_selector = $product_selector ? $product_selector : $p['frontend_price'];
				$selected_products = '#bundling-selected-products';
				
				$sku_type = $p->sku_type == shopProductModel::SKU_TYPE_SELECTABLE ? 1 : 0;
				$sku_features_selectable = self::getSkuFeaturesSelectable($p);

				$plugin_url = $plugin->getPluginStaticUrl();
				$frontend_url = wa()->getRouteUrl('shop/frontend');
				$frontend_currency = wa('shop')->getConfig()->getCurrency(false);
				$format = wa_currency_html(0, $frontend_currency);
				$template = '<script src="' . wa()->getAppStaticUrl('shop/plugins/bundling/js') . 'bundling.frontend.js?' . $plugin->getVersion() . '" type="text/javascript"></script><textarea id="bundling-selected-products" style="display: none;"></textarea><div class="bundling bundling-' . $type . '">' . $plugin->getSettings('template_' . $type) . '</div>';
				$template .= '<script src="' . wa()->getAppStaticUrl('shop/plugins/bundling/js') . 'bundling.frontend.' . $type . '.js?' . $plugin->getVersion() . '" type="text/javascript"></script>';
				
				$event_data = array(
					'product' => $p,
					'bundles' => $bundles
				);
				wa('shop')->event('bundling_products', $event_data);
				$bundles = $event_data['bundles'];
				
				$products = array();
				foreach($bundles as $bundle) {
					$bundle_products = array();
					
					foreach($bundle['products'] as $product) {
						$bundle_products[$product['id'] . '-' . $product['sku_id']] = array(
							'compare_price' => floatval($product['compare_price']),
							'default_frontend_price' => floatval($product['default_frontend_price']),
							'frontend_price' => floatval($product['frontend_price']),
							'frontend_url' => $product['frontend_url'],
							'name' => $product['name'],
							'title' => $product['title'],
							'badge' => $product['badge'],
							'image' => $product['image'],
							'quantity' => $product['quantity'],
							'bundled_product_event' => ifset($product, 'bundled_product_event', array()),
							'params' => $product['params']
						);
					}

					$products = array_merge($products, $bundle_products);
				}

				$products_json = json_encode($products);

				if($inline)
					$template .= $plugin->includeCss();
				
				$template .= <<<JS
		<script type="text/javascript">
		(function($) {
			$.bundlingFrontend.yourBundleImageSize = '{\$your_bundle_image_size}';
			$.bundlingFrontend.init({
				plugin_url: '{\$plugin_url}',
				frontend_url: '{$frontend_url}',
				price: '{$product_selector}',
				excludeOriginal: {\$exclude_original},
				currency: '{$frontend_currency}',
				format: '{$format}',
				type: '{$type}',
				formSelector: '{$form_selector}',
				submitAction: {\$submit_action},
				quantitySelector: '{$quantity_selector}',
				quantityPlusMinusSelector: '{$quantity_plus_minus_selector}',
				skuSelector: '{$sku_selector}',
				skuTypeSelector: '{$sku_type_selector}',
				skuType: {$sku_type},
				skuFeaturesSelectable: {\$_sku_features_selectable_json},
				servicesSelector: '{$services_selector}',
				selectedProducts: '{$selected_products}',
				products: {\$products_json}
			});
		})(jQuery);
		</script>
JS;
				$view->assign('template_show_image', $plugin->getSettings('select_show_image'));
				$view->assign('template_buy_button', $plugin->getSettings('configurator_buy_button'));
				$view->assign('template_quantity', $plugin->getSettings('configurator_quantity'));
				$view->assign('bundles', $bundles);
				$view->assign('currency', $frontend_currency);
				$view->assign('your_bundle_image_size', $plugin->getSettings('your_bundle_image_size'));
				$view->assign('products_json', $products_json);
				$view->assign('_sku_features_selectable_json', json_encode($sku_features_selectable));
				$view->assign('plugin_url', $plugin_url);
				$view->assign('exclude_original', 'false');
				$view->assign('submit_action', "'submit'");
				
				$event_data = array(
					'product' => $p,
					'view' => &$view,
					'template' => &$template,
					'bundles' => &$bundles
				);
				wa('shop')->event('bundling_view', $event_data);

				return $view->fetch('string:' . $template);
			}
		}
	}
	
	public static function getYourBundle($product)
	{
		$plugin = wa('shop')->getPlugin('bundling');
		
		if($plugin->getSettings('on')) {
			if(gettype($product) == 'int')
				$product = new shopProduct($product);

			$bundles = $plugin->getBundles($product['id'], $plugin->getSettings('hide_products_if_not_in_stock'), null, true, $plugin->getSettings('bundle_groups'));
			if($bundles) {
				$template = '<div class="bundling-your">' . $plugin->getSettings('template_your_bundle') . '</div>';
				$frontend_currency = wa('shop')->getConfig()->getCurrency(false);
				
				$product_images = $product->getImages(array(
					'200x0',
					'200x200',
					'96x96',
					'48x48'
				));
						
				foreach($product_images as $id => $image) {
					$product_image = array(
						'thumb' => $image['url_0'],
						'square' => $image['url_1'],
						'crop' => $image['url_2'],
						'crop_small' => $image['url_3']
					);
							
					if(empty($product['skus'][$product['sku_id']]['image_id']) || (!empty($product['skus'][$product['sku_id']]['image_id']) && $product['skus'][$product['sku_id']]['image_id'] == $id))
						break;
				}
				
				$your_bundle_image_size = $plugin->getSettings('your_bundle_image_size');
				$product_image = ifset($product_image[$your_bundle_image_size]);
				
				$view = wa()->getView();
				$view->assign('currency', $frontend_currency);
				$view->assign('product_image', $product_image);
				return $view->fetch('string:' . $template);
			}
		}
	}
}