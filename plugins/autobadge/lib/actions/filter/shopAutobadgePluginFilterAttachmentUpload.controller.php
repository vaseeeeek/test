<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopAutobadgePluginFilterAttachmentUploadController extends waUploadJsonController
{

    protected function process()
    {
        $f = waRequest::file('image');
        try {
            if ($this->processFile($f)) {
                $this->response['filelink'] = wa('shop')->getDataUrl('plugins/autobadge/' . $f->name, true, 'site', true);
            }
        } catch (waException $e) {
            $this->errors[] = _w("Cannot upload file. Try to upload image");
        }
    }

    protected function getPath()
    {
        return wa('shop')->getDataPath('plugins/autobadge/', true, 'site');
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
            $image = waImage::factory($f);
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
            return $f->moveTo($this->path, $this->name);
        }
        return $f->moveTo($this->path, $f->name);
    }

}
