<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopAutobadgePluginFilterCsvUploadController extends waUploadJsonController
{

    protected function process()
    {
        $f = waRequest::file('csv');
        try {
            if ($this->processFile($f)) {
                $csv = new shopAutobadgeCsv(false, ';', 'utf-8');
                $csv->setFile($this->getPath() . $f->name);
                $data = $csv->import();
                if ($data && is_array($data) && count($data) > 1) {
                    $tm = new shopAutobadgeTemplatePluginModel();
                    foreach ($data as $k => $row) {
                        if ($k === 0) {
                            continue;
                        }
                        try {
                            $settings = unserialize($row[1]);
                            if (empty($settings['id']) || empty($settings['construction']) || empty($settings['size'])) {
                                continue;
                            }
                            $name = $row[0] ? $row[0] : _wp("No name template");
                            $id = $tm->insert(array("name" => $name, "settings" => serialize($settings)));
                            $this->response[$id] = array('id' => $id, 'settings' => $settings, 'name' => shopAutobadgeHelper::secureString($name));
                        } catch (Exception $e) {
                            
                        }
                    }
                    waFiles::delete($this->getPath() . $f->name, true);
                }
            }
        } catch (waException $e) {
            $this->errors[] = _w("Cannot upload file. Try to upload CSV file");
        }
    }

    protected function getPath()
    {
        return wa('shop')->getTempPath('autobadge/csv/import/');
    }

    protected function isValid($f)
    {
        $allowed = array('csv');
        if (!in_array(strtolower($f->extension), $allowed)) {
            $this->errors[] = sprintf(_w("Files with extensions %s are allowed only."), '*.' . implode(', *.', $allowed));
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
