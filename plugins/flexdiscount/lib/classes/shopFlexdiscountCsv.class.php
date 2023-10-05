<?php

class shopFlexdiscountCsv implements Serializable
{

    private $fp = null;
    protected $data_mapping = array();
    private $delimiter = ';';
    private $encoding;
    private $offset = 0;
    private $fsize = 0;
    protected $length = 4096; // Max length of the row in the csv file
    private $file;
    private $type;

    public function __construct($file, $delimiter = ';', $encoding = 'utf-8', $type = 'write')
    {
        $this->file = ifempty($file);
        $this->type = ifempty($type, 'write');
        $this->delimiter = ifempty($delimiter, ';');

        $this->encoding = ifempty($encoding, 'utf-8');
        if ($this->file) {
            waFiles::create($this->file);
        }
        $this->restore();
    }

    public function __destruct()
    {
        if ($this->fp) {
            fclose($this->fp);
        }
    }

    public function getFileSize()
    {
        return $this->fsize;
    }

    public function serialize()
    {
        return serialize(array(
            'file' => $this->file,
            'delimiter' => $this->delimiter,
            'encoding' => $this->encoding,
            'data_mapping' => $this->data_mapping,
            'offset' => $this->offset,
            'fsize' => $this->fsize,
            'type' => $this->type
        ));
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->file = ifset($data['file']);
        $this->delimiter = ifempty($data['delimiter'], ';');
        $this->encoding = ifempty($data['encoding'], 'utf-8');
        $this->data_mapping = ifset($data['data_mapping']);
        $this->offset = ifset($data['offset'], 0);
        $this->fsize = ifset($data['fsize'], 0);
        $this->type = ifset($data['type'], 'write');

        $this->restore();
    }

    public function file()
    {
        return $this->file;
    }

    /**
     *
     * @param array $map
     */
    public function setMap($map)
    {
        $this->data_mapping = $map;
        if ($this->data_mapping) {
            $this->write($this->encodeArray($this->data_mapping), true);
        }
    }

    public function write($data, $raw = false)
    {
        fputcsv($this->fp, $raw ? $data : $this->applyDataMapping($data), $this->delimiter);
        $this->offset = ftell($this->fp);
    }

    private function restore()
    {
        setlocale(LC_CTYPE, 'ru_RU.UTF-8', 'en_US.UTF-8');
        if ($this->file) {
            $this->open();
            if (!$this->fp) {
                throw new waException("error while open CSV file");
            }
        } else {
            throw new waException("CSV file not found");
        }
    }

    private function open()
    {
        if ($this->type == 'write') {
            $this->fp = @fopen($this->file, 'a');
            if (!$this->fp) {
                throw new waException("error while open CSV file");
            }
            fseek($this->fp, 0, SEEK_END);

            $fsize = filesize($this->file);
            if (!$this->offset) {
                if ($fsize) {
                    fseek($this->fp, 0);
                    ftruncate($this->fp, 0);
                    fseek($this->fp, 0);
                }
                if ($this->data_mapping) {
                    $this->write($this->data_mapping, true);
                }
            } else {
                fseek($this->fp, 0);
                ftruncate($this->fp, $this->offset);
                fseek($this->fp, 0, SEEK_END);
            }
        } elseif (file_exists($this->file) && !is_dir($this->file)) {
            @ini_set('auto_detect_line_endings', true);

            if (strtolower($this->encoding) != 'utf-8') {
                if ($this->fp) {
                    fclose($this->fp);
                    unset($this->fp);
                }
                if ($file = waFiles::convert($this->file, $this->encoding)) {
                    $this->encoding = 'utf-8';
                    $this->file = $file;
                    $this->fp = fopen($this->file, "rb");
                } else {
                    throw new waException("Error while convert file encoding");
                }
            } elseif (!$this->fp) {
                $this->fp = fopen($this->file, "rb");
            }

            if (!$this->fsize) {
                $this->fsize = filesize($this->file);
            }
            $this->next();
        } else {
            throw new waException("CSV file not found");
        }
    }

    private function next()
    {
        fseek($this->fp, $this->offset);
    }

    public function current()
    {
        return $this->offset;
    }

    /**
     * @param $data
     * @return array
     */
    private function applyDataMapping($data)
    {
        $enclosure = '"';
        $pattern = sprintf("/(?:%s|%s|%s|\s)/", preg_quote($this->delimiter, '/'), preg_quote(',', '/'), preg_quote($enclosure, '/'));
        $mapped = array();
        if (empty($this->data_mapping)) {
            $mapped = $data;
        } else {
            foreach ($this->data_mapping as $key => $column) {
                $value = null;
                if (strpos($key, ':')) {
                    $key = explode(':', $key);
                }
                if (is_array($key)) {
                    $value = $data;
                    while (($key_chunk = array_shift($key)) !== null) {
                        $value = ifset($value[$key_chunk]);
                        if ($value === null) {
                            break;
                        }
                    }
                } else {
                    $value = ifset($data[$key]);
                }
                if (is_array($value)) {
                    foreach ($value as & $item) {
                        if (preg_match($pattern, $item)) {
                            $item = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $item) . $enclosure;
                        }
                    }
                    unset($item);
                    if ($value) {
                        $value = "{" . implode(',', $value) . "}";
                    } else {
                        $value = '';
                    }
                }
                $mapped[] = str_replace("\r\n", "\r", $value);
            }
        }

        return $mapped;
    }

    /**
     * Read CSV-file and returns data
     *
     * @param $limit
     * @return array|bool
     */
    public function read($limit = 50)
    {
        $data = array();
        $i = $first_line = 0;
        while (!feof($this->fp) && $limit > $i++) {
            $real_data = $this->encodeArray(fgetcsv($this->fp, $this->length, $this->delimiter));
            // Пропускаем первую строку с заголовками
            if (!$first_line && $this->offset === 0) {
                $first_line = 1;
                $i--;
                continue;
            }
            if (!$this->notEmptyArray($real_data)) {
                $i--;
                continue;
            }
            $data[] = $real_data;
        }
        if (!$data && !$first_line && feof($this->fp)) {
            fclose($this->fp);
            $this->fp = false;
            return false;
        }
        $this->offset = ftell($this->fp);

        return $data;
    }

    protected function notEmptyArray($a)
    {
        if (!$a) {
            return false;
        }
        $t = false;
        foreach ($a as $v) {
            if (trim($v)) {
                $t = true;
            }
        }
        return $t;
    }

    protected function encodeArray($a)
    {
        if ($this->encoding && is_array($a)) {
            foreach ($a as &$v) {
                $v = @iconv($this->encoding, "utf-8//IGNORE", $v);
            }
        }
        return $a;
    }

}
