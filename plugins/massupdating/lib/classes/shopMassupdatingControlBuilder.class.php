<?php

class shopMassupdatingControlBuilder
{
	public function escape($string)
	{
		return htmlentities($string, ENT_QUOTES, 'UTF-8');
	}
	
	public function build($id, $feature, $default_value = false, $differ_values = false)
	{
		$code = $this->escape($feature['code']);
		$name = 'features[' . $code . ']';
		$placeholder = ($differ_values ? _wp('Значения этой характеристики у выбранных товаров отличаются.') : '');

		if($feature['selectable']) {
			if($feature['multiple']) {
				$control = '<input type="hidden" name="' . $name . '[]"/><div class="massupdating-switcher massupdating-feature-action">';
				$control .= '<a href="#" data-action="add" data-code="' . $code . '" class="selected">' . _wp('Добавить к текущим') . '</a>';
				$control .= '<a href="#" data-action="replace" data-code="' . $code . '">' . _wp('Заменить на выбранные значения') . '</a>';
				$control .= '<input type="hidden" name="feature_action[' . $code . ']" value="add" id="massupdating-feature-action-' . $code . '"/>';
				$control .= '</div> <div id="massupdating-features-multiple">';
				
				foreach($feature['values'] as $value_id => $value) {
					$control .= '<div style="margin-bottom: 5px;"><label><input class="groupbox checkbox" type="checkbox" name="' . $name . '[]" value="' . $this->escape($value) . '" ' . (!empty($default_value[$value_id]) ? ' checked' : '') . '/> ';
					if(is_object($value) && isset($value['icon'])) $control .= $value['icon'];
					$control .= $this->escape($value) . '</label></div>';
				}
				
				$control .= '</div>';
				if($feature['type'] == 'varchar' || $feature['type'] == 'double')$control .= '<a href="javascript: $.massupdating.addFeatureMultipleValue(\'' . $code . '\');" class="massupdating-features-add-value hint inline-link"><b><i>' . _wp('добавить значение') . '</i></b></a>';
								
				$control .= '<script type="text/javascript">(function() { $(\'.massupdating-feature-action a\').click(function(e) { $(\'#massupdating-feature-action-\' + $(this).data(\'code\')).val($(this).data(\'action\')); $(\'.massupdating-feature-action a\').removeClass(\'selected\'); $(this).addClass(\'selected\'); e.preventDefault(); }) })();</script>';
			} else {
				$control = '<select id="massupdating-feature-' . $code . '" name="' . $name . '">';
				$control .= '<option value=""></option>';
				foreach($feature['values'] as $value_id => $value) {
					$control .= '<option value="' . $this->escape($value) . '"' . ($value == $default_value ? ' selected' : '') . '>' . $this->escape($value) . '</option>';
				};
				$control .= '</select>';
				
				$control .= ' <a id="massupdating-features-add-' . $code . '" href="javascript: $.massupdating.addFeatureValue(\'' . $code . '\');" class="massupdating-features-add-value hint inline-link"><b><i>' . _wp('добавить значение') . '</i></b></a>';
			}
		} else {
			if(strpos($feature['type'], '2d') === 0 || strpos($feature['type'], '3d') === 0) {
				$control = '';
				$type = substr($feature['type'], 3);
				if(strpos($type, 'dimension') === 0) {
					$units = shopDimension::getUnits($type);
					$d = intval($feature['type']);
					$feature_unit = null;

					if(!$feature_unit && isset($default_value[$d-1]) && $default_value instanceof shopCompositeValue)
						$feature_unit = $default_value[$d-1]->unit;
					
					for($i = 0; $i < $d; $i++) {
						$code_ = "$code.$i";
						if(!$feature_unit && isset($default_value[$i]) && $default_value instanceof shopCompositeValue)
							$feature_unit = $default_value[$i]->unit;
						if($i)
							$control .= ' × ';
						$control .= '<input type="text" value="' . $this->escape(isset($default_value[$i]) ? $default_value[$i]->convert($feature_unit, false) : '') . '" name="features[' . $code_ . '][value]" class="numerical short">';
					}
					$control .= ' <select name="features[' . $code . '.0][unit]">';
					$control .= '<option value=""></option>';
					foreach($units as $unit) {
						$selected = $feature_unit == $unit['value'];
						$control .= '<option value="' . $this->escape($unit['value']) . '" title="' . $this->escape($unit['title']) . '"' . ($selected ? ' selected' : '') . '>' . $this->escape($unit['title']) . '</option>';
					}
					$control .= '</select>';
				} else {
					for($i=0; $i < intval($feature['type']); $i++) {
						$code_ = "$code.$i";
						if($i)
							$control .= ' × ';
						$control .= '<input type="text" value="' . $this->escape(isset($default_value[$i]) ? $default_value[$i] : '') . '" name="features[' . $code_ . ']" class="numerical short"/>';
					}
				}
			} elseif(strpos($feature['type'], 'range') === 0) {
				$control = '<input type="text" value="' . $this->escape(isset($default_value) && !$default_value->begin->is_null() ? $default_value->begin->value : '') . '" name="' . $name . '[value][begin]" class="numerical short">';
				$control .= ' — ';
				$control .= '<input type="text" value="' . $this->escape(isset($default_value) && !$default_value->end->is_null() ? $default_value->end->value : '') . '" name="' . $name . '[value][end]" class="numerical short"/>';
				
				$units = shopDimension::getUnits($feature['type']);
				if($units) {
					if($default_value instanceof shopRangeValue)
						$feature_unit = $default_value->unit;
					else
						$feature_unit = '';

					$control .= ' <select name="' . $name . '[unit]">';
					$control .= '<option value=""></option>';
					foreach($units as $unit) {
						$selected = $feature_unit == $unit['value'];
						$control .= '<option value="' . $this->escape($unit['value']) . '" title="' . $this->escape($unit['title']) . '"' . ($selected ? ' selected' : '') . '>' . $this->escape($unit['title']) . '</option>';
					}
					$control .= '</select>';
				}
				
			} elseif(strpos($feature['type'], 'dimension') === 0) {
				$units = shopDimension::getUnits($feature['type']);
				if($default_value instanceof shopDimensionValue) {
					$_default_value = $default_value->value;
					$_default_unit = $default_value->unit;
				} else {
					$_default_value = '';
					$_default_unit = '';
				}
				
				$control = '<input type="text" value="' . $this->escape($_default_value) . '" name="' . $name . '[value]">';
				$control .= ' <select name="' . $name . '[unit]">';
				$control .= '<option value=""></option>';
                foreach($units as $unit) {
					$selected = $_default_unit == $unit['value'];
					
					$control .= '<option value="' . $this->escape($unit['value']) . '" title="' . $this->escape($unit['title']) . '"' . ($selected ? ' selected' : '') . '>' . $this->escape($unit['title']) . '</option>';
                }
				$control .= '</select>';
			} elseif($feature['type'] == 'boolean') {
				if(isset($default_value))
					$value = $default_value;
				else
					$value = false;
				
				$control = '<div style="margin-bottom: 5px;"><label><input type="radio" name="' . $name . '" value="1"' . ($value === 1 ? ' checked' : '') . '> ' . _wp('Да') . '</label></div><div style="margin-bottom: 5px;"><label><input type="radio" name="' . $name . '" value="0"' . ($value === 0 ? ' checked' : '') . '> ' . _wp('Нет') . '</label></div><div><label><input type="radio" name="' . $name . '" value=""' . ($value === false ? ' checked' : '') . '> ' . _wp('Не определено') . '</label></div>';
			} elseif($feature['type'] == 'text')
				$control = '<textarea placeholder="' . $placeholder . '" name="' . $name . '">' . $this->escape(ifset($default_value)) . '</textarea>';
			else
				$control = '<input type="text" value="' . $this->escape(ifset($default_value)) . '" placeholder="' . $placeholder . '" name="' . $name . '" data-type="' . $feature['type'] . '">';
		}
		
		if($differ_values)
			$control .= '<div style="margin-top: 5px;" class="hint">' . $placeholder . '</div>';
		
		return $control;
	}
}