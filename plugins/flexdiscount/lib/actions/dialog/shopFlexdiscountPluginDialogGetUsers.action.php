<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginDialogGetUsersAction extends waViewAction
{

    public function execute()
    {
        // Категории контакта
        try {
            // Проверяем наличие плагина Контакты PRO
            wa('contacts')->getPlugin('pro');
            $contact_categories = (new contactsViewModel())->getAllViews(null, true);
            contactsViewModel::setIcons($contact_categories);
        } catch (Exception $ex) {
            $contact_categories = (new waContactCategoryModel())->getAll('id');
            if ($contact_categories) {
                foreach ($contact_categories as &$cc) {
                    $cc['count'] = $cc['cnt'];
                    if ($cc['icon']) {
                        $cc['icon'] = $this->icon16($cc['icon']);
                    }
                }
            }
        }

        // Всего контактов
        $count = (new waContactModel())->countAll();

        $this->view->assign('categories', $contact_categories);
        $this->view->assign('count_all', $count);
        $this->view->assign('plugin_url', shopFlexdiscountApp::get('system')['wa']->getPlugin('flexdiscount')->getPluginStaticUrl());
    }

    private function icon16($url_or_class)
    {
        if (substr($url_or_class, 0, 7) == 'http://') {
            return '<i class="icon16" style="background-image:url(' . htmlspecialchars($url_or_class) . ')"></i>';
        } else {
            return '<i class="icon16 ' . htmlspecialchars($url_or_class) . '"></i>';
        }
    }

}
