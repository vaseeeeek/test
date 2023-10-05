<?php

class shevskySettingsControlsV7
{
	public static $default_charset = 'utf-8';
	public static $plugin_name;
	public static $class;
	
	public function __construct($plugin_name = '')
	{
		if($plugin_name)
			self::$plugin_name = $plugin_name;
		self::$class = get_class();
		
		$this->controls = self::parseAvailableControls();
		
		foreach($this->controls as $control_name => $function) {
			waHtmlControl::registerControl($control_name, array($this, $function));
		}
	}
	
	public static function getAvailableControls()
	{
		return array(
			'tabs', 'menu', 'datetime', 'html', 'includes', 'editor', 'textarea', 'text', 'email', 'helper', 'configLoader', 'url'
		);
	}
	
	public static function parseAvailableControls()
	{
		$controls = self::getAvailableControls();
		$parsed = array();
		foreach($controls as $control_name)
			$parsed['ShevskyV7' . ucfirst($control_name)] = 'get' . ucfirst($control_name) . 'Control';
		return $parsed;
	}
	
	public static function getPath()
	{
		return str_replace(waRequest::server('DOCUMENT_ROOT'), '', str_replace('\\','/', __FILE__));
	}
	
	public static function setPlugin($name)
	{
		if(preg_match('/^shop_([a-z0-9_]+)\[/', $name, $matches)) {
			self::$plugin_name = $matches[1];
		}
	}
	
	public static function getPluginName()
	{
		if(!self::$plugin_name) {
			preg_match('/wa-apps\/shop\/plugins\/([a-z0-9_]+)/i', self::getPath(), $matches);
			self::$plugin_name = end($matches);
			return self::$plugin_name;
		} else
			return self::$plugin_name;
	}
	
	public static function getPluginClassName()
	{
		return 'shop' . ucfirst(self::getPluginName()) . 'Plugin';
	}
	
	public static function getPluginPath()
	{
		return wa()->getAppPath('plugins/' . self::getPluginName(), 'shop');
	}
	
	public static function getPluginSettings()
	{
		$settings = include(self::getPluginPath() . '/lib/config/settings.php');
		return $settings;
	}
	
	public static function getSettingsDefaultValue($key)
	{
		$settings = self::getPluginSettings();
		if(!empty($settings[$key]['value']))
			return $settings[$key]['value'];
		else
			return false;
	}
	
	public static function getKeyByName($name)
	{
		preg_match("/shop_" . self::getPluginName() . "\[([a-z_]+)\]/", $name, $matches);
		$key = $matches[1];
		return $key;
	}
	
	public static function _addCustomParams($list, $params = array())
	{
		$params_string = '';
		foreach ($list as $param => $target) {
			if (is_int($param)) {
				$param = $target;
			}
			if (isset($params[$param])) {
				$param_value = $params[$param];
				if (is_array($param_value)) {
					if (array_filter($param_value, 'is_array')) {
						$param_value = json_encode($param_value);
					} else {
						$param_value = implode(' ', $param_value);
					}
				}
				if ($param_value !== false) {
					if (in_array($param, array('title', 'description', 'placeholder'))) {
						$param_value = self::_wp($param_value, $params);
					} elseif (in_array($param, array('disabled', 'readonly'))) {
						$param_value = $param;
					}
					$param_value = htmlentities((string) $param_value, ENT_QUOTES, self::$default_charset);
					if (in_array($param, array('autofocus'))) {
						$params_string .= " {$target}";
					} else {
						$params_string .= " {$target}=\"{$param_value}\"";
					}
				}
			}
		}
		return $params_string;
	}
	
	public static function controlsMatchesJQuery($matches)
	{
		return '#plugins-settings-form > .field:eq(' . ((int) $matches[0] - 1) . ')';
	}
	
	public static function controlsMatchesCSS($matches)
	{
		return '#plugins-settings-form > .field:nth-of-type(' . ((int) $matches[0]) . ') { display: none; }';
	}
	
