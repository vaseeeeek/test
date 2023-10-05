<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsHtmlBuilder
{
    protected $data;
    protected $selected;

    protected function __construct($selected = 0)
    {
        $this->selected = $selected;
    }

    public function toHtmlSelectOptions()
    {
        $html = "";
        foreach ($this->data as $o) {
            $html .= "<option value='" . $o['id'] . "'" . ($this->selected == $o['id'] ? " selected" : "") . (!empty($o['class']) ? ' class="' . $o['class'] . '"' : '') . ">" . waString::escapeAll($o['name']) . "</option>";
        }
        return $html;
    }

    public function toArray()
    {
        return $this->data;
    }

}