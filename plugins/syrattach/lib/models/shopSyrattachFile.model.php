<?php

/**
 * @package Syrattach/model
 * @author Serge Rodovnichenko <sergerod@gmail.com>
 * @version 1.0.0
 * @copyright (c) 2014, Serge Rodovnichenko
 * @license http://www.webasyst.com/terms/#eula Webasyst
 */
class shopSyrattachFileModel extends waModel
{
    protected $table = 'shop_syrattach_files';

    /**
     * Adds a new record to the database
     * If a file with the same name and extension exists the new name will
     * be given %name%_%counter%.%ext%, i,e if file.pdf exists, the
     * newly uploaded file with the same name will be renamed to file_1.ext
     *
     * @param int $product_id
     * @param waRequestFile $file
     * @return array
     * @throws waException
     */
    public function add($product_id, $file, $copy = false)
    {
        if (!intval($product_id)) {
            throw new waException(_wp("Product ID missing while file metadata saving"));
        }

        $target_dir = shopSyrattachPlugin::getDirectory($product_id);

        $this->checkDirectory($target_dir);

        $data = array(
            'product_id'      => intval($product_id),
            'name'            => $this->getUniqueFileName($file, $target_dir),
            'sort'            => $this->getSortValue(intval($product_id)),
            'upload_datetime' => date("Y-m-d H:i:s"),
            'size'            => $file->size,
            'ext'             => $file->extension
        );

        $data['id'] = $this->insert($data);

        if (!$data['id']) {
            throw new waException(_w('Database error'));
        }

        if (!$copy) {
            $file->moveTo($target_dir, $data['name']);
        } else {
            $file->copyTo($target_dir, $data['name']);
        }

        return $data;
    }

    /**
     *
     * @param int|string $product_id
     * @param bool $file_urls
     * @return array
     */
    public function getByProductId($product_id, $file_urls = false)
    {
        $attachments = $this->select("*")->
        where("product_id=i:product_id", array('product_id' => $product_id))->
        order("sort ASC")->
        fetchAll();

        if ($file_urls) {
            foreach ($attachments as $key => $value) {
                $attachments[$key]['url'] = shopSyrattachPlugin::getFileUrl($value);
            }
        }

        return $attachments;
    }

    /**
     * Deletes record and attached file
     *
     * @param int|string $id
     * @param bool $delete_file
     * @throws Exception
     * @throws waException
     */
    public function delete($id, $delete_file = true)
    {
        $attachment = $this->getById($id);

        if (!$attachment) {
            throw new waException(sprintf_wp("Cannot find a record for attachment ID#%d", $id));
        }

        $file = shopProduct::getPath(
            $attachment['product_id'],
            shopSyrattachPlugin::SYRATTACH_ATTACHMENTS_FOLDER . DIRECTORY_SEPARATOR . $attachment['name'],
            true);

        if (wa()->getConfig()->isDebug()) {
            waLog::log(sprintf_wp("Try to delete '%s'", $file), shopSyrattachPlugin::LOG);
        }

        if (!$this->deleteById($id)) {
            throw new waException(_wp("Delete error"));
        }

        try {
            if ($delete_file) {
                waFiles::delete($file);
            }
        } catch (waException $e) {
            waLog::log(
                sprintf_wp("SyrAttach Plugin cannot delete file %s. Message: %s", $file, $e->getMessage()),
                shopSyrattachPlugin::LOG
            );
        }
    }

    /**
     *
     * @param int $product_id
     * @return int
     */
    private function getSortValue($product_id)
    {

        $info = $this->select('MAX(`sort`)+1 AS `max`, COUNT(1) AS `cnt`')
            ->where($this->getWhereByField('product_id', $product_id))
            ->fetch();

        if ($info['cnt']) {
            return $info['max'];
        }

        return 0;
    }

    /**
     *
     * @param waRequestFile $file
     * @param $path
     * @return string
     * @internal param int $product_id
     */
    private function getUniqueFileName($file, $path)
    {
        if (!file_exists($path . '/' . $file->name)) return $file->name;

        $i = 1;
        $pathinfo = pathinfo($file->name);
        do {
            $name = sprintf('%s_%d', $pathinfo['filename'], $i++);
            $filename = $name . "." . $pathinfo['extension'];
        } while (file_exists($path . DIRECTORY_SEPARATOR . $filename) && is_file($path . DIRECTORY_SEPARATOR . $filename));


        return $filename;
    }

    /**
     *
     * @param $dir string
     * @throws waException
     */
    private function checkDirectory($dir)
    {

        if ((file_exists($dir) && !is_writable($dir)) || (!file_exists($dir) && !waFiles::create($dir, true))) {
            throw new waException("Error saving file. Check write permissions.");
        }
    }
}
