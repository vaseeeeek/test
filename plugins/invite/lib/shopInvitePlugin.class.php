<?php

class shopInvitePlugin extends shopPlugin
{
    /**
     * @var waView $view
     */
    private static $view;
    private static function getView()
    {
        if (!isset(self::$view)) {
            self::$view = waSystem::getInstance()->getView();
        }
        return self::$view;
    }

    /**
     * @var shopInvitePlugin $plugin
     */
    private static $plugin;
    private static function getPlugin()
    {
        if (!isset(self::$plugin)) {
            self::$plugin = wa()->getPlugin('invite');
        }
        return self::$plugin;
    }

    private static $locale;

    public function allowedCurrency()
    {
        return 'RUB';
    }

    public function __construct($info)
    {
        self::$locale = wa()->getLocale();
        parent::__construct($info);
    }


    public static function getFeedbackControl() {
        $plugin = self::getPlugin();
        $view = self::getView();
        $locale = wa()->getLocale();
        return $view->fetch($plugin->getPluginPath() . '/templates/controls/feedbackControl.html');
    }

    public static function getCategoriesControl() {
        $plugin = self::getPlugin();
        $view = self::getView();
        $ccm = new waContactCategoryModel();
        $categories = $ccm->getAll();
        $view->assign('user_categories', $categories);
        $settings = $plugin->getSettings();
        $view->assign('settings', $settings);
        return $view->fetch($plugin->getPluginPath() . '/templates/controls/categoriesControl.html');
    }

    public static function getDomainsControl() {
        wa('site');
        $domains = siteHelper::getDomains();
        $options = array();
        foreach ($domains as $key => $domain) {
            $option = array(
                'title' => $domain,
                'value' => $domain,
            );
            $options[$key] = $option;
        }
        $asm = new waAppSettingsModel();
        $domain = $asm->get(array('shop', 'invite'), 'domain');
        if (empty($domain)) {
            $asm->set(array('shop', 'invite'), 'domain', reset($domains));
        }
        return $options;
    }

    public static function getTemplatesControl() {
        $plugin = self::getPlugin();
        $view = self::getView();

        $templates = self::getTemplates();

        foreach ($templates as $key => $template) {
            $templates[$key]['full_path'] = wa()->getDataPath($templates[$key]['tpl_path'], $templates[$key]['public'], 'shop', true);
            if (file_exists($templates[$key]['full_path'])) {
                $templates[$key]['change_tpl'] = true;
            } else {
                $templates[$key]['full_path'] = wa()->getAppPath($templates[$key]['tpl_path'], 'shop');
                $templates[$key]['change_tpl'] = false;
            }
            $templates[$key]['template'] = file_get_contents($templates[$key]['full_path']);
        }

        $view->assign('templates', $templates);
        return $view->fetch($plugin->getPluginPath() . '/templates/controls/templatesControl.html');
    }

    private static function getTemplates() {
        $templates = array(
            'mail' => array(
                'name' => _wp('Mail notification template (Mail.html)'),
                'tpl_path' => 'plugins/invite/templates/Mail.html',
                'public' => false
            ),
            'deny' => array(
                'name' => _wp('Template refusal to register (Deny.html)'),
                'tpl_path' => 'plugins/invite/templates/Deny.html',
                'public' => false
            ),
        );
        return $templates;
    }

    static public function getInviteFilePath($file_name)
    {
        if (!$file_name) return false;
        $src = '';
        $file_path = wa()->getDataPath('plugins/invite/', true) . $file_name;
        if (file_exists($file_path)) {
            $src = wa()->getDataPath('plugins/invite/', true, 'shop', true) . $file_name;

        }
        return $src;
    }

    static public function getInviteFileUrl($file_name)
    {
        if (!$file_name) return false;
        $src = '';
        $file_path = wa()->getDataPath('plugins/invite/', true) . $file_name;
        if (file_exists($file_path)) {
            $src = wa()->getDataUrl('plugins/invite/', true, 'shop', true) . $file_name;
        }
        return $src;
    }

    public function getPluginPath()
    {
        return $this->path;
    }

    public static function getTabs() {
        $tabs = array(
            'invites' => array(
                'name' => _wp('Invites'),
                'template' => 'Invites.html',
            ),
            'templates' => array(
                'name' => _wp('Templates'),
                'template' => 'Templates.html',
            ),
            'info' => array(
                'name' => _wp('Information'),
                'template' => 'Info.html',
            ),
        );
        return $tabs;
    }

