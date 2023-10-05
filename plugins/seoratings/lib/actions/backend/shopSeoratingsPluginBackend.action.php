<?php

class shopSeoratingsPluginBackendAction extends waViewAction
{
  /**
   * @var shopSeoratingsTemplatesModel
   */
  protected $templatesModel;
  /**
   * @var shopFeatureModel
   */
  protected $featureModel;
  /**
   * @var shopSeoratingsRatingModel
   */
  protected $ratingModel;
  /**
   * @var shopSetModel
   */
  protected $setsModel;
  /**
   * @var shopCategoryModel
   */
  protected $categoryModel;

  public function __construct($params = null)
  {
    parent::__construct($params);
    $this->plugin = wa('shop')->getPlugin('seoratings');
    $this->templatesModel = new shopSeoratingsTemplatesModel();
    $this->featureModel = new shopFeatureModel();
    $this->ratingModel = new shopSeoratingsRatingModel();
    $this->setsModel = new shopSetModel();
    $this->categoryModel = new shopCategoryModel();
  }

  public function execute()
  {
    $seoratingsTemplates = $this->templatesModel->getAllTemplates();
    $features = $this->featureModel->getAll();
    $sets = $this->setsModel->getAll();

    $this->view->assign('categories', $this->categoryModel->getFullTree('id, name'));
    $this->view->assign('features', $features);
    $this->view->assign('sets', $sets);
    $this->view->assign('templates', $this->plugin->getJsTemplates(__DIR__ . '/../../../js/backend/templates'));
    $this->view->assign('frontend_urls', $this->getFrontendUrls());
    $this->view->assign('seoratingsTemplates', $seoratingsTemplates);
    $template_content = file_get_contents(wa()->getAppPath('plugins/seoratings/templates/actions/frontend/templates/standard.html'));
    $this->view->assign('standardHtml', $template_content);
    $this->view->assign('standardCss', file_get_contents(wa()->getAppPath('plugins/seoratings/css/frontend/standard.css')));
  }

  private function getCategoryTree()
  {
    $cats = $this->categoryModel->getTree(0, null, false, null);
    $stack = [];
    $result = [];
    foreach ($cats as $c) {
      $c = [
        'id' => $c['id'],
        'depth' => $c['depth'],
        'name' => $c['name'],
        'children' => []
      ];

      // Number of stack items
      $l = count($stack);

      // Check if we're dealing with different levels
      while ($l > 0 && $stack[$l - 1]['depth'] >= $c['depth']) {
        array_pop($stack);
        $l--;
      }

      // Stack is empty (we are inspecting the root)
      if ($l == 0) {
        // Assigning the root node
        $i = count($result);
        $result[$i] = $c;
        $stack[] = &$result[$i];
      } else {
        // Add node to parent
        $i = count($stack[$l - 1]['children']);
        $stack[$l - 1]['children'][$i] = $c;
        $stack[] = &$stack[$l - 1]['children'][$i];
      }
    }

    return $result;
  }

  protected function getFrontendUrls()
  {
    $plugin = wa('shop')->getPlugin(shopSeoratingsPlugin::PLUGIN_NAME);
    $slug = rtrim($plugin->getSlug($plugin->getSettings()), '/') . '/';
    $frontend_urls = [];
    $routing = wa()->getRouting();
    $domain_routes = $routing->getByApp($this->getAppId());

    foreach ($domain_routes as $domain => $routes) {
      foreach ($routes as $r) {
        if (!empty($r['private'])) {
          continue;
        }
        $routing->setRoute($r, $domain);
        $frontend_url = $routing->getUrl('/frontend', [], true);
        $frontend_urls[] = [
          'url' => $frontend_url . $slug,
        ];
      }
    }

    return $frontend_urls;
  }
}
