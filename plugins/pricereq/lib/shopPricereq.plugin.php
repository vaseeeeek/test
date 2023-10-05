<?php

/*
 * Class shopPricereqPlugin
 * Plugin allows users in the frontend to make requests for a price,
 * backend users can customize the request form and view the requests
 * @author Max Severin <makc.severin@gmail.com>
 */
class shopPricereqPlugin extends shopPlugin {
    
    /**
     * Handler for backend_menu event: add price request button in backend menu tabs
     * @return array
     */
    public function backendMenu() {
        $html = '';

        if ($this->getSettings('status') === 'on') {

            $model = new shopPricereqPluginRequestModel();
            $new_request_count = $model->getNewRequestCount();

            $new_request_html = '';
            if ($new_request_count > 0) {
                $new_request_html = '<sup class="red" style="display:inline">' . $new_request_count . '</sup>';
            }

            $html = '<li ' . (waRequest::get('plugin') == $this->id ? 'class="selected"' : 'class="no-tab"') . '>
                        <a href="?plugin=pricereq">' . _wp('Price request') . $new_request_html . '</a>
                    </li>';
        }

        return array('core_li' => $html);
    }
    
    /**
     * Handler for frontend_head event: add pricereqFrontend module in frontend head section
     * @return string
     */
    public function frontendHead() {
        $settings = $this->getSettings();

        if ( $settings['status'] === 'on' ) {

            foreach ($settings as $id => $setting) {
                $settings[$id] = addslashes(htmlspecialchars($setting));
            }

            $view = wa()->getView();
            $view->assign('pricereq_settings', $settings);
        	$view->assign('pricereq_url', wa()->getRouteUrl('shop/frontend/pricereq/'));
            $html = $view->fetch($this->path.'/templates/FrontendHead.html');

            return $html;

        } else {

            return;

        }
    }

    /**
     * Generates the HTML code for the user control with ID settingNumberControl for number parametrs
     * @param string $name
     * @param array $params
     * @return string
     */
    static public function settingNumberControl($name, $params = array()) {

        $control = '';

        $control_name = htmlentities($name, ENT_QUOTES, 'utf-8');

        $control .= "<input id=\"{$params['id']}\" type=\"number\" name=\"{$control_name}\" ";
        $control .= self::addCustomParams(array('class', 'placeholder', 'value',), $params);
        $control .= self::addCustomParams(array('min', 'max', 'step',), $params['options']);
        $control .= ">";

        return $control;

    }

    /**
     * Generates the HTML code for the user control with ID settingColorControl for color parametrs
     * @param string $name
     * @param array $params
     * @return string
     */
    static public function settingColorControl($name, $params = array()) {
        $control = '';

        $control_name = htmlentities($name, ENT_QUOTES, 'utf-8');
        
        $control .= "<input id=\"{$params['id']}\" type=\"text\" name=\"{$control_name}\" ";
        $control .= self::addCustomParams(array('class', 'placeholder', 'value',), $params);
        $control .= ">";
        if (isset($params['value']) && !empty($params['value'])) {
            $control .= "<span class=\"s-color-replacer\">";
            $control .= "<i class=\"icon16 color\" style=\"background: #{$params['value']};\"></i>";
            $control .= "</span>";
        }
        $control .= "<div class=\"s-colorpicker\"></div>";

        return $control;
    }

    /**
     * Generates the HTML code for the user control with ID settingEmailControl for number parametrs
     * @param string $name
     * @param array $params
     * @return string
     */
    static public function settingEmailControl($name, $params = array()) {

        $control = '';

        $control_name = htmlentities($name, ENT_QUOTES, 'utf-8');

        $control .= "<input id=\"{$params['id']}\" type=\"email\" name=\"{$control_name}\" ";
        $control .= self::addCustomParams(array('class', 'placeholder', 'value',), $params);
        $control .= ">";

        return $control;

    }

    /**
     * Generates the HTML parts of code for the params in user controls added by plugin
     * @param array $list
     * @param array $params
     * @return string
     */
    private static function addCustomParams($list, $params = array()) {
        $params_string = '';

        foreach ($list as $param => $target) {
            if (is_int($param)) {
                $param = $target;
            }
            if (isset($params[$param])) {
                $param_value = $params[$param];
                if (is_array($param_value)) {
                    if (isset($param_value['title'])) {
                        $param_value = $param_value['title'];
                    } else {
                        $param_value = implode(' ', $param_value);
                    }
                }
                if ($param_value !== false) {
                    $param_value = htmlentities((string)$param_value, ENT_QUOTES, 'utf-8');
                    if (in_array($param, array('autofocus'))) {                    	
                        $params_string .= " {$target}";
                    } else {                    	
                        $params_string .= " {$target}=\"{$param_value}\"";
                    }
                }
            }
        }

        return $params_string;
    }

}