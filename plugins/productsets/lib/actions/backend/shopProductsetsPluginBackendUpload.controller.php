<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginBackendUploadController extends waUploadJsonController
{
    protected function process()
    {
        $f = waRequest::file('image');
        try {
            if ($this->processFile($f)) {
                $this->response['url'] = wa()->getDataUrl('plugins/productsets/' . $f->name, true, 'shop', true);
            }
        } catch (Exception $e) {
            $this->errors[] = _w("Cannot upload file. Try to upload image");
        }
    }

    protected function getPath()
    {
        return wa()->getDataPath('plugins/productsets/', true, 'shop');
    }

    protected function isValid($f)
    {
        $allowed = array('jpg', 'jpeg', 'png', 'gif', 'svg');
        if (!in_array(strtolower($f->extension), $allowed)) {
            $this->errors[] = sprintf(_w("Files with extensions %s are allowed only."), '*.' . implode(', *.', $allowed));
            return false;
        }
        // Получаем экземпляр класса waImage
        if (strtolower($f->extension) !== 'svg') {
            try {
                waImage::factory($f);
            } catch (Exception $e) {
                $this->errors[] = _w("Upload only images");
                return false;
            }
        }
        return true;
    }

    protected function save(waRequestFile $f)
    {
        if (file_exists($this->path . DIRECTORY_SEPARATOR . $f->name)) {
            $j = strrpos($f->name, '.');
            $name = substr($f->name, 0, $j);
            $ext = substr($f->name, $j + 1);
            $i = 1;
            while (file_exists($this->path . DIRECTORY_SEPARATOR . $name . '-' . $i . '.' . $ext)) {
                $i++;
            }
            $this->name = $name . '-' . $i . '.' . $ext;
            return $f->moveTo($this->path, $this->name);
        }
        return $f->moveTo($this->path, $f->name);
    }

    public function display()
    {
        if (waRequest::isXMLHttpRequest()) {
            $this->getResponse()->addHeader('Content-Type', 'application/json');
        }
        $this->getResponse()->sendHeaders();
        if (!$this->errors) {
            $data = array($this->response);
            echo json_encode($data);
        } else {
            echo json_encode(array('error' => $this->errors));
        }
    }

}
