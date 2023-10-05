<?php

class shopTageditorPluginFrontendTagController extends waViewController
{
    public function execute()
    {
        try {
            if (waRequest::param('collection') && waRequest::param('value')) {
                if (wa('shop')->getPlugin('tageditor')->getSettings('custom_cloud_urls') == 'subcollection') {
                    if (!in_array(waRequest::param('collection'), array('category', 'search', 'tag'))) {
                        throw new Exception();
                    }
                } else {
                    throw new Exception();
                }
            }

            $tag = shopTageditorPlugin::tag();

            if (!$tag) {
                throw new Exception();
            }

            $do_redirect = (bool) (int) wa('shop')->getPlugin('tageditor')->getSettings('redirect');

            if ($do_redirect && !empty($tag['url']) && $tag['url'] != waRequest::param('url')) {
                //redirect to custom URL, if available
                $this->redirect(wa()->getRouteUrl('shop/frontend/tag', array('tag' => $tag['url'])), 301);
            }

            $whitespace_redirect = wa('shop')->getPlugin('tageditor')->getSettings('whitespace_redirect');
            if ($whitespace_redirect) {
                $tag_url = wa()->getRouteUrl('shop/frontend/tag', array('tag' => ifset($tag['url'], $tag['name'])));

                switch ($whitespace_redirect) {
                    case 'whitespace':
                        if (strpos(rawurldecode(waRequest::server('REQUEST_URI')), ' ')) {
                            $this->redirect(str_replace(' ', '+', $tag_url), 301);
                        }
                        break;
                    case 'plus':
                        if (strpos(rawurldecode(waRequest::server('REQUEST_URI')), '+')) {
                            $this->redirect(str_replace('+', ' ', $tag_url), 301);
                        }
                        break;
                }
            }

            //apply user-defined sort order
            $sort = ifempty($tag['sort_products'], wa('shop')->getPlugin('tageditor')->getSettings('sort_products'));
            if (strpos($sort, ' ')) {
                list($field, $direction) = explode(' ', $sort);
                if ($field && $direction) {
                    $_GET += array(
                        'sort'  => $field,
                        'order' => $direction,
                    );
                }
            }

            waRequest::setParam('tag', $tag['name']);
            $this->executeAction(new shopTageditorPluginFrontendTagAction());
        } catch (Exception $e) {
            $this->executeAction(new shopFrontendAction());    //error 404
        }
    }
}