	public static function getControls($controls, $type = 'jquery') {
		return preg_replace_callback('/([0-9]+)/', self::$class . '::' . ($type == 'jquery' ? 'controlsMatchesJQuery' : 'controlsMatchesCSS'), implode($type == 'jquery' ? ',' : ' ', ifset($controls, array())));
	}
	
	public static function getControlPositions($controls, $plus = 0)
	{
		$settings = self::getPluginSettings();

		$control_positions = array();
		foreach($controls as $control_name) {
			if(isset($settings[$control_name])) {
				array_push($control_positions, array_search($control_name, array_keys($settings)) + 1 + $plus);
			}
		}

		return $control_positions;
	}
	
	public static function getTabsControl($name, $params = array())
	{
		self::setPlugin($name);
		
		if(empty($params['save'])) $params['save'] = false;
		if(empty($params['value'])) $params['value'] = 0;
		if(empty($params['plus'])) $params['plus'] = 0;
		
		$control_name = htmlentities($name, ENT_QUOTES, self::$default_charset);
		
		if(!empty($params['options'])) {
			if(empty($params['style']))
				$params['style'] = '';
			
			$control = "</div> <ul style=\"margin-left: 180px;{$params['style']}\" id=\"{$params['id']}\" class=\"tabs\">";
			
			$controls = array();
			$hidden_controls = array();
			foreach($params['options'] as $key => $value) {
				$selected = $key == $params['value'] ? "selected" : "";
				$classes = !empty($value['class']) ? ($value['class'] . ' ' . $selected) : $selected;
				
				$value['controls'] = self::getControlPositions($value['controls'], $params['plus']);
				$_controls = implode(',', $value['controls']);
				$controls = array_merge($controls, $value['controls']);
				
				if(!$selected)
					$hidden_controls = array_merge($hidden_controls, $value['controls']);
				
				if(empty($value['hide']))
					$control .= '<li class="' . $classes . '"><a data-controls="' . $_controls . '" data-id="' . $key . '" href="#">' . $value['name'] . '</a></li>';
			}
			$control .= '</ul>';
			
			$control .= '<style type="text/css">';
			$control .= self::getControls($hidden_controls, 'css');
			$control .= '</style>';
			$controls = self::getControls($controls, 'jquery');
			
			$control .= <<<HTML
				<script type="text/javascript">
				(function() {
					var shevskySettingsControlsTabsControls = "{$controls}";
					$('#{$params['id']} a').click(function(e) {
						controls = this.dataset.controls.split(',');
						id = this.dataset.id;
						
						$(shevskySettingsControlsTabsControls).hide();
						for(key in controls) {
							control = controls[key]-1;
							$("#plugins-settings-form > .field:eq(" + control + ")").each(function() {
								if(($(this).hasClass('menu') && !$(this).hasClass('hidden')) || !$(this).hasClass('menu'))
									$(this).show();
							});
						}
						
						$('#{$params['id']} li').removeClass('selected');
						$(this).parent().addClass('selected');
						$('#{$params['id']}_value').val(id);
						e.preventDefault();
					});
				})();
				</script>
HTML;
			$control .= '<div style="margin-bottom: 10px;" class="tab-content">';
			return $control . ($params['save'] ? "<input id=\"{$params['id']}_value\" type=\"hidden\" name=\"{$control_name}\" value=\"{$params['value']}\">" : "");
		} else
			throw new waException('Не задан параметр options, в котором должно храниться содержимое табов');
	}
	
