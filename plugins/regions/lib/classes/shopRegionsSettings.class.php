<?php

/**
 * Class shopRegionsSettings
 * @property bool $auto_select_city_enable
 * @property bool $hide_category_visibility_block
 * @property bool $hide_storefronts_links
 * @property bool $ip_analyzer_enable
 * @property bool $ip_analyzer_show
 * @property bool $window_group_by_letter_enable
 * @property bool $window_popular_enable
 * @property bool $window_regions_sidebar_enable
 * @property bool $window_search_enable
 * @property string $button_html
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property string $window_columns
 * @property string $window_css
 * @property string $window_header
 * @property string $window_sort
 * @property string $window_subheader
 * @property string $ip_city_confirm_window_header_template
 */
class shopRegionsSettings
{
	private static $settings = null;
	private static $params = null;
	private static $active_page_urls = array();

	private static $settings_model = null;
	private static $page_template_model = null;
	private static $page_template_excluded_storefront_model = null;

	private $bool_setting_names = array(
		'ip_analyzer_enable' => 1,
		'ip_analyzer_show' => 1,
		'auto_select_city_enable' => 1,
		'hide_storefronts_links' => 1,
		'hide_category_visibility_block' => 1,
		'window_group_by_letter_enable' => 1,
		'window_regions_sidebar_enable' => 1,
		'window_popular_enable' => 1,
		'window_search_enable' => 1,
	);

	public function __construct()
	{
		if (self::$settings === null)
		{
			self::$settings_model = new shopRegionsSettingsModel();
			self::$page_template_model = new shopRegionsPageTemplateModel();
			self::$page_template_excluded_storefront_model = new shopRegionsPageTemplateExcludedStorefrontModel();

			self::$settings = $this->defaultSettings();

			foreach (self::$settings_model->getAll('name') as $name => $row)
			{
				self::$settings[$name] = isset($this->bool_setting_names[$name])
					? $row['value'] == '1'
					: $row['value'];
			}
		}
	}

	function __get($name)
	{
		return ifset(self::$settings[$name], '');
	}

	function __set($name, $value)
	{
		$data = array(
			'name' => $name,
			'value' => $value,
		);

		if (isset($this->bool_setting_names[$name]))
		{
			$data['value'] = !!$value ? '1' : '0';
		}

		if (self::$settings_model->insert($data, waModel::INSERT_ON_DUPLICATE_KEY_UPDATE) !== false)
		{
			self::$settings[$name] = isset($this->bool_setting_names[$name])
				? !!$value
				: $value;
		}
	}

	public function getParams()
	{
		if (self::$params === null)
		{
			$city_param_model = new shopRegionsParamModel();
			self::$params = $city_param_model->order('`sort` ASC')->fetchAll();
		}

		return self::$params;
	}

	public function get($name = null)
	{
		return $name === null
			? self::$settings
			: $this->$name;
	}

	public function update($settings)
	{
		foreach ($this->bool_setting_names as $bool_option_name => $_)
		{
			$this->$bool_option_name = false;
		}

		foreach ($settings as $name => $value)
		{
			$this->$name = $value;
		}
	}

	public function getPageTemplate($url)
	{
		$row = self::$page_template_model->getByField('url', is_string($url) ? trim($url) : $url);

		return $row
			? $row['content']
			: null;
	}

	public function getPageTemplates()
	{
		$page_template_excluded_storefront_model = new shopRegionsPageTemplateExcludedStorefrontModel();
		$page_templates = array();

		foreach (self::$page_template_model->select('*')->query() as $template)
		{
			$page_url = $template['url'];

			$template['ignore_default'] = $template['ignore_default'] == shopRegionsPageTemplateModel::IGNORE_DEFAULT;
			$template['excluded_storefronts'] = array();

			$query = $page_template_excluded_storefront_model
				->select('storefront')
				->where('page_url = :page_url', array('page_url' => $page_url))
				->query();
			foreach ($query as $row)
			{
				$template['excluded_storefronts'][$row['storefront']] = true;
			}

			$page_templates[$page_url] = $template;
		}

		return $page_templates;
	}

