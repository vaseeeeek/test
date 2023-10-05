<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsCategoryData extends shopProductsetsHtmlBuilder
{
    public function __construct($selected = 0, $skip_tree = false)
    {
        parent::__construct($selected);
        if (!$skip_tree) {
            $categories = (new shopCategoryModel())->getTree(null);
            $this->data = $this->createTree($categories);
        }
    }

    public function getChildIds($parent_ids)
    {
        $category_model = new shopCategoryModel();
        $nodes = [];
        $categories = $category_model->getById($parent_ids);
        $sql = "SELECT id FROM {$category_model->getTableName()} WHERE ";
        foreach ($categories as $category) {
            $nodes[] = "(left_key >= {$category['left_key']} AND right_key <= {$category['right_key']})";
        }
        $sql .= implode(' OR ', $nodes);
        return $category_model->query($sql)->fetchAll(null, true);
    }

    public function toHtmlSelectOptions()
    {
        return $this->_toHtmlSelectOptions($this->data);
    }

    private function _toHtmlSelectOptions($cats, $selected = '', $level = 0)
    {
        $html = "";
        foreach ($cats as $c) {
            $html .= "<option value='" . $c['id'] . "'" . ($selected == $c['id'] ? " selected" : "") . ">";
            for ($i = 0; $i < $level; $i++) {
                $html .= "&nbsp;&nbsp;&nbsp;&nbsp;";
            }
            $html .= waString::escapeAll($c['name']);
            $html .= "</option>";
            if (!empty($c['childs'])) {
                $html .= $this->_toHtmlSelectOptions($c['childs'], $selected, $level + 1);
            }
        }
        return $html;
    }

    private function createTree($cats)
    {
        $stack = array();
        $result = array();
        foreach ($cats as $c) {
            $c['childs'] = array();
            // Number of stack items
            $l = count($stack);
            // Check if we're dealing with different levels
            while ($l > 0 && $stack[$l - 1]['depth'] >= $c['depth']) {
                array_pop($stack);
                $l--;
            }
            // Stack is empty (we are inspecting the root)
            if ($l == 0) {
                // Assigning the root node
                $i = count($result);
                $result[$i] = $c;
                $stack[] = &$result[$i];
            } else {
                // Add node to parent
                $i = count($stack[$l - 1]['childs']);
                $stack[$l - 1]['childs'][$i] = $c;
                $stack[] = &$stack[$l - 1]['childs'][$i];
            }
        }
        return $result;
    }

}