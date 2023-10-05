<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginCouponsCsvUploadController extends waUploadJsonController
{

    protected function process()
    {
        $f = waRequest::file('csv');
        try {
            if ($this->processFile($f)) {
                $this->response = $this->name;
            }
        } catch (waException $e) {
            $this->errors[] = _w("Cannot upload file. Try to upload correct CSV file");
        }
    }

    protected function getPath()
    {
        return shopFlexdiscountApp::get('system')['wa']->getTempPath('flexdiscount/csv/import/');
    }

    protected function isValid($f)
    {
        $allowed = array('csv', 'txt');
        if (!in_array(strtolower($f->extension), $allowed)) {
            $this->errors[] = sprintf(_w("File with extension %s are allowed only."), '*.' . implode(', *.', $allowed));
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
        } else {
            $this->name = $f->name;
        }
        return $f->moveTo($this->path, $this->name);
    }

}
