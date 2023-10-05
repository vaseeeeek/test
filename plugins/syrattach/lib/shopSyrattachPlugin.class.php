<?php

/**
 * @package Syrattach
 * @author Serge Rodovnichenko <sergerod@gmail.com>
 * @version 1.0.0
 * @copyright (c) 2014, Serge Rodovnichenko
 * @license http://www.webasyst.com/terms/#eula Webasyst
 */
class shopSyrattachPlugin extends shopPlugin
{

    const SYRATTACH_ATTACHMENTS_FOLDER = "attachments";

    const LOG = 'shop/plugins/syrattach.log';

    /** @var shopSyrattachFileModel */
    private $Attachments;

    public function __construct($info)
    {
        parent::__construct($info);
        $this->Attachments = new shopSyrattachFileModel();
    }

    /**
     * Hook 'backend_product'
     *
     * @param array $product
     * @return array
     */
    public function backendProduct($product)
    {
        $template = $this->path . '/templates/backend_product.html';
        $view = waSystem::getInstance()->getView();
        $count = $this->Attachments->countByField('product_id', $product['id']);

        $view->assign(compact('count', 'product'));
        $html = $view->fetch($template);

        return array('edit_section_li' => $html);
    }

    /**
     * Handler for 'product_custom_fields' hook
     *
     * List of columns in the CSV file
     *
     * @return array
     */
    public function productCustomFields()
    {
        return array(
            'product' => array('file' => _wp('Attached File'))
        );
    }

    /**
     * Handler for 'product_delete' hook
     *
     * We don't care about attached files because they will be deleted by
     * Shopscript with other public files such as images that belongs to
     * products
     *
     * @param array $product_ids
     */
    public function productDelete($product_ids)
    {
        $this->Attachments->deleteByField('product_id', $product_ids['ids']);
    }

    /**
     * Handler for 'product_save' hook
     * Copy files from directory on CSV import
     *
     * @param array $params
     * @throws waException
     */
    public function productSave($params)
    {
        // No data for plugin
        if (!array_key_exists('syrattach_plugin', $params['data'])) {
            return;
        }

        // Нам ID товара нужен позарез
        if (empty($params['data']['id'])) {
            if (wa()->getConfig()->isDebug()) {
                waLog::log(sprintf(_wp('No ID given for product "%s"'), ifset($params['data']['name'], '')) . self::LOG);
            }
            return;
        }

        $data_path = wa()->getDataPath('syrattach', true, 'site', false);
        $files = (array)$params['data']['syrattach_plugin'];

        foreach ($files as $file) {
            if ((strpos($file, '/') !== false) || (strpos($file, '\\') !== false)) {
                waLog::log(sprintf(_wp('Wrong file name "%s" for product "%s". File not saved.'), $file, ifset($params['data']['name'])), self::LOG);
                continue;
            }

            $full_path = $data_path . DIRECTORY_SEPARATOR . $file;
            if (!file_exists($full_path) || !is_file($full_path) || !is_readable($full_path)) {
                waLog::log(sprintf(_wp('File named "%s" not exists or it is not a file or file is not readable. File not saved.'), $file), self::LOG);
                continue;
            }

            $this->Attachments->add(
                $params['data']['id'],
                new waRequestFile(
                    array(
                        'name'     => $file,
                        'type'     => 'application/binary',
                        'size'     => filesize($full_path),
                        'tmp_name' => $full_path,
                        'error'    => 0
                    ),
                    true
                ),
                true
            );
        }
    }

    public static function getDirectory($product_id)
    {
        return shopProduct::getPath($product_id, self::SYRATTACH_ATTACHMENTS_FOLDER, true);
    }

    public static function getFileUrl($attachment, $absolute = false)
    {
        $path = shopProduct::getFolder($attachment['product_id']) .
            "/" .
            "{$attachment['product_id']}" .
            "/" .
            self::SYRATTACH_ATTACHMENTS_FOLDER .
            "/{$attachment['name']}";

        return waSystem::getInstance()->getDataUrl($path, true, 'shop', $absolute);
    }

    /**
     * Template editor
     *
     * Renders the template with custom form control
     *
     * @param string $param
     * @param array $settings
     * @return string
     */
    public static function templateControl($param, $settings)
    {
        $control_template_path = 'plugins/syrattach/templates/settings/template_control.html';
        $control_template = waSystem::getInstance()->getAppPath($control_template_path, 'shop');
        $view = waSystem::getInstance()->getView();
        $template_path = 'plugins/syrattach/templates/frontend_product.html';
        $original_template = waSystem::getInstance()->getAppPath($template_path, 'shop');
        $modified_template = waSystem::getInstance()->getDataPath($template_path, false, 'shop', false);

        if (file_exists($modified_template)) {
            $template = file_get_contents($modified_template);
            $template_modified = true;
        } else {
            $template = file_get_contents($original_template);
            $template_modified = false;
        }

        $view->assign(compact('settings', 'template', 'template_modified'));

        return $view->fetch($control_template);
    }

    /**
     * Helper method.
     * Returns an array of attached files
     *
     * array(
     *     array(
     *        'id'
     *        'name'
     *        'ext'
     *        'description',
     *        'size',
     *        'url'
     *     )
     * )
     *
     * @param int $product_id
     * @return array
     */
    public static function getList($product_id)
    {
        $product_id = intval($product_id);
        if (!$product_id) {
            return array();
        }

        $Attachment = new shopSyrattachFileModel();

        $files = $Attachment
            ->select("`id`,`name`, `ext`, `description`, `size`")
            ->where('product_id=i:id', array('id' => $product_id))
            ->order('`sort` ASC')
            ->fetchAll();

        foreach ($files as &$file) {
            $file['url'] = shopSyrattachPlugin::getFileUrl($file + array('product_id' => $product_id));
        }

        return $files;
    }

    /**
     * Helper method.
     *
     * Returns the rendered template with list of files
     *
     * @param int $product_id
     * @param bool $force_on_empty If TRUE render template even the list of files is empty
     * @return string
     */
    public static function render($product_id, $force_on_empty = false)
    {
        $attachments = self::getList($product_id);

        $result = "";

        if ($attachments || $force_on_empty) {
            $view = wa()->getView();
            $template_path = 'plugins/syrattach/templates/frontend_product.html';
            $original_template = wa()->getAppPath($template_path, 'shop');
            $modified_template = wa()->getDataPath($template_path, false, 'shop', false);

            if (file_exists($modified_template)) {
                $template = $modified_template;
            } else {
                $template = $original_template;
            }

            $view->assign(compact('attachments'));

            $result = $view->fetch($template);
        }

        return $result;
    }

    /**
     * Handler for frontend_product hook
     *
     * @param shopProduct $product
     * @return string
     */
    public function frontendProduct($product)
    {
        $placement = $this->getSettings('frontend_product_hook');

        if (!in_array($placement, array('block', 'block_aux'))) {
            return array();
        }

        return array($placement => self::render($product->id));
    }

}