    /**
     * Обработчик хука signup
     *
     * @param waContact $contact
     */
    public function signupHandler($contact)
    {
        $settings = $this->getSettings();
        if ($settings['enable']) {

            $invite_code = $contact->get('invite_code');
            $invitations_model = new shopInvitePluginInvitationsModel();
            $contact_categories_model = new waContactCategoriesModel();

            $domain = wa()->getRouting()->getDomain();
            if (!isset($settings['domains'][$domain])) {
                return false;
            }

            $invite = $invitations_model->getByField(
                array(
                    'code'          => $invite_code,
                    'registered'    => 0,
                )
            );

            if (empty($invite) && $settings['only_invite']) {
                $contact->delete();
                wa()->getResponse()->redirect(wa()->getRouteUrl('shop/frontend') . 'invite/deny');
                return false;
            }

            if (!empty($invite)) {
                $invite['registered'] = 1;
                $invite['contact_id'] = $contact->getId();
                $invitations_model->updateById($invite_code, $invite);
                $contact_categories_model->insert(array('category_id' => $invite['category_id'], 'contact_id' => $contact->getId()), 1);
            }
        }
    }

    public function frontendMy() {
        $settings = $this->getSettings();
        $domain = wa()->getRouting()->getDomain();
        if (!isset($settings['domains'][$domain])) {
            return false;
        }

        $categories = self::getUserCategories();

        if (empty($categories)) {
            return false;
        }

        $view = self::getView();
        $shopUrl = wa()->getRouteUrl('shop/frontend');
        $view->assign('shop_url', $shopUrl);
        return $view->fetch($this->path . '/templates/actions/frontend/my_tab.html');
    }

    public static function getUserCategories() {
        $user = wa()->getUser();
        if (!$user->getId()) return array();

        $plugin = self::getPlugin();
        $settings = $plugin->getSettings();

        $cc = new waContactCategoriesModel();
        $categories = $cc->getContactCategories($user->getId());

        if (empty($categories)) {
            return array();
        }

        $settings_cats = $settings['categories'];

        if (empty($settings_cats)) {
            return array();
        }

        foreach ($categories as $i => $category) {
            if (!array_key_exists($category['id'], $settings_cats)) {
                unset($categories[$i]);
            }
        }
        return $categories;
    }

    public function saveSettings($settings = array())
    {
        $templates = self::getTemplates();

        foreach ($templates as $key => $template) {
            if (!isset($settings[$key])) {
                $settings[$key] = $template['tpl_path'];
            }
            $post_template = $settings[$key];

            if (isset($settings['reset_tpl_'.$key])) {
                $template_path = wa()->getDataPath($template['tpl_path'], $template['public'], 'shop', true);
                @unlink($template_path);
            } else {
                $template_path = wa()->getDataPath($template['tpl_path'], $template['public'], 'shop', true);

                if (!file_exists($template_path)) {
                    $template_path = wa()->getAppPath($template['tpl_path'], 'shop');
                }

                $template_content = file_get_contents($template_path);
                if ($template_content != $post_template) {
                    $template_path = wa()->getDataPath($template['tpl_path'], $template['public'], 'shop', true);

                    $f = fopen($template_path, 'w');
                    if (!$f) {
                        throw new waException('Не удаётся сохранить шаблон. Проверьте права на запись ' . $template_path);
                    }
                    fwrite($f, $post_template);
                    fclose($f);
                }
            }
        }

        wa('site');
        $domains = siteHelper::getDomains();
        $config = waSystem::getInstance()->getConfig();
        $auth = $config->getAuth();
        $locale = wa()->getLocale();
        foreach ($domains as $key => $domain) {
            if (isset($settings['domains'][$domain])) {
                $auth[$domain]['app'] = 'shop';
                $auth[$domain]['fields']['invite_code'] = array (
                    'caption' => $locale == 'ru_RU' ? 'Код приглашения' : 'Invitation code',
                    'placeholder' => '',
                );
                $auth[$domain]['fields']['email'] = array (
                    'caption' => 'Email',
                    'placeholder' => '',
                    'required' => true,
                );
                $auth[$domain]['fields']['password'] = array (
                    'caption' => _wp('Password'),
                    'placeholder' => '',
                    'required' => true,
                );
            }
            else {
                if (isset($auth[$domain]) && isset($auth[$domain]['fields']['invite_code'])) {
                    unset($auth[$domain]['fields']['invite_code']);
                }
            }
        }
        if (!$config->setAuth($auth)) {
            throw new waException(sprintf(_w('File could not be saved due to the insufficient file write permissions for the "%s" folder.'), 'wa-config/'));
        }

        parent::saveSettings($settings);
    }

    public function frontendHead() {
        $settings = $this->getSettings();
        $domain = wa()->getRouting()->getDomain();
        if (isset($settings['domains'][$domain])) {
            $this->addJs('js/invite_head.js');
        }
    }
}
