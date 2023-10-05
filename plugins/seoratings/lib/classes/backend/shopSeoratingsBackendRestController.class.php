<?php

/**
 * Rest Controller. With overridden run method.
 * For working with Backbone library.
 */
abstract class shopSeoratingsBackendRestController extends waJsonActions
{
  /**
   *
   * @param null $params
   *
   * @throws waException
   */
  public function run($params = null)
  {
    $action = $params;
    if (!$action) {
      $action = 'default';
    }
    $this->action = $action;
    $this->preExecute();
    $this->execute($this->action);
    $this->postExecute();

    if ($this->action == $action) {
      if (waRequest::isXMLHttpRequest()) {
        $this->getResponse()->addHeader('Content-type', 'application/json');
      }
      $this->getResponse()->sendHeaders();
      if (!$this->errors) {
        echo waUtils::jsonEncode($this->response);
      } else {
        echo waUtils::jsonEncode($this->errors);
      }
    }
  }

  public function saveAction()
  {
    if (isset($_REQUEST['_method'])) {
      $method = strtolower($_REQUEST['_method']);
      if ($method === 'delete') {
        $this->delete();
      } elseif ($method === 'put') {
        $this->update();
      }
    } else {
      $this->insert();
    }
  }

  protected function delete()
  {
    $this->response = $this->model->deleteById(intval($_REQUEST['id']));
  }

  protected function update()
  {
    $model = $this->fetchData();
    $this->model->updateById($model['id'], $model);
  }

  protected function insert()
  {
    $model = $this->fetchData();
    $id = $this->model->insert($model);
    $this->response = compact('id');
  }

  protected function fetchData() { }
}