	public static function getDatetimeControl($name, $params = array())
	{
		$control_name = htmlentities($name, ENT_QUOTES, self::$default_charset);
		
		$control = "<input type=\"text\" class=\"numerical\" name=\"{$control_name}\" value=\"{$params['value']}\"> <a href=\"javascript:void(0)\"><i class=\"icon16 calendar\"></i></a>";
		$control .= <<<HTML
		<script type="text/javascript">
			var datetime_{$params['id']} = $('input[name="{$control_name}"]');
			datetime_{$params['id']}.datepicker({
				'dateFormat': 'yy-mm-dd'
			});
			datetime_{$params['id']}.next('a').click(function() {
				$('input[name="{$control_name}"]').datepicker('show');
			});
			datetime_{$params['id']}.datepicker('widget').hide();
		</script>
HTML;
		return $control;
	}
	
	public static function getHtmlControl($name, $params = array())
	{
		return $params['value'];
	}
	
	public static function getIncludesControl($name, $params = array())
	{
		$control = '';
		if(!empty($params['includes']))
			foreach($params['includes'] as $key => $value) {
				$value['path'] = wa()->getRootUrl(true) . $value['path'];
				if($value['type'] == 'js') $control .= "<script type=\"text/javascript\" src=\"{$value['path']}\"></script>";
				elseif($value['type'] == 'css') $control .= "<link rel=\"stylesheet\" href=\"{$value['path']}\" type=\"text/css\">";
			}
		return $control;
	}
	
	public static function getVariableDescription($key, $description_key)
	{		
		switch($description_key) {
			case 'waContact':
				return "Возвращает массив с данными пользователя из соответствующей записи приложения «Контакты». Используйте функцию <a class=\"code\" href=\"http://www.webasyst.ru/developers/docs/basics/classes/waContact#method-get\" target=\"_blank\">get()</a> на эту переменную для получения данных о пользователе. Например, <code>{\${$key}->get('name')}</code> вернет имя контакта, а<br/><code>{\${$key}->get('email', 'value')}</code> позволит вставить его Email-адрес.";
				break;
		}
	}
	
	public static function initVariables($control, $variables)
	{
		$control .= '<p style="margin: 10px 0;">' . (ifset($variables['_locale'], 'Доступные переменные')) . ':</p>';
		if(isset($variables['_locale']))
			unset($variables['_locale']);
		
		$control .= '<table style="margin-bottom: 0;">';
		foreach($variables as $key => $description) {
			if($key != '_locale') {
				if(in_array($description, array('waContact')))
					$description = self::getVariableDescription($key, $description);
				
				$control .= '<tr class="shevskySettingsControls-variable">';
				$control .= '<td class="key" valign="top"><a href="#">{$' . $key . '}</a></td><td class="description"><span class="hint">' . (gettype($description) == 'string' ? $description : $description['description']) . '</span></td>';
				$control .= '</tr>';
				
				if(gettype($description) == 'array')
					foreach($description['values'] as $value_key => $value_description) {
						$control .= '<tr class="shevskySettingsControls-variable value">';
						$control .= '<td class="key" valign="top"><a href="#">{$' . $key . '<span>[\'' . $value_key . '\']</span>}</a></td><td class="description"><span class="hint">' . $value_description . '</span></td>';
						$control .= '</tr>';
					}
			}
		}
		$control .= '</table>';
		
		$control .= <<<HTML
<script type="text/javascript">
$('.shevskySettingsControls-variable .key a, .shevskySettingsControls-variable .description code').click(function(e) {
	if(document.createRange) {
		var rng = document.createRange();
		rng.selectNode(this);
		var sel = window.getSelection();
		sel.removeAllRanges();
		sel.addRange(rng);
	} else {
		var rng = document.body.createTextRange();
		rng.moveToElementText(this);
		rng.select();
	}
	
	e.preventDefault();
});
</script>
<style type="text/css">
	.shevskySettingsControls-variable {
		line-height: 1.5em;
	}
	.shevskySettingsControls-variable .key {
		padding-right: 20px;
	}
	.shevskySettingsControls-variable .key a {
		font-weight: bold;
		display: inline-block;
	}
	.shevskySettingsControls-variable .description {
		max-width: 550px;
	}
	.shevskySettingsControls-variable .description code, .shevskySettingsControls-variable .description a.code {
		background: #eee;
		padding: 3px;
		color: #222;
	}
	.shevskySettingsControls-variable .description a.code {
		font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', 'source-code-pro', monospace;
	}
	
	.shevskySettingsControls-variable.value .key {
		padding-left: 10px;
	}
	.shevskySettingsControls-variable.value .key a {
		color: #aaa;
		font-size: 0.8em;
	}
	.shevskySettingsControls-variable.value .key a span {
		color: #03c;
	}
	.shevskySettingsControls-variable.value .key a:hover span {
		color: #ff0000;
	}
</style>
HTML;
		
		return $control;
	}
	
