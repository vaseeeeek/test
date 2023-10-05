<?php

class shopSeoratingsPluginSettingsActions extends waJsonActions
{
  public function loadAction()
  {
    if (!isset($_FILES['developer_templates_file'])) {
      $this->redirect('/webasyst/shop/?action=plugins#/seoratings');

      return;
    }
    $fileExtension = pathinfo($_FILES['developer_templates_file']['name'], PATHINFO_EXTENSION);
    $filePath = $_FILES['developer_templates_file']['tmp_name'];
    $fileSize = $_FILES['developer_templates_file']['size'];

    $this->process($filePath, $fileExtension, $fileSize);
    $this->redirectBack();
  }

  public function clearAction()
  {
    $shopTemplates = new shopSeoratingsTemplatesModel();
    $shopTemplates->deleteByField('developer', 1);
    $this->redirectBack();
  }

  public function exportAction()
  {
    $shopTemplates = new shopSeoratingsTemplatesModel();
    $templates = $shopTemplates->getDeveloperTemplates();

    if (!$templates) {
      return;
    }

    $this->getResponse()->addHeader('Content-Description', 'File Transfer');
    $this->getResponse()->addHeader('Content-Type', 'application/json');
    $this->getResponse()->addHeader('Content-Disposition', 'attachment; filename=templates.json');
    $this->getResponse()->addHeader('Content-Transfer-Encoding', 'binary');
    $this->getResponse()->addHeader('Expires', '0');
    $this->getResponse()->addHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
    $this->getResponse()->addHeader('Pragma', 'public');
    $this->getResponse()->sendHeaders();

    echo json_encode($templates);
    exit;
  }

  private function process(string $filePath, string $fileExtension, int $fileSize)
  {
    $shopTemplates = new shopSeoratingsTemplatesModel();
    $processorMethodName = $this->getProcessorMethodName($fileExtension);
    $data = $this->{$processorMethodName}($filePath, $fileSize);
    foreach ($data as $row) {
      $shopTemplates->exec("insert into `shop_seoratings_templates` (`name`, `html`, `css`, `developer`) values (s:name, s:html, s:css, 1)", $row);
    }
  }

  /**
   * @param string $extension File extension
   *
   * @return string Method name for given extension.
   */
  private function getProcessorMethodName($extension)
  {
    return 'process' . ucfirst($extension);
  }

  /**
   * @param string $filePath File resource
   * @param int $fileSize Size of the file
   *
   * @return array Data
   */
  private function processJson(string $filePath, int $fileSize)
  {
    $fileContents = file_get_contents($filePath);

    return json_decode($fileContents, true);
  }

  private function redirectBack()
  {
    $this->redirect('/webasyst/shop/?action=plugins#/seoratings');
  }
}