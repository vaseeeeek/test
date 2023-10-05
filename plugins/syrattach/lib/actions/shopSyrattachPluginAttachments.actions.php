<?php

/**
 * Description of shopSyrattachPluginAttachments
 *
 * @package Syrattach/controller
 * @author Serge Rodovnichenko <sergerod@gmail.com>
 * @version 1.0.0
 * @copyright (c) 2014, Serge Rodovnichenko
 * @license http://www.webasyst.com/terms/#eula Webasyst
 */
class shopSyrattachPluginAttachmentsActions extends waViewActions
{
    /** @var string */
    protected $template_folder = 'templates/Attachments';

    /** @var shopProductModel */
    private $Product;

    /** @var shopSyrattachFileModel */
    private $Attachment;

    /**
     * Shows the tab content
     *
     * @throws waException
     */
    public function defaultAction()
    {
        $product_id = waRequest::get('id', NULL, waRequest::TYPE_INT);
        $product = $this->Product->getById($product_id);
        if (!$product) {
            throw new waException(_wp("Unknown product"));
        }

        $attachments = $this->Attachment->getByProductId($product_id, TRUE);
        $count = count($attachments);

        $max_file_size = $this->determineMaxUploadSize();

        $this->view->assign(compact('attachments', 'count', 'max_file_size', 'product'));
    }

    public function deleteAction()
    {
        $errors = array();
        $response = _wp("Deleted");

        $this->getResponse()->addHeader('Content-type', 'application/json');

        $id = waRequest::post('id', NULL, waRequest::TYPE_INT);

        try {
            $this->Attachment->delete($id, TRUE);
        } catch (waException $exc) {
            $errors[] = $exc->getMessage();
        }

        $this->view->assign(compact('response', 'errors'));
    }

    /**
     * Список всех аттачей
     *
     * @throws waException
     */
    public function listAction()
    {
        $product_id = waRequest::get('product_id', 0, waRequest::TYPE_INT);
        $errors = array();
        $response = '';
        $this->getResponse()->addHeader('Content-type', 'application/json');

        try {
            if (!$product_id) {
                throw new waException(_wp('Unknown product'));
            }

            $response['attachments'] = $this->Attachment->getByProductId($product_id, TRUE);
            $response['count'] = count($response['attachments']);

        } catch (waException $ex) {
            $errors[] = $ex->getMessage();
        }

        $this->view->assign(compact('response', 'errors'));
    }

    /**
     * Сохранение описания вложения
     *
     * @throws waException
     */
    public function descriptionsaveAction()
    {
        $id = waRequest::post('id', 0, waRequest::TYPE_INT);
        $data = waRequest::post('data', array(), waRequest::TYPE_ARRAY);
        $errors = array();
        $response = '';
        $this->getResponse()->addHeader('Content-type', 'application/json');

        try {

            if (!$id) {
                throw new waException(_wp('Unknown attachment ID'));
            }

            if (empty($data) || !is_array($data) || !isset($data['description'])) {
                throw new waException(_wp('Description is not set'));
            }

            // TODO: Не лучшая идея. Переделать при случае.
            $this->Attachment->updateById($id, array('description' => $data['description']));

            $response = _wp("Saved");

        } catch (waException $exc) {
            $errors[] = $exc->getMessage();
        }

        $this->view->assign(compact('response', 'errors'));
    }

    /**
     * Returns full path to the template file to render
     *
     * @return string
     */
    protected function getTemplate()
    {
        $pluginRoot = $this->getPluginRoot();

        if ($this->template === NULL) {
            if ($this->getResponse()->getHeader('Content-type') === 'application/json') {
                return "{$pluginRoot}templates/json.tpl";
            }
            $template = ucfirst($this->action);
        } else {
            // If path contains / or : then it's a full path to template
            if (strpbrk($this->template, '/:') !== FALSE) {
                return $this->template;
            }

            // otherwise it's a template name and we need to figure out its directory
            $template = $this->template;
        }

        $match = array();
        preg_match("/[A-Z][^A-Z]+/", get_class($this), $match);
        $template = "{$pluginRoot}{$this->template_folder}/$template" . $this->view->getPostfix();

        return $template;
    }

    /**
     * Initialize controller-wide variables
     */
    protected function preExecute()
    {
        parent::preExecute();
        $this->Product = new shopProductModel();
        $this->Attachment = new shopSyrattachFileModel();
    }

    /**
     * Determines maximum upload size in bytes
     *
     * @see http://stackoverflow.com/a/2840875/2558549
     *
     * @return int
     */
    private function determineMaxUploadSize()
    {
        $max_upload = $this->convertPHPSizeToBytes(ini_get('upload_max_filesize'));
        $max_post = $this->convertPHPSizeToBytes(ini_get('post_max_size'));
        $memory_limit = $this->convertPHPSizeToBytes(ini_get('memory_limit'));
        $upload_mb = min($max_upload, $max_post, $memory_limit);

        return (int)($upload_mb * 0.8);
    }

    /**
     * Convert php.ini sizes like 64M to the numbers
     *
     * @see http://stackoverflow.com/a/22500394/2558549
     *
     * @param int|string $size
     * @return int
     */
    private function convertPHPSizeToBytes($size)
    {
        if (is_numeric($size)) {
            return $size;
        }
        $sSuffix = substr($size, -1);
        $iValue = substr($size, 0, -1);
        switch (strtoupper($sSuffix)) {
            case 'P':
                $iValue *= 1024;
            case 'T':
                $iValue *= 1024;
            case 'G':
                $iValue *= 1024;
            case 'M':
                $iValue *= 1024;
            case 'K':
                $iValue *= 1024;
                break;
        }

        return $iValue;
    }

}