	public static function initReturnDefaultLink($control, $control_name, $settings, $editor = false, $variables = false)
	{
		$plugin_name = self::getPluginName();
		$key = self::getKeyByName($control_name);
		$default_value = json_encode(self::getSettingsDefaultValue($key));
		$string = gettype($settings) == 'string' ? $settings : 'Вернуть значение по умолчанию';
		$control .= '<div style="' . ($editor ? ($variables ? 'position: absolute;' : '') .'width: 100%; text-align: right;' : '') . 'padding-top: 5px;" class="hint"><a href="#" id="shevskySettingsControls-return-default-' . $key . '">' . $string . '</a></div>';
		$editor = $editor ? 'true' : 'false';
		$control .= <<<HTML
<script type="text/javascript">
(function($) {
	var default_value = {$default_value};
	
	$('#shevskySettingsControls-return-default-{$key}').click(function(e) {
		if(confirm('{$string}?')) {
			if({$editor})
				ace.edit('shevskySettingsControls-{$plugin_name}_shop_{$plugin_name}_{$key}-body-container').getSession().setValue(default_value);
			else
				$('#{$plugin_name}_shop_{$plugin_name}_{$key}').val(default_value).focus();
		} e.preventDefault();
	});
})(jQuery);
</script>
HTML;
		return $control;
	}
	
    public static function getTextareaControl($name, $params = array())
    {
		$control = '';
		$control_name = htmlentities($name, ENT_QUOTES, self::$default_charset);
		$value = htmlentities((string) $params['value'], ENT_QUOTES, self::$default_charset);
		$control .= "<textarea name=\"{$control_name}\"";
		$control .= self::_addCustomParams(array('class', 'style', 'cols', 'rows', 'wrap', 'id', 'placeholder', 'readonly', 'autofocus', 'disabled'), $params);
        $control .= ">{$value}</textarea>";

		$editor = !empty($params['wysiwyg']) || !empty($params['editor']) || !empty($params['wisywig']);
		
		if($editor && !isset($params['return_default_link']))
			$params['return_default_link'] = true;
		if(!empty($params['return_default_link']))
			$control = self::initReturnDefaultLink($control, $control_name, $params['return_default_link'], $editor, !empty($params['variables']));
		
		if(!empty($params['variables']))
			$control = self::initVariables($control, $params['variables']);
		
		if($editor) {
			$path = wa('shop')->getAppPath('plugins/' . self::getPluginName());
			$url = wa('shop')->getAppStaticUrl() . 'plugins/' . self::getPluginName();
			$script = '/' . ifset($params['script'], 'js/' . self::$class .  '.js');
			
			if(file_exists($path . $script)) {
				$control .= <<<HTML
<script type="text/javascript">
(function($) {
	$.getScript('{$url}{$script}')
	.done(function(script, textStatus) {
		shevskySettingsControls.editor($('#{$params['id']}'));
	})
	.fail(function(jqxhr, settings, waException) {
		console.log('Не удалось подключить {$script}');
	});
})(jQuery);
</script>
HTML;
			}
		}
		
        return $control;
    }
	
	public static function getEditorControl($name, $params = array())
	{
		$params['editor'] = true;
		return self::getTextareaControl($name, $params);
	}
	
