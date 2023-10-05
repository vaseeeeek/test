<?php

class shopSeoratingsPluginBackendTemplatesActions extends shopSeoratingsBackendRestController
{
  /**
   * @var shopSeoratingsTemplatesModel
   */
  protected $model;

  /**
   * @var shopSeoratingsRatingModel
   */
  protected $ratingModel;

  public function __construct()
  {
    $this->model = new shopSeoratingsTemplatesModel();
    $this->ratingModel = new shopSeoratingsRatingModel();
  }

  public function defaultAction()
  {
    $ratingId = waRequest::request('id', 0, waRequest::TYPE_INT);
    $templates = $this->model->getAllTemplates();
    $rating = $this->ratingModel->getById($ratingId);

    $this->response = [
      'templates' => $templates,
      'rating' => $rating
    ];
  }

  public function getRatingTemplate()
  {
    $ratingId = waRequest::request('id');
    $templates = $this->model->getAllTemplates();
    $rating = $this->ratingModel->getById($ratingId);

    $this->response = [
      'templates' => $templates,
      'rating' => $rating
    ];
  }

  public function getOneAction()
  {
    $template_id = waRequest::request('id', 'standard');
    $template = $this->model->getById($template_id);

    if ($template === null) {
      $css = file_get_contents(wa()->getAppPath("plugins/seoratings/css/frontend/{$template_id}.css", 'shop'));
      $html = file_get_contents(wa()->getAppPath("plugins/seoratings/templates/actions/frontend/templates/{$template_id}.html", 'shop'));
    } else {
      $css = $template['css'];
      $html = $template['html'];
    }

    $this->response = [
      'template_id' => $template_id,
      'html' => $html,
      'css' => $css
    ];
  }

  protected function fetchData()
  {
    return json_decode(waRequest::post('model'), true);
  }
}
