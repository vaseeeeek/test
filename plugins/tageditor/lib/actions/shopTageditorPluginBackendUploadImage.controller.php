<?php

class shopTageditorPluginBackendUploadImageController extends waController
{
    public function execute()
    {
        $path = wa()->getDataPath('plugins/tageditor/tags/images', true);
        if (!is_writable($path)) {
            $folder = substr($path, strlen(wa()->getDataPath('', true)));
            $errors = sprintf(_wp('File cannot be saved due to insufficient write permissions for %s folder.'), $folder);
        } else {
            $errors = array();
            $file = waRequest::file('file');
            $name = $file->name;
            if ($this->processFile($file, $path, $name, $errors)) {
                $response = wa()->getDataUrl('plugins/tageditor/tags/images/'.$name, true);
            }
            if ($errors) {
                if (is_array($errors)) {
                    $errors = implode('<br>', $errors);
                }
            }
        }

        if ($errors) {
            echo json_encode(array('error' => $errors));
        } else {
            echo json_encode(array(
                'filelink' => $response,    //redactor 1
                'url' => $response,    //redactor 2
            ));
        }
    }

    private function processFile(waRequestFile $file, $path, &$name, &$errors = array())
    {
        if ($file->uploaded()) {
            if (!$this->isFileValid($file, $errors)) {
                return false;
            }
            if (!$this->saveFile($file, $path, $name)) {
                $errors[] = sprintf(_wp('Failed to upload file %s.'), $file->name);
                return false;
            }
            return true;
        } else {
            $errors[] = sprintf(_wp('Failed to upload file %s.'), $file->name).' ('.$file->error.')';
            return false;
        }
    }

    private function isFileValid($file, &$errors = array())
    {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        if (!in_array(strtolower($file->extension), $allowed)) {
            $errors[] = sprintf(_ws('Files with extensions %s are allowed only.'), '*.'.implode(', *.', $allowed));
            return false;
        }
        return true;
    }

    private function saveFile(waRequestFile $file, $path, &$name)
    {
        $name = $file->name;
        if (!preg_match('//u', $name)) {
            $tmp_name = @iconv('windows-1251', 'utf-8//ignore', $name);
            if ($tmp_name) {
                $name = $tmp_name;
            }
        }
        if (file_exists($path.DIRECTORY_SEPARATOR.$name)) {
            $i = strrpos($name, '.');
            $ext = substr($name, $i + 1);
            $name = substr($name, 0, $i);
            $i = 1;
            while (file_exists($path.DIRECTORY_SEPARATOR.$name.'-'.$i.'.'.$ext)) {
                $i++;
            }
            $name = $name.'-'.$i.'.'.$ext;
        }
        return $file->moveTo($path, $name);
    }
}
