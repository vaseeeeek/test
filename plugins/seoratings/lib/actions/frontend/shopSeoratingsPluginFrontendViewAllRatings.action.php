<?php

class shopSeoratingsPluginFrontendViewAllRatingsAction extends shopFrontendAction
{
  protected $currentUrl;
  /**
   * @var shopSeoratingsPlugin
   */
  protected $plugin;
  /**
   * @var shopSeoratingsRatingModel
   */
  protected $ratingsModel;

  /**
   * shopSeoratingsPluginFrontendViewAllRatingsAction constructor.
   *
   * @param null $params
   *
   * @throws waDbException
   * @throws waException
   */
  public function __construct($params = null)
  {
    parent::__construct($params);
    $this->plugin = wa('shop')->getPlugin('seoratings');
    $this->ratingsModel = new shopSeoratingsRatingModel();
  }

  /**
   * @throws waException
   */
  public function execute()
  {
    $settings = $this->plugin->getSettings();
    $response = $this->getResponse();

    if (!empty($settings['ratings_list_meta_title'])) {
      $response->setTitle($settings['ratings_list_meta_title']);
    } else {
      $response->setTitle($settings['breadcrumbs_category_title']);
    }

    $response->setMeta('description', $settings['ratings_list_meta_description']);
    $response->setMeta('keywords', $settings['ratings_list_meta_keywords']);

    $ratings = $this->ratingsModel->getPublishedRatings();
    $this->view->assign('ratings', $ratings);
    $this->view->assign('plugin_settings', $settings);
    $this->view->assign('theme_id', waRequest::getTheme());
  }
}
