<?php

class shopRegionsPluginSettingsAction extends waViewAction
{
	public function execute()
	{
		shopRegionsPlugin::push();

		$plugin = wa('shop')->getPlugin('regions');

		$is_submit = waRequest::post('is_submit', false);

		if (waRequest::isXMLHttpRequest() and $is_submit)
		{
			$this->ajaxSettingSave();
		}

		$settings = new shopRegionsSettings();

		$plugin_static_url = $plugin->getPluginStaticUrl();

		$settings_array = $settings->get();
		$settings_array['window_css'] = $settings->getWindowCssContent();


		$this->view->assign(array(
			'regions_css' => array(
				$plugin_static_url . 'css/general.css',
				$plugin_static_url . 'css/bs_ui.css',
				$plugin_static_url . 'css/helper.css',
				$plugin_static_url . 'css/variable.css',
				$plugin_static_url . 'css/settings.css',
			),
			'regions_js' => array(
				$plugin_static_url . 'js/smartymixed.js',
				$plugin_static_url . 'js/bs_ui.js',
				$plugin_static_url . 'js/settings.js',
				$plugin_static_url . 'js/variable.js',
			),
			'regions_settings' => $settings_array,
			'page_templates' => $settings->getPageTemplates(),
			'params' => $settings->getParams(),
			'pages_urls' => $this->getPagesUrls(),
			'variables_template_path' => shopRegionsPlugin::getVariablesTemplatePath(),
			'plugin_version' => waSystemConfig::isDebug() ? time() : $plugin->getVersion(),
			'region_window_sort_options' => $this->getWindowSortOptions(),
			'storefronts' => shopRegionsRouting::getAllStorefronts(),
		));

		shopRegionsPlugin::pop();
	}

	private function getWindowSortOptions()
	{
		return array(
			array('title' => 'По названию (по алфавиту)', 'value' => shopRegionsCityModel::WINDOW_SORT_BY_NAME),
			array('title' => 'Вручную (как задано в бекенде)', 'value' => shopRegionsCityModel::WINDOW_SORT_CUSTOM),
		);
	}

	private function ajaxSettingSave()
	{
		$settings = new shopRegionsSettings();
		$regions_settings = waRequest::post('regions_settings', array());
		$this->validateSettings($regions_settings);

		$settings->update($regions_settings);
		$this->saveParams();
		$this->saveTemplates();
		$this->saveStyle($regions_settings['window_css']);
	}

	private function saveParams()
	{
		$params = json_decode(waRequest::post('params_json', '[]'), true);
		usort($params, array($this, '_sortParams'));

		$sort = 1;
		foreach ($params as $param_attributes)
		{
			$param_id = $param_attributes['id'];
			$param_name = trim($param_attributes['name']);

			$param = shopRegionsParam::load($param_id);

			if ($param_id > 0 && $param)
			{
				if ($param_attributes['delete'])
				{
					$param->delete();
				}
				else
				{
					$param->setName($param_name);
					$param->setSort($sort++);
					$param->save();
				}
			}
			elseif ($param_id < 0)
			{
				$param = shopRegionsParam::build($param_attributes);
				$param->setSort($sort++);
				$param->save();
			}
		}
	}

	private function _sortParams($p1, $p2)
	{
		if ($p1['sort'] == $p2['sort'])
		{
			return 0;
		}

		return $p1['sort'] < $p2['sort'] ? -1 : 1;
	}

	private function validateSettings(&$settings)
	{
		$settings['window_columns'] = intval(ifset($settings['window_columns']));
		$settings['window_columns'] = $settings['window_columns'] >= 1 ? $settings['window_columns'] : 1;
	}

	private function saveTemplates()
	{
		$input_page_templates = waRequest::post('page_templates', array());

		$page_templates_urls = ifset($input_page_templates['page_url'], array());
		$page_templates_contents = ifset($input_page_templates['page_content'], array());
		$page_templates_ignore_default = ifset($input_page_templates['ignore_default'], array());
		$page_templates_excluded_storefronts_lists = ifset($input_page_templates['excluded_storefronts_list'], array());

		$page_templates = array();
		foreach ($page_templates_urls as $idx => $url)
		{
			if (!strlen($url))
			{
				continue;
			}

			$storefronts_list = trim(ifset($page_templates_excluded_storefronts_lists[$idx], ''));
			$page_templates[$url] = array(
				'content' => ifset($page_templates_contents[$idx]),
				'ignore_default' => ifset($page_templates_ignore_default[$idx], 'Y'),
				'excluded_storefronts' => strlen($storefronts_list) ? explode(',', $storefronts_list) : array(),
			);
		}

		$settings = new shopRegionsSettings();
		$settings->updatePageTemplates($page_templates);
	}

	/**
	 * @return array
	 */
	private function getPagesUrls()
	{
		$page_model = new shopPageModel();
		$pages_urls = array();

		$sql = '
SELECT `full_url`, `name`
FROM ' . $page_model->getTableName() . '
GROUP BY `full_url`
ORDER BY `name` <> \'\', `id`
';

		foreach ($page_model->query($sql) as $page_row)
		{
			$url = $page_row['full_url'];
			$name = trim(ifset($page_row['name'], ''));

			$option_title = $url;
			if (strlen($name))
			{
				$option_title =  "{$name} ({$url})";
			}

			$pages_urls[$url] = $option_title;
		}

		return $pages_urls;
	}

	private function saveStyle($style)
	{
		$settings = new shopRegionsSettings();

		$data_path = $settings->getCustomWindowStylePath();
		$plugin_path = $settings->getWindowStylePath();

		$style = preg_replace('/\r\n/', "\n", trim($style));
		$default_style = file_get_contents($plugin_path);

		if (strlen($style) == 0 || $style == trim($default_style))
		{
			waFiles::delete($data_path, true);
		}
		else
		{
			waFiles::write($data_path, $style);
		}
	}
}