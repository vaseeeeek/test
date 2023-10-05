<?php

/**
 * File Upload controller
 *
 * @package Syrattach/controller
 * @author Serge Rodovnichenko <sergerod@gmail.com>
 * @version 1.0.0
 * @copyright (c) 2014, Serge Rodovnichenko
 * @license http://www.webasyst.com/terms/#eula Webasyst
 */
class shopSyrattachPluginAttachmentsUploadController extends shopUploadController
{
    /** @var shopProductModel */
    private $Product;

    /** @var shopSyrattachFileModel */
    private $SyrattachFile;


    public function __construct()
    {
        $this->Product = new shopProductModel();
        $this->SyrattachFile = new shopSyrattachFileModel();
    }

    /**
     *
     * @param waRequestFile $file
     * @return array
     * @throws waException
     */
    protected function save(waRequestFile $file)
    {
        $product_id = waRequest::post('syrattach_product_id', NULL, waRequest::TYPE_INT);
        $config = $this->getConfig();

        $this->checkProductRights($product_id);

        $data = $this->SyrattachFile->add($product_id, $file);

        return array(
            'id'          => $data['id'],
            'name'        => $data['name'],
            'type'        => $file->type,
            'size'        => $file->size,
            'description' => ''
        );

    }

    /**
     * Throws an error if user hasn't enough rights to access product
     * We're trying to keep our main method clean
     *
     * @param int $product_id
     * @throws waException
     */
    private function checkProductRights($product_id)
    {
        if (!$this->Product->checkRights($product_id)) {
            throw new waException(_wp('Access denied'));
        }
    }
}
