<?php

class shopSeoratingsTemplatesModel extends waModel
{
  protected $table = 'shop_seoratings_templates';
  public $reserved = [
    'standard',
    'grid',
    'table',
  ];

  protected $boolean_fields = [
    'developer',
  ];

  public function getAllTemplates($key = null, $normalize = false)
  {
    return $this->prepareResult(parent::getAll($key, $normalize));
  }

  public function getDeveloperTemplates()
  {
    return $this->prepareResult(parent::getByField('developer', 1, true) ?: []);
  }

  public function getByTemplateName($template)
  {
    return $this->getByField('name', $template);
  }

  protected function prepareResult(array $result)
  {
    $result = array_map(function ($item) {
      foreach ($item as $key => $value) {
        if (in_array($key, $this->boolean_fields)) {
          $item[$key] = boolval($value);
        }
      }

      return $item;
    }, $result);

    return $result;
  }
}