	public function updatePageTemplates(array $page_templates)
	{
		$excluded_storefronts_data = array();

		$existing_urls = self::$page_template_model->select('url')->fetchAll('url');

		foreach ($page_templates as $url => $page_attributes)
		{
			$excluded_storefronts_data[$url] = $page_attributes['excluded_storefronts'];
			unset($existing_urls[$url]);

			$page_template_data = array(
				'url' => $url,
				'content' => $page_attributes['content'],
				'ignore_default' => $page_attributes['ignore_default'],
			);

			self::$page_template_model->insert($page_template_data, waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
		}

		self::$page_template_model->deleteByField('url', array_keys($existing_urls));
		self::$page_template_excluded_storefront_model->save($excluded_storefronts_data);
	}

	public function getActivePageUrls($storefront)
	{
		if (!array_key_exists($storefront, self::$active_page_urls))
		{
			$excluded_page_url_rows = self::$page_template_excluded_storefront_model
				->select('page_url')
				->where('storefront = :storefront', array('storefront' => $storefront))
				->fetchAll('page_url');

			$excluded_page_urls = array_keys($excluded_page_url_rows);

			$where = array(
				'`content` <> \'\''
			);
			$params = array();
			if (count($excluded_page_urls))
			{
				$where[] = '`url` NOT IN (s:excluded_urls)';
				$params['excluded_urls'] = $excluded_page_urls;
			}

			$sql = '
	SELECT `url`, `ignore_default`
	FROM `' . self::$page_template_model->getTableName() . '`
	';

			$sql .= 'WHERE ' . implode(' AND ', $where);

			self::$active_page_urls[$storefront] = self::$page_template_model->query($sql, $params)->fetchAll();
		}

		return self::$active_page_urls[$storefront];
	}

	public function getWindowStyleUrl()
	{
		$custom_path = $this->getCustomWindowStylePath();

		return file_exists($custom_path)
			? wa()->getDataUrl('plugins/regions/window.css', true, 'shop')
			: wa()->getAppStaticUrl('shop') . 'plugins/regions/css/window.css';
	}

	public function getCustomWindowStylePath($create = false)
	{
		return wa('shop')->getDataPath('plugins/regions/', true, 'shop', $create) . 'window.css';
	}

	public function getWindowStylePath()
	{
		return wa('shop')->getAppPath('plugins/regions/css/window.css', 'shop');
	}

	public function getWindowCssContent()
	{
		$plugin_path = $this->getWindowStylePath();
		$data_path = $this->getCustomWindowStylePath();

		return file_exists($data_path) && strlen(trim($style = file_get_contents($data_path)))
			? $style
			: file_get_contents($plugin_path);
	}

	private function defaultSettings()
	{
		return array(
			'auto_select_city_enable' => true,
			'hide_category_visibility_block' => true,
			'hide_storefronts_links' => true,
			'ip_analyzer_enable' => true,
			'ip_analyzer_show' => true,
			'window_group_by_letter_enable' => true,
			'window_popular_enable' => false,
			'window_regions_sidebar_enable' => false,
			'window_search_enable' => true,
			'button_html' => '
<div class="shop-regions-button">
  	Ваш регион:
  	<a class="shop-regions__link shop-regions-button__link shop-regions__link_pseudo shop-regions__trigger-show-window">{$region.name}</a>
</div>
',
			'meta_title' => '{$title}',
			'meta_description' => '{$meta_description}',
			'meta_keywords' => '{$meta_keywords}',
			'window_columns' => 3,
			'window_header' => 'Укажите свой город',
			'window_sort' => 'name',
			'window_subheader' => 'От этого зависит стоимость доставки и варианты оплаты в ваш регион',
			'ip_city_confirm_window_header_template' => '{$ip_city.name} ваш город?',
		);
	}
}