	public static function getEmailControl($name, $params = array())
	{
		$params['type'] = 'email';
		return self::getTextControl($name, $params);
	}
	
	public static function getTextControl($name, $params = array())
	{
		if(empty($params['type']))
			$params['type'] = 'text';
		
        $control = '';
        $control_name = htmlentities($name, ENT_QUOTES, self::$default_charset);
        $control .= "<input id=\"{$params['id']}\" type=\"{$params['type']}\" name=\"{$control_name}\" ";

		if(!empty($params['default'])) {
			if(preg_match('/^[\w]+::[\w]+$/', $params['default'])) {
				$default_params = explode('::', $params['default']);
				if(empty($params['value']) || $params['value'] == $params['default'])
					$params['value'] = call_user_func_array(array(in_array($default_params[0], array('self', 'this', 'plugin')) ? self::getPluginClassName() : $default_params[0], $default_params[1]), array());
			} else
				if(empty($params['value']) || $params['value'] == $params['default'])
					$params['value'] = $params['default'];
		}

		$control .= self::_addCustomParams(array('class', 'style', 'size', 'maxlength', 'value', 'placeholder', 'readonly', 'required', 'autofocus', 'disabled', 'autocomplete'), $params);
		
        $control .= ">";
        return $control;
	}
	
	public static function getMenuControls($options)
	{
		$controls = array();
		foreach($options as $option) {
			$controls = array_merge($controls, $option['controls']);
		}
		return $controls;
	}
	
	public static function getMenuControl($name, $params = array())
	{
		self::setPlugin($name);
		
		if(empty($params['save'])) $params['save'] = false;
		if(empty($params['value'])) $params['value'] = 0;
		if(empty($params['plus'])) $params['plus'] = 0;
		
		$control_name = htmlentities($name, ENT_QUOTES, self::$default_charset);
		
		if(!empty($params['options'])) {
			$control = (!empty($params['hint']) ? ($params['hint'] . ' ') : '') . '<ul style="display: inline-block;" id="' . $params['id'] . '" class="menu-h with-icons">';
			
			$hidden_controls = array();
			foreach($params['options'] as $key => $value) {
				$selected = $key == $params['value'] ? "selected" : "";
				$classes = !empty($value['class']) ? ($value['class'] . ' ' . $selected) : $selected;
				$value['controls'] = self::getControlPositions($value['controls'], $params['plus']);
				
				$_controls = implode(',', $value['controls']);
				
				if(!$selected)
					$hidden_controls = array_merge($hidden_controls, $value['controls']);
				
				$control .= '<li class="' . $classes . '"><a data-controls="' . $_controls . '" data-id="' . $key . '" href="#">' . $value['name'] . '</a></li>';

			}
			$control .= '</ul>';
			
			$control .= '<style type="text/css">';
			$control .= self::getControls($hidden_controls, 'css');
			$control .= '</style>';
			$params['controls'] = self::getControlPositions(self::getMenuControls($params['options']), $params['plus']);
			$controls = self::getControls($params['controls'], 'jquery');
			$hidden_controls = self::getControls($hidden_controls, 'jquery');
			
			$control .= <<<HTML
			<script type="text/javascript">
				(function() {
					var shevskySettingsControlsMenuControls = "{$controls}";
					$(shevskySettingsControlsMenuControls).addClass('menu');
					var shevskySettingsControlsMenuHiddenControls = "{$hidden_controls}";
					$(shevskySettingsControlsMenuHiddenControls).addClass('hidden');
					
					$('#{$params['id']} a').click(function(e) {
						controls = this.dataset.controls.split(',');
						id = this.dataset.id;
						
						$(shevskySettingsControlsMenuControls).addClass('hidden').hide();
						for(key in controls) {
							control = controls[key]-1;
							$("#plugins-settings-form > .field:eq(" + control + ")").removeClass('hidden').show();
						}
						
						$('#{$params['id']} li').removeClass('selected');
						$(this).parent().addClass('selected');
						$('#{$params['id']}_value').val(id);
						e.preventDefault();
					});
				})();
			</script>
HTML;
			
			return $control . ($params['save'] ? "<input id=\"{$params['id']}_value\" type=\"hidden\" name=\"{$control_name}\" value=\"{$params['value']}\">" : "");
		} else
			throw new waException('Не задан параметр options, в котором должно храниться содержимое меню');
	}
	
