<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginDialogGetProductsAction extends waViewAction
{

    public function execute()
    {
        $this->view->assign('categories', $this->getTree());
        $this->view->assign('plugin_url', shopFlexdiscountApp::get('system')['wa']->getPlugin('flexdiscount')->getPluginStaticUrl());
    }

    private function getTree()
    {
        $categories = (new shopCategoryModel())->getFullTree('id, left_key, right_key, parent_id, depth, name, count, type, status, include_sub_categories');

        foreach ($categories as &$item) {
            if (!isset($item['children_count'])) {
                $item['children_count'] = 0;
            }
            if (isset($categories[$item['parent_id']])) {
                $parent = &$categories[$item['parent_id']];
                if (!isset($parent['children_count'])) {
                    $parent['children_count'] = 0;
                }
                ++$parent['children_count'];
                unset($parent);
            }
        }
        unset($item);

        $category_routes_model = new shopCategoryRoutesModel();
        foreach ($category_routes_model->getRoutes(array_keys($categories), false) as $category_id => $routes) {
            foreach ($routes as &$r) {
                $r = rtrim($r, '/*');
            }
            unset($r);
            $categories[$category_id]['routes'] = $routes;
        }

        $stack = array();
        $hierarchy = array();
        foreach ($categories as $item) {
            $c = array(
                'id' => $item['id'],
                'total_count' => 0,
                'parent_id' => $item['parent_id'],
                'count' => $item['count'],
                'depth' => $item['depth'],
                'children' => array()
            );

            $l = count($stack);

            while ($l > 0 && $stack[$l - 1]['depth'] >= $item['depth']) {
                array_pop($stack);
                $l--;
            }

            if ($l == 0) {
                $i = count($hierarchy);
                $hierarchy[$i] = $c;
                $stack[] = & $hierarchy[$i];
            } else {
                // Add node to parent
                $i = count($stack[$l - 1]['children']);
                $stack[$l - 1]['children'][$i] = $c;
                $stack[] = & $stack[$l - 1]['children'][$i];
            }
        }

        $hierarchy = array(
            'id' => 0,
            'count' => 0,
            'total_count' => 0,
            'children' => $hierarchy
        );
        $this->totalCount($hierarchy, $categories);

        return $categories;
    }

    private function totalCount(&$tree, &$plain_list)
    {
        $total = $tree['count'];
        foreach ($tree['children'] as &$node) {
            $total += $this->totalCount($node, $plain_list);
        }
        if (isset($plain_list[$tree['id']])) {
            $plain_list[$tree['id']]['total_count'] = $total;
        }
        return $total;
    }

}
