<?php

class shopRegionsBackendHideStorefrontLinksHandlerAction implements shopRegionsIHandlerAction
{
	public function execute($handler_params)
	{
		$html = '';

		$settings = new shopRegionsSettings();

		if ($settings->hide_storefronts_links)
		{
			$html .=
				'
<style>
	.s-product-frontend-url {
		display: none;
	}
	
	.s-product-frontend-url-not-empty + br {
		display: none;
	}

	.s-inline-mixed-string br {
		display: none;
	}
	#s-category-frontend-link {
		display: none !important;
	}

	#s-product-list-settings-form .value.no-shift.small {
		display: none;
	}

	#s-product-frontend-links br {
		display: none;
	}
	#s-product-frontend-links .s-product-frontend-url-not-empty a {
		display: none !important;
	}
	
	#s-product-frontend-links .s-product-frontend-url-not-empty a.shown_link {
		display: inline !important;
	}
	#s-category-frontend-link.shown_link {
		display: inline !important;
	}
	/*#s-product-frontend-links .s-product-frontend-url-not-empty:first-child {
		display: inline-block !important;
	}*/
</style>
';
		}

		if ($settings->hide_category_visibility_block)
		{
			$html .=
				'<style>
					[id^=s-product-category-visibility-block-] {
						 display: none;
					}
					
					#s-product-list-settings-form .s-dialog-form .field-group:first-child .field-group .value.no-shift.small {
						display: none;
					}
				</style>';
		}

		return array(
			'core_li' => $html,
		);
	}
}