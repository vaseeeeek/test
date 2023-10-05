<?php

class shopFeaturestipsPluginSettingsActions extends waViewActions
{
    private $Type;
    private $Features;
    private $plugin;
    private $model;
    private $do;

    public function defaultAction()
    {
        $this->setTemplate('Settings');
        $this->Type = new shopTypeModel();
        $this->view->assign('product_types', $this->Type->getTypes());
        $plugin = wa('shop')->getPlugin('featurestips');
        $this->view->assign('settings',$plugin->getSettings());
        $this->view->assign('settingsHTML',$plugin->getSettingsHTML());
        $this->view->assign('instruction_locale',substr(wa()->getLocale(), 0, 2));
    }

    public function listAction()
    {
        $this->Features = new shopFeatureModel();
        $model = new shopFeaturestipsModel();
        $do = new shopFeaturestipsPluginDo();

        $all_features = $all_tips = array();

        if(waRequest::get('type_id', 0, 'string') == 'globaltips') {
            $type_id = waRequest::get('type_id', 0, 'string');
            $all_tips = $do->GetTipsArrayWithKeyTypeId($model->getTipsGlobal());
            $all_features = $this->Features->getFeatures(true, null, 'id');
        } elseif(waRequest::get('type_id', 0, 'int') > 0) {
            $type_id = waRequest::get('type_id', 0, 'int');
            $all_tips = $do->GetTipsArrayWithKeyTypeId($model->getTipsByTypeId($type_id));
            $all_features = $this->Features->getByType($type_id, 'id');
        } else {
            exit;
        }

        foreach($all_features as $key => $value)
        {
            if(array_key_exists($value['id'], $all_tips)) {
                $all_features[$key]['tip'] = $all_tips[$value['id']]['value'];
                $all_features[$key]['tipglobal'] = $all_tips[$value['id']]['global'];
            } else {
                $all_features[$key]['tip'] = null;
                $all_features[$key]['tipglobal'] = null;
            }
        }

        $this->view->assign('all_features', $all_features);
        $this->view->assign('type_id', $type_id);

    }

    public function editAction()
    {
        $type = $feature = $tip = array();

        $feature_id = waRequest::get('feature_id', 0, 'int');
        $type_id = waRequest::get('type_id', 0, 'int');

        $model = new shopFeaturestipsModel();
        $this->Features = new shopFeatureModel();

        if(waRequest::get('type_id', 0, 'string') == 'globaltips') {
            $type_id = 'globaltips';
            $global = 1;
        } elseif(waRequest::get('type_id', 0, 'int') > 0) {
            $type_id = waRequest::get('type_id', 0, 'int');
            $global = 0;
        } else {
            exit;
        }

        if($feature_id <= 0) { exit; }

        $feature = $this->Features->getById($feature_id);

        if($model->countByField('feature_id', $feature_id) == 0) {
            $tip['value'] = "";
        } else {
            $q = array();
            if($global == 1) {
                $q = array('feature_id' => $feature_id, 'global' => '1');
            } elseif($global == 0) {
                $q = array('feature_id' => $feature_id, 'global' => '0', 'type_id' => $type_id);
            }
            if(count($q) > 0) {
                if($model->countByField($q) == 1) {
                    $tip = $model->getByField($q);
                } elseif($model->countByField($q) == 0) {
                    $tip['value'] = "";
                } else {
                    exit;
                }
            } else {
                exit;
            }
            unset($q);
        }

        if($global == 1) {
            $type['name'] = _w('Globally, for all types');
        } elseif($global == 0) {
            $this->Type = new shopTypeModel();
            $type = $this->Type->getById($type_id);
        }

        $this->view->assign('feature_name', $feature['name']);
        $this->view->assign('feature_id', $feature_id);
        $this->view->assign('type_name', $type['name']);
        $this->view->assign('type_id', $type_id);
        $this->view->assign('tip_value', $tip['value']);

    }

    public function saveEditAction()
    {
        $model = new shopFeaturestipsModel();
        $feature_id = waRequest::post('feature_id', 0, 'int');
        $value = trim(waRequest::post('value', 0, 'string'));
        $value = strip_tags($value, '<p><b><strong><i><em><h1><h2><h3><del><ul><li><ol><br><hr>');
        $marker = "";

        if(waRequest::post('type_id', 0, 'string') == 'globaltips') {
            $type_id = null;
            $global = 1;
        } elseif(waRequest::post('type_id', 0, 'int') > 0) {
            $type_id = waRequest::post('type_id', 0, 'int');
            $global = 0;
        } else {
            exit;
        }
        $this->setTemplate(wa('shop')->getConfig()->getPluginPath('featurestips') . '/templates/json.tpl');

        if($model->countByField('feature_id', $feature_id) == 0) {
            $marker = "new";
        } else {
            $q = array();
            if($global == 1) {
                $q = array('feature_id' => $feature_id, 'global' => '1');
            } elseif($global == 0) {
                $q = array('feature_id' => $feature_id, 'global' => '0', 'type_id' => $type_id);
            }

            if(count($q) > 0) {
                if($model->countByField($q) == 1) {
                    $item = $model->getByField($q);
                    if($item['value'] != "" && trim($value) == "") {
                        $marker = "delete";
                    } else {
                        $marker = "edit";
                    }
                    $edit_id = $item['id'];
                } elseif($model->countByField($q) == 0) {
                    $marker = "new";
                }
            }
            unset($q);
        }

        switch ($marker) {
            case "edit":
                $model->updateById($edit_id, array('value' => $value));
                $result = "ok";
                break;
            case "new":
                $model->insert(array(
                    'feature_id'	=> $feature_id,
                    'type_id'		=> $type_id,
                    'value'			=> $value,
                    'global'		=> $global
                ));
                $result = "ok";
                break;
            case "delete":
                $model->deleteById($edit_id);
                $result = "ok";
                break;
            default:
                $result = "error";
        }

        $this->view->assign('result', $result);
        $this->getResponse()->addHeader('Content-type', 'application/json');
    }

    public function clearAction()
    {
        $feature_id = waRequest::post('feature_id', 0, 'int');

        if($feature_id == 0) { exit; }

        if(waRequest::post('type_id', 0, 'string') == 'globaltips') {
            $type_id = null;
            $global = 1;
        } elseif(waRequest::post('type_id', 0, 'int') > 0) {
            $type_id = waRequest::post('type_id', 0, 'int');
            $global = 0;
        } else {
            exit;
        }
        $this->setTemplate(wa('shop')->getConfig()->getPluginPath('featurestips') . '/templates/json.tpl');
        $model = new shopFeaturestipsModel();

        $marker = "0";
        if($model->countByField('feature_id', $feature_id) != 0) {
            $q = array();
            if($global == 1) {
                $q = array('feature_id' => $feature_id, 'global' => '1');
            } elseif($global == 0) {
                $q = array('feature_id' => $feature_id, 'global' => '0', 'type_id' => $type_id);
            }

            if(count($q) > 0) {
                if($model->countByField($q) == 1) {
                    $item = $model->getByField($q);
                    $marker = "delete";
                    $delete_id = $item['id'];
                }
            }
            unset($q);
        }

        if($marker == "delete") {
            $model->deleteById($delete_id);
            $result = "ok";
        } else {
            $result = "error";
        }

        $this->view->assign('result', $result);
        $this->getResponse()->addHeader('Content-type', 'application/json');
    }
}