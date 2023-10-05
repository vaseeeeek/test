<?php

class shopRegionsBackendHideProductsStorefrontLinksHandlerAction implements shopRegionsIHandlerAction
{
	public function execute($handler_params)
	{
		$settings = new shopRegionsSettings();
		if (!$settings->hide_storefronts_links && !$settings->hide_category_visibility_block)
		{
			return array();
		}

		$product_url = null;
		if ($handler_params instanceof shopProduct)
		{
			$routing = wa()->getRouting();
			$domain = waSystemConfig::getActive()->getDomain();
			$routes = $routing->getRoutes($domain);

			$current_route = null;
			foreach ($routes as $route)
			{
				if (is_array($route) && array_key_exists('app', $route) && $route['app'] == 'shop')
				{
					$current_route = $route;

					break;
				}
			}

			if (is_array($current_route))
			{
				$product_url = wa()->getRouteUrl(
					'shop/frontend/product',
					array('product_url' => $handler_params->url,),
					true,
					$domain,
					$current_route['url']
				);
			}
		}

		$html = '
<script>
	(function() {
		var product_url = ' . json_encode($product_url) . ';
		
		showMainLink(\'.s-inline-mixed-string #s-category-frontend-link\');
		showMainLink(\'#s-product-frontend-links .s-product-frontend-url-not-empty a\', product_url);
		
		function showMainLink(selector, product_url)
		{
			if (product_url) {
				var $link = $(selector).first();
				
				$link.addClass(\'shown_link\');
				$link
					.attr(\'href\', product_url)
					.text(product_url);

				return;
			}
			
			console.log(selector, product_url);

			$($(selector).toArray().sort(function(a, b) {
				var a_l = ($(a).attr(\'href\') || \'\').length;
				var b_l = ($(b).attr(\'href\') || \'\').length;
				
				if (a_l == b_l)
				{
					return 0;
				}
				
				return a_l - b_l;
			})[0]).addClass(\'shown_link\');
		}
	})();
</script>
';

		return array(
			'title_suffix' => $html,
		);
	}
}