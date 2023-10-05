<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginSettingsRedactorImageUploadController extends waUploadJsonController
{

    protected function process()
    {
        $f = waRequest::file('image');
        try {
            if ($this->processFile($f)) {
                $this->response['url'] = wa()->getDataUrl('shop/plugins/quickorder/' . $f->name, true, 'site');
            }
        } catch (Exception $e) {
            $this->errors[] = _w("Cannot upload file. Try to upload image");
        }
    }

    protected function getPath()
    {
        return wa()->getDataPath('shop/plugins/quickorder/', true, 'site');
    }

    protected function isValid($f)
    {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        if (!in_array(strtolower($f->extension), $allowed)) {
            $this->errors[] = sprintf(_w("Files with extensions %s are allowed only."), '*.' . implode(', *.', $allowed));
            return false;
        }
        // Получаем экземпляр класса waImage
        try {
            waImage::factory($f);
        } catch (Exception $e) {
            $this->errors[] = _w("Upload only images");
            return false;
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
            $result = $f->moveTo($this->path, $this->name);
            return $result;
        }
        $result = $f->moveTo($this->path, $f->name);
        return $result;
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
