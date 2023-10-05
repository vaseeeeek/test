<?php

/**
 * Class shopBrandTemplateLayout
 *
 * @property-read string $meta_title
 * @property-read string $meta_description
 * @property-read string $meta_keywords
 * @property-read string $h1
 * @property-read string $description
 * @property-read string $additional_description
 * @property-read string $content
 */
abstract class shopBrandMetaLayout
{
	private $meta_layout = array(
		'meta_title' => '',
		'meta_description' => '',
		'meta_keywords' => '',
		'h1' => '',
		'description' => '',
		'additional_description' => '',
		'content' => '',
	);

	public function __construct(array $template_layout)
	{
		foreach ($template_layout as $field => $template)
		{
			if (array_key_exists($field, $this->meta_layout))
			{
				$this->meta_layout[$field] = $template;
			}

			if($field === 'name' && wa()->getEnv() === 'frontend') {
                $this->meta_layout[$field] = $template;
            }
		}
	}

	public function getTemplates()
	{
		return $this->meta_layout;
	}

	public function __get($name)
	{
		return array_key_exists($name, $this->meta_layout)
			? $this->meta_layout[$name]
			: '';
	}

	abstract public function isFetched();
}