	public static function getHelperControl($name, $params = array())
	{
		if(empty($params['helper']))
			throw new waException('Не задан параметр helper, в котором должен содержаться код хелпера');

		if(!function_exists('parseHelper')) {
			function parseHelper($matches) {
				switch($matches[1]) {
					case '&':
						$options = explode(',', $matches[4]);
						$html = '<select class="shevskySettingsControls-helper-select">';
						foreach($options as $option)
							$html .= "<option>{$option}</option>";
						$html .= '</select><div style="display: inline-block; width: 1px; height: 1px; overflow: hidden; opacity: 0;">\'' . $options[0] . '\'</div>';
						break;
					case '?':
						$data = array(
							'false',
							'true'
						);
						if(!empty($matches[5])) {
							$data = explode('|', htmlentities($matches[5]));
						}
						$html = '<span data-true="' . $data[1] . '" data-false="' . $data[0] . '" class="shevskySettingsControls-helper-bool" style="color: green; cursor: pointer;">' . $data[1] . '</span>';
						break;
				}
				
				return $html;
			}
		}
        
		$params['helper'] = preg_replace_callback('/(&|\?)\$([a-zA-Z0-9_]+)(?::(\[([a-z,]+)\]|\'([^\']+)\'))?/i', 'parseHelper', $params['helper']);
		
		if(empty($params['string_use_helper']))
			$params['string_use_helper'] = 'Используйте хелпер ';
		if(empty($params['string_for']))
			$params['string_for'] = ' для установки шаблона плагина в любом месте.';
		
		return <<<HTML
		<div class="shevskySettingsControls-helper">{$params['string_use_helper']}<b>{{$params['helper']}}</b>{$params['string_for']}</div>
		<script type="text/javascript">
			$('.shevskySettingsControls-helper-select').change(function() {
				$(this).parent().find('div').html('\'' + $('option:selected', this).html() + '\'');
			});
		
			$('.shevskySettingsControls-helper b').click(function() {
				if(document.createRange) {
					var rng = document.createRange();
					rng.selectNode(this);
					var sel = window.getSelection();
					sel.removeAllRanges();
					sel.addRange(rng);
				} else {
					var rng = document.body.createTextRange();
					rng.moveToElementText(this);
					rng.select();
				}
			});
			$('.shevskySettingsControls-helper-bool').click(function() {
				$(this).text($(this).text() == $(this).data('true') ? $(this).data('false') : $(this).data('true'));
				$(this).css('color', $(this).text() == $(this).data('true') ? 'green' : 'red');
				return false;
			});
		</script>
		<style type="text/css">
			.shevskySettingsControls-helper b {
				background: #eee;
				padding: 3px;
				color: #222;
				font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', 'source-code-pro', monospace;
			}
		</style>
HTML;
	}

