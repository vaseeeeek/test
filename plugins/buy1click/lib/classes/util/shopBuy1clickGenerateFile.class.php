<?php

class shopBuy1clickGenerateFile {

    private $name;
    private $path;
    private $folder;

    public function __construct($fileName, $extension, $folder = "/assets/", $path = null) {
        $this->name = $fileName . $extension;
        $this->folder = $folder;

        if(isset($path)) {
            $this->path = $path;
        } else {
            $this->path = shopBuy1clickPlugin::getPath($folder) ;
        }
//        var_dump("PATH: " . $this->path . $this->name);
    }

    public function save($data) {
        if (!$this->isExisting()) {
            waFiles::create($this->path . $this->name);
        }
        return waFiles::write($this->path . $this->name, $data);
    }

    public function read() {
        $data = file($this->path . $this->name);
        if (count($data) == 1) {
            return json_decode($data[0]);
        }
        throw new ErrorException("Format error");
    }

    public function  isExisting() {

        return file_exists($this->path . $this->name);
    }

    public function getPath() {
        $pluginId = shopBuy1clickPlugin::PLUGIN_ID;
        return  "plugins/{$pluginId}{$this->folder}{$this->name}";
    }

}