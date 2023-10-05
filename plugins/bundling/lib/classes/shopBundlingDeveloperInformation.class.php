<?php
class shopBundlingDeveloperInformation {
	public static function init($plugin_name, $plugin_info, $controls, $colors = array()) {
		if(!empty($plugin_info)) {
			$plugin_info['description'] = _wp($plugin_info['description']);
			$plugin_static_url = wa()->getAppStaticUrl('shop/plugins/' . $plugin_name);
			$plugin_icon = ifset($plugin_info['icon']['64']) ? ($plugin_static_url . $plugin_info['icon']['64']) : 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAEUklEQVR4nO2aQWgVRxiAP1/DUyTII8hDgkRJSw+23nIU8VA8FsnJg4hITyLFgxRpU0VykJJDDyKePHgqIiKllFLaIm2VSIME1AhttCqiIUp4UI2tTU16+HfJvHlvZ2b3zeyusB/8hze7/87//29n/pl/FioqKioqKt4ENgFjwDfARqW9EbWdBAYLsCsXTgOvgJVIxpRrR5X2JeBc7tblwCyrTq4ALeBGJAvatfmCbAzKMdqdNMmpgmwMxiAwg3sAfge2FmGoT04Dz4AJ4BbuzscyC4wDc1jmhDVBzO+NjcAjYJ3hngfAVWAZ2AEMG+79D9gCPPFkX3DUWV2XJeAQUFPurwEHac8SupzMyXYvXCDZkY8NegcNel8HtNc7G4AjSDpTnXgM9Bn0asA9TWchelYjoL3BuEa7M5ccdM5rOjdsCjXbDQWynEGnzP44MwB8gqRB9d+cwz4E7ms6LeA40Axor3dMk+BRg94hg963Ae3tygjwKzCtySSSt00cIdmR10gQ1DehD8kOSwa9Tz34lIrLBmN+sugOAIsG/Xg4XIrkseXeV8hWOle+Mxg06aA/gaS0MWQmT7sUnol07wNnPPnUxiAwCvQnXM8SgPXAHmBIa2+SLgi3CFwY2cbqImWa9urMBmAfnbOx/voeoH1x0kACE8/cI1qfxw3P0yXodlh1PpbpqP1L4HkKQxeBs8D7wJR2TQ/CQ+36AjLJXutiT7CCSDfnQ4oahAnaJ0Z1Vj+stP+NbKW90yRf59UgxHNCA0mRF+kcdl8hiye13SsfBHDOVfaEcioNdeAK+Ts/hWSHUtAP/Ey+zgffxqYtiQ0hKc5l1/UU+BG4iyxh30aGkmuO3g7cTmlfcL7A/s/NA/vpvnOrAXuxL19XkBRZKtYjs7LJ6Bnc/uEmnblfl0VKVskZxWzwM2Bziuc1sb8JB/yYno0R4AdkiTqJlKpNxn6UoY+9lmfOKf1fwb6V9sr3FuNUWUBSZVpquM0HsbjsJFMbkEQah34B/s3Q/zKSKVwxHZZkwlcR8c8edO95siETZaiivlVk574C8G4Puu94siETpgC8TPGcnWQbn33I6tCVFxn6MGJ6/W4ixcSHyHIWpGDZjbXICvC3lP3viySJB8D1qP/bwGcUeMr7IeYU1cJ8TK0ziL3GYApO7tTpPK3RZRa3IGzG/tXHc5KLroUxjn2x0kLKVN3mhDqyYrQFcgWpMQYn7XZ4K5K3XbLHX8gC6Y/o9zCwC/cNznvAnXTmhaWBfQfnU/SSe6GsI1/n1SCUYh7YTf7OxzKag39WBug8nMhD5inR97/DJAfhdUK7iyTpziOHMaWiWxCmkOwwjlt6i6WF1BiH6Kw2l9L5GDUIk7SntTqyYtQ/blblETKu1Xp/P6vnDqV2PmYA2cQkbYCyHI/Xo2e+Ud/zJNHrBxK5EaogYtpKe9/S9oLps7NeOIEMD/1c7x/g80B9VlRUVFRUpOR/Ii5EJmoMCOwAAAAASUVORK5CYII=';
			
			if(!$colors)
				$colors = array('#fff 0%', '#d6ffd1 160px');
			
			$_locale = array(
				_wp('developed in'),
				_wp('Shevsky Lab'),
				_wp('Support')
			);
			$_locale_domain = wa()->getLocale() == 'en_US' ? 'com' : 'ru';
			
			array_unshift($controls, <<<HTML
<div class="shevskyInformation">
<h4 class="version">ver. {$plugin_info['version']}</h4>
<div class="description">{$plugin_info['description']}</div>
<div class="copy gray">&copy; 2017, {$_locale[0]} {$_locale[1]}</div>
<div>{$_locale[2]} &mdash; <a class="inline-link" href="https://lab.shevsky.com"><b><i>lab.shevsky.com</i></b></a></div>
</div>
<style type="text/css">
	#wa-plugins-container {
		border-top-color: #ccc;
	}
	#wa-plugins-content {
		background: linear-gradient(to bottom, {$colors[0]}, {$colors[1]}, #fff 0%);
		padding-top: 18px;
	}
	#wa-plugins-content:before {
		content: " ";
		width: 52px;
		height: 52px;
		display: block;
		position: absolute;
		/* opacity: 0.2; */
		background: url('{$plugin_icon}');
		background-size: 52px;
		background-repeat: no-repeat;
	}
	#wa-plugins-content h1, .shevskyInformation .version {
		margin-left: 68px;
	}
	
	.shevskyInformation {
		margin-bottom: 30px;
	}
	
	.shevskyInformation .version {
		margin-top: -3px;
		margin-bottom: 8px;
	}
	
	.shevskyInformation .copy {
		margin: 10px 0;
	}
	
	.shevskyInformation a .icon16.new-window {
		background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAgCAYAAAAbifjMAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAIGNIUk0AAHolAACAgwAA+f8AAIDpAAB1MAAA6mAAADqYAAAXb5JfxUYAAAC5SURBVHja1JRBDoQgDEWB9DZyF9dcULZ6l96ns8EJwX6owUycJqxon78/+L2IuJkKbrKmAYQujpy7u60p+S6gNCH4WIHWrEFpJPscaEHQxDWly1eOnOE6wbICGjYB6mENRHcMVEtEvmffNkFV7lx7nn+JJtlV+f//G1+cBy7GvrvM4zxwzAhuz4O6WYPSUPY50IKgicxX6THCdYJpBeSFCVAPKyC6ZeAoD2RZYB6Uux/kgUn2q/LgMwCHiacu4bwoNAAAAABJRU5ErkJggg==');
	}
	
	.shevskyInformation a:hover i.new-window {
		background-position: 0 -16px;
	}
</style>
HTML
			);
		}
		
		return $controls;
	}
}