	public static function getConfigLoaderControl($name, $params = array())
	{
		$control_name = str_replace(array('[', ']', ''), array('-', ''), preg_replace('/shop_([a-z0-9_]+)\[(.*)\]/i', '$2', htmlentities($name, ENT_QUOTES, self::$default_charset)));
		
		if(empty($params['string_load_config']))
			$params['string_load_config'] = 'Подгрузить настройки для темы дизайна';
		
		$values = $params['options'];
		$select = '<select><option value="">-</option>';
		$html_values = '';
		foreach($values as $id => $data) {
			$select .= "<option value=\"{$id}\">{$data['title']}</option>";
			foreach($data['values'] as $field => $value) {
				if(gettype($value) == 'array')
					$value = implode(',', $value);
				$html_values .= '<textarea style="display: none;" class="config ' . htmlspecialchars($id) . '" data-field="' . htmlspecialchars($field, ENT_QUOTES, 'utf-8') . '">' . htmlspecialchars($value) . '</textarea>';
			}
		}
		$select .= '</select>';
		
		$plugin_name = self::getPluginName();
		
		return <<<HTML
		<div class="sheskySettingsControls-config-loader" id="sheskySettingsControls-config-loader-{$control_name}">
			{$params['string_load_config']} {$select}
			<a style="display: none; margin-left: 5px;" href="#" class="button blue">Подгрузить</a>
			<div style="display: none;" class="shevskySettingsControls-arrow"></div>
			{$html_values}
		</div>
		<script type="text/javascript">
			(function($) {
				var wrapper = '#sheskySettingsControls-config-loader-{$control_name}';
				
				$('select', wrapper).change(function() {
					$('a', wrapper)[!$(this).val() ? 'hide' : 'show']();
					$('.shevskySettingsControls-arrow', wrapper).css('display', !$(this).val() ? 'none' : 'inline-block');
				});
				$('a', wrapper).click(function(e) {
					var id = $('select', wrapper).val();
					if(id != '') {
						$('textarea.config.' + id, wrapper).each(function() {
							var field = $(this).data('field');
							var value = $(this).val();
							var elem = $('[name^="shop_{$plugin_name}[' + field + ']"]', '.fields');
							switch(elem.get(0).tagName.toLowerCase()) {
								case 'textarea':
									if(elem.hasClass('ShevskyEditor'))
										ace.edit('shevskySettingsControls-{$plugin_name}_shop_{$plugin_name}_' + field + '-body-container').getSession().setValue(value);
									else
										$('#{$plugin_name}_shop_{$plugin_name}_' + field).val(value).focus();
									break;
								case 'input':
									switch(elem.attr('type')) {
										case 'radio':
											elem.filter('[value="' + value + '"]').prop('checked', true);
											break;
										case 'checkbox':
											elem.filter(':checked').prop('checked', false);
											values = value.split(',');
											for(key in values)
												elem.filter('[value="' + values[key] + '"]').prop('checked', true);
											break;
										case 'text':
											$(elem).val(value);
											break;
									}
									break;
								case 'select':
									$(elem).val(value);
									break;
							};
						});
						
						$('select', wrapper).val('');
						$('a', wrapper).hide();
						$('.shevskySettingsControls-arrow', wrapper).hide();
					}
							
					e.preventDefault();
				});
			})(jQuery);
		</script>
		<style type="text/css">
			.shevskySettingsControls-arrow {
				background: url('data:image/gif;base64,R0lGODlhHwATALMJAP39/f7+/kxMTAMDA/z8/Pv7+wUFBZmZmQAAAP///wAAAAAAAAAAAAAAAAAAAAAAACH/C05FVFNDQVBFMi4wAwEAAAAh/wtYTVAgRGF0YVhNUDw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMy1jMDExIDY2LjE0NTY2MSwgMjAxMi8wMi8wNi0xNDo1NjoyNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNiAoV2luZG93cykiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6MjI3RDg0MDE1NDY0MTFFNjg4QzNGOTREMjkzOUIxNUMiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6MjI3RDg0MDI1NDY0MTFFNjg4QzNGOTREMjkzOUIxNUMiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDoyMjdEODNGRjU0NjQxMUU2ODhDM0Y5NEQyOTM5QjE1QyIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDoyMjdEODQwMDU0NjQxMUU2ODhDM0Y5NEQyOTM5QjE1QyIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PgH//v38+/r5+Pf29fTz8vHw7+7t7Ovq6ejn5uXk4+Lh4N/e3dzb2tnY19bV1NPS0dDPzs3My8rJyMfGxcTDwsHAv769vLu6ubi3trW0s7KxsK+urayrqqmop6alpKOioaCfnp2cm5qZmJeWlZSTkpGQj46NjIuKiYiHhoWEg4KBgH9+fXx7enl4d3Z1dHNycXBvbm1sa2ppaGdmZWRjYmFgX15dXFtaWVhXVlVUU1JRUE9OTUxLSklIR0ZFRENCQUA/Pj08Ozo5ODc2NTQzMjEwLy4tLCsqKSgnJiUkIyIhIB8eHRwbGhkYFxYVFBMSERAPDg0MCwoJCAcGBQQDAgEAACH5BAkUAAkALAAAAAAfABMAAARUMMlJq0Q2a43w/lwHjlQneCTYHWL6IcORdHRto6E83zxura7dBhhs/RC6oq+EDAqHTZcxd+ndPoGdDGHoer/daSa7EjsngN3prEnT2BrCE24pzEgRACH5BAkKAAkALAAAAAAfABMAAARUMMlJa0I2a43w/lwHjlQneCTYHWL6IcNxdXRtq4g823wb6imfZeWSCEu5ohE1TBaPFaILGnX2rp/ATIYweL9gL5WiXY2VgNlJuUnT2BvCDL4pXFIRACH5BAkKAAkALAAAAAAfABMAAARTMMlJK6o464nu/lrngaTUCWP5dUengshwJGJtizAy03efYqwXbxMUujJF44+SVB1DOmVu5+t9ArwZwsDterlPDZYVFlYAPJR5g8atNYThW1OgvSIAIfkECQoACQAsAAAAAB8AEwAABFQwyUkpqjjrie7+WueBpNQJY/l1R6eCyHAkYm2LMDLTd59irBdvExS6MkXjz6ITDonN1xG68/U+Ad4MYeh6v92pJssSOykAHuqsSePYGcITjinQhBEAIfkECRQACQAsAAAAAB8AEwAABFQwyUknqjhre7fXSPiNVCh05BceYuohw5GEdG2jICLPd49jKxdvExS2gDqh5IjcuZhN4y/K89k8AZ4MYeh6v10oJrsSKyUA3umsSdPYGsIQninMlBEAOw==');
				position: absolute;
				margin-left: 5px;
				width: 31px;
				height: 19px;
				opacity: 0.5;
				display: inline-block;
			}
		</style>
HTML;
	}

