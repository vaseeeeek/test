<?php

class shopBrandTemplateLayoutsCollection
{
	const NONE_PRIORITY = -1;
	const BASIC_PRIORITY = 0;
	const ONLY_EMPTY_BASIC_PRIORITY = 1;
	const OVERWRITE_BASIC_PRIORITY = 2;

	protected $template_layouts = array();

	public function push(shopBrandTemplateLayout $template_layout, $priority_default = self::BASIC_PRIORITY, array $priorities = array())
	{
		foreach ($template_layout->getTemplates() as $name => $template)
		{
			$priorities[$name] = isset($priorities[$name]) ? $priorities[$name] : $priority_default;
		}

		$this->template_layouts[] = array(
			'template_layout' => $template_layout,
			'priorities' => $priorities,
		);
	}

	/**
	 * @return array
	 */
	private function getUpperItems()
	{
		$uppers = array();

		foreach ($this->template_layouts as $layout_data)
		{
			/** @var shopBrandTemplateLayout $template_layout */
			$template_layout = $layout_data['template_layout'];
			foreach ($template_layout->getTemplates() as $field => $template)
			{
				if (!isset($uppers[$field]))
				{
					$uppers[$field] = array(
						'value' => '',
						'priority' => self::NONE_PRIORITY,
					);
				}

				if (!$this->isEmpty($template))
				{
					$priority = $layout_data['priorities'][$field];
					$upper = &$uppers[$field];
					$is_rewrite = false;

					if ($upper['priority'] == self::NONE_PRIORITY)
					{
						$is_rewrite = true;
					}
					elseif ($upper['priority'] == self::BASIC_PRIORITY)
					{
						if ($priority == self::OVERWRITE_BASIC_PRIORITY)
						{
							$is_rewrite = true;
						}
					}

					if ($is_rewrite)
					{
						$upper = array(
							'value' => $template,
							'priority' => $priority,
						);
					}
				}
			}
		}

		unset($upper);
		$result_template_layout_fields = array();

		foreach ($uppers as $field => $upper)
		{
			$result_template_layout_fields[$field] = $upper['value'];
		}

		return $result_template_layout_fields;
	}

	public function mergeTemplateLayouts()
	{
		return new shopBrandTemplateLayout($this->getUpperItems());
	}

	private function isEmpty(&$template)
	{
		return !isset($template) || trim(strip_tags($template, '<img>')) === '';
	}
}