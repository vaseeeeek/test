<?php

class shopSeoratingsPluginFrontendViewSingleRatingAction extends shopFrontendAction
{
  /**
   * @var string Current url.
   */
  protected $currentUrl;

  /**
   * @var shopSeoratingsPlugin Plugin instance.
   */
  protected $plugin;

  public function __construct($params = null)
  {
    parent::__construct($params);
    $this->plugin = wa('shop')->getPlugin('seoratings');
    $this->currentUrl = trim(waSystem::getInstance()->getRouting()->getCurrentUrl(), '/');
  }

  protected function preExecute()
  {
  }

  /**
   * @throws waDbException
   * @throws waException
   * @throws SmartyException
   */
  public function execute()
  {
    $url = $this->getRatingUrl();

    $seoratingsRatingModel = new shopSeoratingsRatingModel();
    $rating = $seoratingsRatingModel->getByField('url', $url);
    
    if (!$rating) {
      throw new waException('', 404);
    }
    
    $seoratingsRatingProductsModel = new shopSeoratingsRatingProductsModel();

    if ($rating['type'] === 'set') {
      $products = $seoratingsRatingProductsModel->getProductsFromSet($rating['set_id'], $rating['reverse']);
    } else {
      $products = $seoratingsRatingProductsModel->getRatingProducts($rating['id'], $rating['reverse']);
    }
    
    $response = $this->getResponse();

    if (!$rating['published']) {
      $response->setMeta('seoratings_robots', 'noindex,nofollow');
    }

    if (!empty($rating['meta_title'])) {
      $response->setTitle($rating['meta_title']);
    } else {
      $response->setTitle($rating['title']);
    }
    $response->setMeta('description', $rating['meta_description']);
    $response->setMeta('keywords', $rating['meta_keywords']);

    $html = shopSeoratingsPlugin::display($rating, $products, null, [
      'templates' => $this->plugin->getJsTemplates(__DIR__ . '/../../../js/frontend/templates'),
    ]);

    $this->view->assign('plugin_settings', $this->plugin->getSettings());
    $this->view->assign('rating', $rating);
    $this->view->assign('html', $html);
    $this->view->assign('theme_id', waRequest::getTheme());
  }

  protected function getUrlParts()
  {
    $slug = substr($this->currentUrl, 0, strpos($this->currentUrl, '/'));
    $url = substr($this->currentUrl, strpos($this->currentUrl, '/') + 1);

    return [
      'slug' => $slug,
      'url' => $url,
    ];
  }

  protected function getRatingUrl()
  {
    return $this->getUrlParts()['url'];
  }
}