	public static function getUrlControl($name, $params = array())
	{
		$route = wa()->getRouteUrl('shop/frontend', array(), true);
		preg_match('/http(s?):\/\/(.*)/', $route, $url_matches);
		
		$domain = implode('</b> / <b>', explode('/', $url_matches[2]));
		$control = '';
		$control_name = htmlentities($name, ENT_QUOTES, self::$default_charset);
		
		if(!empty($params['value']))
			$value = htmlentities((string) $params['value'], ENT_QUOTES, self::$default_charset);
		else
			$value = '';
		$control .= '<span class="shevskySettingsControls-url"><b>' . $domain . '</b></span>';
		$control .= "<input data-dir=\"{$route}\" id=\"{$params['id']}\" type=\"text\" name=\"{$control_name}\" ";
		if($value)
			$control .= " value=\"{$value}\"";
		$control .= ">";
		$control .= "<br/> <span class=\"hint\"><a target=\"_blank\" href=\"" . $route . $value . "\">Открыть страницу</a></span>";
		$control .= <<<JS
<script type="text/javascript">
	$('#plugins-settings-form').on('submit', function() {
		var urlInput = $('#{$params['id']}');
		var urlValue = urlInput.val().replace(/[^0-9a-zA-Z_-]/g, '');
		var link = urlInput.parent().find('a');
		urlInput.val(urlValue)
		link.attr('href', urlInput.data('dir') + (urlValue ? urlValue : 'shipping'));
	});
</script>	
JS;
		return $control;
	}
}
